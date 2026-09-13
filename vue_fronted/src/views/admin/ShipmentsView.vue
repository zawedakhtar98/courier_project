<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ShipmentsForm from './ShipmentsForm.vue'
import { getAllShipments } from '../../services/admin/ShipmentService'

const router = useRouter()
const route = useRoute()

const isAddingShipment = ref(false)
const shipmentInitialData = ref({})
const toastMessage = ref(null)

const handleSaveShipment = (record) => {
  shipments.value.unshift(record)
  toastMessage.value = `Shipment ${record.awbNo} added successfully!`
}

const fetchShipments = async () => {
  try {
    const response = await getAllShipments();
    if (response && response.data) {
      shipments.value = response.data.map(item => ({
        ...item,
        awbNo: item.awb_number || '-',
        shipDate: item.created_at ? new Date(item.created_at).toISOString().split('T')[0] : '-',
        destination: item.receiver_city || '-',
        serviceName: item.service_partner_id || '-', // Will need joining to get real name
        networkNo: '',
        pcs: 1, // DB might not have pcs directly, map appropriately if available
        actWeight: item.actual_weight || 0,
        chgWeight: item.chargeable_weight || 0,
        manifestNo: item.manifest_no || '',
        manifestDate: item.manifest_date || '-',
        mfStatus: item.status || 'Pending',
      }));
    }
  } catch (error) {
    console.error("Failed to fetch shipments:", error);
    showToast("Failed to load shipments.");
  }
}

onMounted(() => {
  if (route.query.action === 'new') {
    isAddingShipment.value = true
    shipmentInitialData.value = { ...route.query }
  }
  fetchShipments();
})

// Initial dataset based on the shipment table
const shipments = ref([])

// Filter & search states
const searchQuery = ref('')
const selectedService = ref('All')
const selectedStatus = ref('All')
// const toastMessage = ref(null)
const selectedShipment = ref(null)
const showDetailModal = ref(false)

// Action toast notification
const showToast = (message) => {
  toastMessage.value = message
  setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

// Copy AWB or Network Number
const copyToClipboard = (text, label = 'AWB') => {
  if (!text) return
  navigator.clipboard.writeText(text)
  showToast(`${label} ${text} copied to clipboard!`)
}

// Open detail modal
const viewDetails = (item) => {
  selectedShipment.value = item
  showDetailModal.value = true
}

// Service options for filter
const availableServices = computed(() => {
  const set = new Set()
  shipments.value.forEach((s) => {
    if (s.serviceName) set.add(s.serviceName)
  })
  return Array.from(set)
})

// Filtered shipments
const filteredShipments = computed(() => {
  return shipments.value.filter((item) => {
    const q = searchQuery.value.trim().toLowerCase()
    const matchesSearch =
      !q ||
      item.awbNo.toLowerCase().includes(q) ||
      item.destination.toLowerCase().includes(q) ||
      item.serviceName.toLowerCase().includes(q) ||
      item.networkNo.toLowerCase().includes(q) ||
      item.shipDate.toLowerCase().includes(q)

    const matchesService =
      selectedService.value === 'All' || item.serviceName === selectedService.value
    const matchesStatus =
      selectedStatus.value === 'All' || item.mfStatus === selectedStatus.value

    return matchesSearch && matchesService && matchesStatus
  })
})

// Summary metrics
const totalPcs = computed(() => shipments.value.reduce((acc, curr) => acc + curr.pcs, 0))
const totalChgWt = computed(() =>
  shipments.value.reduce((acc, curr) => acc + curr.chgWeight, 0).toFixed(2),
)
const pendingManifestCount = computed(
  () => shipments.value.filter((s) => s.mfStatus === 'Pending').length,
)
</script>

<template>
  <div class="shipment-view-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>&nbsp;
      </div>
      <div class="d-flex justify-content-md-end">
        <button type="button"
          class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2"
          @click="isAddingShipment = true; shipmentInitialData = {}">
          <i class="bi bi-plus-lg"></i>
          <span>Add New Shipment</span>
        </button>
      </div>
    </div>

    <!-- Toast Alert Notification -->
    <div v-if="toastMessage"
      class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-4 shadow-lg"
      style="z-index: 1060; min-width: 300px" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div>{{ toastMessage }}</div>
      </div>
      <button type="button" class="btn-close" aria-label="Close" @click="toastMessage = null"></button>
    </div>

    <!-- Main Card Container -->
    <template v-if="!isAddingShipment">
      <div class="custom-card p-4">
        <!-- Search and Filter Toolbar -->
        <div class="row g-3 align-items-center justify-content-between mb-4">
          <!-- Search Input -->
          <div class="col-12 col-md-5 col-lg-4">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input v-model="searchQuery" type="text" class="form-control border-start-0 bg-light"
                placeholder="Search AWB, destination, network..." />
              <button v-if="searchQuery" class="btn btn-light border border-start-0 text-muted" type="button"
                @click="searchQuery = ''">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <!-- Filters -->
          <div class="col-12 col-md-7 col-lg-6 d-flex gap-2 justify-content-md-end flex-wrap">
            <div class="d-flex align-items-center gap-2">
              <label class="text-muted small fw-medium mb-0">Service:</label>
              <select v-model="selectedService" class="form-select form-select-sm bg-light" style="width: auto;">
                <option value="All">All Services</option>
                <option v-for="service in availableServices" :key="service" :value="service">
                  {{ service }}
                </option>
              </select>
            </div>

            <div class="d-flex align-items-center gap-2">
              <label class="text-muted small fw-medium mb-0">Status:</label>
              <select v-model="selectedStatus" class="form-select form-select-sm bg-light" style="width: auto;">
                <option value="All">All Status</option>
                <option value="Pending">Pending</option>
                <option value="Manifested">Manifested</option>
                <option value="In Transit">In Transit</option>
                <option value="Delivered">Delivered</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Shipment Table wrapper -->
        <div class="table-responsive shipment-table-wrapper">
          <table class="table shipment-table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">AWBNO</th>
                <th scope="col">Ship Date</th>
                <th scope="col">Destination</th>
                <th scope="col">Service Name</th>
                <th scope="col" class="text-center">Pcs</th>
                <th scope="col" class="text-end">Act. Weight</th>
                <th scope="col" class="text-end">Chg. Weight</th>
                <th scope="col">Manifest No</th>
                <th scope="col">ManifestDate</th>
                <th scope="col">MfStatus</th>
                <th scope="col" class="text-center" style="min-width: 95px">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredShipments.length === 0">
                <td colspan="12" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                  <span>No shipments found matching your criteria.</span>
                </td>
              </tr>
              <tr v-for="item in filteredShipments" :key="item.awbNo">
                <!-- AWBNO -->
                <td class="fw-semibold text-dark">{{ item.awbNo }}</td>

                <!-- Ship Date -->
                <td>{{ item.shipDate }}</td>

                <!-- Destination -->
                <td class="fw-medium text-dark">{{ item.destination }}</td>

                <!-- Service Name -->
                <td>{{ item.serviceName }}</td>

                <!-- Pcs -->
                <td class="text-center">{{ item.pcs }}</td>

                <!-- Act. Weight -->
                <td class="text-end">{{ item.actWeight }}</td>

                <!-- Chg. Weight -->
                <td class="text-end">{{ item.chgWeight }}</td>

                <!-- Manifest No -->
                <td>{{ item.manifestNo || '' }}</td>

                <!-- ManifestDate -->
                <td>{{ item.manifestDate || '-' }}</td>

                <!-- MfStatus -->
                <td>
                  <span class="mf-status-pending" v-if="item.mfStatus === 'Pending'">
                    <span class="mf-icon">⌛</span>
                    <span>Pending</span>
                  </span>
                  <span class="mf-status-completed"
                    v-else-if="item.mfStatus === 'Manifested' || item.mfStatus === 'Delivered'">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    <span>{{ item.mfStatus }}</span>
                  </span>
                  <span class="text-primary fw-medium" v-else>
                    {{ item.mfStatus }}
                  </span>
                </td>

                <!-- Action -->
                <td class="text-center action-cell">
                  <div class="d-inline-flex align-items-center gap-2">
                    <!-- Green Document/Copy Button -->
                    <button type="button" class="btn-action-green" title="Copy AWB Number"
                      @click="copyToClipboard(item.awbNo, 'AWB')">
                      <i class="bi bi-copy"></i>
                    </button>

                    <!-- 3-Dots Dropdown Menu -->
                    <div class="dropdown">
                      <button class="btn-action-dots" type="button" :id="'actionMenu_' + item.awbNo"
                        data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"
                        title="More actions">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                        :aria-labelledby="'actionMenu_' + item.awbNo">
                        <li>
                          <button class="dropdown-item d-flex align-items-center gap-2 py-2" @click="viewDetails(item)">
                            <i class="bi bi-eye text-primary"></i>
                            <span>View Details</span>
                          </button>
                        </li>
                        <li>
                          <button class="dropdown-item d-flex align-items-center gap-2 py-2"
                            @click="copyToClipboard(item.networkNo || item.awbNo, 'Tracking No')">
                            <i class="bi bi-upc-scan text-success"></i>
                            <span>Copy Tracking </span>
                          </button>
                        </li>
                        <!-- <li>
                          <button class="dropdown-item d-flex align-items-center gap-2 py-2"
                            @click="showToast(`Generating shipping label for AWB: ${item.awbNo}...`)">
                            <i class="bi bi-printer text-secondary"></i>
                            <span>Print Label</span>
                          </button>
                        </li> -->
                        <li>
                          <hr class="dropdown-divider" />
                        </li>
                        <li>
                          <button class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger"
                            @click="showToast(`Cancelled shipment ${item.awbNo}`)">
                            <i class="bi bi-trash"></i>
                            <span>Delete Shipment</span>
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination / Footer Info -->
        <div
          class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-3 pt-3 border-top gap-2 text-muted small">
          <div>
            Showing <strong>1</strong> to <strong>{{ filteredShipments.length }}</strong> of
            <strong>{{ shipments.length }}</strong> shipments
          </div>
          <div class="d-flex align-items-center gap-1">
            <button class="btn btn-sm btn-light border px-2 py-1" disabled>
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-sm btn-primary px-3 py-1 fw-bold">1</button>
            <button class="btn btn-sm btn-light border px-2 py-1" disabled>
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Shipment Details Modal -->
      <div v-if="showDetailModal && selectedShipment" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom px-4 py-3">
              <div class="d-flex align-items-center gap-2">
                <div class="badge bg-primary fs-6 px-3 py-2">
                  AWB: {{ selectedShipment.awbNo }}
                </div>
                <span class="mf-status-pending ms-2">
                  <span class="mf-icon">⌛</span>
                  <span>{{ selectedShipment.mfStatus }}</span>
                </span>
              </div>
              <button type="button" class="btn-close" aria-label="Close" @click="showDetailModal = false"></button>
            </div>

            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="p-3 bg-light rounded-3">
                    <span class="text-muted small d-block">Ship Date</span>
                    <span class="fw-semibold text-dark">{{ selectedShipment.shipDate }}</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 bg-light rounded-3">
                    <span class="text-muted small d-block">Destination</span>
                    <span class="fw-semibold text-dark">{{ selectedShipment.destination }}</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 bg-light rounded-3">
                    <span class="text-muted small d-block">Service Name</span>
                    <span class="fw-semibold text-dark">{{ selectedShipment.serviceName }}</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 bg-light rounded-3">
                    <span class="text-muted small d-block">Network Number</span>
                    <span class="fw-semibold text-dark font-monospace">{{ selectedShipment.networkNo || 'N/A' }}</span>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="p-3 bg-light rounded-3 text-center">
                    <span class="text-muted small d-block">Packages (Pcs)</span>
                    <span class="fw-bold text-dark fs-5">{{ selectedShipment.pcs }}</span>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="p-3 bg-light rounded-3 text-center">
                    <span class="text-muted small d-block">Actual Weight</span>
                    <span class="fw-bold text-dark fs-5">{{ selectedShipment.actWeight }} kg</span>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="p-3 bg-light rounded-3 text-center">
                    <span class="text-muted small d-block">Chargeable Weight</span>
                    <span class="fw-bold text-dark fs-5">{{ selectedShipment.chgWeight }} kg</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 bg-light rounded-3">
                    <span class="text-muted small d-block">Manifest No</span>
                    <span class="fw-semibold text-dark">{{ selectedShipment.manifestNo || 'Not Generated' }}</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 bg-light rounded-3">
                    <span class="text-muted small d-block">Manifest Date</span>
                    <span class="fw-semibold text-dark">{{ selectedShipment.manifestDate || '-' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary rounded-3 px-3" @click="showDetailModal = false">
                Close
              </button>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary rounded-3 px-3"
                  @click="copyToClipboard(selectedShipment.awbNo, 'AWB')">
                  <i class="bi bi-copy me-1"></i> Copy AWB
                </button>
                <button type="button" class="btn btn-primary rounded-3 px-3"
                  @click="showToast('Printing Shipping Label...')">
                  <i class="bi bi-printer me-1"></i> Print Label
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
    <template v-else>
      <ShipmentsForm :initial-data="shipmentInitialData" @save-shipment="handleSaveShipment"
        @close="isAddingShipment = false; router.replace({ query: {} })" />
    </template>
  </div>
</template>

<style scoped>
.shipment-view-container {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

/* Table wrapper and scroll behavior */
.shipment-table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  border-radius: 8px;
  width: 100%;
  max-width: 100%;
}

.shipment-table {
  width: 100%;
  min-width: 1050px;
  border-collapse: collapse;
  white-space: nowrap;
  font-size: 0.85rem;
}

.shipment-table thead th {
  background-color: #f8fafc;
  color: #1e293b;
  font-weight: 700;
  font-size: 0.82rem;
  padding: 0.8rem 0.9rem;
  border-top: none;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
  white-space: nowrap;
}

.shipment-table tbody td {
  padding: 0.8rem 0.9rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  white-space: nowrap;
}

.shipment-table tbody tr:hover {
  background-color: #f8fafc;
}

/* Action column */
.action-cell {
  position: relative;
}

/* Status: Hourglass + Pending in orange */
.mf-status-pending {
  color: #f59e0b;
  font-weight: 600;
  font-size: 0.88rem;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  white-space: nowrap;
}

.mf-icon {
  font-size: 0.95rem;
  line-height: 1;
}

.mf-status-completed {
  color: #10b981;
  font-weight: 600;
  font-size: 0.88rem;
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

/* Action: Green Copy/File button */
.btn-action-green {
  background-color: #22c55e;
  color: #ffffff;
  border: none;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-action-green:hover {
  background-color: #16a34a;
  transform: scale(1.05);
}

.btn-action-green:active {
  transform: scale(0.95);
}

/* Action: 3-dots menu button */
.btn-action-dots {
  background: transparent;
  color: #64748b;
  border: none;
  width: 28px;
  height: 30px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.btn-action-dots:hover {
  background-color: #f1f5f9;
  color: #1e293b;
}

.btn-action-dots:focus {
  outline: none;
  box-shadow: none;
}

.dropdown-menu {
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  min-width: 200px;
  padding: 0.5rem;
  z-index: 1050;
}

.dropdown-item {
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  transition: background-color 0.15s ease;
}

.dropdown-item:hover {
  background-color: #f8fafc;
}
</style>
