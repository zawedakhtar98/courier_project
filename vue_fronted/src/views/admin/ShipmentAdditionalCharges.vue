<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from "vue-toastification";
import { getAllServicePartners } from '@/services/admin/servicePartner';
import { getShipChargesList } from '@/services/admin/shipmentChargesMasterService';
import { addNewShipAdditionalCharge, getShipAdditionalChargesList, deleteShipAdditionalCharge } from '@/services/admin/shipmentAddChargesService';
const toast = useToast();
const isEditing = ref(false);
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

const fetchServicePartners = async () => {
  try {
    const resp = await getAllServicePartners(10, 1);
    if (resp.status == "success") {
      ServicePartnerList.value = resp.data;
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
      ShipChargesMasterList.value = resp.data;
    }
  }
  catch (err) {
    toast.error(err.message);
  }
}

const addShipAdditionalCharge = async () => {
  try {
    const resp = await addNewShipAdditionalCharge(formData.value);
    if (resp.status == "success") {
      toast.success(resp.message);
      showShipAddiChargesModal.value = false;
      formData.value = {
        service_partner_id: '',
        charge_type_id: '',
        value: '',
      };
      fetchShipAdditionalChargesList();
    } else {
      toast.error(resp.message || 'Failed to map additional charge');
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
        message: 'Value should be entered as a flat charge amount (e.g. enter 100). ',
        badgeClass: 'bg-success-subtle text-success border-success'
      };
    default:
      return null;
  }
});

const calculationValue = (item) => {
  if (!item) return '';
  const method = item.calculation_method || item.charge_type_calculation_method || item.shipment_charge_type?.calculation_method;
  const val = item.value ?? item.charge_type_value;
  if (val === undefined || val === null || val === '') return '-';

  switch (method) {
    case 'percentage':
      return `${val}%`;
    case 'per_kg':
      return `${val} / kg`;
    case 'flat':
      return `${val} (Flat)`;
    default:
      return `${val}`;
  }
};

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

onMounted(() => {
  fetchServicePartners();
  fetchShipChargesMasterList();
  fetchShipAdditionalChargesList();
})

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


const openAddModal = () => {
  isEditing.value = false;
  formData.value = {
    service_partner_id: '',
    charge_type_id: '',
    value: '',
  };
  showShipAddiChargesModal.value = true;
}
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
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Map Charges</span>
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
            placeholder="Search..." />
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
              <!-- <th class="border-0">Status</th> -->
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
              </tr>
            </template>
            <template v-else>
              <tr v-if="filterShipAdditionalChargesList.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                  No matching records found
                </td>
              </tr>
              <tr v-for="item in filterShipAdditionalChargesList" :key="item.id">
                <td>{{ item.service_partner_name }}</td>
                <td>{{ item.charges_type }}</td>
                <td>{{ item.charges_value }}</td>
                <td>{{ item.calculation_method }}</td>
                <td>{{ item.calculation_basis }}</td>
                <td>{{ item.description }}</td>
                <!-- <td>
                  <button class="btn btn-outline-secondary border-0 p-1 me-2"
                    :class="{ 'text-primary': item.status === 'active', 'text-secondary': item.status === 'inactive' }">
                    <i class="bi"
                      :class="item.status === 'active' ? 'bi-toggle-on text-primary' : 'bi-toggle-off text-secondary'"></i>
                    {{ item.status }}
                  </button>
                </td> -->
                <td>
                  <div class="d-flex justify-content-end">
                    <button class="btn btn-outline-danger border-0 p-1" @click="deleteCharge(item)" title="Delete">
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

    <!-- Modal: Add / Edit Partner -->
    <div v-if="showShipAddiChargesModal" class="modal-backdrop fade show"></div>
    <div v-if="showShipAddiChargesModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <form @submit.prevent="addShipAdditionalCharge">
            <div class="modal-header border-bottom px-4 py-3">
              <h5 class="modal-title fw-bold text-dark">
                <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
                {{ isEditing ? 'Edit Shipment Charges' : 'Map Shipment Charges' }}
              </h5>
              <button type="button" class="btn-close" @click="showShipAddiChargesModal = false"></button>
            </div>
            <div class="modal-body px-4 py-3">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label small fw-semibold">Service Partner<span class="text-danger">*</span></label>
                  <select v-model="formData.service_partner_id" class="form-select" required>
                    <option value="">Select Service Partner</option>
                    <option v-for="partner in ServicePartnerList" :key="partner.id" :value="partner.id">
                      {{ partner.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-semibold">Shipment Charges Type<span
                      class="text-danger">*</span></label>
                  <select v-model="formData.charge_type_id" class="form-select" required>
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
                    <input v-model="formData.value" type="number" min="0.5" step="0.01" class="form-control"
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
                {{ isEditing ? 'Update' : 'Add' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
