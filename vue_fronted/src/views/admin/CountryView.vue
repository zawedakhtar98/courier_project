<script setup>
import { ref, computed, onMounted } from 'vue'
import { getAllCountries } from '@/services/admin/CountryService'
import { addCountry } from '@/services/admin/CountryService'
import { useToast } from 'vue-toastification'

const countries = ref([])
const toast = useToast();

const searchQuery = ref('')
const selectedStatusFilter = ref('All')
const alertMessage = ref(null)

const showCountryModal = ref(false)
const isEditing = ref(false)
const showAssignZoneModal = ref(false)
const countryToAssign = ref(null)
const targetZoneCode = ref('')

const formCountry = ref({
  name: '',
  code: '',
  status: 'Active',
})

const filteredCountries = computed(() => {
  return countries.value.filter((c) => {
    const matchesSearch =
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.code.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesStatus = selectedStatusFilter.value === 'All' || c.status === selectedStatusFilter.value
    return matchesSearch && matchesStatus
  })
})

onMounted(async () => {
  const response = await getAllCountries();
  countries.value = response.data;
})

const showAlert = (msg) => {
  alertMessage.value = msg
  setTimeout(() => {
    alertMessage.value = null
  }, 3500)
}

const openAddModal = () => {
  isEditing.value = false
  formCountry.value = {
    name: '',
    code: '',
    status: 'Active',
  }
  showCountryModal.value = true
}

const openEditModal = (country) => {
  isEditing.value = true
  formCountry.value = { ...country }
  showCountryModal.value = true
}


const saveCountry = async () => {
  if (!formCountry.value.name) {
    toast.error('Please enter Country Name.');
    return
  }

  if (isEditing.value) {
    const idx = countries.value.findIndex((c) => c.id === formCountry.value.id)
    if (idx !== -1) {
      countries.value[idx] = { ...formCountry.value }
      showAlert(`Country ${formCountry.value.name} updated successfully.`)
    }
  } else {
    countries.value.unshift({ ...formCountry.value })

    const resp = await addCountry(formCountry.value);

    if (resp.status === 'success') {
      toast.success(resp.message);
    } else {
      toast.error(resp.message);
    }
  }
  showCountryModal.value = false
}

const toggleCountryStatus = (country) => {
  if (country.status === 'Active') {
    country.status = 'Inactive'
  } else if (country.status === 'Inactive') {
    country.status = 'Active'
  } else {
    country.status = 'Active'
  }
  showAlert(`${country.name} status updated to ${country.status}.`)
}

const deleteCountry = (country) => {
  if (confirm(`Remove ${country.name} from country directory?`)) {
    countries.value = countries.value.filter((c) => c.id !== country.id)
    showAlert(`${country.name} removed.`)
  }
}

const getStatusBadge = (status) => {
  switch (status) {
    case 'Active':
      return 'bg-success text-white'
    case 'Inactive':
      return 'bg-danger text-white'
    default:
      return 'bg-secondary text-white'
  }
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
          <span class="text-secondary small fw-medium">Country Master</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add Country</span>
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

    <!-- Filters & Table -->
    <div class="custom-card p-4">
      <div class="row g-3 mb-4 justify-content-end">
        <!-- Search -->
        <div class="col-12 col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input v-model="searchQuery" type="text" class="form-control bg-light border-start-0"
              placeholder="Search by country and country code " />
          </div>
        </div>

        <!-- Status Filter -->
        <div class="col-6 col-md-3">
          <select v-model="selectedStatusFilter" class="form-select bg-light">
            <option value="All">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Countries Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="border-0 rounded-start">Country Name & Code</th>
              <th class="border-0">Status</th>
              <th class="border-0 text-end rounded-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredCountries.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-geo-alt fs-1 d-block mb-2 text-secondary"></i>
                No matching countries found.
              </td>
            </tr>
            <tr v-for="country in filteredCountries" :key="country.id">
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                      {{ country.name }}
                      <span class="badge bg-primary-subtle text-primary border font-monospace small px-2 py-0">
                        {{ country.code }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <button type="button" class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                  :class="getStatusBadge(country.status)" @click="toggleCountryStatus(country)"
                  title="Click to cycle status">
                  {{ country.status }}
                </button>
              </td>
              <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                  <button type="button" class="btn btn-sm btn-light text-secondary p-2 rounded-2"
                    @click="openEditModal(country)" title="Edit Country">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-light text-danger p-2 rounded-2"
                    @click="deleteCountry(country)" title="Delete Country">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Add / Edit Country -->
    <div v-if="showCountryModal" class="modal-backdrop fade show"></div>
    <div v-if="showCountryModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
              {{ isEditing ? 'Edit Country Details' : 'Add New Country' }}
            </h5>
            <button type="button" class="btn-close" @click="showCountryModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Country Name <span class="text-danger">*</span></label>
                <input v-model="formCountry.name" type="text" class="form-control"
                  placeholder="e.g. Singapore, Germany" />
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Country Code <span class="text-danger">*</span></label>
                <input v-model="formCountry.code" type="text" class="form-control" placeholder="Country Code" />
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showCountryModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="saveCountry">
              {{ isEditing ? 'Save Changes' : 'Add Country' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Quick Zone Reassignment -->
    <div v-if="showAssignZoneModal && countryToAssign" class="modal-backdrop fade show"></div>
    <div v-if="showAssignZoneModal && countryToAssign" class="modal fade show d-block" tabindex="-1" role="dialog"
      aria-modal="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i class="bi bi-arrow-left-right text-primary me-2"></i> Reassign Shipping Zone
            </h5>
            <button type="button" class="btn-close" @click="showAssignZoneModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-3">
              <span class="fs-2">{{ countryToAssign.flagEmoji }}</span>
              <div>
                <h6 class="fw-bold mb-0 text-dark">{{ countryToAssign.name }}</h6>
                <span class="text-muted small">Current Zone: <strong>{{ countryToAssign.zoneName }}</strong></span>
              </div>
            </div>

            <label class="form-label small fw-semibold">Select New Zone</label>
            <select v-model="targetZoneCode" class="form-select">
              <option v-for="z in availableZones" :key="z.code" :value="z.code">
                {{ z.code }} - {{ z.name }}
              </option>
            </select>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showAssignZoneModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="applyZoneReassignment">
              Confirm Assignment
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

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
