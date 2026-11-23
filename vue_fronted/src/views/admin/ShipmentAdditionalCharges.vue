<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from "vue-toastification";
import { getAllServicePartners } from '@/services/admin/servicePartner';
import { getShipChargesList } from '@/services/admin/shipmentChargesMasterService';
import {
  addNewShipAdditionalCharge,
  bulkAddShipAdditionalCharges,
  bulkUploadShipAdditionalCharges,
  updateShipAdditionalCharge,
  getShipAdditionalChargesList,
  deleteShipAdditionalCharge
} from '@/services/admin/shipmentAddChargesService';

const toast = useToast();
const isEditing = ref(false);
const editingChargeId = ref(null);
const showShipAddiChargesModal = ref(false);
const filterStatus = ref('All')
const ServicePartnerList = ref([])
const ShipChargesMasterList = ref([])
const isLoading = ref(true);
const searchQuery = ref('');

const formData = ref({
  service_partner_id: '',
  charge_type_id: '',
  value: '',
})

const ShipAdditionalChargesList = ref([]);

// Bulk Map (UI) states
const showBulkAddModal = ref(false)
const bulkAddPartnerId = ref('')
const bulkApplyToAllPartners = ref(false)
const bulkAddMode = ref('upsert')
const bulkChargeSearch = ref('')
const bulkCharges = ref([])
const isSavingBulkCharges = ref(false)

// Bulk Upload (CSV) states
const showBulkUploadModal = ref(false)
const uploadFile = ref(null)
const uploadMode = ref('upsert')
const isUploading = ref(false)
const uploadResult = ref(null)
const isDragOver = ref(false)
const fileInputRef = ref(null)

const fetchServicePartners = async () => {
  try {
    const resp = await getAllServicePartners(100, 1);
    if (resp.status == "success") {
      ServicePartnerList.value = resp.data || [];
    }
  }
  catch (err) {
    toast.error(err.message);
  }
}

const fetchShipChargesMasterList = async () => {
  try {
    const resp = await getShipChargesList();
    if (resp.status == "success") {
      ShipChargesMasterList.value = resp.data || [];
    }
  }
  catch (err) {
    toast.error(err.message);
  }
}

const fetchShipAdditionalChargesList = async () => {
  try {
    isLoading.value = true;
    const resp = await getShipAdditionalChargesList();
    if (resp.status == "success") {
      ShipAdditionalChargesList.value = resp.data || [];
    }
  }
  catch (err) {
    toast.error(err.message);
  }
  finally {
    isLoading.value = false;
  }
}

// Single Add / Edit
const openAddModal = () => {
  isEditing.value = false;
  editingChargeId.value = null;
  formData.value = {
    service_partner_id: ServicePartnerList.value?.[0]?.id || '',
    charge_type_id: ShipChargesMasterList.value?.[0]?.id || '',
    value: '',
  };
  showShipAddiChargesModal.value = true;
}

const openEditModal = (item) => {
  isEditing.value = true;
  editingChargeId.value = item.id;
  formData.value = {
    service_partner_id: item.service_partner_id,
    charge_type_id: item.charge_type_id,
    value: item.charges_value,
  };
  showShipAddiChargesModal.value = true;
}

const saveSingleCharge = async () => {
  try {
    if (isEditing.value && editingChargeId.value) {
      const resp = await updateShipAdditionalCharge(editingChargeId.value, {
        value: formData.value.value,
        status: 'active'
      });
      if (resp?.status === 'success') {
        toast.success(resp.message || 'Additional Charge updated successfully!');
        showShipAddiChargesModal.value = false;
        fetchShipAdditionalChargesList();
      } else {
        toast.error(resp?.message || 'Failed to update additional charge');
      }
    } else {
      const resp = await addNewShipAdditionalCharge(formData.value);
      if (resp?.status === "success") {
        toast.success(resp.message);
        showShipAddiChargesModal.value = false;
        formData.value = {
          service_partner_id: '',
          charge_type_id: '',
          value: '',
        };
        fetchShipAdditionalChargesList();
      } else {
        toast.error(resp?.message || 'Failed to map additional charge');
      }
    }
  }
  catch (err) {
    toast.error(err.message);
  }
}

const deleteCharge = async (item) => {
  const name = item.charges_type || 'this charge';
  if (!confirm(`Are you sure you want to delete "${name}"?`)) {
    return;
  }
  try {
    const resp = await deleteShipAdditionalCharge(item.id);
    if (resp?.status === 'success') {
      toast.success(resp.message || 'Additional Charge deleted successfully!');
      fetchShipAdditionalChargesList();
    } else {
      toast.error(resp?.message || 'Failed to delete charge');
    }
  } catch (err) {
    toast.error(err.message);
  }
};

// Bulk Map Methods
const openBulkAddModal = () => {
  bulkAddPartnerId.value = ServicePartnerList.value?.[0]?.id || ''
  bulkApplyToAllPartners.value = false
  bulkAddMode.value = 'upsert'
  bulkChargeSearch.value = ''
  initBulkCharges()
  showBulkAddModal.value = true
}

const initBulkCharges = () => {
  const mappedMap = {}
  if (!bulkApplyToAllPartners.value && bulkAddPartnerId.value) {
    ShipAdditionalChargesList.value
      .filter(item => item.service_partner_id == bulkAddPartnerId.value)
      .forEach(item => {
        mappedMap[item.charge_type_id] = item.charges_value
      })
  }

  bulkCharges.value = ShipChargesMasterList.value.map(charge => {
    const existingVal = mappedMap[charge.id]
    return {
      charge_type_id: charge.id,
      name: charge.name,
      code: charge.code,
      calculation_method: charge.calculation_method,
      calculation_basis: charge.calculation_basis,
      description: charge.description,
      value: existingVal !== undefined ? existingVal : '',
      selected: existingVal !== undefined,
      currentValue: existingVal,
      status: 'active',
    }
  })
}

watch(bulkAddPartnerId, () => {
  if (!bulkApplyToAllPartners.value) {
    initBulkCharges()
  }
})

watch(bulkApplyToAllPartners, (newVal) => {
  if (newVal) {
    bulkCharges.value.forEach(c => {
      c.currentValue = undefined
    })
  } else {
    initBulkCharges()
  }
})

const selectAllBulkCharges = () => {
  bulkCharges.value.forEach(c => { c.selected = true })
}

const deselectAllBulkCharges = () => {
  bulkCharges.value.forEach(c => { c.selected = false })
}

const filteredBulkCharges = computed(() => {
  const q = bulkChargeSearch.value.trim().toLowerCase()
  if (!q) return bulkCharges.value
  return bulkCharges.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.code.toLowerCase().includes(q) ||
    (c.calculation_method || '').toLowerCase().includes(q) ||
    (c.calculation_basis || '').toLowerCase().includes(q)
  )
})

const selectedBulkChargesCount = computed(() => {
  return bulkCharges.value.filter(c => c.selected && c.value !== '' && !isNaN(c.value) && Number(c.value) >= 0).length
})

const handleSaveBulkCharges = async () => {
  const selected = bulkCharges.value.filter(c => c.selected && c.value !== '' && !isNaN(c.value) && Number(c.value) >= 0)
  if (selected.length === 0) {
    toast.error('Please select at least one charge and enter a valid numeric value.')
    return
  }

  let partnerIds = []
  if (bulkApplyToAllPartners.value) {
    partnerIds = ServicePartnerList.value.map(p => p.id)
  } else {
    if (!bulkAddPartnerId.value) {
      toast.error('Please select a service partner.')
      return
    }
    partnerIds = [Number(bulkAddPartnerId.value)]
  }

  isSavingBulkCharges.value = true
  try {
    const payload = {
      service_partner_ids: partnerIds,
      mode: bulkAddMode.value,
      charges: selected.map(c => ({
        charge_type_id: c.charge_type_id,
        value: Number(c.value),
        status: c.status || 'active',
      }))
    }

    const resp = await bulkAddShipAdditionalCharges(payload)
    if (resp?.status === 'success') {
      toast.success(resp.message || 'Additional Charges mapped successfully!')
      showBulkAddModal.value = false
      await fetchShipAdditionalChargesList()
    } else {
      toast.error(resp?.message || 'Failed to map additional charges.')
    }
  } catch (err) {
    toast.error(err.message || 'Error occurred while saving charges.')
  } finally {
    isSavingBulkCharges.value = false
  }
}

// Bulk Upload (CSV) Methods
const openBulkUploadModal = () => {
  uploadFile.value = null
  uploadResult.value = null
  isUploading.value = false
  isDragOver.value = false
  uploadMode.value = 'upsert'
  showBulkUploadModal.value = true
}

const closeBulkUploadModal = () => {
  showBulkUploadModal.value = false
  uploadFile.value = null
  uploadResult.value = null
  isUploading.value = false
}

const downloadSampleCsv = () => {
  const sampleRows = [
    ['Service Partner', 'Charge Type', 'Value', 'Status'],
    ['DHL', 'Fuel Surcharge', '40.00', 'active'],
    ['DHL', 'GST', '18.00', 'active'],
    ['DHL', 'Remote Area Charge', '2500', 'active'],
    ['UPS', 'Fuel Surcharge', '42.00', 'active'],
    ['UPS', 'GST', '18.00', 'active'],
    ['Express ITs', 'CFX', '4.75', 'active'],
    ['Ekart', 'Fuel Surcharge', '43.25', 'active'],
    ['Ekart', 'GST', '18.00', 'active']
  ];
  const csvContent = sampleRows.map(e => e.join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', 'shipment_additional_charges_sample.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

const onFileChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  validateAndSetFile(file)
}

const onDropFile = (e) => {
  isDragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (!file) return
  validateAndSetFile(file)
}

const validateAndSetFile = (file) => {
  const fileName = file.name.toLowerCase()
  if (!fileName.endsWith('.csv') && !fileName.endsWith('.txt')) {
    toast.error('Please upload a valid CSV file (.csv).')
    return
  }
  uploadFile.value = file
  uploadResult.value = null
}

const removeFile = () => {
  uploadFile.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const handleBulkUpload = async () => {
  if (!uploadFile.value) {
    toast.error('Please select a CSV file to upload.')
    return
  }

  isUploading.value = true
  uploadResult.value = null

  try {
    const fd = new FormData()
    fd.append('file', uploadFile.value)
    fd.append('mode', uploadMode.value)

    const resp = await bulkUploadShipAdditionalCharges(fd)
    if (resp?.status === 'success') {
      uploadResult.value = resp.data
      toast.success(resp.message || 'Bulk upload completed successfully!')
      await fetchShipAdditionalChargesList()
    } else {
      toast.error(resp?.message || 'Failed to upload charges.')
    }
  } catch (err) {
    toast.error(err.message || 'Error occurred during file upload.')
  } finally {
    isUploading.value = false
  }
}

// Helpers
const getMethodBadgeClass = (method) => {
  switch (method) {
    case 'percentage':
      return 'bg-primary-subtle text-primary border-primary';
    case 'per_kg':
      return 'bg-info-subtle text-info border-info';
    case 'flat':
      return 'bg-success-subtle text-success border-success';
    default:
      return 'bg-secondary-subtle text-secondary border-secondary';
  }
}

const getMethodUnit = (method) => {
  switch (method) {
    case 'percentage': return '%';
    case 'per_kg': return '/ kg';
    case 'flat': return 'Flat';
    default: return '';
  }
}

const selectedChargeType = computed(() => {
  return ShipChargesMasterList.value.find(item => item.id == formData.value.charge_type_id);
});

const calculationValueHint = computed(() => {
  if (!selectedChargeType.value) return null;
  const method = selectedChargeType.value.calculation_method;
  const basis = selectedChargeType.value.calculation_basis;
  const basisFormatted = basis ? ` of ${basis.replace(/_/g, ' ')}` : '';

  switch (method) {
    case 'percentage':
      return {
        method,
        unit: '%',
        placeholder: 'e.g. 18',
        message: `Value should be entered in percent${basisFormatted} (e.g. enter 18).`,
        badgeClass: 'bg-primary-subtle text-primary border-primary'
      };
    case 'per_kg':
      return {
        method,
        unit: '/ kg',
        placeholder: 'e.g. 50',
        message: 'Value should be entered per KG (e.g. enter 50).',
        badgeClass: 'bg-info-subtle text-info border-info'
      };
    case 'flat':
      return {
        method,
        unit: 'Flat',
        placeholder: 'Enter flat amount (e.g. 100)',
        message: 'Value should be entered as a flat charge amount (e.g. enter 100).',
        badgeClass: 'bg-success-subtle text-success border-success'
      };
    default:
      return null;
  }
});

const filterShipAdditionalChargesList = computed(() => {
  if (!Array.isArray(ShipAdditionalChargesList.value)) return [];

  const query = searchQuery.value.trim().toLowerCase();

  return ShipAdditionalChargesList.value.filter((item) => {
    const statusMatch = filterStatus.value === 'All' ||
      String(item.status || '').toLowerCase() === filterStatus.value.toLowerCase();

    const searchMatch = !query ||
      (item.service_partner_name || '').toLowerCase().includes(query) ||
      (item.charges_type || '').toLowerCase().includes(query) ||
      String(item.charges_value || '').toLowerCase().includes(query);

    return statusMatch && searchMatch;
  });
})

onMounted(() => {
  fetchServicePartners();
  fetchShipChargesMasterList();
  fetchShipAdditionalChargesList();
})
</script>

<template>
  <div class="container-fluid p-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary-subtle text-primary px-2 py-1 rounded-pill small fw-semibold">Settings</span>
          <i class="bi bi-chevron-right text-muted small"></i>
          <span class="text-secondary small fw-medium">Shipment Additional Charges</span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Shipment Additional Charges</h4>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold"
          @click="openBulkAddModal">
          <i class="bi bi-layers-fill"></i>
          <span>Bulk Map Charges</span>
        </button>
        <button class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold"
          @click="openBulkUploadModal">
          <i class="bi bi-file-earmark-arrow-up"></i>
          <span>Bulk Upload CSV</span>
        </button>
        <button class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Map Single Charge</span>
        </button>
      </div>
    </div>

    <!-- Filters & Table Section -->
    <div class="custom-card p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <!-- Search -->
        <div class="input-group" style="max-width: 380px;">
          <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input v-model="searchQuery" type="text" class="form-control bg-light border-start-0"
            placeholder="Search service partner or charge..." />
        </div>

        <!-- Status Filter Pills -->
        <div class="d-flex align-items-end gap-2">
          <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Status:</span>
          <button v-for="status in ['All', 'Active', 'Inactive']" :key="status" type="button"
            class="btn btn-sm rounded-pill px-3"
            :class="filterStatus === status ? 'btn-primary' : 'btn-light text-secondary'"
            @click="filterStatus = status">
            {{ status }}
          </button>
        </div>
      </div>

      <!-- Partner Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="border-0 rounded-start">Service Partner Name</th>
              <th class="border-0">Charges Type</th>
              <th class="border-0">Value</th>
              <th class="border-0">Calculation Method</th>
              <th class="border-0">Calculation Basis</th>
              <th class="border-0">Description</th>
              <th class="border-0 text-end rounded-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="isLoading">
              <tr v-for="i in 3" :key="'skel-' + i">
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-8 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-6 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-4 rounded-pill bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-2 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr v-if="filterShipAdditionalChargesList.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                  No matching records found
                </td>
              </tr>
              <tr v-for="item in filterShipAdditionalChargesList" :key="item.id">
                <td><strong class="text-dark">{{ item.service_partner_name }}</strong></td>
                <td>
                  <span class="fw-semibold">{{ item.charges_type }}</span>
                  <span v-if="item.code" class="badge bg-light text-secondary border font-monospace ms-1 small" style="font-size: 0.7rem;">
                    {{ item.code }}
                  </span>
                </td>
                <td>
                  <span class="fw-bold text-dark">{{ item.charges_value }}</span>
                  <span class="text-muted small ms-1">{{ getMethodUnit(item.calculation_method) }}</span>
                </td>
                <td>
                  <span class="badge" :class="getMethodBadgeClass(item.calculation_method)">
                    {{ item.calculation_method }}
                  </span>
                </td>
                <td>
                  <span class="small text-muted">{{ item.calculation_basis?.replace(/_/g, ' ') || '-' }}</span>
                </td>
                <td><span class="small text-secondary">{{ item.description }}</span></td>
                <td>
                  <div class="d-flex justify-content-end gap-1">
                    <button class="btn btn-sm btn-light text-primary p-2 rounded-2" @click="openEditModal(item)" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-light text-danger p-2 rounded-2" @click="deleteCharge(item)" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: ADD / EDIT SINGLE CHARGE -->
    <div v-if="showShipAddiChargesModal" class="modal-backdrop fade show"></div>
    <div v-if="showShipAddiChargesModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <form @submit.prevent="saveSingleCharge">
            <div class="modal-header border-bottom px-4 py-3">
              <h5 class="modal-title fw-bold text-dark">
                <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
                {{ isEditing ? 'Edit Shipment Charge' : 'Map Shipment Charge' }}
              </h5>
              <button type="button" class="btn-close" @click="showShipAddiChargesModal = false"></button>
            </div>
            <div class="modal-body px-4 py-3">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label small fw-semibold">Service Partner<span class="text-danger">*</span></label>
                  <select v-model="formData.service_partner_id" class="form-select" :disabled="isEditing" required>
                    <option value="">Select Service Partner</option>
                    <option v-for="partner in ServicePartnerList" :key="partner.id" :value="partner.id">
                      {{ partner.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-semibold">Shipment Charges Type<span class="text-danger">*</span></label>
                  <select v-model="formData.charge_type_id" class="form-select" :disabled="isEditing" required>
                    <option value="">Select Charges Type</option>
                    <option v-for="charge in ShipChargesMasterList" :key="charge.id" :value="charge.id">
                      {{ charge.name }} ({{ charge.calculation_method }})
                    </option>
                  </select>
                </div>
                <div class="col-md-12">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-semibold mb-0">
                      Charges Value <span class="text-danger">*</span>
                    </label>
                    <span v-if="selectedChargeType" class="badge" :class="calculationValueHint?.badgeClass">
                      Method: {{ selectedChargeType.calculation_method }}
                    </span>
                  </div>
                  <div class="input-group">
                    <input v-model="formData.value" type="number" min="0" step="0.01" class="form-control"
                      :placeholder="calculationValueHint ? calculationValueHint.placeholder : 'Enter charges value'"
                      required />
                    <span v-if="calculationValueHint?.unit" class="input-group-text bg-light fw-semibold">
                      {{ calculationValueHint.unit }}
                    </span>
                  </div>
                  <!-- Intimation Message -->
                  <div v-if="calculationValueHint" class="form-text mt-1 text-primary d-flex align-items-center gap-1">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>{{ calculationValueHint.message }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
              <button type="button" class="btn btn-light rounded-3 px-3" @click="showShipAddiChargesModal = false">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm">
                <i class="bi bi-check2 me-1"></i>
                {{ isEditing ? 'Update Charge' : 'Add Charge' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL: BULK MAP SHIPMENT ADDITIONAL CHARGES (UI) -->
    <div v-if="showBulkAddModal" class="modal-backdrop fade show"></div>
    <div v-if="showBulkAddModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <!-- Modal Header -->
          <div class="modal-header border-bottom px-4 py-3 bg-light">
            <div class="d-flex align-items-center gap-2">
              <div class="bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-layers-fill fs-5"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0">Bulk Map Shipment Additional Charges</h5>
                <span class="text-muted small">Configure and map multiple charge types for a courier service partner at once.</span>
              </div>
            </div>
            <button type="button" class="btn-close" @click="showBulkAddModal = false" :disabled="isSavingBulkCharges"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body px-4 py-3" style="max-height: 75vh; overflow-y: auto;">
            <!-- Top Controls Card -->
            <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-sm">
              <div class="row g-3 align-items-end">
                <!-- Service Partner Dropdown -->
                <div class="col-md-5">
                  <label class="form-label small fw-semibold">Target Service Partner <span class="text-danger">*</span></label>
                  <select v-model="bulkAddPartnerId" class="form-select" :disabled="bulkApplyToAllPartners">
                    <option value="" disabled>Select Service Partner</option>
                    <option v-for="p in ServicePartnerList" :key="p.id" :value="p.id">
                      {{ p.name }} ({{ p.service_code || 'Code: ' + p.id }})
                    </option>
                  </select>
                </div>

                <!-- Apply to All Partners Switch -->
                <div class="col-md-4">
                  <div class="form-check form-switch p-2 ps-5 bg-light rounded-3 border">
                    <input v-model="bulkApplyToAllPartners" class="form-check-input cursor-pointer" type="checkbox" id="applyAllPartnersSwitch" />
                    <label class="form-check-label small fw-semibold cursor-pointer" for="applyAllPartnersSwitch">
                      Apply to All Service Partners ({{ ServicePartnerList.length }})
                    </label>
                  </div>
                </div>

                <!-- Save Mode -->
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Mapping Mode</label>
                  <select v-model="bulkAddMode" class="form-select">
                    <option value="upsert">Upsert (Update & Insert)</option>
                    <option value="skip">Skip if already mapped</option>
                    <option value="overwrite">Overwrite all charges</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Master Charges Selection Section -->
            <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-sm">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                <div>
                  <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-cash-stack text-success"></i> Available Master Charges
                    <span class="badge bg-primary rounded-pill small ms-1">
                      {{ selectedBulkChargesCount }} of {{ bulkCharges.length }} Enabled
                    </span>
                  </h6>
                  <span class="text-muted small">Select charges to map and input their rate values.</span>
                </div>

                <!-- Quick Actions & Search -->
                <div class="d-flex align-items-center gap-2">
                  <div class="input-group input-group-sm" style="max-width: 200px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input v-model="bulkChargeSearch" type="text" class="form-control bg-light border-start-0" placeholder="Filter charges..." />
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap" @click="selectAllBulkCharges">
                    <i class="bi bi-check-all me-1"></i> Select All
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap" @click="deselectAllBulkCharges">
                    Clear
                  </button>
                </div>
              </div>

              <!-- Charges Grid Table -->
              <div class="table-responsive rounded-3 border">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th width="40" class="text-center">#</th>
                      <th>Charge Name & Code</th>
                      <th>Calculation Method</th>
                      <th>Calculation Basis</th>
                      <th width="220">Charge Value</th>
                      <th width="110" class="text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="filteredBulkCharges.length === 0">
                      <td colspan="6" class="text-center py-4 text-muted">
                        No charges found matching your search.
                      </td>
                    </tr>
                    <tr v-for="charge in filteredBulkCharges" :key="charge.charge_type_id"
                      :class="{ 'table-primary bg-opacity-10': charge.selected }">
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input cursor-pointer" v-model="charge.selected" />
                      </td>
                      <td>
                        <div class="fw-bold text-dark">{{ charge.name }}</div>
                        <span class="badge bg-light text-secondary border font-monospace small" style="font-size: 0.7rem;">
                          {{ charge.code }}
                        </span>
                        <span v-if="charge.currentValue !== undefined && !bulkApplyToAllPartners"
                          class="badge bg-warning-subtle text-warning border border-warning-subtle small ms-1" style="font-size: 0.7rem;">
                          Currently: {{ charge.currentValue }}{{ getMethodUnit(charge.calculation_method) }}
                        </span>
                      </td>
                      <td>
                        <span class="badge" :class="getMethodBadgeClass(charge.calculation_method)">
                          {{ charge.calculation_method }}
                        </span>
                      </td>
                      <td>
                        <span class="small text-muted">{{ charge.calculation_basis?.replace(/_/g, ' ') || '-' }}</span>
                      </td>
                      <td>
                        <div class="input-group input-group-sm">
                          <input v-model="charge.value" type="number" step="0.01" min="0" class="form-control"
                            :placeholder="'e.g. ' + (charge.calculation_method === 'percentage' ? '18' : '100')"
                            :disabled="!charge.selected"
                            @focus="charge.selected = true" />
                          <span class="input-group-text bg-light fw-semibold small">
                            {{ getMethodUnit(charge.calculation_method) }}
                          </span>
                        </div>
                      </td>
                      <td class="text-center">
                        <select v-model="charge.status" class="form-select form-select-sm" :disabled="!charge.selected" style="width: 100px;">
                          <option value="active">Active</option>
                          <option value="inactive">Inactive</option>
                        </select>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <div class="small text-muted">
              <span class="fw-semibold text-primary">{{ selectedBulkChargesCount }} charges</span> ready to map to
              <strong>{{ bulkApplyToAllPartners ? 'All ' + ServicePartnerList.length + ' Partners' : (ServicePartnerList.find(p => p.id == bulkAddPartnerId)?.name || 'Selected Partner') }}</strong>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-light rounded-3 px-3 border" @click="showBulkAddModal = false" :disabled="isSavingBulkCharges">
                Cancel
              </button>
              <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm fw-semibold d-flex align-items-center gap-2"
                :disabled="selectedBulkChargesCount === 0 || (!bulkApplyToAllPartners && !bulkAddPartnerId) || isSavingBulkCharges"
                @click="handleSaveBulkCharges">
                <span v-if="isSavingBulkCharges" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-else class="bi bi-check2-circle"></i>
                <span>{{ isSavingBulkCharges ? 'Saving Charges...' : 'Save ' + selectedBulkChargesCount + ' Charges' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: BULK UPLOAD SHIPMENT ADDITIONAL CHARGES (CSV) -->
    <div v-if="showBulkUploadModal" class="modal-backdrop fade show"></div>
    <div v-if="showBulkUploadModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <!-- Modal Header -->
          <div class="modal-header border-bottom px-4 py-3 bg-light">
            <div class="d-flex align-items-center gap-2">
              <div class="bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-file-earmark-arrow-up fs-5"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0">Bulk Upload Additional Charges (CSV)</h5>
                <span class="text-muted small">Upload CSV spreadsheet with courier additional charges.</span>
              </div>
            </div>
            <button type="button" class="btn-close" @click="closeBulkUploadModal" :disabled="isUploading"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body px-4 py-4" style="max-height: 75vh; overflow-y: auto;">
            <!-- Format Guide & Sample Download -->
            <div class="card border-0 bg-primary-subtle text-primary rounded-3 p-3 mb-4">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                  <div class="fw-bold small d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i> Supported Formats: Row-by-Row or Matrix CSV
                  </div>
                  <div class="small text-muted mt-1" style="color: #495057 !important;">
                    <strong>Row-by-Row:</strong> <code>Service Partner, Charge Type, Value, Status</code><br />
                    <strong>Matrix:</strong> <code>Service Partner, Fuel Surcharge, GST, Remote Area Charge...</code>
                  </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm text-nowrap rounded-pill px-3 fw-semibold bg-white"
                  @click="downloadSampleCsv">
                  <i class="bi bi-download me-1"></i> Download Sample CSV
                </button>
              </div>
            </div>

            <!-- Upload Options -->
            <div class="mb-4">
              <label class="form-label small fw-semibold">Import Mode</label>
              <select v-model="uploadMode" class="form-select">
                <option value="upsert">Upsert (Update matching charges and create new)</option>
                <option value="skip">Skip if already mapped for service partner</option>
              </select>
            </div>

            <!-- Drag & Drop Zone -->
            <div class="mb-4">
              <label class="form-label small fw-semibold">Select CSV File <span class="text-danger">*</span></label>
              <div v-if="!uploadFile"
                class="upload-dropzone p-4 rounded-3 text-center cursor-pointer transition-all"
                :class="{ 'border-primary bg-primary-subtle': isDragOver, 'border-secondary-subtle bg-light': !isDragOver }"
                @dragover.prevent="isDragOver = true"
                @dragleave.prevent="isDragOver = false"
                @drop.prevent="onDropFile"
                @click="fileInputRef?.click()">
                <input ref="fileInputRef" type="file" accept=".csv,text/csv" class="d-none" @change="onFileChange" />
                <i class="bi bi-cloud-arrow-up fs-1 text-primary mb-2 d-block"></i>
                <span class="fw-semibold text-dark d-block">Drag & drop your CSV file here, or <span class="text-primary text-decoration-underline">browse</span></span>
                <span class="text-muted small">Supports standard comma-separated .csv files</span>
              </div>

              <!-- Selected File Card -->
              <div v-else class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <div class="fs-3 text-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark small text-truncate" style="max-width: 320px;">
                      {{ uploadFile.name }}
                    </div>
                    <div class="text-muted small">
                      {{ (uploadFile.size / 1024).toFixed(1) }} KB
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-2" :disabled="isUploading" @click="removeFile">
                  <i class="bi bi-trash me-1"></i> Remove
                </button>
              </div>
            </div>

            <!-- Upload Results Card -->
            <div v-if="uploadResult" class="mb-2">
              <div class="alert alert-success border-0 shadow-xs rounded-3 p-3 mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div>
                  <div class="fw-bold">Bulk Upload Processed Successfully!</div>
                  <div class="small">The additional charges have been processed and applied.</div>
                </div>
              </div>

              <!-- Metrics -->
              <div class="row g-2 text-center mb-3">
                <div class="col-3">
                  <div class="p-2 bg-light rounded-3 border">
                    <div class="fs-5 fw-bold text-dark">{{ uploadResult.total_rows }}</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">TOTAL ROWS</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="p-2 bg-success-subtle rounded-3 border border-success-subtle">
                    <div class="fs-5 fw-bold text-success">{{ uploadResult.inserted_count }}</div>
                    <div class="small text-success" style="font-size: 0.72rem;">CREATED</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="p-2 bg-primary-subtle rounded-3 border border-primary-subtle">
                    <div class="fs-5 fw-bold text-primary">{{ uploadResult.updated_count }}</div>
                    <div class="small text-primary" style="font-size: 0.72rem;">UPDATED</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="p-2 bg-secondary-subtle rounded-3 border border-secondary-subtle">
                    <div class="fs-5 fw-bold text-secondary">{{ uploadResult.skipped_count }}</div>
                    <div class="small text-secondary" style="font-size: 0.72rem;">SKIPPED</div>
                  </div>
                </div>
              </div>

              <!-- Unmatched Rows Details -->
              <div v-if="uploadResult.unmatched_rows && uploadResult.unmatched_rows.length > 0"
                class="p-3 bg-warning-subtle rounded-3 border border-warning">
                <div class="fw-semibold text-warning-emphasis small mb-1 d-flex align-items-center gap-1">
                  <i class="bi bi-exclamation-triangle-fill"></i> Unmatched / Skipped Rows ({{ uploadResult.unmatched_count }}):
                </div>
                <div class="small text-dark font-monospace" style="max-height: 120px; overflow-y: auto;">
                  <div v-for="(msg, i) in uploadResult.unmatched_rows" :key="i" class="text-danger small py-0.5">
                    &bull; {{ msg }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <button v-if="!uploadResult" type="button" class="btn btn-light rounded-3 px-3" :disabled="isUploading"
              @click="closeBulkUploadModal">
              Cancel
            </button>
            <button v-else type="button" class="btn btn-secondary rounded-3 px-3"
              @click="uploadResult = null; uploadFile = null">
              Upload Another File
            </button>

            <button v-if="!uploadResult" type="button" class="btn btn-primary rounded-3 px-4 shadow-sm d-flex align-items-center gap-2"
              :disabled="!uploadFile || isUploading"
              @click="handleBulkUpload">
              <span v-if="isUploading" class="spinner-border spinner-border-sm" role="status"></span>
              <i v-else class="bi bi-cloud-arrow-up"></i>
              <span>{{ isUploading ? 'Processing File...' : 'Upload & Process' }}</span>
            </button>
            <button v-else type="button" class="btn btn-primary rounded-3 px-4 shadow-sm"
              @click="closeBulkUploadModal">
              Done
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.upload-dropzone {
  border: 2px dashed #cbd5e1;
  background-color: #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
}
.upload-dropzone:hover {
  border-color: #3b82f6;
  background-color: #eff6ff;
}
.cursor-pointer {
  cursor: pointer;
}
</style>
