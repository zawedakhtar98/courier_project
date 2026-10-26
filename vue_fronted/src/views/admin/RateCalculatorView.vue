<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { DOCUMENT_TYPE } from '@/constant'
import { getAllCountries } from '@/services/admin/CountryService';
import { shipmentRateCalculator } from '@/services/admin/shipmentRateCalculatorService';
import { useToast } from 'vue-toastification';

const router = useRouter()
const route = useRoute()
const toast = useToast();

// ----------------------------------------------------
// Global State & Data
// ----------------------------------------------------
const countryList = ref([]);
const isLoading = ref(false);
const calculationError = ref(null);
let inFlightCalculation = false;
let isInternalRouteUpdate = false;

function getLocalDateString(date = new Date()) {
  return new Intl.DateTimeFormat('en-CA').format(date);
}

const currentDate = ref(getLocalDateString());

// ----------------------------------------------------
// Helper: Box parsing and volumetric calculation
// ----------------------------------------------------
const defaultBox = () => ({ actWt: 0.00, length: 0, width: 0, height: 0, volWt: 0, chgWt: 0 });

const calculateRowVolWt = (row) => {
  if (row.length && row.width && row.height) {
    row.volWt = parseFloat(((row.length * row.width * row.height) / 5000).toFixed(2));
  } else {
    row.volWt = 0;
  }
  const maxWt = Math.max(row.actWt || 0, row.volWt);
  actual_weight.value = maxWt;
  // Round up to nearest 0.5 kg
  row.chgWt = parseFloat((Math.ceil(maxWt * 2) / 2).toFixed(2));
  chargeable_weight.value = row.chgWt;
};

const parseBoxesFromQuery = (boxesParam) => {
  if (!boxesParam) return [defaultBox()];
  try {
    let parsed = boxesParam;
    if (typeof parsed === 'string') {
      try {
        parsed = JSON.parse(decodeURIComponent(parsed));
      } catch {
        parsed = JSON.parse(parsed);
      }
    }
    if (Array.isArray(parsed) && parsed.length > 0) {
      return parsed.map(b => {
        const row = {
          actWt: Number(b.actWt) || 0,
          length: Number(b.length) || 0,
          width: Number(b.width) || 0,
          height: Number(b.height) || 0,
          volWt: 0,
          chgWt: 0
        };
        calculateRowVolWt(row);
        return row;
      });
    }
  } catch (error) {
    console.error('Failed to parse boxes from query parameter:', error);
  }
  return [defaultBox()];
};

// ----------------------------------------------------
// Calculator Form State
// ----------------------------------------------------
const shipmentDate = ref(getLocalDateString());
const destination = ref('');
const pincode = ref('');
const city = ref('');
const state = ref('');
const packageType = ref('');
const boxRows = ref([defaultBox()]);

// Volumetric totals
const actual_weight = ref(0.0);
const chargeable_weight = ref(0.0);

// Results display state
const showResults = ref(false);
const rateResults = ref([]);

// Restore all state from URL query
const restoreStateFromQuery = (query) => {
  if (!query) return;

  // Restore destination
  if (query.destination !== undefined && query.destination !== null && query.destination !== '') {
    destination.value = !isNaN(Number(query.destination))
      ? Number(query.destination)
      : query.destination;
    if (countryList.value?.length) {
      const match = countryList.value.find(c => String(c.id) === String(query.destination));
      if (match) destination.value = match.id;
    }
  } else {
    destination.value = '';
  }

  // Restore packageType
  packageType.value = (typeof query.package_type === 'string' && query.package_type) ||
    (typeof query.packageType === 'string' && query.packageType) ||
    '';

  // Restore shipmentDate
  shipmentDate.value = (typeof query.shipment_date === 'string' && query.shipment_date) ||
    (typeof query.shipmentDate === 'string' && query.shipmentDate) ||
    getLocalDateString();

  // Restore location
  pincode.value = typeof query.pincode === 'string' ? query.pincode : '';
  city.value = typeof query.city === 'string' ? query.city : '';
  state.value = typeof query.state === 'string' ? query.state : '';

  // Restore boxes
  boxRows.value = parseBoxesFromQuery(query.boxes);
  boxRows.value.forEach(row => calculateRowVolWt(row));
};

// ----------------------------------------------------
// Countries Data
// ----------------------------------------------------
const getCountryList = async () => {
  try {
    const response = await getAllCountries();
    countryList.value = response.data;
    if (destination.value && Array.isArray(countryList.value)) {
      const match = countryList.value.find(c => String(c.id) === String(destination.value));
      if (match) {
        destination.value = match.id;
      }
    }
  } catch (error) {
    console.error('Failed to fetch countries:', error);
  }
};

// ----------------------------------------------------
// Box Actions
// ----------------------------------------------------
const addBox = () => {
  const newRow = defaultBox();
  calculateRowVolWt(newRow);
  boxRows.value.push(newRow);
  syncDraftInputsToUrl();
};

const duplicateBox = (index) => {
  const target = boxRows.value[index];
  if (target) {
    const clone = { ...target };
    boxRows.value.push(clone);
    syncDraftInputsToUrl();
  }
};

const deleteBox = (index) => {
  if (boxRows.value.length <= 1) {
    toast.error('At least one box row is required');
    return;
  }
  boxRows.value.splice(index, 1);
  syncDraftInputsToUrl();
};

const handleFieldChange = (row) => {
  calculateRowVolWt(row);
  syncDraftInputsToUrl();
};

// Totals Computed Row
const totalPCS = computed(() => boxRows.value.length);
const totalActWt = computed(() => {
  const sum = boxRows.value.reduce((acc, r) => acc + (r.actWt || 0), 0);
  return parseFloat(sum.toFixed(2));
});
const totalVolWt = computed(() => {
  const sum = boxRows.value.reduce((acc, r) => acc + (r.volWt || 0), 0);
  return parseFloat(sum.toFixed(2));
});
const totalChgWt = computed(() => {
  const sum = boxRows.value.reduce((acc, r) => acc + (r.chgWt || 0), 0);
  return parseFloat(sum.toFixed(2));
});

// Computed Form Payload
const RateCalFormInput = computed(() => ({
  shipment_date: shipmentDate.value,
  destination: destination.value,
  pincode: pincode.value,
  state: state.value,
  city: city.value,
  package_type: packageType.value,
  actual_weight: totalActWt.value,
  chargeable_weight: totalChgWt.value,
  boxes: boxRows.value,
}));

// Validation for calculation
const isFormValidForCalculation = computed(() => {
  if (!RateCalFormInput.value.destination) return false;
  if (!RateCalFormInput.value.package_type) return false;
  if (!RateCalFormInput.value.shipment_date) return false;
  if (!RateCalFormInput.value.boxes?.length) return false;
  const hasInvalidBox = RateCalFormInput.value.boxes.some(
    box => !(Number(box.actWt) > 0 && Number(box.length) > 0 && Number(box.width) > 0 && Number(box.height) > 0)
  );
  return !hasInvalidBox;
});

// Build URL query parameters from current state
const buildQueryFromState = (isCalculated = false) => {
  const query = {};

  if (destination.value) {
    query.destination = String(destination.value);
  }

  if (packageType.value) {
    query.package_type = String(packageType.value);
  }

  if (pincode.value) {
    query.pincode = String(pincode.value);
  }

  if (city.value) {
    query.city = String(city.value);
  }

  if (state.value) {
    query.state = String(state.value);
  }

  if (shipmentDate.value && shipmentDate.value !== currentDate.value) {
    query.shipment_date = shipmentDate.value;
  }

  const hasBoxData = boxRows.value.some(
    b => Number(b.actWt) > 0 || Number(b.length) > 0 || Number(b.width) > 0 || Number(b.height) > 0
  );
  if (hasBoxData || boxRows.value.length > 1) {
    const simplified = boxRows.value.map(b => ({
      actWt: Number(b.actWt) || 0,
      length: Number(b.length) || 0,
      width: Number(b.width) || 0,
      height: Number(b.height) || 0
    }));
    query.boxes = JSON.stringify(simplified);
  }

  if (isCalculated) {
    query.calculated = '1';
  }

  return query;
};

// Sync draft changes to URL with debounce
let draftTimer = null;
const syncDraftInputsToUrl = () => {
  if (isInternalRouteUpdate) return;
  clearTimeout(draftTimer);
  draftTimer = setTimeout(() => {
    if (isInternalRouteUpdate) return;
    // Only preserve calculated='1' if results are currently visible and valid
    const isCalculated = showResults.value && route.query.calculated === '1' && isFormValidForCalculation.value;
    const query = buildQueryFromState(isCalculated);
    isInternalRouteUpdate = true;
    router.replace({ query }).catch(() => { }).finally(() => {
      setTimeout(() => {
        isInternalRouteUpdate = false;
      }, 100);
    });
  }, 250);
};

watch(
  [shipmentDate, destination, pincode, city, state, packageType, boxRows],
  () => {
    syncDraftInputsToUrl();
  },
  { deep: true }
);

// Watch for browser back/forward navigation
watch(
  () => route.query,
  async (newQuery) => {
    if (isInternalRouteUpdate) return;
    restoreStateFromQuery(newQuery);

    if (newQuery.calculated === '1') {
      if (isFormValidForCalculation.value) {
        await getShipmentRates(true);
      } else {
        calculationError.value = 'URL parameters are incomplete or invalid for calculation.';
      }
    } else {
      showResults.value = false;
      rateResults.value = [];
      calculationError.value = null;
    }
  }
);

// Reset Calculator
const resetCalculator = () => {
  destination.value = '';
  pincode.value = '';
  city.value = '';
  state.value = '';
  packageType.value = '';
  shipmentDate.value = getLocalDateString();
  boxRows.value = [defaultBox()];
  showResults.value = false;
  rateResults.value = [];
  calculationError.value = null;
  isInternalRouteUpdate = true;
  router.replace({ query: {} }).catch(() => { }).finally(() => {
    setTimeout(() => {
      isInternalRouteUpdate = false;
    }, 100);
  });
  toast.info('Calculator reset to default');
};

// ----------------------------------------------------
// Main API Calculation (POST)
// ----------------------------------------------------
const getShipmentRates = async (isAuto = false) => {
  if (inFlightCalculation) return;

  calculationError.value = null;

  if (!RateCalFormInput.value.destination) {
    if (!isAuto) toast.error('Please select destination');
    return;
  }
  if (!RateCalFormInput.value.package_type) {
    if (!isAuto) toast.error('Please select package type');
    return;
  }
  if (!RateCalFormInput.value.shipment_date) {
    if (!isAuto) toast.error('Please select shipment date');
    return;
  }
  if (!RateCalFormInput.value.boxes?.length) {
    if (!isAuto) toast.error('Please add at least one box');
    return;
  }

  const invalidBoxIndex = RateCalFormInput.value.boxes.findIndex(
    box => !(Number(box.actWt) > 0 && Number(box.length) > 0 && Number(box.width) > 0 && Number(box.height) > 0)
  );
  if (invalidBoxIndex !== -1) {
    const errorMsg = RateCalFormInput.value.boxes.length > 1
      ? `Weight and box dimensions should be greater than 0 for all box fields, Please check Box ${invalidBoxIndex + 1}`
      : 'Weight and box dimensions should be greater than 0 for all box fields, Please check Box 1';
    if (!isAuto) {
      toast.error(errorMsg);
    } else {
      calculationError.value = errorMsg;
    }
    return;
  }

  inFlightCalculation = true;
  isLoading.value = true;

  // Update URL to preserve submitted calculation state
  const query = buildQueryFromState(true);
  isInternalRouteUpdate = true;
  router.replace({ query }).catch(() => { }).finally(() => {
    setTimeout(() => {
      isInternalRouteUpdate = false;
    }, 100);
  });

  try {
    const res = await shipmentRateCalculator(RateCalFormInput.value);
    if (res && res.data && Array.isArray(res.data)) {
      rateResults.value = res.data;
    } else if (Array.isArray(res)) {
      rateResults.value = res;
    } else {
      rateResults.value = [];
    }
    showResults.value = true;
    if (rateResults.value.length > 0) {
      if (!isAuto) toast.success('Rates calculated successfully!');
    } else {
      if (!isAuto) toast.info('No rates found for the selected destination and weight.');
    }
  } catch (error) {
    console.error('Failed to calculate shipment rates:', error);
    const msg = error?.response?.data?.message || 'Failed to calculate rates. Please try again.';
    calculationError.value = msg;
    if (!isAuto) {
      toast.error(msg);
    }
  } finally {
    isLoading.value = false;
    inFlightCalculation = false;
  }
};

// ----------------------------------------------------
// Lifecycle onMounted
// ----------------------------------------------------
onMounted(async () => {
  // 1. Populate state from URL query
  restoreStateFromQuery(route.query);

  // 2. Fetch country list and bind destination
  await getCountryList();

  // 3. If URL has calculated === '1' and inputs are valid, calculate rates
  if (route.query.calculated === '1') {
    if (isFormValidForCalculation.value) {
      await getShipmentRates(true);
    } else {
      calculationError.value = 'URL parameters are incomplete or invalid for calculation.';
    }
  }
});

const destinationCountryName = computed(() => {
  const found = countryList.value.find(c => String(c.id) === String(destination.value));
  return found ? found.name : (destination.value || 'Destination');
});

const formatAmount = (val) => {
  if (val === null || val === undefined || isNaN(val)) return '0';
  const num = Number(val);
  return num % 1 === 0 ? num.toString() : Number(num.toFixed(2)).toString();
};

const getFuelCharge = (rate) => {
  return rate.additional_charges?.find(c =>
    c.code?.toLowerCase().includes('fuel') ||
    c.name?.toLowerCase().includes('fuel') ||
    c.code?.toLowerCase() === 'fsc'
  );
};

const getGstCharge = (rate) => {
  return rate.additional_charges?.find(c =>
    c.code?.toLowerCase().includes('gst') ||
    c.name?.toLowerCase().includes('gst') ||
    c.calculation_basis === 'sub_total'
  );
};

const getOtherCharges = (rate) => {
  return rate.additional_charges?.filter(c => {
    const isFuel = c.code?.toLowerCase().includes('fuel') ||
      c.name?.toLowerCase().includes('fuel') ||
      c.code?.toLowerCase() === 'fsc';
    const isGst = c.code?.toLowerCase().includes('gst') ||
      c.name?.toLowerCase().includes('gst') ||
      c.calculation_basis === 'sub_total';
    return !isFuel && !isGst;
  }) || [];
};

const getOtherChargesTotal = (rate) => {
  const charges = getOtherCharges(rate);
  return charges.reduce((acc, c) => acc + (Number(c.amount) || 0), 0);
};

// Filter & Sort for carrier results table
const searchCarrierQuery = ref('');
const sortKey = ref(null);
const sortAsc = ref(true);

const toggleSort = (key) => {
  if (sortKey.value === key) {
    sortAsc.value = !sortAsc.value;
  } else {
    sortKey.value = key;
    sortAsc.value = true;
  }
};

const filteredRates = computed(() => {
  let list = [...rateResults.value];
  if (searchCarrierQuery.value) {
    const q = searchCarrierQuery.value.toLowerCase().trim();
    list = list.filter(r => {
      const name = (r.service_partner?.name || '').toLowerCase();
      const code = (r.service_partner?.service_code || r.service || '').toLowerCase();
      return name.includes(q) || code.includes(q);
    });
  }
  if (sortKey.value === 'price') {
    list.sort((a, b) => {
      const valA = Number(a.final_total) || 0;
      const valB = Number(b.final_total) || 0;
      return sortAsc.value ? valA - valB : valB - valA;
    });
  }
  return list;
});

// Booking navigation route handler
const bookShipment = (rate) => {
  // Convert box rows to a simplified JSON query param
  const simplifiedBoxes = boxRows.value.map(b => ({
    actWt: b.actWt,
    length: b.length,
    width: b.width,
    height: b.height
  }))

  router.push({
    name: 'admin-add-new-shipment',
    query: {
      destination: destination.value,
      service: rate.service,
      packageType: packageType.value,
      goodsType: packageType.value,
      pincode: pincode.value,
      city: city.value,
      state: state.value,
      boxes: JSON.stringify(simplifiedBoxes)
    }
  })
}
</script>

<template>
  <div class="container-fluid p-0 page-rate-calculator">

    <!-- MAIN RATE CALCULATOR CARD -->
    <div class="custom-card p-4 mb-4 bg-white shadow-sm border-0">

      <!-- Card Title Header -->
      <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-2">
        <div class="header-icon bg-danger-subtle p-2 rounded-3 text-danger">
          <i class="bi bi-calculator fs-4"></i>
        </div>
        <h5 class="fw-bold mb-0 text-dark">Rate Calculator</h5>
      </div>

      <!-- Main Calculator inputs -->
      <div class="row g-3 mb-4">
        <!-- Date -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2.4">
          <label class="form-label small fw-bold text-danger text-uppercase mb-1">
            <i class="bi bi-calendar-event me-1"></i> Date
          </label>
          <input v-model="shipmentDate" :min="currentDate" type="date" class="form-control form-control-custom" />
        </div>

        <!-- Destination Country -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2.4">
          <label class="form-label small fw-bold text-danger text-uppercase mb-1">
            <i class="bi bi-geo-alt-fill me-1"></i> Destination <span class="text-danger">*</span>
          </label>
          <v-select :options="countryList" v-model="destination" :reduce="country => country.id" label="name"
            placeholder="Search Destination..." />
        </div>

        <!-- Pincode -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2.4">
          <label class="form-label small fw-bold text-danger text-uppercase mb-1">
            <i class="bi bi-geo-alt-fill me-1"></i> Pincode
          </label>
          <input v-model="pincode" type="text" class="form-control form-control-custom" />
        </div>

        <!-- City -->
        <div class="col-12 col-sm-6 col-md-6 col-lg-2.4">
          <label class="form-label small fw-bold text-danger text-uppercase mb-1">
            <i class="bi bi-building me-1"></i> City
          </label>
          <input v-model="city" type="text" class="form-control form-control-custom" />
        </div>

        <!-- State -->
        <div class="col-12 col-sm-6 col-md-6 col-lg-2.4">
          <label class="form-label small fw-bold text-danger text-uppercase mb-1">
            <i class="bi bi-flag me-1"></i> State
          </label>
          <input v-model="state" type="text" class="form-control form-control-custom" />
        </div>
      </div>

      <!-- Lower goods/dimensions section -->
      <div class="row g-4">
        <!-- Goods Type Selector -->
        <div class="col-12 col-md-3">
          <div class="d-flex flex-column h-100">
            <label class="form-label small fw-bold text-danger text-uppercase mb-1">
              <i class="bi bi-journal-text me-1"></i> Package Type <span class="text-danger">*</span>
            </label>
            <select v-model="packageType" class="form-select form-select-custom mb-2">
              <option value="" selected disabled>Select Package Type</option>
              <option v-for="(item, index) in DOCUMENT_TYPE" :key="index" :value="item">{{ item
              }}</option>
            </select>

            <!-- <div
              class="goods-info d-flex align-items-start gap-1 p-2 bg-light rounded-3 text-danger-emphasis small mt-2">
              <i class="bi bi-exclamation-circle-fill text-danger mt-0.5"></i>
              <span>Goods Type Info</span>
            </div> -->
          </div>
        </div>

        <!-- Dimension Boxes Table -->
        <div class="col-12 col-md-9">
          <div class="table-responsive">
            <table class="table align-middle custom-dim-table table-borderless">
              <thead>
                <tr class="text-uppercase text-secondary small fw-bold text-center">
                  <th style="width: 15%;">Actual Wt</th>
                  <th style="width: 15%;">Length</th>
                  <th style="width: 15%;">Width</th>
                  <th style="width: 15%;">Height</th>
                  <th style="width: 15%;">Vol. Wt</th>
                  <th style="width: 15%;">Chg. Wt</th>
                  <th style="width: 10%;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(box, idx) in boxRows" :key="idx" class="text-center">
                  <td>
                    <input v-model.number="box.actWt" @input="handleFieldChange(box)" type="number"
                      class="form-control text-center" min="0" step="0.5" />
                  </td>
                  <td>
                    <input v-model.number="box.length" @input="handleFieldChange(box)" type="number"
                      class="form-control text-center" min="0" />
                  </td>
                  <td>
                    <input v-model.number="box.width" @input="handleFieldChange(box)" type="number"
                      class="form-control text-center" min="0" />
                  </td>
                  <td>
                    <input v-model.number="box.height" @input="handleFieldChange(box)" type="number"
                      class="form-control text-center" min="0" />
                  </td>
                  <td>
                    <input :value="box.volWt.toFixed(2)" type="text" class="form-control text-center bg-light"
                      readonly />
                  </td>
                  <td>
                    <div class="input-group input-group-sm justify-content-center align-items-center">
                      <input :value="box.chgWt.toFixed(2)" type="text" class="form-control text-center bg-light"
                        readonly style="max-width: 70px;" />
                      <span class="input-group-text bg-light text-muted border-start-0 py-2"
                        style="font-size: 0.72rem;">Kg</span>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                      <button @click="duplicateBox(idx)" type="button"
                        class="btn btn-outline-success btn-action-copy rounded-2 p-1" title="Duplicate Box">
                        <i class="bi bi-copy"></i>
                      </button>
                      <button @click="deleteBox(idx)" type="button"
                        class="btn btn-outline-danger btn-action-delete rounded-2 p-1" title="Delete Box">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table Totals Row -->
          <div
            class="totals-bar p-2 bg-light rounded-3 d-flex flex-wrap gap-2 justify-content-start align-items-center mb-3">
            <!-- Total Pcs -->
            <div class="input-group input-group-totals">
              <span class="input-group-text text-white bg-secondary border-0 small font-semibold px-2">Total
                PCS</span>
              <input :value="totalPCS" type="text" class="form-control bg-white text-center border-0 fw-bold" readonly
                style="max-width: 60px;" />
            </div>

            <!-- Actual Wt -->
            <div class="input-group input-group-totals">
              <span class="input-group-text text-white bg-secondary border-0 small font-semibold px-2">Act.
                Wt</span>
              <input :value="totalActWt.toFixed(2)" type="text"
                class="form-control bg-white text-center border-0 fw-bold text-dark" readonly
                style="max-width: 75px;" />
            </div>

            <!-- Vol Wt -->
            <div class="input-group input-group-totals">
              <span class="input-group-text text-white bg-secondary border-0 small font-semibold px-2">Vol.
                Wt</span>
              <input :value="totalVolWt.toFixed(2)" type="text"
                class="form-control bg-white text-center border-0 fw-bold text-dark" readonly
                style="max-width: 75px;" />
            </div>

            <!-- Chg Wt -->
            <div class="input-group input-group-totals">
              <span class="input-group-text text-white bg-secondary border-0 small font-semibold px-2">Chg.
                Wt</span>
              <input :value="totalChgWt.toFixed(2)" type="text"
                class="form-control bg-white text-center border-0 fw-bold text-danger" readonly
                style="max-width: 75px;" />
            </div>
          </div>

          <!-- Bottom Calculator Actions -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <button @click="addBox" type="button"
                class="btn btn-add-box fw-bold px-3 py-2 d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>ADD BOX</span>
              </button>

              <button @click="resetCalculator" type="button"
                class="btn btn-reset fw-bold px-3 py-2 d-flex align-items-center gap-2" title="Reset calculator inputs">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>RESET</span>
              </button>
            </div>

            <button @click="getShipmentRates(false)" :disabled="isLoading" type="button"
              class="btn btn-get-rate fw-bold px-4 py-2 text-uppercase d-flex align-items-center gap-2">
              <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              <span>{{ isLoading ? 'CALCULATING...' : 'GET RATE' }}</span>
              <i v-if="!isLoading" class="bi bi-arrow-right"></i>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- ERROR ALERT -->
    <div v-if="calculationError && !isLoading"
      class="alert alert-danger d-flex align-items-center justify-content-between gap-3 mb-4 rounded-3 shadow-xs"
      role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
        <div>
          <strong>Calculation Error:</strong> {{ calculationError }}
        </div>
      </div>
      <button @click="calculationError = null" type="button" class="btn-close" aria-label="Close"></button>
    </div>

    <!-- LOADING STATE -->
    <div v-if="isLoading" class="custom-card p-5 mb-4 bg-white shadow-sm border-0 text-center animate-fade rounded-3">
      <div class="spinner-border text-danger mb-3" style="width: 2.75rem; height: 2.75rem;" role="status">
        <span class="visually-hidden">Calculating rates...</span>
      </div>
      <h6 class="fw-bold text-dark mb-1">Calculating Best Rates...</h6>
      <p class="text-muted small mb-0">Contacting courier partners for destination and package specifications.</p>
    </div>

    <!-- CALCULATOR SEARCH RESULTS SECTION (Matches Carrier Rates & AWB Design) -->
    <div v-if="showResults && !isLoading"
      class="custom-card p-4 bg-white shadow-sm border-0 animate-fade rounded-4 mb-4">

      <!-- Header with Title & Search / Filter Controls -->
      <div
        class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-3">
        <!-- <div>
          <h4 class="fw-bold text-dark mb-1 carrier-section-title">Carrier Rates & AWB</h4>
          <div class="text-secondary small">Compare carrier rates and create Air Waybill</div>
        </div> -->

        <div class="d-flex align-items-center gap-2 w-100 w-md-auto justify-content-md-end">
          <div class="search-carrier-wrapper">
            <input v-model="searchCarrierQuery" type="text" class="form-control form-control-sm search-carrier-input"
              placeholder="Search service or carrier..." />
          </div>
          <button type="button"
            class="btn btn-outline-secondary btn-sm btn-filter-carrier d-flex align-items-center gap-1">
            <i class="bi bi-funnel-fill text-danger"></i>
            <span>Filters</span>
          </button>
        </div>
      </div>

      <!-- Carrier Rates Table -->
      <div v-if="rateResults.length > 0" class="table-responsive carrier-table-wrapper rounded-3">
        <table class="table align-middle carrier-rates-table mb-0">
          <thead>
            <tr class="carrier-thead-row">
              <th class="ps-4" style="width: 22%;">Service</th>
              <th @click="toggleSort('price')" class="cursor-pointer" style="width: 20%;">
                <span class="d-inline-flex align-items-center gap-1">
                  <span>Total Net Price</span>
                  <i class="bi bi-arrow-down-up opacity-75 small"></i>
                </span>
              </th>
              <th style="width: 24%;">Price Breakdown</th>
              <th class="text-center" style="width: 14%;">Chargeable Weight</th>
              <th class="text-center" style="width: 10%;">
                <span class="d-inline-flex align-items-center gap-1">
                  <span>Expected delivery date</span>
                  <i class="bi bi-arrow-down-up opacity-75 small"></i>
                </span>
              </th>
              <th class="text-center pe-4" style="width: 10%;">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rate in filteredRates" :key="rate.id" class="carrier-row">
              <!-- Col 1: Service -->
              <td class="ps-4 py-4 align-middle border-end-cell">
                <div class="d-flex flex-column align-items-start">
                  <div class="carrier-logo-card mb-2">
                    <img v-if="rate.service_partner?.logo" :src="rate.service_partner.logo"
                      :alt="rate.service_partner.name" class="carrier-logo-img" />
                    <span v-else class="fw-bold fs-5 text-dark">
                      {{ rate.service_partner?.name || 'COURIER' }}
                    </span>
                  </div>
                  <div class="carrier-service-name">
                    {{ rate.service_partner?.name || 'EXPRESS' }}
                  </div>
                </div>
              </td>

              <!-- Col 2: Total Net Price -->
              <td class="py-4 align-middle border-end-cell">
                <div class="d-flex flex-column gap-1">
                  <div class="carrier-price-row">
                    <span class="carrier-price-label">PER KG :</span>
                    <span class="carrier-price-val ms-1">{{ formatAmount(rate.per_kg_rate ?? (rate.final_total /
                      (rate.chargeable_weight || 1))) }}</span>
                  </div>
                  <div class="carrier-price-row">
                    <span class="carrier-price-label">Sub Total :</span>
                    <span class="carrier-price-val ms-1">{{ formatAmount(rate.sub_total || rate.base_freight) }}</span>
                  </div>
                  <div class="carrier-price-row">
                    <span class="carrier-price-label">Grand Total :</span>
                    <span class="carrier-price-val ms-1">{{ formatAmount(rate.final_total) }}</span>
                  </div>
                  <div class="carrier-grand-price">
                    {{ formatAmount(rate.final_total) }}
                  </div>
                </div>
              </td>

              <!-- Col 3: Price Breakdown -->
              <td class="py-4 align-middle border-end-cell">
                <div class="carrier-breakdown-box">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="breakdown-name-item">
                      <span class="red-dot">•</span> Freight
                    </span>
                    <span class="breakdown-val-item">{{ formatAmount(rate.base_freight) }}</span>
                  </div>

                  <template v-if="getFuelCharge(rate)">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="breakdown-name-item">
                        <span class="red-dot">•</span> FSC
                      </span>
                      <span class="breakdown-val-item">{{ formatAmount(getFuelCharge(rate).amount) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="breakdown-name-item">
                        <span class="red-dot">•</span> FSC%
                      </span>
                      <span class="breakdown-val-item">{{ formatAmount(getFuelCharge(rate).rate_value) }}</span>
                    </div>
                  </template>

                  <div v-if="getOtherChargesTotal(rate) > 0"
                    class="d-flex justify-content-between align-items-center mb-1">
                    <span class="breakdown-name-item">
                      <span class="red-dot">•</span> OTHER CHARGES
                    </span>
                    <span class="breakdown-val-item">{{ formatAmount(getOtherChargesTotal(rate)) }}</span>
                  </div>

                  <div v-if="getGstCharge(rate)" class="d-flex justify-content-between align-items-center">
                    <span class="breakdown-name-item">
                      <span class="red-dot">•</span> GST
                    </span>
                    <span class="breakdown-val-item">{{ formatAmount(getGstCharge(rate).amount) }}</span>
                  </div>
                </div>
              </td>

              <!-- Col 4: Chargeable Weight -->
              <td class="text-center py-4 align-middle border-end-cell">
                <div class="d-flex flex-column align-items-center justify-content-center">
                  <div class="carrier-scale-icon mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                      stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z" />
                      <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z" />
                      <path d="M7 21h10" />
                      <path d="M12 3v18" />
                      <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2" />
                    </svg>
                  </div>
                  <div class="carrier-weight-num">
                    {{ Number(rate.chargeable_weight || totalChgWt).toFixed(1) }}
                  </div>
                  <div class="carrier-weight-unit">KG</div>
                </div>
              </td>

              <!-- Col 5: Expected delivery date -->
              <td class="text-center py-4 align-middle border-end-cell">
                <div class="d-flex flex-column align-items-center justify-content-center">
                  <div class="carrier-clock-badge mb-1">
                    <i class="bi bi-clock-fill text-danger fs-6"></i>
                  </div>
                  <span v-if="rate.max_transit_days" class="carrier-delivery-text">
                    {{ rate.max_transit_days }} Days
                  </span>
                </div>
              </td>

              <!-- Col 6: Action -->
              <td class="text-center pe-4 py-4 align-middle">
                <button @click="bookShipment(rate)" type="button"
                  class="btn btn-create-awb d-inline-flex align-items-center justify-content-center gap-1 shadow-xs">
                  <i class="bi bi-file-earmark-text-fill"></i>
                  <span>Book Now</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- No rates available fallback -->
      <div v-else class="text-center py-5">
        <i class="bi bi-inbox text-muted display-4 mb-3 d-block"></i>
        <h6 class="fw-bold text-dark">No rates available</h6>
        <p class="text-muted small">Try adjusting the package weight or destination country.</p>
      </div>

      <!-- Footer: Showing results -->
      <div v-if="rateResults.length > 0" class="pt-3 text-secondary small">
        Showing 1 to {{ filteredRates.length }} of {{ rateResults.length }} results
      </div>
    </div>
  </div>
</template>


