<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useToast } from "vue-toastification";
import { addNewShipCharges, getShipChargesList, deleteShipCharge } from '@/services/admin/shipmentChargesMasterService';
const toast = useToast();
const isEditing = ref(false);
const filterStatus = ref('All')
const showShipmentChargesMasterModal = ref(false);
const shipChargesList = ref([]);
const searchQuery = ref('');
const openAddModal = () => {
  isEditing.value = false;
  showShipmentChargesMasterModal.value = true;
}

const isLoading = ref(true);

const formData = reactive({
  name: '',
  code: '',
  calculation_method: '',
  calculation_basis: '',
  description: '',
  status: 'active',
})

const isValidated = computed(() => {
  if (!formData.name || !formData.code || !formData.calculation_method || !formData.calculation_basis || !formData.description || !formData.status) {
    return false;
  }
  return true;
})

const submitFormData = async () => {
  if (!isValidated.value) {
    toast.error("Please fill all the fields");
    return;
  }
  const resp = await addNewShipCharges(formData);
  if (resp.status === 'success') {
    toast.success('Shipment Charges Master created successfully!');
    showShipmentChargesMasterModal.value = false;
    formData.name = '';
    formData.code = '';
    formData.calculation_method = '';
    formData.calculation_basis = '';
    formData.description = '';
    formData.status = 'active';
    const resp = await getShipChargesList();
    shipChargesList.value = resp?.data || [];
  } else {
    toast.error(resp.message);
  }
}

onMounted(async () => {
  isLoading.value = true;
  const resp = await getShipChargesList();
  shipChargesList.value = resp?.data || [];
  isLoading.value = false;
})

const deleteShipChargeMaster = async (shipCharge) => {
  const name = shipCharge.name
  const id = shipCharge.id;
  const confirmMessage = name
    ? `Are you sure you want to delete shipment charge "${name}"?`
    : 'Are you sure you want to delete this shipment charge?';

  if (!confirm(confirmMessage)) {
    return;
  }

  const resp = await deleteShipCharge(id);
  if (resp?.status === 'success') {
    toast.success('Shipment Charges Master deleted successfully!');
    const respList = await getShipChargesList();
    shipChargesList.value = respList?.data || [];
  } else {
    toast.error(resp?.message || 'Failed to delete shipment charge');
  }
}

const shipChargesListFiltered = computed(() => {
  if (!Array.isArray(shipChargesList.value)) return [];
  return shipChargesList.value.filter((item) => {
    const statusMatch = filterStatus.value === 'All' ||
      String(item.status || '').toLowerCase() === filterStatus.value.toLowerCase();
    const searchMatch = (item.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (item.code || '').toLowerCase().includes(searchQuery.value.toLowerCase());
    return statusMatch && searchMatch;
  });
})

const formatSentenceCase = (text) => {
  if (!text) return '';
  const str = String(text).replace(/_/g, ' ').toLowerCase();
  return str.charAt(0).toUpperCase() + str.slice(1);
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
          <span class="text-secondary small fw-medium">Shipment Charges Master</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add New</span>
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
              <th class="border-0">S.L No</th>
              <th class="border-0 rounded-start">Name</th>
              <th class="border-0">Code</th>
              <th class="border-0">Calculation Method</th>
              <th class="border-0">Calculation Basis</th>
              <th class="border-0">Description</th>
              <th class="border-0">Status</th>
              <th class="border-0 text-end rounded-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="isLoading">
              <tr v-for="i in 3" :key="'skel-' + i">
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-8 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td>
                  <div class="placeholder-glow"><span
                      class="placeholder col-6 rounded-pill bg-secondary bg-opacity-25"></span></div>
                </td>
                <td class="text-start">
                  <div class="placeholder-glow"><span
                      class="placeholder col-6 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td class="text-start">
                  <div class="placeholder-glow"><span
                      class="placeholder col-5 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td class="text-start">
                  <div class="placeholder-glow"><span
                      class="placeholder col-9 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td class="text-start">
                  <div class="placeholder-glow"><span
                      class="placeholder col-5 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
                <td class="text-center">
                  <div class="placeholder-glow"><span
                      class="placeholder col-6 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr v-if="shipChargesListFiltered.length === 0">
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                  No matching records found
                </td>
              </tr>
              <tr v-for="(shipCharge, index) in shipChargesListFiltered" :key="shipCharge.id">
                <td>{{ index + 1 }}</td>
                <td>{{ shipCharge.name }}</td>
                <td>{{ shipCharge.code }}</td>
                <td>{{ formatSentenceCase(shipCharge.calculation_method) }}</td>
                <td>{{ formatSentenceCase(shipCharge.calculation_basis) }}</td>
                <td>{{ shipCharge.description }}</td>
                <td>
                  <button type="button" class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                    :class="shipCharge.status === 'active' ? 'bg-success text-white' : 'bg-secondary text-white'">
                    {{ shipCharge.status }}
                  </button>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger" @click="deleteShipChargeMaster(shipCharge)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Add / Edit Partner -->
    <div v-if="showShipmentChargesMasterModal" class="modal-backdrop fade show"></div>
    <div v-if="showShipmentChargesMasterModal" class="modal fade show d-block" tabindex="-1" role="dialog"
      aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
              {{ isEditing ? 'Edit Shipment Charges' : 'Add New Shipment Charges' }}
            </h5>
            <button type="button" class="btn-close" @click="showShipmentChargesMasterModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" v-model="formData.name" class="form-control" placeholder="Enter name" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Code<span class="text-danger">*</span></label>
                <input type="text" v-model="formData.code" class="form-control" placeholder="Enter code" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Calculation Method<span class="text-danger">*</span></label>
                <select name="" v-model="formData.calculation_method" id="" class="form-select">
                  <option value="">Select Method</option>
                  <option value="flat">Flat</option>
                  <option value="per_kg">Per KG</option>
                  <option value="percentage">Percentage</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Calculation Based On<span
                    class="text-danger">*</span></label>
                <select name="" id="" v-model="formData.calculation_basis" class="form-select">
                  <option value="">Select Method</option>
                  <option value="weight">Weight</option>
                  <option value="freight">Freight</option>
                  <option value="shipment_value">Shipment Value</option>
                  <option value="subtotal">Subtotal</option>
                </select>
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Description<span class="text-danger">*</span></label>
                <textarea v-model="formData.description" class="form-control"
                  placeholder="Enter description"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showShipmentChargesMasterModal = false">
              Cancel
            </button>
            <button type="button" :disabled="isValidated ? false : true"
              class="btn btn-primary rounded-3 px-4 shadow-sm" @click="submitFormData">
              <i class="bi bi-check2 me-1"></i>
              {{ isEditing ? 'Update' : 'Create' }}
            </button>
          </div>
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
