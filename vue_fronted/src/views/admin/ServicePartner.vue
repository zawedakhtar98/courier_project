<script setup>
import { ref, computed } from 'vue'

const zones = ref([
  {
    id: 'ZN-01',
    code: 'ZONE-NA',
    name: 'DHL',
    description: 'Direct air freight routes covering US, Canada & Mexico metro hubs.',
    transitDays: '1-3 Days',
    fuelSurcharge: 12.5,
    rateMultiplier: 1.0,
    status: 'Active',
    countries: ['United States', 'Canada', 'Mexico'],
  },
  {
    id: 'ZN-02',
    code: 'ZONE-EU',
    name: 'Express ITs',
    description: 'Schengen zone expedited courier corridor via Frankfurt Hub.',
    transitDays: '2-4 Days',
    fuelSurcharge: 14.0,
    rateMultiplier: 1.15,
    status: 'Active',
    countries: ['United Kingdom', 'Germany', 'France', 'Netherlands', 'Italy', 'Spain', 'Switzerland'],
  },
  {
    id: 'ZN-03',
    code: 'ZONE-ME',
    name: 'UPS',
    description: 'Gulf cooperation council cross-border customs cleared line.',
    transitDays: '2-3 Days',
    fuelSurcharge: 11.0,
    rateMultiplier: 1.05,
    status: 'Active',
    countries: ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Oman', 'Bahrain'],
  },
])

const searchQuery = ref('')
const filterStatus = ref('All')
const alertMessage = ref(null)

// Modal states
const showZoneModal = ref(false)
const isEditing = ref(false)
const showCountryMappingModal = ref(false)
const selectedZone = ref(null)

const availableCountries = [
  'United States', 'Canada', 'Mexico', 'United Kingdom', 'Germany', 'France',
  'Netherlands', 'Italy', 'Spain', 'Switzerland', 'United Arab Emirates',
  'Saudi Arabia', 'Qatar', 'Kuwait', 'Oman', 'Bahrain', 'Singapore',
  'Japan', 'South Korea', 'Australia', 'Malaysia', 'Hong Kong',
  'India', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Brazil', 'Argentina',
  'Chile', 'Colombia', 'Peru', 'New Zealand', 'South Africa', 'Sweden', 'Norway'
]

const formZone = ref({
  id: '',
  code: '',
  name: '',
  description: '',
  transitDays: '2-4 Days',
  fuelSurcharge: 12.0,
  rateMultiplier: 1.0,
  status: 'Active',
  countries: [],
})

const countrySearch = ref('')

const filteredZones = computed(() => {
  return zones.value.filter((z) => {
    const matchesSearch =
      z.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      z.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      z.countries.some((c) => c.toLowerCase().includes(searchQuery.value.toLowerCase()))
    const matchesStatus = filterStatus.value === 'All' || z.status === filterStatus.value
    return matchesSearch && matchesStatus
  })
})

const totalCountriesMapped = computed(() => {
  const allCountries = new Set()
  zones.value.forEach((z) => z.countries.forEach((c) => allCountries.add(c)))
  return allCountries.size
})

const showAlert = (msg) => {
  alertMessage.value = msg
  setTimeout(() => {
    alertMessage.value = null
  }, 3500)
}

const openAddModal = () => {
  isEditing.value = false
  formZone.value = {
    id: `ZN-${String(zones.value.length + 1).padStart(2, '0')}`,
    code: 'ZONE-',
    name: '',
    description: '',
    transitDays: '2-4 Days',
    fuelSurcharge: 12.0,
    rateMultiplier: 1.0,
    status: 'Active',
    countries: [],
  }
  showZoneModal.value = true
}

const openEditModal = (zone) => {
  isEditing.value = true
  formZone.value = {
    id: zone.id,
    code: zone.code,
    name: zone.name,
    description: zone.description,
    transitDays: zone.transitDays,
    fuelSurcharge: zone.fuelSurcharge,
    rateMultiplier: zone.rateMultiplier,
    status: zone.status,
    countries: [...zone.countries],
  }
  showZoneModal.value = true
}

const saveZone = () => {
  if (!formZone.value.name || !formZone.value.code) {
    alert('Please enter Zone Code and Zone Name.')
    return
  }

  if (isEditing.value) {
    const idx = zones.value.findIndex((z) => z.id === formZone.value.id)
    if (idx !== -1) {
      zones.value[idx] = { ...zones.value[idx], ...formZone.value }
      showAlert(`Zone ${formZone.value.name} updated successfully.`)
    }
  } else {
    zones.value.unshift({ ...formZone.value })
    showAlert(`Zone ${formZone.value.name} created successfully.`)
  }
  showZoneModal.value = false
}

const toggleStatus = (zone) => {
  zone.status = zone.status === 'Active' ? 'Inactive' : 'Active'
  showAlert(`Zone "${zone.name}" is now ${zone.status}.`)
}

const deleteZone = (zone) => {
  if (confirm(`Are you sure you want to delete zone "${zone.name}"?`)) {
    zones.value = zones.value.filter((z) => z.id !== zone.id)
    showAlert(`Zone "${zone.name}" removed successfully.`)
  }
}

const openCountryMapping = (zone) => {
  selectedZone.value = zone
  countrySearch.value = ''
  showCountryMappingModal.value = true
}

const toggleCountryForSelectedZone = (country) => {
  if (!selectedZone.value) return
  const idx = selectedZone.value.countries.indexOf(country)
  if (idx > -1) {
    selectedZone.value.countries.splice(idx, 1)
  } else {
    selectedZone.value.countries.push(country)
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
            placeholder="Search by zone name..." />
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

      <!-- Zone Table -->
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
            <tr v-if="filteredZones.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                No matching records found
              </td>
            </tr>
            <tr v-for="zone in filteredZones" :key="zone.id">
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                      {{ zone.name }}
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 280px;">
                  {{ zone.code }}
                </div>
              </td>
              <td>
                <button type="button" class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                  :class="zone.status === 'Active' ? 'bg-success text-white' : 'bg-secondary text-white'"
                  @click="toggleStatus(zone)" title="Click to toggle status">
                  {{ zone.status }}
                </button>
              </td>
              <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                  <!-- <button type="button" class="btn btn-sm btn-light text-primary p-2 rounded-2"
                    @click="openCountryMapping(zone)" title="Map Countries">
                    <i class="bi bi-globe"></i>
                  </button> -->
                  <button type="button" class="btn btn-sm btn-light text-secondary p-2 rounded-2"
                    @click="openEditModal(zone)" title="Edit Zone">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-light text-danger p-2 rounded-2" @click="deleteZone(zone)"
                    title="Delete Zone">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Add / Edit Zone -->
    <div v-if="showZoneModal" class="modal-backdrop fade show"></div>
    <div v-if="showZoneModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow-md">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
              {{ isEditing ? 'Edit Service Partner' : 'Create New Service Partner' }}
            </h5>
            <button type="button" class="btn-close" @click="showZoneModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Servive Partner Name <span
                    class="text-danger">*</span></label>
                <input v-model="formZone.name" type="text" class="form-control"
                  placeholder="Enter service partner name" />
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Servive Code<span class="text-danger">*</span></label>
                <input v-model="formZone.name" type="text" class="form-control"
                  placeholder="Enter partner service code" />
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showZoneModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="saveZone">
              <i class="bi bi-check2 me-1"></i>
              {{ isEditing ? 'Save Changes' : 'Create Zone' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Country Mapping -->
    <div v-if="showCountryMappingModal && selectedZone" class="modal-backdrop fade show"></div>
    <div v-if="showCountryMappingModal && selectedZone" class="modal fade show d-block" tabindex="-1" role="dialog"
      aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-globe me-2 text-primary"></i> Map Countries to {{ selectedZone.name }}
              </h5>
              <span class="text-muted small">Zone Code: <strong class="text-primary">{{ selectedZone.code }}</strong>
                &bull; Currently Mapped: {{ selectedZone.countries.length }} countries</span>
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
                    :class="{ 'border-primary bg-primary-subtle text-primary fw-semibold': selectedZone.countries.includes(country) }"
                    @click="toggleCountryForSelectedZone(country)">
                    <input type="checkbox" class="form-check-input mt-0"
                      :checked="selectedZone.countries.includes(country)" />
                    <span class="small text-truncate">{{ country }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <span class="small text-muted">{{ selectedZone.countries.length }} countries selected</span>
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
