<script setup>
import { ref, computed } from 'vue'

const countries = ref([
  {
    id: 'CT-01',
    name: 'United States',
    iso2: 'US',
    iso3: 'USA',
    dialCode: '+1',
    zoneCode: 'ZONE-NA',
    zoneName: 'North America Priority',
    currency: 'USD ($)',
    customsDoc: 'Commercial Invoice + HS Codes',
    status: 'Active',
    flagEmoji: '🇺🇸',
  },
  {
    id: 'CT-02',
    name: 'Canada',
    iso2: 'CA',
    iso3: 'CAN',
    dialCode: '+1',
    zoneCode: 'ZONE-NA',
    zoneName: 'North America Priority',
    currency: 'CAD ($)',
    customsDoc: 'CCI + Commercial Invoice',
    status: 'Active',
    flagEmoji: '🇨🇦',
  },
  {
    id: 'CT-03',
    name: 'United Kingdom',
    iso2: 'GB',
    iso3: 'GBR',
    dialCode: '+44',
    zoneCode: 'ZONE-EU',
    zoneName: 'Western & Northern Europe',
    currency: 'GBP (£)',
    customsDoc: 'UK EORI + Commercial Invoice',
    status: 'Active',
    flagEmoji: '🇬🇧',
  },
  {
    id: 'CT-04',
    name: 'Germany',
    iso2: 'DE',
    iso3: 'DEU',
    dialCode: '+49',
    zoneCode: 'ZONE-EU',
    zoneName: 'Western & Northern Europe',
    currency: 'EUR (€)',
    customsDoc: 'EU EORI + EUR.1 Certificate',
    status: 'Active',
    flagEmoji: '🇩🇪',
  },
  {
    id: 'CT-05',
    name: 'United Arab Emirates',
    iso2: 'AE',
    iso3: 'ARE',
    dialCode: '+971',
    zoneCode: 'ZONE-ME',
    zoneName: 'Middle East & GCC Express',
    currency: 'AED (د.إ)',
    customsDoc: 'Attested Invoice + Certificate of Origin',
    status: 'Active',
    flagEmoji: '🇦🇪',
  },
  {
    id: 'CT-06',
    name: 'Saudi Arabia',
    iso2: 'SA',
    iso3: 'SAU',
    dialCode: '+966',
    zoneCode: 'ZONE-ME',
    zoneName: 'Middle East & GCC Express',
    currency: 'SAR (﷼)',
    customsDoc: 'SABER Certificate + Saudi National ID',
    status: 'Active',
    flagEmoji: '🇸🇦',
  },
  {
    id: 'CT-07',
    name: 'Singapore',
    iso2: 'SG',
    iso3: 'SGP',
    dialCode: '+65',
    zoneCode: 'ZONE-APAC',
    zoneName: 'Asia-Pacific Priority Hub',
    currency: 'SGD ($)',
    customsDoc: 'GST Registration + Permit',
    status: 'Active',
    flagEmoji: '🇸🇬',
  },
  {
    id: 'CT-08',
    name: 'Australia',
    iso2: 'AU',
    iso3: 'AUS',
    dialCode: '+61',
    zoneCode: 'ZONE-APAC',
    zoneName: 'Asia-Pacific Priority Hub',
    currency: 'AUD ($)',
    customsDoc: 'Biosecurity Declaration + Invoice',
    status: 'Active',
    flagEmoji: '🇦🇺',
  },
  {
    id: 'CT-09',
    name: 'India',
    iso2: 'IN',
    iso3: 'IND',
    dialCode: '+91',
    zoneCode: 'ZONE-SAARC',
    zoneName: 'South Asia Regional Corridor',
    currency: 'INR (₹)',
    customsDoc: 'KYC Document + GSTIN Invoice',
    status: 'Active',
    flagEmoji: '🇮🇳',
  },
  {
    id: 'CT-10',
    name: 'Japan',
    iso2: 'JP',
    iso3: 'JPN',
    dialCode: '+81',
    zoneCode: 'ZONE-APAC',
    zoneName: 'Asia-Pacific Priority Hub',
    currency: 'JPY (¥)',
    customsDoc: 'Standard Commercial Invoice',
    status: 'Active',
    flagEmoji: '🇯🇵',
  },
  {
    id: 'CT-11',
    name: 'France',
    iso2: 'FR',
    iso3: 'FRA',
    dialCode: '+33',
    zoneCode: 'ZONE-EU',
    zoneName: 'Western & Northern Europe',
    currency: 'EUR (€)',
    customsDoc: 'EU EORI + Commercial Invoice',
    status: 'Active',
    flagEmoji: '🇫🇷',
  },
  {
    id: 'CT-12',
    name: 'Brazil',
    iso2: 'BR',
    iso3: 'BRA',
    dialCode: '+55',
    zoneCode: 'ZONE-LATAM',
    zoneName: 'Latin America Freight',
    currency: 'BRL (R$)',
    customsDoc: 'CPF/CNPJ Tax ID Required',
    status: 'Restricted',
    flagEmoji: '🇧🇷',
  },
])

const availableZones = [
  { code: 'ZONE-NA', name: 'North America Priority' },
  { code: 'ZONE-EU', name: 'Western & Northern Europe' },
  { code: 'ZONE-ME', name: 'Middle East & GCC Express' },
  { code: 'ZONE-APAC', name: 'Asia-Pacific Priority Hub' },
  { code: 'ZONE-SAARC', name: 'South Asia Regional Corridor' },
  { code: 'ZONE-LATAM', name: 'Latin America Freight' },
  { code: 'UNASSIGNED', name: 'Unassigned / Direct' },
]

const searchQuery = ref('')
const selectedZoneFilter = ref('All')
const selectedStatusFilter = ref('All')
const alertMessage = ref(null)

// Modal states
const showCountryModal = ref(false)
const isEditing = ref(false)
const showAssignZoneModal = ref(false)
const countryToAssign = ref(null)
const targetZoneCode = ref('')

const formCountry = ref({
  id: '',
  name: '',
  iso2: '',
  iso3: '',
  dialCode: '+',
  zoneCode: 'ZONE-NA',
  zoneName: 'North America Priority',
  currency: 'USD ($)',
  customsDoc: '',
  status: 'Active',
  flagEmoji: '🌐',
})

const filteredCountries = computed(() => {
  return countries.value.filter((c) => {
    const matchesSearch =
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.iso2.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.iso3.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.currency.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesZone = selectedZoneFilter.value === 'All' || c.zoneCode === selectedZoneFilter.value
    const matchesStatus = selectedStatusFilter.value === 'All' || c.status === selectedStatusFilter.value
    return matchesSearch && matchesZone && matchesStatus
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
  formCountry.value = {
    id: `CT-${String(countries.value.length + 1).padStart(2, '0')}`,
    name: '',
    iso2: '',
    iso3: '',
    dialCode: '+',
    zoneCode: 'ZONE-NA',
    zoneName: 'North America Priority',
    currency: 'USD ($)',
    customsDoc: 'Commercial Invoice',
    status: 'Active',
    flagEmoji: '🌐',
  }
  showCountryModal.value = true
}

const openEditModal = (country) => {
  isEditing.value = true
  formCountry.value = { ...country }
  showCountryModal.value = true
}

const onZoneSelectedInForm = () => {
  const z = availableZones.find((item) => item.code === formCountry.value.zoneCode)
  if (z) {
    formCountry.value.zoneName = z.name
  }
}

const saveCountry = () => {
  if (!formCountry.value.name || !formCountry.value.iso2) {
    alert('Please enter Country Name and ISO-2 Code.')
    return
  }

  formCountry.value.iso2 = formCountry.value.iso2.toUpperCase()
  formCountry.value.iso3 = formCountry.value.iso3 ? formCountry.value.iso3.toUpperCase() : formCountry.value.iso2

  if (isEditing.value) {
    const idx = countries.value.findIndex((c) => c.id === formCountry.value.id)
    if (idx !== -1) {
      countries.value[idx] = { ...formCountry.value }
      showAlert(`Country ${formCountry.value.name} updated successfully.`)
    }
  } else {
    countries.value.unshift({ ...formCountry.value })
    showAlert(`Country ${formCountry.value.name} added to directory.`)
  }
  showCountryModal.value = false
}

const openQuickZoneModal = (country) => {
  countryToAssign.value = country
  targetZoneCode.value = country.zoneCode
  showAssignZoneModal.value = true
}

const applyZoneReassignment = () => {
  if (!countryToAssign.value) return
  const z = availableZones.find((item) => item.code === targetZoneCode.value)
  if (z) {
    countryToAssign.value.zoneCode = z.code
    countryToAssign.value.zoneName = z.name
    showAlert(`${countryToAssign.value.name} reassigned to ${z.name}.`)
  }
  showAssignZoneModal.value = false
}

const toggleCountryStatus = (country) => {
  if (country.status === 'Active') {
    country.status = 'Suspended'
  } else if (country.status === 'Suspended') {
    country.status = 'Restricted'
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
    case 'Suspended':
      return 'bg-secondary text-white'
    case 'Restricted':
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
          <span class="text-secondary small fw-medium">Country Directory</span>
        </div>
        <h4 class="fw-bold mb-1 text-dark">Country Directory & Regional Zone Mapping</h4>
        <p class="text-muted mb-0 small">
          Manage international destinations, ISO country codes, assigned tariff zones, and customs requirements.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <router-link :to="{ name: 'admin-zones' }" class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2">
          <i class="bi bi-grid-1x2 me-1"></i> Zone Setup
        </router-link>
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1" @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add Country</span>
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
            <i class="bi bi-globe-americas"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Total Destinations</div>
            <h4 class="fw-bold mb-0 text-dark">{{ countries.length }}</h4>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-success-subtle text-success p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-airplane-engines"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Active Deliveries</div>
            <h4 class="fw-bold mb-0 text-dark">{{ countries.filter(c => c.status === 'Active').length }}</h4>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-info-subtle text-info p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-diagram-3"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Configured Zones</div>
            <h4 class="fw-bold mb-0 text-dark">6 Zones</h4>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
            <i class="bi bi-shield-exclamation"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Restricted Lines</div>
            <h4 class="fw-bold mb-0 text-dark">{{ countries.filter(c => c.status === 'Restricted').length }}</h4>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Table -->
    <div class="custom-card p-4">
      <div class="row g-3 mb-4">
        <!-- Search -->
        <div class="col-12 col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              class="form-control bg-light border-start-0"
              placeholder="Search by country, ISO code, currency..."
            />
          </div>
        </div>

        <!-- Zone Filter -->
        <div class="col-6 col-md-4">
          <select v-model="selectedZoneFilter" class="form-select bg-light">
            <option value="All">All Shipping Zones</option>
            <option v-for="z in availableZones" :key="z.code" :value="z.code">
              {{ z.code }} - {{ z.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div class="col-6 col-md-3">
          <select v-model="selectedStatusFilter" class="form-select bg-light">
            <option value="All">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Suspended">Suspended</option>
            <option value="Restricted">Restricted</option>
          </select>
        </div>
      </div>

      <!-- Countries Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="border-0 rounded-start">Country & Code</th>
              <th class="border-0">Assigned Zone</th>
              <th class="border-0">Currency & Dial</th>
              <th class="border-0">Customs Clearance Notes</th>
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
                  <div class="fs-2 text-center" style="width: 36px;">
                    {{ country.flagEmoji }}
                  </div>
                  <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                      {{ country.name }}
                      <span class="badge bg-primary-subtle text-primary border font-monospace small px-2 py-0">
                        {{ country.iso2 }} / {{ country.iso3 }}
                      </span>
                    </div>
                    <span class="text-muted small">
                      Dial Code: <strong>{{ country.dialCode }}</strong>
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div>
                    <span class="badge bg-light text-dark border font-monospace small fw-bold">
                      {{ country.zoneCode }}
                    </span>
                    <div class="text-secondary small fw-medium" style="font-size: 0.78rem;">
                      {{ country.zoneName }}
                    </div>
                  </div>
                  <button
                    type="button"
                    class="btn btn-sm btn-link text-primary p-0"
                    title="Change Zone"
                    @click="openQuickZoneModal(country)"
                  >
                    <i class="bi bi-arrow-left-right small"></i>
                  </button>
                </div>
              </td>
              <td>
                <div class="fw-semibold text-dark small">{{ country.currency }}</div>
              </td>
              <td>
                <div class="text-muted small text-truncate" style="max-width: 220px;" :title="country.customsDoc">
                  <i class="bi bi-file-earmark-text text-secondary me-1"></i>
                  {{ country.customsDoc || 'Standard Declaration' }}
                </div>
              </td>
              <td>
                <button
                  type="button"
                  class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                  :class="getStatusBadge(country.status)"
                  @click="toggleCountryStatus(country)"
                  title="Click to cycle status"
                >
                  {{ country.status }}
                </button>
              </td>
              <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                  <button
                    type="button"
                    class="btn btn-sm btn-light text-primary p-2 rounded-2"
                    @click="openQuickZoneModal(country)"
                    title="Assign Zone"
                  >
                    <i class="bi bi-grid-1x2"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-light text-secondary p-2 rounded-2"
                    @click="openEditModal(country)"
                    title="Edit Country"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-light text-danger p-2 rounded-2"
                    @click="deleteCountry(country)"
                    title="Delete Country"
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

    <!-- Modal: Add / Edit Country -->
    <div v-if="showCountryModal" class="modal-backdrop fade show"></div>
    <div
      v-if="showCountryModal"
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
              {{ isEditing ? 'Edit Country Details' : 'Add New Destination Country' }}
            </h5>
            <button type="button" class="btn-close" @click="showCountryModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Country Name <span class="text-danger">*</span></label>
                <input
                  v-model="formCountry.name"
                  type="text"
                  class="form-control"
                  placeholder="e.g. Singapore, Germany"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-semibold">ISO-2 Code <span class="text-danger">*</span></label>
                <input
                  v-model="formCountry.iso2"
                  type="text"
                  maxlength="2"
                  class="form-control text-uppercase"
                  placeholder="e.g. SG"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-semibold">ISO-3 Code</label>
                <input
                  v-model="formCountry.iso3"
                  type="text"
                  maxlength="3"
                  class="form-control text-uppercase"
                  placeholder="e.g. SGP"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Flag Emoji / Icon</label>
                <input
                  v-model="formCountry.flagEmoji"
                  type="text"
                  class="form-control"
                  placeholder="e.g. 🇸🇬"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Dial Code</label>
                <input
                  v-model="formCountry.dialCode"
                  type="text"
                  class="form-control"
                  placeholder="e.g. +65"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Local Currency</label>
                <input
                  v-model="formCountry.currency"
                  type="text"
                  class="form-control"
                  placeholder="e.g. SGD ($)"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Assigned Shipping Zone</label>
                <select
                  v-model="formCountry.zoneCode"
                  class="form-select"
                  @change="onZoneSelectedInForm"
                >
                  <option v-for="z in availableZones" :key="z.code" :value="z.code">
                    {{ z.code }} - {{ z.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Operation Status</label>
                <select v-model="formCountry.status" class="form-select">
                  <option value="Active">Active</option>
                  <option value="Suspended">Suspended</option>
                  <option value="Restricted">Restricted</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Customs Documentation & Clearances</label>
                <textarea
                  v-model="formCountry.customsDoc"
                  class="form-control"
                  rows="2"
                  placeholder="e.g. EORI number, HS code invoice declaration, national ID requirements..."
                ></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showCountryModal = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="saveCountry">
              <i class="bi bi-check2 me-1"></i>
              {{ isEditing ? 'Save Changes' : 'Add Country' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Quick Zone Reassignment -->
    <div v-if="showAssignZoneModal && countryToAssign" class="modal-backdrop fade show"></div>
    <div
      v-if="showAssignZoneModal && countryToAssign"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
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
