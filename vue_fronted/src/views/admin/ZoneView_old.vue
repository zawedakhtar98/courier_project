<script setup>
import { ref, computed } from 'vue'

const zones = ref([
  {
    id: 'ZN-01',
    code: 'ZONE-NA',
    name: 'North America Priority',
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
    name: 'Western & Northern Europe',
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
    name: 'Middle East & GCC Express',
    description: 'Gulf cooperation council cross-border customs cleared line.',
    transitDays: '2-3 Days',
    fuelSurcharge: 11.0,
    rateMultiplier: 1.05,
    status: 'Active',
    countries: ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Oman', 'Bahrain'],
  },
  {
    id: 'ZN-04',
    code: 'ZONE-APAC',
    name: 'Asia-Pacific Priority Hub',
    description: 'High-frequency commercial lanes connecting East & South East Asia.',
    transitDays: '3-5 Days',
    fuelSurcharge: 15.5,
    rateMultiplier: 1.25,
    status: 'Active',
    countries: ['Singapore', 'Japan', 'South Korea', 'Australia', 'Malaysia', 'Hong Kong'],
  },
  {
    id: 'ZN-05',
    code: 'ZONE-SAARC',
    name: 'South Asia Regional Corridor',
    description: 'Subcontinent express line with overland & direct cargo links.',
    transitDays: '2-4 Days',
    fuelSurcharge: 9.5,
    rateMultiplier: 0.95,
    status: 'Active',
    countries: ['India', 'Bangladesh', 'Sri Lanka', 'Nepal'],
  },
  {
    id: 'ZN-06',
    code: 'ZONE-LATAM',
    name: 'Latin America Freight',
    description: 'Central & South American destinations with customs broker support.',
    transitDays: '4-7 Days',
    fuelSurcharge: 16.5,
    rateMultiplier: 1.35,
    status: 'Inactive',
    countries: ['Brazil', 'Argentina', 'Chile', 'Colombia', 'Peru'],
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
          <span class="text-secondary small fw-medium">Zone Master</span>
        </div>
        <h4 class="fw-bold mb-1 text-dark">Zone Configuration & Regional Corridors</h4>
        <p class="text-muted mb-0 small">
          Configure geographical shipping zones, delivery SLAs, fuel surcharges, and mapped countries.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <router-link :to="{ name: 'admin-countries' }" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2">
          <i class="bi bi-globe-americas me-1"></i> View Countries
        </router-link>
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1" @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add New Zone</span>
        </button>
      </div>
    </div>

    <!-- Alert Notification -->
    <transition name="fade">
      <div v-if="alertMessage" class="alert alert-success d-flex align-items-center mb-4 shadow-sm rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div>{{ alertMessage }}</div>
      </div>
    </transition>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-grid-1x2-fill"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Total Zones</div>
            <h4 class="fw-bold mb-0 text-dark">{{ zones.length }}</h4>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-success-subtle text-success p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-check2-circle"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Active Corridors</div>
            <h4 class="fw-bold mb-0 text-dark">{{ zones.filter(z => z.status === 'Active').length }}</h4>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-info-subtle text-info p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-globe2"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Mapped Countries</div>
            <h4 class="fw-bold mb-0 text-dark">{{ totalCountriesMapped }}</h4>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-warning-subtle text-warning p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Avg. Transit SLA</div>
            <h4 class="fw-bold mb-0 text-dark">2.8 Days</h4>
          </div>
        </div>
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
          <input
            v-model="searchQuery"
            type="text"
            class="form-control bg-light border-start-0"
            placeholder="Search by zone name, code, country..."
          />
        </div>

        <!-- Status Filter Pills -->
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Status:</span>
          <button
            v-for="status in ['All', 'Active', 'Inactive']"
            :key="status"
            type="button"
            class="btn btn-sm rounded-pill px-3"
            :class="filterStatus === status ? 'btn-primary' : 'btn-light text-secondary'"
            @click="filterStatus = status"
          >
            {{ status }}
          </button>
        </div>
      </div>

      <!-- Zone Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="border-0 rounded-start">Zone Code & Name</th>
              <th class="border-0">Mapped Countries</th>
              <th class="border-0">Transit SLA</th>
              <th class="border-0">Fuel Surcharge</th>
              <th class="border-0">Multiplier</th>
              <th class="border-0">Status</th>
              <th class="border-0 text-end rounded-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredZones.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                No matching zones found.
              </td>
            </tr>
            <tr v-for="zone in filteredZones" :key="zone.id">
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 bg-primary-subtle text-primary p-2 fs-5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-geo-alt-fill"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                      {{ zone.name }}
                      <span class="badge bg-light text-dark border font-monospace small px-2 py-0">{{ zone.code }}</span>
                    </div>
                    <span class="text-muted small text-truncate d-inline-block" style="max-width: 280px;">
                      {{ zone.description }}
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 280px;">
                  <span
                    v-for="country in zone.countries.slice(0, 3)"
                    :key="country"
                    class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-2 py-1 small"
                  >
                    {{ country }}
                  </span>
                  <button
                    v-if="zone.countries.length > 3"
                    class="badge bg-primary-subtle text-primary border-0 rounded-pill px-2 py-1 small cursor-pointer"
                    @click="openCountryMapping(zone)"
                  >
                    +{{ zone.countries.length - 3 }} more
                  </button>
                  <button
                    v-if="zone.countries.length === 0"
                    class="badge bg-warning-subtle text-warning border-0 rounded-pill px-2 py-1 small cursor-pointer"
                    @click="openCountryMapping(zone)"
                  >
                    + Assign Countries
                  </button>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-1 text-dark fw-semibold small">
                  <i class="bi bi-lightning-charge text-warning"></i>
                  {{ zone.transitDays }}
                </div>
              </td>
              <td>
                <span class="fw-semibold text-dark small">{{ zone.fuelSurcharge }}%</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border font-monospace small">{{ zone.rateMultiplier }}x</span>
              </td>
              <td>
                <button
                  type="button"
                  class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                  :class="zone.status === 'Active' ? 'bg-success text-white' : 'bg-secondary text-white'"
                  @click="toggleStatus(zone)"
                  title="Click to toggle status"
                >
                  {{ zone.status }}
                </button>
              </td>
              <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                  <button
                    type="button"
                    class="btn btn-sm btn-light text-primary p-2 rounded-2"
                    @click="openCountryMapping(zone)"
                    title="Map Countries"
                  >
                    <i class="bi bi-globe"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-light text-secondary p-2 rounded-2"
                    @click="openEditModal(zone)"
                    title="Edit Zone"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-light text-danger p-2 rounded-2"
                    @click="deleteZone(zone)"
                    title="Delete Zone"
                  >
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
    <div
      v-if="showZoneModal"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold text-dark">
              <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
              {{ isEditing ? 'Edit Shipping Zone' : 'Create New Shipping Zone' }}
            </h5>
            <button type="button" class="btn-close" @click="showZoneModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Zone Code <span class="text-danger">*</span></label>
                <input
                  v-model="formZone.code"
                  type="text"
                  class="form-control"
                  placeholder="e.g. ZONE-NA, ZONE-EU"
                />
                <span class="text-muted" style="font-size: 0.75rem;">Unique identifier for rating matrix.</span>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Zone Name <span class="text-danger">*</span></label>
                <input
                  v-model="formZone.name"
                  type="text"
                  class="form-control"
                  placeholder="e.g. North America Priority"
                />
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Description</label>
                <textarea
                  v-model="formZone.description"
                  class="form-control"
                  rows="2"
                  placeholder="Operational details, hub routes or carrier partner..."
                ></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Estimated Transit SLA</label>
                <input
                  v-model="formZone.transitDays"
                  type="text"
                  class="form-control"
                  placeholder="e.g. 2-4 Days"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Fuel Surcharge (%)</label>
                <div class="input-group">
                  <input
                    v-model.number="formZone.fuelSurcharge"
                    type="number"
                    step="0.1"
                    class="form-control"
                  />
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Base Rate Multiplier</label>
                <div class="input-group">
                  <input
                    v-model.number="formZone.rateMultiplier"
                    type="number"
                    step="0.05"
                    class="form-control"
                  />
                  <span class="input-group-text">x</span>
                </div>
              </div>
              <div class="col-12">
                <div class="form-check form-switch mt-2">
                  <input
                    id="zoneStatusSwitch"
                    v-model="formZone.status"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :true-value="'Active'"
                    :false-value="'Inactive'"
                  />
                  <label class="form-check-label fw-semibold small text-dark ms-2" for="zoneStatusSwitch">
                    Zone Operational Status (Active / Inactive)
                  </label>
                </div>
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
    <div
      v-if="showCountryMappingModal && selectedZone"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-globe me-2 text-primary"></i> Map Countries to {{ selectedZone.name }}
              </h5>
              <span class="text-muted small">Zone Code: <strong class="text-primary">{{ selectedZone.code }}</strong> &bull; Currently Mapped: {{ selectedZone.countries.length }} countries</span>
            </div>
            <button type="button" class="btn-close" @click="showCountryMappingModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="mb-3">
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input
                  v-model="countrySearch"
                  type="text"
                  class="form-control bg-light border-start-0"
                  placeholder="Filter available countries..."
                />
              </div>
            </div>

            <div class="p-3 bg-light rounded-3 border" style="max-height: 320px; overflow-y: auto;">
              <div class="row g-2">
                <div
                  v-for="country in filteredAvailableCountries"
                  :key="country"
                  class="col-6 col-md-4"
                >
                  <div
                    class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-white cursor-pointer transition-all"
                    :class="{ 'border-primary bg-primary-subtle text-primary fw-semibold': selectedZone.countries.includes(country) }"
                    @click="toggleCountryForSelectedZone(country)"
                  >
                    <input
                      type="checkbox"
                      class="form-check-input mt-0"
                      :checked="selectedZone.countries.includes(country)"
                    />
                    <span class="small text-truncate">{{ country }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <span class="small text-muted">{{ selectedZone.countries.length }} countries selected</span>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="showCountryMappingModal = false">
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
