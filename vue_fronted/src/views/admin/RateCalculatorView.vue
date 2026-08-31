<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// ----------------------------------------------------
// Global Mock State
// ----------------------------------------------------
const walletBalance = ref(408.61)
const searchAwbQuery = ref('')
const showRechargeModal = ref(false)
const rechargeAmount = ref(500)

// Calculator Form State
const calcDate = ref('2026-08-13')
const destination = ref('ALBANIA')
const pincode = ref('')
const city = ref('')
const state = ref('')
const goodsType = ref('NDox')

// Box Rows State
const boxRows = ref([
  { actWt: 2, length: 20, width: 20, height: 20, volWt: 1.60, chgWt: 2.00 },
  { actWt: 1, length: 20, width: 15, height: 19, volWt: 1.14, chgWt: 1.50 }
])

// Volumetric calculations
const calculateRowVolWt = (row) => {
  if (row.length && row.width && row.height) {
    row.volWt = parseFloat(((row.length * row.width * row.height) / 5000).toFixed(2))
  } else {
    row.volWt = 0
  }
  const maxWt = Math.max(row.actWt || 0, row.volWt)
  // Round up to nearest 0.5 kg
  row.chgWt = parseFloat((Math.ceil(maxWt * 2) / 2).toFixed(2))
}

const addBox = () => {
  const newRow = { actWt: 1, length: 10, width: 10, height: 10, volWt: 0.20, chgWt: 1.00 }
  calculateRowVolWt(newRow)
  boxRows.value.push(newRow)
}

const duplicateBox = (index) => {
  const target = boxRows.value[index]
  if (target) {
    const clone = { ...target }
    boxRows.value.push(clone)
  }
}

const deleteBox = (index) => {
  if (boxRows.value.length <= 1) {
    showAlert('At least one box row is required', 'danger')
    return
  }
  boxRows.value.splice(index, 1)
}

const handleFieldChange = (row) => {
  calculateRowVolWt(row)
}

// Totals Computed Row
const totalPCS = computed(() => boxRows.value.length)
const totalActWt = computed(() => {
  const sum = boxRows.value.reduce((acc, r) => acc + (r.actWt || 0), 0)
  return parseFloat(sum.toFixed(2))
})
const totalVolWt = computed(() => {
  const sum = boxRows.value.reduce((acc, r) => acc + (r.volWt || 0), 0)
  return parseFloat(sum.toFixed(2))
})
const totalChgWt = computed(() => {
  const sum = boxRows.value.reduce((acc, r) => acc + (r.chgWt || 0), 0)
  return parseFloat(sum.toFixed(2))
})

// Search AWB / Recharge logic
const alertMessage = ref(null)
const showAlert = (text, type = 'success') => {
  alertMessage.value = { type, text }
  setTimeout(() => {
    alertMessage.value = null
  }, 4000)
}

const handleAwbSearch = () => {
  if (!searchAwbQuery.value) return
  showAlert(`AWB Number "${searchAwbQuery.value}" search initialized. (No results)`, 'danger')
}

const processRecharge = () => {
  walletBalance.value += rechargeAmount.value
  showRechargeModal.value = false
  showAlert(`Recharged ₹${rechargeAmount.value}. New Balance: ₹${walletBalance.value}`)
}

// Results display state
const showResults = ref(false)
const rateResults = ref([])

const getRates = () => {
  // Populate mock rates based on user selections
  const destIso = destination.value.substring(0, 3)
  rateResults.value = [
    {
      id: 'rate-fedex',
      service: 'FEDEX_IP',
      vendor: 'FedEx',
      logoClass: 'fedex-logo-box',
      logoText: 'FedEx',
      logoColor: '#4D148C',
      available: true,
      iso: destIso,
      details: {
        deliveryType: 'Drop off *',
        transitTime: '3-5 Business Days',
        badge: 'Tracked Delivery'
      },
      price: parseFloat((5810.32 + (totalChgWt.value - 3.5) * 500).toFixed(2))
    },
    {
      id: 'rate-dhl',
      service: 'DHL_EXPRESS',
      vendor: 'DHL',
      logoClass: 'dhl-logo-box',
      logoText: 'DHL',
      logoColor: '#FFCC00',
      available: true,
      iso: destIso,
      details: {
        deliveryType: 'Pickup *',
        transitTime: '2-4 Business Days',
        badge: 'Tracked Delivery'
      },
      price: parseFloat((6420.50 + (totalChgWt.value - 3.5) * 600).toFixed(2))
    },
    {
      id: 'rate-ups',
      service: 'UPS_SAVER',
      vendor: 'UPS',
      logoClass: 'ups-logo-box',
      logoText: 'UPS',
      logoColor: '#351C15',
      available: true,
      iso: destIso,
      details: {
        deliveryType: 'Drop off *',
        transitTime: '4-6 Business Days',
        badge: 'Tracked Delivery'
      },
      price: parseFloat((5120.10 + (totalChgWt.value - 3.5) * 450).toFixed(2))
    }
  ]
  showResults.value = true
  showAlert('Rates calculated successfully!')
}

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
      goodsType: goodsType.value,
      city: city.value,
      state: state.value,
      boxes: JSON.stringify(simplifiedBoxes)
    }
  })
}
</script>

<template>
  <div class="container-fluid p-0">

    <!-- Top Alert System -->
    <transition name="fade">
      <div v-if="alertMessage"
        class="alert position-fixed top-0 start-50 translate-middle-x mt-5 z-3 shadow-lg rounded-3 border-0 px-4 py-3"
        :class="alertMessage.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
        style="width: 90%; max-width: 480px;">
        <div class="d-flex align-items-center gap-2">
          <i class="bi"
            :class="alertMessage.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
          <div class="fw-semibold text-break flex-fill">{{ alertMessage.text }}</div>
          <button type="button" class="btn-close btn-close-white ms-auto" @click="alertMessage = null"></button>
        </div>
      </div>
    </transition>


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
          <input v-model="calcDate" type="date" class="form-control form-control-custom" />
        </div>

        <!-- Destination Country -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2.4">
          <label class="form-label small fw-bold text-danger text-uppercase mb-1">
            <i class="bi bi-geo-alt-fill me-1"></i> Destination
          </label>
          <select v-model="destination" class="form-select form-select-custom text-uppercase">
            <option value="ALBANIA">Albania</option>
            <option value="SINGAPORE">Singapore</option>
            <option value="UNITED STATES">United States</option>
            <option value="AUSTRIA">Austria</option>
            <option value="GERMANY">Germany</option>
            <option value="INDIA">India</option>
          </select>
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
              <i class="bi bi-journal-text me-1"></i> Goods Type
            </label>
            <select v-model="goodsType" class="form-select form-select-custom mb-2">
              <option value="NDox">NDox</option>
              <option value="Dox">Dox</option>
            </select>

            <div
              class="goods-info d-flex align-items-start gap-1 p-2 bg-light rounded-3 text-danger-emphasis small mt-2">
              <i class="bi bi-exclamation-circle-fill text-danger mt-0.5"></i>
              <span>Goods Type Info</span>
            </div>
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
            <button @click="addBox" type="button"
              class="btn btn-add-box fw-bold px-3 py-2 d-flex align-items-center gap-2">
              <i class="bi bi-plus-lg"></i>
              <span>ADD BOX</span>
            </button>

            <button @click="getRates" type="button"
              class="btn btn-get-rate fw-bold px-4 py-2 text-uppercase d-flex align-items-center gap-2">
              <span>GET RATE</span>
              <i class="bi bi-arrow-right"></i>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- CALCULATOR SEARCH RESULTS SECTION -->
    <div v-if="showResults" class="custom-card p-4 bg-white shadow-sm border-0 animate-fade">

      <!-- Results Header -->
      <div
        class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-search text-danger fs-4"></i>
          <div>
            <h5 class="fw-bold mb-0 text-dark">Your search results</h5>
            <span class="text-muted small">Found {{ rateResults.length }} rates</span>
          </div>
        </div>

        <!-- Search Route Badges -->
        <div class="d-flex flex-wrap gap-2">
          <!-- Origin -> Dest -->
          <span
            class="badge bg-light text-secondary border px-3 py-2 d-flex align-items-center gap-2 rounded-pill small fw-semibold">
            <i class="bi bi-geo-alt-fill text-danger"></i>
            <span>India</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span>{{ destination }}</span>
          </span>

          <!-- Chargeable Weight -->
          <span
            class="badge bg-light text-secondary border px-3 py-2 d-flex align-items-center gap-2 rounded-pill small fw-semibold">
            <i class="bi bi-speedometer2 text-danger"></i>
            <span>{{ totalChgWt.toFixed(2) }} KG</span>
          </span>
        </div>
      </div>

      <!-- Service Rates Table Layout -->
      <div class="table-responsive">
        <table class="table align-middle rate-results-table">
          <thead class="text-secondary small fw-bold border-bottom">
            <tr>
              <th style="width: 25%;">Service</th>
              <th style="width: 35%;">Details</th>
              <th style="width: 10%;" class="text-center">Info</th>
              <th style="width: 30%;" class="text-end">Price</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rate in rateResults" :key="rate.id" class="border-bottom-hover">
              <!-- Service Logo & Name -->
              <td>
                <div class="d-flex align-items-center gap-3">
                  <!-- Custom Mock Logo -->
                  <div class="vendor-logo-wrapper d-flex align-items-center justify-content-center fw-bold"
                    :style="{ backgroundColor: rate.logoColor + '10', color: rate.logoColor }">
                    <span class="small">{{ rate.logoText }}</span>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="fw-bold text-dark fs-6">{{ rate.service }}</span>
                    <div class="d-flex gap-1 mt-1">
                      <span class="badge bg-success-subtle text-success py-1 px-2 rounded-pill"
                        style="font-size: 0.68rem;">Available</span>
                      <span class="badge bg-light text-secondary border py-1 px-2 rounded-pill"
                        style="font-size: 0.68rem;">{{ rate.iso }}</span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Services details (Dropoff, Transit times, Tracked) -->
              <td>
                <div class="d-flex flex-column gap-1">
                  <div class="d-flex align-items-center gap-2 text-dark font-medium small">
                    <i class="bi bi-truck text-muted"></i>
                    <span>{{ rate.details.deliveryType }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 text-muted small">
                    <i class="bi bi-clock text-muted"></i>
                    <span>{{ rate.details.transitTime }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-1 mt-1">
                    <span
                      class="badge bg-primary-subtle text-primary border border-primary border-opacity-10 py-1 px-2 d-flex align-items-center gap-1 rounded-3"
                      style="font-size: 0.72rem;">
                      <i class="bi bi-shield-check"></i>
                      <span>{{ rate.details.badge }}</span>
                    </span>
                  </div>
                </div>
              </td>

              <!-- Info details -->
              <td class="text-center">
                <i class="bi bi-info-circle text-muted fs-5 cursor-pointer" title="Service Terms and Policies"></i>
              </td>

              <!-- Price & Book Now button -->
              <td>
                <div class="d-flex align-items-center justify-content-end gap-3 flex-wrap">
                  <div class="text-end">
                    <div class="fw-bold text-dark fs-4">₹ {{ rate.price.toFixed(2) }}</div>
                    <span class="text-success small d-flex align-items-center gap-1 justify-content-end"
                      style="font-size: 0.75rem;">
                      <i class="bi bi-check2-circle"></i> Inclusive All Tax
                    </span>
                  </div>

                  <button @click="bookShipment(rate)" type="button"
                    class="btn btn-book-now px-4 py-2 fw-bold d-flex align-items-center gap-1">
                    <span>Book Now</span>
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- More Info dropdown -->
      <div class="text-center pt-3 border-top mt-2">
        <a href="#" @click.prevent
          class="text-decoration-none text-secondary small fw-bold d-inline-flex align-items-center gap-1">
          <span>More info</span>
          <i class="bi bi-chevron-down"></i>
        </a>
      </div>

    </div>

    <!-- Recharge Wallet Modal -->
    <div v-if="showRechargeModal" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.6); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
          <div class="modal-header bg-danger text-white p-3 px-4">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-wallet2 me-2"></i> Recharge Wallet
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showRechargeModal = false"></button>
          </div>
          <form @submit.prevent="processRecharge">
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label small fw-bold">Enter Amount (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light fw-bold">₹</span>
                  <input v-model.number="rechargeAmount" type="number" class="form-control fw-bold fs-5 text-center"
                    min="10" max="10000" required />
                </div>
                <div class="text-muted small mt-2 text-center">
                  Recommended:
                  <button type="button" @click="rechargeAmount = 200"
                    class="btn btn-xs btn-outline-secondary py-0 px-2 small rounded-pill m-1">₹200</button>
                  <button type="button" @click="rechargeAmount = 500"
                    class="btn btn-xs btn-outline-secondary py-0 px-2 small rounded-pill m-1">₹500</button>
                  <button type="button" @click="rechargeAmount = 1000"
                    class="btn btn-xs btn-outline-secondary py-0 px-2 small rounded-pill m-1">₹1000</button>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light p-3 px-4 justify-content-between">
              <button type="button" class="btn btn-outline-secondary rounded-3" @click="showRechargeModal = false">
                Cancel
              </button>
              <button type="submit" class="btn btn-recharge px-4 py-2 fw-semibold">
                Proceed Recharge
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Form Inputs */
.form-control-custom,
.form-select-custom {
  border-radius: 8px;
  border: 1px solid #dcdcdc;
  padding: 0.55rem 0.75rem;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s ease-in-out;
  background-color: #fcfcfc;
}

.form-control-custom:focus,
.form-select-custom:focus {
  border-color: #dc3545;
  background-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.header-panel {
  border: 1px solid #f1f5f9;
  border-radius: 12px;
  background: #ffffff;
}

.header-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #8a8a8a !important;
}

.search-group {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  border-radius: 8px;
  overflow: hidden;
}

.btn-search-custom {
  background-color: #e02229;
  color: #ffffff;
  border: none;
  padding-left: 1.25rem;
  padding-right: 1.25rem;
  font-size: 0.9rem;
  transition: opacity 0.15s ease;
}

.btn-search-custom:hover {
  opacity: 0.9;
  color: #ffffff;
}

.btn-recharge {
  background-color: #e02229;
  color: #ffffff;
  border: none;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  transition: opacity 0.15s ease;
}

.btn-recharge:hover {
  opacity: 0.9;
  color: #ffffff;
}

.balance-badge {
  background-color: #a3181d;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.98rem;
  border-radius: 8px;
  height: 38px;
  min-width: 100px;
}

.icon-btn {
  background-color: #f7f9fa;
  border: 1px solid #e9ecef;
}

.profile-avatar-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background-color: #6f42c1;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.88rem;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* Dimension Grid styling */
.custom-dim-table th {
  border: none;
  color: #4b5563;
  padding-bottom: 0.5rem;
}

.custom-dim-table td {
  padding: 0.35rem;
  border: none;
}

.btn-action-copy {
  color: #198754;
  border-color: rgba(25, 135, 84, 0.2);
  background-color: #ffffff;
}

.btn-action-copy:hover {
  background-color: #198754;
  color: #ffffff;
  border-color: #198754;
}

.btn-action-delete {
  color: #dc3545;
  border-color: rgba(220, 53, 69, 0.2);
  background-color: #ffffff;
}

.btn-action-delete:hover {
  background-color: #dc3545;
  color: #ffffff;
  border-color: #dc3545;
}

/* Totals styling */
.totals-bar {
  border: 1px solid #e2e8f0;
}

.input-group-totals {
  width: auto;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.input-group-totals .input-group-text {
  font-size: 0.78rem;
  font-weight: 700;
  background-color: #5a6268 !important;
}

.input-group-totals .form-control {
  padding: 0.35rem 0.5rem;
  font-size: 0.88rem;
}

/* Buttons */
.btn-add-box {
  color: #e02229;
  background-color: #ffffff;
  border: 1.5px solid #e02229;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.btn-add-box:hover {
  background-color: #fdf3f4;
  transform: translateY(-1px);
}

.btn-get-rate {
  background-color: #e02229;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 10px rgba(224, 34, 41, 0.15);
}

.btn-get-rate:hover {
  background-color: #c91b22;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 6px 14px rgba(224, 34, 41, 0.22);
}

/* Search results Rate cards */
.rate-results-table th {
  padding: 0.75rem;
  border: none;
}

.rate-results-table td {
  padding: 1.25rem 0.75rem;
  vertical-align: middle;
}

.border-bottom-hover {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.15s ease;
}

.border-bottom-hover:hover {
  background-color: #fafbfc;
}

.vendor-logo-wrapper {
  width: 72px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: -0.01em;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.btn-book-now {
  background-color: #e02229;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.btn-book-now:hover {
  background-color: #c91b22;
  color: #ffffff;
  transform: translateY(-1px);
}

/* Animations */
.animate-fade {
  animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
