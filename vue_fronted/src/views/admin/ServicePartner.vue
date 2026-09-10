<script setup>
import { ref, computed, onMounted } from 'vue'
import { getAllServicePartners } from '@/services/admin/servicePartner';
import { addNewServicePartner } from '@/services/admin/servicePartner';
import { useToast } from "vue-toastification";
const toast = useToast();
const servicePartnersList = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const searchQuery = ref('')
const filterStatus = ref('All')
const alertMessage = ref(null)

// Modal states
const showPartnerModal = ref(false)
const isEditing = ref(false)
const showCountryMappingModal = ref(false)
const selectedPartner = ref(null)

const partnerForm = ref({
  id: '',
  name: '',
  service_code: '',
  status: 'Active',
})

onMounted(async () => {
  isLoading.value = true;
  try {
    const resp = await getAllServicePartners(10, 1);
    if (resp.status == 'success') {
      servicePartnersList.value = resp.data;
    }
  } finally {
    isLoading.value = false;
  }
})

const filteredservicePartnersList = computed(() => {
  return servicePartnersList.value.filter((z) => {
    const matchesSearch =
      z.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      z.service_code.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesStatus = filterStatus.value === 'All' || z.status === filterStatus.value
    return matchesSearch && matchesStatus
  })
})



const showAlert = (msg) => {
  alertMessage.value = msg
  setTimeout(() => {

    alertMessage.value = null
  }, 3500)
}

const openAddModal = () => {
  isEditing.value = false
  partnerForm.value = {
    id: servicePartnersList.value.length + 1,
    service_code: '',
    name: '',
    status: 'Active'
  }
  showPartnerModal.value = true
}

const openEditModal = (partner) => {
  isEditing.value = true
  partnerForm.value = {
    id: servicePartnersList.id,
    service_code: servicePartnersList.service_code,
    name: servicePartnersList.name,
    status: servicePartnersList.status
  }
  showPartnerModal.value = true
}

const savePartner = async () => {
  if (!partnerForm.value.name || !partnerForm.value.service_code) {
    toast.error('Please enter service partner name and service partner code.')
    return
  }

  showPartnerModal.value = false
  isSaving.value = true

  try {
    if (isEditing.value) {
      const idx = servicePartnersList.value.findIndex((z) => z.id === partnerForm.value.id)
      if (idx !== -1) {
        servicePartnersList.value[idx] = { ...servicePartnersList.value[idx], ...partnerForm.value }
        showAlert(`Partner ${partnerForm.value.name} updated successfully.`)
      }
    } else {
      const resp = await addNewServicePartner(partnerForm.value);
      if (resp.status == 'success') {
        servicePartnersList.value.unshift({ ...partnerForm.value, id: resp.data?.id || servicePartnersList.value.length + 1 })
        toast.success(resp.message);
      } else {
        toast.error(resp.message);
      }
    }
  } finally {
    isSaving.value = false
  }
}


const deletePartner = (partner) => {
  if (confirm(`Are you sure you want to delete partner "${servicePartnersList.name}"?`)) {
    partners.value = partners.value.filter((z) => z.id !== servicePartnersList.id)
    showAlert(`Partner "${servicePartnersList.name}" removed successfully.`)
  }
}

const filteredAvailableCountries = computed(() => {
  if (!countrySearch.value) return availableCountries
  return availableCountries.filter((c) =>
    c.toLowerCase().includes(countrySearch.value.toLowerCase())
  )
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
          <span class="text-secondary small fw-medium">Service Partner</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add New Service Partner</span>
        </button>
      </div>
    </div>

    <!-- Alert Notification -->
    <transition name="fade">
      <div v-if="alertMessage" class="alert alert-success d-flex align-items-center mb-4 shadow-sm rounded-3"
        role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div>{{ alertMessage }}</div>
      </div>
    </transition>

    <!-- Filters & Table Section -->
    <div class="custom-card p-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <!-- Search -->
        <div class="input-group" style="max-width: 380px;">
          <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input v-model="searchQuery" type="text" class="form-control bg-light border-start-0"
            placeholder="Search by partner name..." />
        </div>

        <!-- Status Filter Pills -->
        <div class="d-flex align-items-center gap-2">
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
              <th class="border-0 rounded-start">Name</th>
              <th class="border-0">Service Code</th>
              <th class="border-0">Status</th>
              <th class="border-0 text-end rounded-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="isLoading">
              <tr v-for="i in 5" :key="'skel-' + i">
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
                <td class="text-end">
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr v-if="isSaving && !isEditing">
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
                <td class="text-end">
                  <div class="placeholder-glow"><span
                      class="placeholder col-3 rounded bg-secondary bg-opacity-25"></span></div>
                </td>
              </tr>
              <tr v-if="filteredservicePartnersList.length === 0 && !isSaving">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                  No matching records found
                </td>
              </tr>
              <tr v-for="servicePartnersList in filteredservicePartnersList" :key="servicePartnersList.id">
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <div>
                      <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        {{ servicePartnersList.name }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 280px;">
                    {{ servicePartnersList.service_code }}
                  </div>
                </td>
                <td>
                  <button type="button" class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                    :class="servicePartnersList.status === 'Active' ? 'bg-success text-white' : 'bg-secondary text-white'"
                    title="Click to toggle status">
                    {{ servicePartnersList.status }}
                  </button>
                </td>
                <td class="text-end">
                  <div class="d-flex align-items-center justify-content-end gap-1">
                    <button type="button" class="btn btn-sm btn-light text-secondary p-2 rounded-2"
                      @click="openEditModal(servicePartnersList)" title="Edit Partner">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light text-danger p-2 rounded-2"
                      @click="deletePartner(servicePartnersList)" title="Delete Partner">
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
    <div v-if="showPartnerModal" class="modal-backdrop fade show"></div>
    <div v-if="showPartnerModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
              {{ isEditing ? 'Edit Service Partner' : 'Create New Service Partner' }}
            </h5>
            <button type="button" class="btn-close" @click="showPartnerModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Service Partner Name <span
                    class="text-danger">*</span></label>
                <input v-model="partnerForm.name" type="text" class="form-control"
                  placeholder="Enter service partner name" />
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Service Code<span class="text-danger">*</span></label>
                <input v-model="partnerForm.service_code" type="text" class="form-control"
                  placeholder="Enter partner service code" />
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showPartnerModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="savePartner">
              <i class="bi bi-check2 me-1"></i>
              {{ isEditing ? 'Save Changes' : 'Create Partner' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Country Mapping -->
    <div v-if="showCountryMappingModal && selectedPartner" class="modal-backdrop fade show"></div>
    <div v-if="showCountryMappingModal && selectedPartner" class="modal fade show d-block" tabindex="-1" role="dialog"
      aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-globe me-2 text-primary"></i> Map Countries to {{ selectedservicePartnersList.name }}
              </h5>
              <span class="text-muted small">Partner Code: <strong class="text-primary">{{
                selectedservicePartnersList.code
                  }}</strong>
                &bull; Currently Mapped: {{ selectedservicePartnersList.countries.length }} countries</span>
            </div>
            <button type="button" class="btn-close" @click="showCountryMappingModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="mb-3">
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input v-model="countrySearch" type="text" class="form-control bg-light border-start-0"
                  placeholder="Filter available countries..." />
              </div>
            </div>

            <div class="p-3 bg-light rounded-3 border" style="max-height: 320px; overflow-y: auto;">
              <div class="row g-2">
                <div v-for="country in filteredAvailableCountries" :key="country" class="col-6 col-md-4">
                  <div
                    class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-white cursor-pointer transition-all"
                    :class="{ 'border-primary bg-primary-subtle text-primary fw-semibold': selectedservicePartnersList.countries.includes(country) }"
                    @click="toggleCountryForSelectedPartner(country)">
                    <input type="checkbox" class="form-check-input mt-0"
                      :checked="selectedservicePartnersList.countries.includes(country)" />
                    <span class="small text-truncate">{{ country }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <span class="small text-muted">{{ selectedservicePartnersList.countries.length }} countries selected</span>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm"
              @click="showCountryMappingModal = false">
              Done
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
