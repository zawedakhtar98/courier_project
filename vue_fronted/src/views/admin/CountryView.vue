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
    currency: 'SGD (S$)',
    customsDoc: 'TradeNet Permit + Invoice',
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
    currency: 'AUD (A$)',
    customsDoc: 'Customs B374 + Biosecurity Dec',
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
    customsDoc: 'KYC + IEC + Commercial Invoice',
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
    customsDoc: 'Customs Declaration + Packing List',
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
    status: 'Inactive',
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
  if (!formCountry.value.name ) {
    alert('Please enter Country Name.')
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
          <span class="text-secondary small fw-medium">Country Directory</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">       
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

    <!-- Filters & Table -->
    <div class="custom-card p-4">
      <div class="row g-3 mb-4 justify-content-end">
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
              placeholder="Search by country and country code "
            />
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
                        {{ country.iso2 }}
                      </span>
                    </div>
                  </div>
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
                <input
                  v-model="formCountry.name"
                  type="text"
                  class="form-control"
                  placeholder="e.g. Singapore, Germany"
                />
              </div>  
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Country Code <span class="text-danger">*</span></label>
                <input
                  v-model="formCountry.name"
                  type="text"
                  class="form-control"
                  placeholder="Country Code"
                />
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
