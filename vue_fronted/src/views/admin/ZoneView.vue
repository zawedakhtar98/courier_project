<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { addZone, getZoneList, updateZone, addZoneCountryMapping, unmapZoneCountry, bulkMapZoneCountries } from '@/services/admin/ZoneMasterService'
import { getAllCountries } from '@/services/admin/CountryService'

const toast = useToast();

const zones = ref([]);

const searchQuery = ref('')
const filterStatus = ref('All')
const alertMessage = ref(null)

// Modal states
const showZoneModal = ref(false)
const isEditing = ref(false)
const showCountryMappingModal = ref(false)
const selectedZone = ref(null)

// Dropdown Mapping states
const selectedMappingZoneId = ref(null)
const selectedCountryIds = ref([])
const isMappingSubmitting = ref(false)

// Bulk Upload states
const showBulkUploadModal = ref(false)
const uploadFile = ref(null)
const uploadMode = ref('append') // 'append' or 'overwrite'
const isUploading = ref(false)
const uploadResult = ref(null)
const isDragOver = ref(false)
const fileInputRef = ref(null)

const availableCountries = ref([]);

const formZone = ref({
  name: '',
  status: 'Active'
})

const countrySearch = ref('')

const filteredZones = computed(() => {
  return zones.value.filter((z) => {
    const matchesSearch =
      z.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
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
onMounted(() => {
  fetchZoneList();
  fetchCountries();
})
const fetchZoneList = async () => {
  const resp = await getZoneList();
  if (resp.status === 'success') {
    zones.value = resp.data;
  }
}

const fetchCountries = async () => {
  const resp = await getAllCountries();
  if (resp.status === 'success') {
    availableCountries.value = resp.data;
  }
}

const saveZone = async () => {
  if (!formZone.value.name || !formZone.value.code) {
    toast.error('Please enter Zone Code and Zone Name.')
    return
  }

  if (isEditing.value) {
    const idx = zones.value.findIndex((z) => z.id === formZone.value.id)
    if (idx !== -1) {
      zones.value[idx] = { ...zones.value[idx], ...formZone.value }
      toast.success(`Zone ${formZone.value.name} updated successfully.`)
    }
  } else {
    const resp = await addZone(formZone.value);
    if (resp.status === 'success') {
      zones.value.unshift({ ...formZone.value })
      toast.success(`Zone ${formZone.value.name} created successfully.`)
    } else {
      toast.error(resp.message)
    }
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

// Active zone object selected in mapping modal
const activeMappingZone = computed(() => {
  if (!selectedMappingZoneId.value) return null
  return zones.value.find(z => z.id === selectedMappingZoneId.value) || null
})

// All available countries formatted for v-select
const availableCountryOptions = computed(() => {
  if (!availableCountries.value?.length) return []
  return availableCountries.value.map(c => ({
    id: c.id,
    name: c.name,
    code: c.code,
    label: `${c.name} (${c.code || 'N/A'})`
  }))
})

// Filter out countries already mapped to the selected zone
const unmappedCountryOptions = computed(() => {
  if (!activeMappingZone.value) return availableCountryOptions.value
  const mappedSet = new Set((activeMappingZone.value.countries || []).map(n => n.toLowerCase()))
  return availableCountryOptions.value.filter(c => !mappedSet.has(c.name.toLowerCase()))
})

const selectAllUnmappedCountries = () => {
  selectedCountryIds.value = unmappedCountryOptions.value.map(c => c.id)
}

const clearSelectedCountries = () => {
  selectedCountryIds.value = []
}

const openCountryMapping = (zone = null) => {
  if (zone) {
    selectedMappingZoneId.value = zone.id
    selectedZone.value = zone
  } else if (zones.value.length > 0) {
    if (!selectedMappingZoneId.value) {
      selectedMappingZoneId.value = zones.value[0].id
    }
  }
  selectedCountryIds.value = []
  showCountryMappingModal.value = true
}

const closeCountryMappingModal = () => {
  showCountryMappingModal.value = false
  selectedCountryIds.value = []
}

const saveDropdownMapping = async () => {
  if (!selectedMappingZoneId.value) {
    toast.error('Please select a Zone from the dropdown.')
    return
  }
  if (!selectedCountryIds.value || selectedCountryIds.value.length === 0) {
    toast.error('Please select at least one Country from the dropdown.')
    return
  }

  isMappingSubmitting.value = true
  try {
    const payload = {
      zone_id: selectedMappingZoneId.value,
      country_id: selectedCountryIds.value
    }
    const resp = await addZoneCountryMapping(payload)
    if (resp.status === 'success') {
      toast.success(resp.message || 'Countries mapped successfully!')
      selectedCountryIds.value = []
      // Re-fetch zones to refresh mapped countries count and badges
      await fetchZoneList()
    } else {
      toast.error(resp.message || 'Failed to map countries.')
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message || err.message || 'Error mapping countries.'
    toast.error(errorMsg)
  } finally {
    isMappingSubmitting.value = false
  }
}

const removeCountryFromZone = async (countryName) => {
  if (!activeMappingZone.value) return
  const match = availableCountries.value.find(c => c.name.toLowerCase() === countryName.toLowerCase())
  if (!match) return

  try {
    const resp = await unmapZoneCountry({
      zone_id: activeMappingZone.value.id,
      country_id: match.id
    })
    if (resp.status === 'success') {
      toast.success(`Removed ${countryName} from ${activeMappingZone.value.name}`)
      await fetchZoneList()
    } else {
      toast.error(resp.message || 'Failed to remove country.')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error removing country.')
  }
}

// Bulk Upload Methods
const openBulkUploadModal = () => {
  uploadFile.value = null
  uploadResult.value = null
  uploadMode.value = 'append'
  isUploading.value = false
  isDragOver.value = false
  showBulkUploadModal.value = true
}

const closeBulkUploadModal = () => {
  showBulkUploadModal.value = false
  uploadFile.value = null
  uploadResult.value = null
  isUploading.value = false
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
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const downloadSampleCsv = () => {
  const sampleCsv = `zone_name,country_code,country_name
Zone_1,US,United States
Zone_1,CA,Canada
Zone_1,MX,Mexico
Zone_2,GB,United Kingdom
Zone_2,DE,Germany
Zone_2,FR,France
Zone_3,AE,United Arab Emirates
Zone_3,SA,Saudi Arabia
Zone_4,IN,India
Zone_4,SG,Singapore
Zone_4,MY,Malaysia
`
  const blob = new Blob([sampleCsv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.setAttribute('download', 'zone_country_mapping_sample.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const handleBulkUpload = async () => {
  if (!uploadFile.value) {
    toast.error('Please select a CSV file first.')
    return
  }

  isUploading.value = true
  uploadResult.value = null

  try {
    const formData = new FormData()
    formData.append('file', uploadFile.value)
    formData.append('mode', uploadMode.value)

    const resp = await bulkMapZoneCountries(formData)
    if (resp.status === 'success') {
      uploadResult.value = resp.data
      toast.success(resp.message || 'Bulk mapping completed successfully!')
      // Refresh the zones list with updated country counts & mappings
      await fetchZoneList()
    } else {
      toast.error(resp.message || 'Failed to process bulk mapping.')
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message || err.message || 'An error occurred during upload.'
    toast.error(errorMsg)
  } finally {
    isUploading.value = false
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
          <span class="text-secondary small fw-medium">Zone Master</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openCountryMapping(null)">
          <i class="bi bi-globe"></i>
          <span>Map Zone & Countries</span>
        </button>
        <button class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openBulkUploadModal">
          <i class="bi bi-file-earmark-arrow-up"></i>
          <span>Bulk Upload CSV</span>
        </button>
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-1"
          @click="openAddModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add New Zone</span>
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
              <th class="border-0 rounded-start">Zone Name</th>
              <th class="border-0">Map Countries</th>
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
                  <div
                    class="rounded-3 bg-primary-subtle text-primary p-2 fs-5 d-flex align-items-center justify-content-center"
                    style="width: 44px; height: 44px;">
                    <i class="bi bi-geo-alt-fill"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                      {{ zone.name }}
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 280px;">
                  <span v-for="country in zone.countries.slice(0, 3)" :key="country"
                    class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-2 py-1 small">
                    {{ country }}
                  </span>
                  <button v-if="zone.countries.length > 3"
                    class="badge bg-primary-subtle text-primary border-0 rounded-pill px-2 py-1 small cursor-pointer"
                    @click="openCountryMapping(zone)">
                    +{{ zone.countries.length - 3 }} more
                  </button>
                  <button v-if="zone.countries.length === 0"
                    class="badge bg-warning-subtle text-warning border-0 rounded-pill px-2 py-1 small cursor-pointer"
                    @click="openCountryMapping(zone)">
                    + Assign Countries
                  </button>
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
                  <button type="button" class="btn btn-sm btn-light text-primary p-2 rounded-2"
                    @click="openCountryMapping(zone)" title="Map Countries">
                    <i class="bi bi-globe"></i>
                  </button>
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
              {{ isEditing ? 'Edit Zone' : 'Create New Zone' }}
            </h5>
            <button type="button" class="btn-close" @click="showZoneModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-semibold">Zone Name <span class="text-danger">*</span></label>
                <input v-model="formZone.name" type="text" class="form-control"
                  placeholder="e.g. North America Priority" />
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

    <!-- Modal: Map Zone with Country (Dropdown List Selection) -->
    <div v-if="showCountryMappingModal" class="modal-backdrop fade show"></div>
    <div v-if="showCountryMappingModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div
                class="rounded-3 bg-primary-subtle text-primary p-2 fs-5 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;">
                <i class="bi bi-globe"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0">Map Zone with Countries</h5>
                <span class="text-muted small">Select Zone and Countries from dropdowns to map them</span>
              </div>
            </div>
            <button type="button" class="btn-close" @click="closeCountryMappingModal"></button>
          </div>

          <div class="modal-body px-4 py-3">
            <!-- 1. Select Zone Dropdown -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark mb-1">
                <i class="bi bi-geo-alt-fill text-primary me-1"></i> 1. Select Zone (from Zone Master) <span
                  class="text-danger">*</span>
              </label>
              <v-select v-model="selectedMappingZoneId" :options="zones" label="name" :reduce="z => z.id"
                placeholder="Choose a zone from list..." :clearable="false">
                <template #option="option">
                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="fw-semibold text-dark">{{ option.name }}</span>
                    <span class="badge bg-secondary-subtle text-secondary small">
                      {{ (option.countries || []).length }} countries mapped
                    </span>
                  </div>
                </template>
              </v-select>
            </div>

            <!-- 2. Select Countries Dropdown -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-bold text-dark mb-0">
                  <i class="bi bi-flag-fill text-primary me-1"></i> 2. Select Countries (from Country List) <span
                    class="text-danger">*</span>
                </label>
                <div class="d-flex gap-2 align-items-center">
                  <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small"
                    @click="selectAllUnmappedCountries" :disabled="!unmappedCountryOptions.length">
                    Select All Available ({{ unmappedCountryOptions.length }})
                  </button>
                  <span class="text-muted small">|</span>
                  <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger small"
                    @click="clearSelectedCountries" :disabled="!selectedCountryIds.length">
                    Clear Selection
                  </button>
                </div>
              </div>

              <v-select multiple v-model="selectedCountryIds" :options="unmappedCountryOptions" label="label"
                :reduce="c => c.id"
                placeholder="Search country by name or code (e.g. United States, Germany, India)..." />
              <div class="form-text small text-muted">
                Search and select multiple countries to attach to this zone in one click.
              </div>
            </div>

            <!-- Action Button -->
            <div class="d-flex justify-content-end mb-4">
              <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm d-flex align-items-center gap-2"
                :disabled="!selectedMappingZoneId || selectedCountryIds.length === 0 || isMappingSubmitting"
                @click="saveDropdownMapping">
                <span v-if="isMappingSubmitting" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-if="!isMappingSubmitting" class="bi bi-plus-circle"></i>
                <span>{{ isMappingSubmitting ? 'Mapping...' : `Add ${selectedCountryIds.length > 0 ?
                  selectedCountryIds.length : ''}
                  Country(ies) to ${activeMappingZone?.name || 'Zone'}` }}</span>
              </button>
            </div>

            <!-- 3. Currently Mapped Countries for the selected zone -->
            <div v-if="activeMappingZone" class="p-3 bg-light rounded-3 border">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold small text-dark">
                  <i class="bi bi-check2-circle text-success me-1"></i>
                  Currently Mapped Countries for {{ activeMappingZone.name }}:
                  <span class="badge bg-primary rounded-pill ms-1">{{ (activeMappingZone.countries || []).length
                    }}</span>
                </span>
                <span class="text-muted small" style="font-size: 0.75rem;">Click &times; to remove country</span>
              </div>

              <div v-if="(activeMappingZone.countries || []).length === 0" class="text-muted small py-3 text-center">
                <i class="bi bi-info-circle me-1"></i> No countries mapped to {{ activeMappingZone.name }} yet. Select
                from the
                dropdown above to add countries.
              </div>

              <div v-else class="d-flex flex-wrap gap-1" style="max-height: 200px; overflow-y: auto;">
                <span v-for="cName in activeMappingZone.countries" :key="cName"
                  class="badge bg-white text-dark border d-inline-flex align-items-center gap-1 px-2 py-1 small rounded-pill shadow-xs">
                  <span>{{ cName }}</span>
                  <i class="bi bi-x-circle-fill text-danger cursor-pointer ms-1 hover-opacity"
                    title="Remove country from this zone" @click="removeCountryFromZone(cName)"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <span class="small text-muted">
              Total {{ availableCountries.length }} countries in database
            </span>
            <button type="button" class="btn btn-secondary rounded-3 px-4 shadow-sm" @click="closeCountryMappingModal">
              Done
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Bulk Upload Zone Country Mappings -->
    <div v-if="showBulkUploadModal" class="modal-backdrop fade show"></div>
    <div v-if="showBulkUploadModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div
                class="rounded-3 bg-primary-subtle text-primary p-2 fs-5 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px;">
                <i class="bi bi-file-earmark-arrow-up"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0">Bulk Upload Zone Country Mappings</h5>
                <span class="text-muted small">Upload CSV file to map countries to zones (Row-by-Row)</span>
              </div>
            </div>
            <button type="button" class="btn-close" :disabled="isUploading" @click="closeBulkUploadModal"></button>
          </div>

          <div class="modal-body px-4 py-3">
            <!-- Sample Template Info Card -->
            <div
              class="p-3 bg-light rounded-3 border mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
              <div>
                <div class="fw-semibold text-dark small">
                  <i class="bi bi-info-circle text-primary me-1"></i> Format: <code>zone_name, country_code,
                country_name</code>
                </div>
                <div class="text-muted small">
                  Matches by 2-letter ISO country code (e.g. US, IN, AE) or country name.
                </div>
              </div>
              <button type="button"
                class="btn btn-sm btn-outline-secondary text-nowrap rounded-2 d-flex align-items-center gap-1"
                @click="downloadSampleCsv">
                <i class="bi bi-download"></i>
                <span>Download Sample CSV</span>
              </button>
            </div>

            <!-- File Upload Dropzone -->
            <div v-if="!uploadResult" class="mb-3">
              <input ref="fileInputRef" type="file" accept=".csv, text/csv, text/plain" class="d-none"
                @change="onFileChange" />

              <div v-if="!uploadFile"
                class="border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer transition-all"
                :class="isDragOver ? 'border-primary bg-primary-subtle' : 'bg-light'"
                @dragover.prevent="isDragOver = true" @dragleave.prevent="isDragOver = false" @drop.prevent="onDropFile"
                @click="$refs.fileInputRef?.click()">
                <div class="fs-1 text-primary mb-2">
                  <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <div class="fw-semibold text-dark">Click or drag & drop CSV file here</div>
                <div class="text-muted small mt-1">Supports standard CSV (.csv) up to 10MB</div>
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
                <button type="button" class="btn btn-sm btn-outline-danger rounded-2" :disabled="isUploading"
                  @click="removeFile">
                  <i class="bi bi-trash me-1"></i> Remove
                </button>
              </div>
            </div>

            <!-- Upload Results Card -->
            <div v-if="uploadResult" class="mb-3">
              <div class="alert alert-success border-0 shadow-xs rounded-3 p-3 mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div>
                  <div class="fw-bold">Bulk Mapping Completed Successfully!</div>
                  <div class="small">The country mappings have been saved and applied to your zones.</div>
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
                    <div class="fs-5 fw-bold text-success">{{ uploadResult.mapped_count }}</div>
                    <div class="small text-success" style="font-size: 0.72rem;">MAPPED</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="p-2 bg-secondary-subtle rounded-3 border border-secondary-subtle">
                    <div class="fs-5 fw-bold text-secondary">{{ uploadResult.skipped_count }}</div>
                    <div class="small text-secondary" style="font-size: 0.72rem;">DUPLICATES / SKIPPED</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="p-2 bg-warning-subtle rounded-3 border border-warning-subtle">
                    <div class="fs-5 fw-bold text-warning">{{ uploadResult.unmatched_count }}</div>
                    <div class="small text-warning" style="font-size: 0.72rem;">UNMATCHED</div>
                  </div>
                </div>
              </div>

              <!-- Unmatched Rows Warning Details -->
              <div v-if="uploadResult.unmatched_rows && uploadResult.unmatched_rows.length > 0"
                class="p-3 bg-warning-subtle rounded-3 border border-warning">
                <div class="fw-semibold text-warning-emphasis small mb-2 d-flex align-items-center gap-1">
                  <i class="bi bi-exclamation-triangle-fill"></i> Unmatched Rows ({{ uploadResult.unmatched_count }}):
                </div>
                <div class="small text-dark font-monospace" style="max-height: 120px; overflow-y: auto;">
                  <div v-for="(msg, i) in uploadResult.unmatched_rows" :key="i" class="text-danger small py-0.5">
                    &bull; {{ msg }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
            <button v-if="!uploadResult" type="button" class="btn btn-light rounded-3 px-3" :disabled="isUploading"
              @click="closeBulkUploadModal">
              Cancel
            </button>
            <button v-else type="button" class="btn btn-secondary rounded-3 px-3"
              @click="uploadResult = null; uploadFile = null">
              Upload Another File
            </button>

            <button v-if="!uploadResult" type="button"
              class="btn btn-primary rounded-3 px-4 shadow-sm d-flex align-items-center gap-2"
              :disabled="!uploadFile || isUploading" @click="handleBulkUpload">
              <span v-if="isUploading" class="spinner-border spinner-border-sm" role="status"></span>
              <i v-if="!isUploading" class="bi bi-cloud-arrow-up"></i>
              <span>{{ isUploading ? 'Processing CSV...' : 'Upload & Map Countries' }}</span>
            </button>
            <button v-else type="button" class="btn btn-primary rounded-3 px-4 shadow-sm" @click="closeBulkUploadModal">
              Done
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
