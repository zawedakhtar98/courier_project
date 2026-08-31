<script>
export function defaultSlabData() {
  return [
    { weight: '0.500', zones: ['1,214', '1,206', '1,366', '1,289', '1,609', '1,788', '1,460', '3,309', '1,607', '2,471', '3,343', '1,586', '2,752', '1,642'] },
    { weight: '1.000', zones: ['1,461', '1,441', '1,657', '1,442', '1,983', '2,132', '1,684', '4,221', '1,822', '3,215', '3,854', '1,779', '3,416', '1,694'] },
    { weight: '1.500', zones: ['1,707', '1,705', '1,946', '1,594', '2,349', '2,467', '1,910', '5,123', '1,990', '3,572', '4,362', '1,959', '3,944', '2,006'] },
    { weight: '2.000', zones: ['1,953', '1,962', '2,235', '1,745', '2,713', '2,804', '2,135', '6,027', '2,227', '3,931', '4,867', '2,136', '4,472', '2,306'] },
    { weight: '2.500', zones: ['2,242', '2,228', '2,386', '1,895', '2,813', '3,143', '2,362', '6,934', '2,473', '4,292', '5,375', '2,388', '5,000', '2,619'] },
    { weight: '3.000', zones: ['2,433', '2,436', '2,651', '2,137', '3,049', '3,413', '2,667', '7,882', '2,732', '4,663', '5,590', '2,634', '5,489', '2,863'] },
    { weight: '3.500', zones: ['2,623', '2,644', '2,915', '2,381', '3,285', '3,684', '2,971', '8,830', '2,991', '5,036', '5,804', '2,880', '5,979', '3,109'] }
  ]
}
</script>

<script setup>
import { ref, computed } from 'vue'

const servicePartners = [
  'DHL EXPRESS',
  'FedEx International',
  'Aramex Worldwide',
  'UPS Worldwide',
  'DTDC Global',
]

const packageTypes = [
  'NONDOC',
  'DOC',
  'Parcel',
  'Heavy Cargo',
  'Fragile',
]

const rateTypes = [
  'Slab',
  'Flat',
  'Per KG',
]

// 14 Available Zone Masters
const availableZoneMasters = Array.from({ length: 14 }, (_, i) => ({
  id: i + 1,
  name: `Zone ${i + 1}`,
  code: `ZONE-${String(i + 1).padStart(2, '0')}`,
}))

// Main Rate Groups State
const rateGroups = ref([
  {
    id: 'grp-dhl-1',
    service: 'DHL EXPRESS',
    packageType: 'NONDOC',
    rateType: 'Slab',
    zoneCount: 14,
    status: 'Active',
    rows: defaultSlabData(),
  },
  {
    id: 'grp-fedex-1',
    service: 'FedEx International',
    packageType: 'DOC',
    rateType: 'Slab',
    zoneCount: 14,
    status: 'Active',
    rows: [
      { weight: '0.500', zones: ['950', '980', '1,100', '1,050', '1,320', '1,450', '1,200', '2,800', '1,350', '2,100', '2,900', '1,300', '2,400', '1,400'] },
      { weight: '1.000', zones: ['1,180', '1,220', '1,390', '1,290', '1,650', '1,800', '1,450', '3,600', '1,590', '2,750', '3,400', '1,520', '2,950', '1,500'] },
      { weight: '1.500', zones: ['1,420', '1,460', '1,680', '1,480', '1,980', '2,150', '1,700', '4,400', '1,800', '3,200', '3,900', '1,750', '3,500', '1,800'] },
    ],
  },
])

const expandedCouriers = ref({
  'grp-dhl-1': true,
  'grp-fedex-1': false,
})

const toggleCourier = (id) => {
  expandedCouriers.value[id] = !expandedCouriers.value[id]
}

const searchQuery = ref('')
const filterStatus = ref('All')
const alertMessage = ref(null)

// Modal states
const showRateSlabModal = ref(false)
const isEditing = ref(false)
const editingGroupId = ref(null)
const editingRowIndex = ref(null)

const formRateSlab = ref({
  servicePartner: 'DHL EXPRESS',
  packageType: 'NONDOC',
  rateType: 'Slab',
  weightFrom: '0.00',
  weightTo: '0.500',
  currency: '₹',
  status: 'Active',
  selectedZones: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
  zoneRates: {},
})

const bulkRateValue = ref('')
const zoneSearchFilter = ref('')

const sortedSelectedZones = computed(() => {
  return [...formRateSlab.value.selectedZones].sort((a, b) => a - b)
})

const filteredZoneMastersList = computed(() => {
  if (!zoneSearchFilter.value.trim()) return availableZoneMasters
  const q = zoneSearchFilter.value.toLowerCase().trim()
  return availableZoneMasters.filter(
    (z) => z.name.toLowerCase().includes(q) || z.code.toLowerCase().includes(q)
  )
})

const isZoneSelected = (zoneId) => {
  return formRateSlab.value.selectedZones.includes(zoneId)
}

const toggleZone = (zoneId) => {
  const idx = formRateSlab.value.selectedZones.indexOf(zoneId)
  if (idx > -1) {
    formRateSlab.value.selectedZones.splice(idx, 1)
  } else {
    formRateSlab.value.selectedZones.push(zoneId)
    if (formRateSlab.value.zoneRates[zoneId] === undefined) {
      formRateSlab.value.zoneRates[zoneId] = bulkRateValue.value || ''
    }
  }
}

const selectAllZones = () => {
  formRateSlab.value.selectedZones = availableZoneMasters.map((z) => z.id)
}

const deselectAllZones = () => {
  formRateSlab.value.selectedZones = []
}

const removeSelectedZone = (zoneId) => {
  const idx = formRateSlab.value.selectedZones.indexOf(zoneId)
  if (idx > -1) {
    formRateSlab.value.selectedZones.splice(idx, 1)
  }
}

const applyBulkRateToSelected = () => {
  if (!bulkRateValue.value) return
  formRateSlab.value.selectedZones.forEach((zId) => {
    formRateSlab.value.zoneRates[zId] = bulkRateValue.value
  })
  showAlert(
    `Applied ${formRateSlab.value.currency}${bulkRateValue.value} to ${formRateSlab.value.selectedZones.length} selected zones.`
  )
}

const showAlert = (msg) => {
  alertMessage.value = msg
  setTimeout(() => {
    alertMessage.value = null
  }, 3500)
}

const openAddModal = () => {
  isEditing.value = false
  editingGroupId.value = null
  editingRowIndex.value = null
  bulkRateValue.value = ''
  zoneSearchFilter.value = ''

  const allZones = availableZoneMasters.map((z) => z.id)
  const defaultRates = {
    1: '1214', 2: '1206', 3: '1366', 4: '1289', 5: '1609', 6: '1788', 7: '1460',
    8: '3309', 9: '1607', 10: '2471', 11: '3343', 12: '1586', 13: '2752', 14: '1642'
  }

  formRateSlab.value = {
    servicePartner: 'DHL EXPRESS',
    packageType: 'NONDOC',
    rateType: 'Slab',
    weightFrom: '0.00',
    weightTo: '0.500',
    currency: '₹',
    status: 'Active',
    selectedZones: allZones,
    zoneRates: defaultRates,
  }
  showRateSlabModal.value = true
}

const openEditModal = (group, rowIdx) => {
  isEditing.value = true
  editingGroupId.value = group.id
  editingRowIndex.value = rowIdx ?? 0
  bulkRateValue.value = ''
  zoneSearchFilter.value = ''

  const row = group.rows[rowIdx ?? 0]
  const ratesMap = {}
  const selected = []

  if (row) {
    row.zones.forEach((val, idx) => {
      ratesMap[idx + 1] = String(val).replace(/,/g, '')
      selected.push(idx + 1)
    })
  }

  formRateSlab.value = {
    servicePartner: group.service,
    packageType: group.packageType,
    rateType: group.rateType,
    weightFrom: '0.00',
    weightTo: row ? String(row.weight) : '0.500',
    currency: '₹',
    status: group.status,
    selectedZones: selected.length > 0 ? selected : availableZoneMasters.map((z) => z.id),
    zoneRates: ratesMap,
  }
  showRateSlabModal.value = true
}

const saveRateSlab = () => {
  if (!formRateSlab.value.servicePartner) {
    alert('Please select a Service Partner.')
    return
  }

  if (formRateSlab.value.selectedZones.length === 0) {
    alert('Please select at least one Zone Master.')
    return
  }

  // Construct zones array for 14 zones
  const zoneValues = []
  for (let i = 1; i <= 14; i++) {
    const rateVal = formRateSlab.value.zoneRates[i]
    if (rateVal !== undefined && rateVal !== '') {
      const numVal = Number(String(rateVal).replace(/,/g, ''))
      zoneValues.push(!isNaN(numVal) ? numVal.toLocaleString() : String(rateVal))
    } else {
      zoneValues.push('—')
    }
  }

  const weightFormatted = Number(formRateSlab.value.weightTo || 0.5).toFixed(3)
  const newRow = {
    weight: weightFormatted,
    zones: zoneValues,
  }

  let targetGroup = rateGroups.value.find(
    (g) => g.service === formRateSlab.value.servicePartner && g.packageType === formRateSlab.value.packageType
  )

  if (!targetGroup) {
    const newGroupId = `grp-${Date.now()}`
    targetGroup = {
      id: newGroupId,
      service: formRateSlab.value.servicePartner,
      packageType: formRateSlab.value.packageType,
      rateType: formRateSlab.value.rateType,
      zoneCount: 14,
      status: formRateSlab.value.status,
      rows: [newRow],
    }
    rateGroups.value.unshift(targetGroup)
    expandedCouriers.value[newGroupId] = true
    showAlert(`Mapped new rate slab for ${formRateSlab.value.servicePartner} successfully.`)
  } else {
    if (isEditing.value && editingRowIndex.value !== null && targetGroup.rows[editingRowIndex.value]) {
      targetGroup.rows[editingRowIndex.value] = newRow
      showAlert(`Updated rate slab row for ${targetGroup.service}.`)
    } else {
      const existingIdx = targetGroup.rows.findIndex((r) => String(r.weight) === weightFormatted)
      if (existingIdx > -1) {
        targetGroup.rows[existingIdx] = newRow
      } else {
        targetGroup.rows.push(newRow)
        targetGroup.rows.sort((a, b) => Number(a.weight) - Number(b.weight))
      }
      showAlert(`Saved rate slab for ${targetGroup.service} (${formRateSlab.value.packageType}).`)
    }
  }

  showRateSlabModal.value = false
}

const toggleGroupStatus = (group) => {
  group.status = group.status === 'Active' ? 'Inactive' : 'Active'
  showAlert(`${group.service} rate slab is now ${group.status}.`)
}

const deleteGroup = (group) => {
  if (confirm(`Are you sure you want to delete rate slab for "${group.service}"?`)) {
    rateGroups.value = rateGroups.value.filter((g) => g.id !== group.id)
    showAlert(`Rate slab for "${group.service}" deleted successfully.`)
  }
}

const filteredRateGroups = computed(() => {
  return rateGroups.value.filter((g) => {
    const matchesSearch =
      g.service.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      g.packageType.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesStatus = filterStatus.value === 'All' || g.status === filterStatus.value
    return matchesSearch && matchesStatus
  })
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
          <span class="text-secondary small fw-medium">Rate Slab Master</span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Map Service Partner Rate Slabs</h4>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold"
          @click="openAddModal">
          <i class="bi bi-plus-circle"></i>
          <span>Map New Partner Rate Slab</span>
        </button>
      </div>
    </div>

    <!-- Alert Notification -->
    <transition name="fade">
      <div v-if="alertMessage" class="alert alert-success d-flex align-items-center mb-4 shadow-sm rounded-3 py-2 px-3"
        role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div class="small fw-medium">{{ alertMessage }}</div>
      </div>
    </transition>

    <!-- Filters & Table Section -->
    <div class="custom-card p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <!-- Search -->
        <div class="input-group" style="max-width: 380px;">
          <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input v-model="searchQuery" type="text" class="form-control bg-light border-start-0"
            placeholder="Search service partner or package type..." />
        </div>

        <!-- Status Filter Pills -->
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Status:</span>
          <button v-for="status in ['All', 'Active', 'Inactive']" :key="status" type="button"
            class="btn btn-sm rounded-pill px-3"
            :class="filterStatus === status ? 'btn-primary shadow-sm' : 'btn-light text-secondary'"
            @click="filterStatus = status">
            {{ status }}
          </button>
        </div>
      </div>

      <!-- Rate Slabs Table -->
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="40"></th>
              <th>Service Partner</th>
              <th>Package Type</th>
              <th>Rate Type</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="filteredRateGroups.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                No matching service partner rate slabs found.
              </td>
            </tr>

            <template v-for="group in filteredRateGroups" :key="group.id">
              <!-- ACCORDION PARENT ROW -->
              <tr class="main-row cursor-pointer" @click="toggleCourier(group.id)"
                :aria-expanded="expandedCouriers[group.id]">
                <td class="text-center">
                  <span class="accordion-icon" :class="{ rotated: !expandedCouriers[group.id] }">▼</span>
                </td>

                <td>
                  <strong class="text-dark">{{ group.service }}</strong>
                </td>

                <td>
                  <span class="nondoc-badge">{{ group.packageType }}</span>
                </td>

                <td>
                  <span class="badge bg-light text-dark border font-monospace">{{ group.rateType }}</span>
                </td>

                <td>
                  <button type="button" class="badge border-0 rounded-pill px-3 py-1 cursor-pointer"
                    :class="group.status === 'Active' ? 'bg-success text-white' : 'bg-secondary text-white'"
                    @click.stop="toggleGroupStatus(group)" title="Click to toggle status">
                    {{ group.status }}
                  </button>
                </td>

                <td class="text-end">
                  <div class="d-flex align-items-center justify-content-end gap-1" @click.stop>
                    <button type="button" class="btn btn-sm btn-light text-primary p-2 rounded-2"
                      @click="openEditModal(group)" title="Add/Edit Slab">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light text-danger p-2 rounded-2"
                      @click="deleteGroup(group)" title="Delete Slab">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- INNER SLABS ACCORDION CONTENT -->
              <tr v-show="expandedCouriers[group.id]">
                <td :colspan="6" class="p-0">
                  <div class="inner-table p-3 bg-light border-top border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                      <span class="small fw-bold text-secondary text-uppercase tracking-wider">
                        <i class="bi bi-table me-1 text-primary"></i> {{ group.service }} &bull; Weight Slabs (14 Zones)
                      </span>
                      <span class="badge bg-white text-muted border small">
                        {{ group.rows.length }} Slab Rows
                      </span>
                    </div>

                    <div class="rate-table-wrapper rounded-3 border bg-white overflow-hidden shadow-sm">
                      <table class="table table-responsive table-bordered table-hover mb-0">
                        <thead class="bg-light">
                          <tr>
                            <th class="sticky-col">To Weight (kg)</th>
                            <th v-for="zone in group.zoneCount" :key="zone">
                              Zone {{ zone }}
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(row, index) in group.rows" :key="index">
                            <td class="sticky-col weight-cell fw-bold">{{ row.weight }}</td>
                            <td v-for="(val, zIdx) in row.zones" :key="zIdx">
                              {{ val }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: MAP SERVICE PARTNER RATE SLAB (DYNAMIC ZONE RATES) -->
    <div v-if="showRateSlabModal" class="modal-backdrop fade show"></div>
    <div v-if="showRateSlabModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <!-- Modal Header -->
          <div class="modal-header border-bottom px-4 py-3 bg-light">
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0">
                <i :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'" class="text-primary me-2"></i>
                {{ isEditing ? 'Edit Map Service Partner Rate Slab' : 'Map Service Partner Rate Slab' }}
              </h5>
              <span class="text-muted small">
                Configure rate slabs, multi-select zones, and dynamically set zone pricing.
              </span>
            </div>
            <button type="button" class="btn-close" @click="showRateSlabModal = false"></button>
          </div>

          <!-- Modal Body Form -->
          <form @submit.prevent="saveRateSlab" class="d-flex flex-column" style="overflow-y: auto;">
            <div class="modal-body px-4 py-4">
              <!-- Top Row: General Settings -->
              <div class="card border border-light-subtle rounded-3 p-3 mb-4 bg-white shadow-sm">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                  <i class="bi bi-sliders text-primary"></i> Slab Header Information
                </h6>
                <div class="row g-3">
                  <!-- Service Partner -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">Service Partner <span
                        class="text-danger">*</span></label>
                    <select v-model="formRateSlab.servicePartner" class="form-select" required>
                      <option v-for="p in servicePartners" :key="p" :value="p">{{ p }}</option>
                    </select>
                  </div>

                  <!-- Package Type -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">Package Type <span class="text-danger">*</span></label>
                    <select v-model="formRateSlab.packageType" class="form-select" required>
                      <option v-for="pt in packageTypes" :key="pt" :value="pt">{{ pt }}</option>
                    </select>
                  </div>

                  <!-- Rate Type -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">Rate Type <span class="text-danger">*</span></label>
                    <select v-model="formRateSlab.rateType" class="form-select" required>
                      <option v-for="rt in rateTypes" :key="rt" :value="rt">{{ rt }}</option>
                    </select>
                  </div>

                  <!-- Weight From -->
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Weight From (kg)</label>
                    <div class="input-group">
                      <input v-model="formRateSlab.weightFrom" type="number" step="0.001" min="0" class="form-control"
                        placeholder="0.000" required />
                      <span class="input-group-text bg-light small">kg</span>
                    </div>
                  </div>

                  <!-- Weight To -->
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Weight To (kg) <span
                        class="text-danger">*</span></label>
                    <div class="input-group">
                      <input v-model="formRateSlab.weightTo" type="number" step="0.001" min="0.001" class="form-control"
                        placeholder="0.500" required />
                      <span class="input-group-text bg-light small">kg</span>
                    </div>
                  </div>

                  <!-- Currency -->
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Currency Prefix</label>
                    <select v-model="formRateSlab.currency" class="form-select">
                      <option value="₹">₹ (INR)</option>
                      <option value="$">$ (USD)</option>
                      <option value="€">€ (EUR)</option>
                      <option value="£">£ (GBP)</option>
                      <option value="AED ">AED</option>
                    </select>
                  </div>

                  <!-- Status -->
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select v-model="formRateSlab.status" class="form-select">
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Zone Master Multi-Select Section -->
              <div class="card border border-light-subtle rounded-3 p-3 mb-4 bg-white shadow-sm">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                  <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                      <i class="bi bi-grid-3x3-gap-fill text-primary"></i> Zone Master (Multi-Select)
                      <span class="badge bg-primary rounded-pill small ms-1">
                        {{ formRateSlab.selectedZones.length }} / {{ availableZoneMasters.length }} Selected
                      </span>
                    </h6>
                    <span class="text-muted small">Select zones to dynamically generate their rate inputs below.</span>
                  </div>

                  <!-- Quick Select All / Clear Buttons -->
                  <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="max-width: 180px;">
                      <span class="input-group-text bg-light border-end-0"><i
                          class="bi bi-search text-muted"></i></span>
                      <input v-model="zoneSearchFilter" type="text" class="form-control bg-light border-start-0"
                        placeholder="Filter zones..." />
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                      @click="selectAllZones">
                      <i class="bi bi-check-all me-1"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                      @click="deselectAllZones">
                      Clear
                    </button>
                  </div>
                </div>

                <!-- Zone Checkbox Chips Grid -->
                <div class="zone-chips-container p-2 rounded-3 bg-light border">
                  <div class="row g-2">
                    <div v-for="zone in filteredZoneMastersList" :key="zone.id"
                      class="col-6 col-sm-4 col-md-3 col-lg-2">
                      <div
                        class="zone-chip p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer transition-all"
                        :class="{
                          'border-primary bg-primary text-white shadow-sm': isZoneSelected(zone.id),
                          'bg-white text-dark': !isZoneSelected(zone.id),
                        }" @click="toggleZone(zone.id)">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                          <input type="checkbox" class="form-check-input m-0 cursor-pointer"
                            :checked="isZoneSelected(zone.id)" @click.stop="toggleZone(zone.id)" />
                          <span class="small fw-semibold text-truncate">{{ zone.name }}</span>
                        </div>
                        <span class="badge font-monospace small px-1 py-0"
                          :class="isZoneSelected(zone.id) ? 'bg-white text-primary' : 'bg-light text-muted'"
                          style="font-size: 0.65rem;">
                          {{ zone.code }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Dynamic Zone Rate Inputs Section -->
              <div class="card border border-light-subtle rounded-3 p-3 bg-white shadow-sm">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                  <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                      <i class="bi bi-currency-dollar text-success"></i> Dynamic Zone Rates
                      <span
                        class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">
                        {{ formRateSlab.selectedZones.length }} Inputs Generated
                      </span>
                    </h6>
                    <span class="text-muted small">
                      Enter price for each selected zone (Weight: {{ formRateSlab.weightFrom }}kg to {{
                        formRateSlab.weightTo }}kg)
                    </span>
                  </div>

                  <!-- Quick Bulk Fill Tool -->
                  <div v-if="formRateSlab.selectedZones.length > 0"
                    class="d-flex align-items-center gap-2 p-1 bg-light rounded-3 border">
                    <span class="small text-muted fw-semibold ps-2 d-none d-sm-inline">Quick Bulk Rate:</span>
                    <div class="input-group input-group-sm" style="max-width: 140px;">
                      <span class="input-group-text">{{ formRateSlab.currency }}</span>
                      <input v-model="bulkRateValue" type="number" class="form-control" placeholder="e.g. 1500" />
                    </div>
                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-2" @click="applyBulkRateToSelected"
                      :disabled="!bulkRateValue" title="Apply rate to all selected zones">
                      Apply to All
                    </button>
                  </div>
                </div>

                <!-- Empty State if no zones selected -->
                <div v-if="formRateSlab.selectedZones.length === 0"
                  class="p-5 text-center bg-light rounded-3 border border-dashed">
                  <i class="bi bi-exclamation-circle fs-1 text-warning d-block mb-2"></i>
                  <h6 class="fw-bold text-dark">No Zones Selected</h6>
                  <p class="text-muted small mb-3">
                    Please select one or more zones from the Zone Master above to dynamically create rate input fields.
                  </p>
                  <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" @click="selectAllZones">
                    <i class="bi bi-check-all me-1"></i> Select All 14 Zones
                  </button>
                </div>

                <!-- Dynamic Rate Input Cards Grid -->
                <div v-else class="row g-3">
                  <div v-for="zoneId in sortedSelectedZones" :key="zoneId" class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="dynamic-rate-box p-3 rounded-3 border bg-light h-100 position-relative">
                      <!-- Remove zone button -->
                      <button type="button" class="btn btn-link p-0 text-muted position-absolute top-0 end-0 mt-1 me-2"
                        @click="removeSelectedZone(zoneId)" title="Deselect Zone">
                        <i class="bi bi-x-circle-fill text-danger opacity-50 hover-opacity-100"></i>
                      </button>

                      <div class="d-flex align-items-center gap-1 mb-2">
                        <span class="fw-bold small text-dark">Zone {{ zoneId }}</span>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace"
                          style="font-size: 0.65rem;">
                          ZN-{{ String(zoneId).padStart(2, '0') }}
                        </span>
                      </div>

                      <label class="form-label text-muted small mb-1" style="font-size: 0.75rem;">Rate Amount</label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white fw-bold text-secondary">{{ formRateSlab.currency
                          }}</span>
                        <input v-model="formRateSlab.zoneRates[zoneId]" type="text"
                          class="form-control bg-white fw-semibold" placeholder="0.00" required />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
              <div class="small text-muted">
                <span class="fw-semibold text-primary">{{ formRateSlab.selectedZones.length }} zones</span> ready to map
              </div>
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light rounded-3 px-3 border" @click="showRateSlabModal = false">
                  Cancel
                </button>
                <button type="submit"
                  class="btn btn-primary rounded-3 px-4 shadow-sm fw-semibold d-flex align-items-center gap-1">
                  <i class="bi bi-check2"></i>
                  <span>{{ isEditing ? 'Save Changes' : 'Submit Rate Slab' }}</span>
                </button>
              </div>
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

.tracking-wider {
  letter-spacing: 0.05em;
}

.hover-opacity-100:hover {
  opacity: 1 !important;
}

.nondoc-badge {
  background: #e9eef4;
  color: #526170;
  border-radius: 15px;
  padding: 4px 12px;
  font-size: 13px;
  font-weight: 600;
}

.accordion-icon {
  display: inline-block;
  font-size: 11px;
  transition: transform 0.25s ease;
  color: #6c757d;
}

.accordion-icon.rotated {
  transform: rotate(-90deg);
}

.rate-table-wrapper {
  overflow-x: auto;
}

.rate-table {
  min-width: 1300px;
  font-size: 13px;
}

.rate-table th,
.rate-table td {
  padding: 10px 14px;
  text-align: center;
  white-space: nowrap;
}

.rate-table thead th {
  background: #f8f9fa;
  color: #495057;
  font-weight: 600;
}

.rate-table .sticky-col {
  position: sticky;
  left: 0;
  background: #fff;
  z-index: 2;
  text-align: left;
}

.rate-table thead .sticky-col {
  background: #f8f9fa;
  z-index: 3;
}

.weight-cell {
  color: #0d6efd;
}

.zone-chip {
  user-select: none;
}

.dynamic-rate-box {
  border: 1px solid #dee2e6 !important;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.dynamic-rate-box:hover {
  border-color: #0d6efd !important;
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.08);
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
