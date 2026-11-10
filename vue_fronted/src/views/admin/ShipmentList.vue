<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getShipments, deleteShipment } from '@/services/admin/shipmentService'
import { getAllServicePartners } from '@/services/admin/servicePartner'

const router = useRouter()

// Reactive shipment list & loading states
const shipments = ref([])
const isLoading = ref(false)
const servicePartners = ref([])

// Pagination state
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
})

// Filter & search states
const searchQuery = ref('')
const selectedService = ref('All')
const selectedStatus = ref('All')
const toastMessage = ref(null)
// Modal states
const selectedShipment = ref(null)
const showDetailModal = ref(false)

// Action Modal States: Track Order & Print Invoice
const trackingShipment = ref(null)
const showTrackModal = ref(false)
const invoiceShipment = ref(null)
const showInvoiceModal = ref(false)

// Action toast notification
const showToast = (message) => {
  toastMessage.value = message
  setTimeout(() => {
    toastMessage.value = null
  }, 3500)
}

// Copy to clipboard helper
const copyToClipboard = (text, label = 'AWB') => {
  if (!text) return
  navigator.clipboard.writeText(text)
  showToast(`${label} ${text} copied to clipboard!`)
}

// Date formatter: DD/MM/YYYY
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return dateStr
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}/${month}/${year}`
}

// Action 1: View Details
const viewDetails = (item) => {
  selectedShipment.value = item
  showDetailModal.value = true
}

// Action 2: Track Order
const handleTrackOrder = (item) => {
  trackingShipment.value = item
  showTrackModal.value = true
}

// Action 3: Order Cloning
const handleOrderCloning = (item) => {
  const clonePayload = {
    destination: item.destination_country_id || item.destination_country?.id || item.destination,
    service_partner: item.service_partner_id || item.service_partner?.id,
    packageType: item.package_type || 'NONDOC',
    sender_name: item.sender_name || '',
    sender_company_name: item.sender_company_name || '',
    sender_address_line_1: item.sender_address_line_1 || '',
    sender_address_line_2: item.sender_address_line_2 || '',
    sender_city: item.sender_city || '',
    sender_state: item.sender_state || '',
    sender_pincode: item.sender_pincode || '',
    sender_mobile: item.sender_mobile || '',
    sender_email: item.sender_email || '',
    sender_kyc_type: item.sender_kyc_type || '',
    sender_kyc_number: item.sender_kyc_number || '',
    consignee_name: item.consignee_name || '',
    consignee_company_name: item.consignee_company_name || '',
    consignee_address_line_1: item.consignee_address_line_1 || '',
    consignee_address_line_2: item.consignee_address_line_2 || '',
    consignee_city: item.consignee_city || '',
    consignee_state: item.consignee_state || '',
    consignee_pincode: item.consignee_pincode || '',
    consignee_mobile_no: item.consignee_mobile_no || '',
    consignee_email: item.consignee_email || '',
    boxes: item.boxes && item.boxes.length > 0
      ? item.boxes.map(b => ({
        actWt: b.actual_weight,
        length: b.length,
        width: b.width,
        height: b.height
      }))
      : [{ actWt: item.total_actual_weight || 1, length: 10, width: 10, height: 10 }]
  }

  sessionStorage.setItem('cloned_shipment_data', JSON.stringify(clonePayload))
  showToast(`Cloning order ${item.shipment_no || item.awb_number}...`)
  router.push({
    name: 'admin-add-new-shipment',
    query: {
      isCloned: '1',
      destination: clonePayload.destination,
      service_partner: clonePayload.service_partner,
      packageType: clonePayload.packageType,
      pincode: clonePayload.consignee_pincode,
      city: clonePayload.consignee_city,
      state: clonePayload.consignee_state,
      boxes: JSON.stringify(clonePayload.boxes)
    }
  })
}

// Action 4: Print Invoice
const handlePrintInvoice = (item) => {
  invoiceShipment.value = item
  showInvoiceModal.value = true
}

const printDirectInvoice = () => {
  window.print()
}

const defaultInvoiceItems = (shipment) => {
  if (shipment?.invoice?.items && shipment.invoice.items.length > 0) {
    return shipment.invoice.items
  }
  return [
    {
      box_no: 1,
      description: shipment.package_type === 'DOC' ? 'DOCUMENTS / SAMPLES' : 'COMMERCIAL SHIPMENT GOODS',
      hsn_code: '49019900',
      unit_type: 'PCS',
      quantity: shipment.total_box || (shipment.boxes ? shipment.boxes.length : 1),
      unit_price: 100,
      total_price: (shipment.total_box || (shipment.boxes ? shipment.boxes.length : 1)) * 100
    }
  ]
}

const totalCalculatedInvoice = (shipment) => {
  if (shipment?.invoice?.total_invoice_amount) {
    return parseFloat(shipment.invoice.total_invoice_amount).toFixed(2)
  }
  const items = defaultInvoiceItems(shipment)
  const sum = items.reduce((acc, it) => acc + (parseFloat(it.total_price || it.amount) || 0), 0)
  return sum.toFixed(2)
}

// Fetch shipments dynamically from API
const fetchShipments = async (page = 1) => {
  isLoading.value = true
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      search: searchQuery.value.trim(),
      service: selectedService.value,
      status: selectedStatus.value,
    }
    const response = await getShipments(params)
    if (response && response.status === 'success') {
      const paginatedData = response.data
      shipments.value = paginatedData.data || []
      pagination.value = {
        current_page: paginatedData.current_page || 1,
        last_page: paginatedData.last_page || 1,
        per_page: paginatedData.per_page || 15,
        total: paginatedData.total || 0,
        from: paginatedData.from || 0,
        to: paginatedData.to || 0,
      }
    }
  } catch (error) {
    console.error('Failed to load shipments:', error)
    showToast('Failed to load shipments from server.')
  } finally {
    isLoading.value = false
  }
}

// Fetch service partners for dropdown filter
const loadServicePartners = async () => {
  try {
    const resp = await getAllServicePartners(100, 1)
    if (resp && resp.status === 'success') {
      servicePartners.value = resp.data || []
    }
  } catch (err) {
    console.error('Failed to load service partners:', err)
  }
}

// Delete shipment
const handleDeleteShipment = async (item) => {
  const awb = item.shipment_no || item.awb_number || item.id
  if (!confirm(`Are you sure you want to delete shipment ${awb}? This cannot be undone.`)) {
    return
  }
  try {
    const res = await deleteShipment(item.id)
    if (res && res.status === 'success') {
      showToast(`Shipment ${awb} deleted successfully!`)
      fetchShipments(pagination.value.current_page)
    } else {
      showToast(res?.message || 'Failed to delete shipment.')
    }
  } catch (err) {
    console.error('Failed to delete shipment:', err)
    showToast(err?.response?.data?.message || 'Failed to delete shipment.')
  }
}

// Debounced search watcher
let searchDebounceTimer = null
watch(searchQuery, () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    fetchShipments(1)
  }, 350)
})

// Filter change watchers
watch([selectedService, selectedStatus], () => {
  fetchShipments(1)
})

// Pagination navigation
const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page && page !== pagination.value.current_page) {
    fetchShipments(page)
  }
}

// Summary metrics computed from current data
const totalPcs = computed(() => {
  return shipments.value.reduce((acc, curr) => acc + (parseInt(curr.total_box) || (curr.boxes ? curr.boxes.length : 1)), 0)
})

const totalChgWt = computed(() => {
  return shipments.value
    .reduce((acc, curr) => acc + (parseFloat(curr.total_chargeable_weight) || 0), 0)
    .toFixed(2)
})

onMounted(async () => {
  await Promise.all([
    fetchShipments(1),
    loadServicePartners(),
  ])
})
</script>

<template>
  <div class="shipment-view-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h4 class="fw-bold mb-1 text-dark">Shipment List</h4>
        <p class="text-muted small mb-0">View, search, and manage all booked courier shipments</p>
      </div>
      <div class="d-flex justify-content-md-end gap-2">
        <button type="button"
          class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-2"
          @click="fetchShipments(pagination.current_page)" :disabled="isLoading" title="Refresh List">
          <i class="bi bi-arrow-clockwise" :class="{ 'spin-icon': isLoading }"></i>
          <span>Refresh</span>
        </button>
        <button type="button"
          class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2"
          @click="router.push({ name: 'add-new-shipment' })">
          <i class="bi bi-plus-lg"></i>
          <span>Add New Shipment</span>
        </button>
      </div>
    </div>

    <!-- Toast Alert Notification -->
    <div v-if="toastMessage"
      class="alert alert-dark alert-dismissible fade show position-fixed top-0 end-0 m-4 shadow-lg text-white"
      style="z-index: 1060; min-width: 320px; background-color: #1e293b;" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill text-info fs-5"></i>
        <div>{{ toastMessage }}</div>
      </div>
      <button type="button" class="btn-close btn-close-white" aria-label="Close" @click="toastMessage = null"></button>
    </div>

    <!-- Main Card Container -->
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
              placeholder="Search AWB, Shipper, Consignee, Destination..." />
            <button v-if="searchQuery" class="btn btn-light border border-start-0 text-muted" type="button"
              @click="searchQuery = ''">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="col-12 col-md-7 col-lg-7 d-flex gap-2 justify-content-md-end flex-wrap align-items-center">
          <div class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-medium mb-0">Service:</label>
            <select v-model="selectedService" class="form-select form-select-sm bg-light" style="width: auto;">
              <option value="All">All Services</option>
              <option v-for="sp in servicePartners" :key="sp.id" :value="sp.id">
                {{ sp.name }}
              </option>
            </select>
          </div>

          <div class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-medium mb-0">Status:</label>
            <select v-model="selectedStatus" class="form-select form-select-sm bg-light" style="width: auto;">
              <option value="All">All Status</option>
              <option value="created">Created</option>
              <option value="pickup_in_progress">Pickup In Progress</option>
              <option value="ready_to_ship">Ready To Ship</option>
              <option value="in_transit">In Transit</option>
              <option value="delivered">Delivered</option>
              <option value="cancel">Cancelled</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Shipment Table matching the uploaded image design -->
      <div class="table-responsive shipment-table-wrapper border rounded-3">
        <table class="table shipment-table align-middle mb-0">
          <thead>
            <tr>
              <!-- Added SR No before AWB Number as requested -->
              <th scope="col" class="text-center" style="width: 65px;">SR No</th>
              <th scope="col">Awb Number</th>
              <th scope="col">Sender</th>
              <th scope="col">Consignee</th>
              <th scope="col">Booking Date</th>
              <th scope="col">Forwarding Number</th>
              <th scope="col">Service</th>
              <th scope="col">Origin Hub</th>
              <th scope="col">Destination</th>
              <th scope="col">Added By</th>
              <th scope="col" class="text-center" style="width: 80px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading Row -->
            <tr v-if="isLoading">
              <td colspan="11" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span class="fw-medium">Loading shipments...</span>
              </td>
            </tr>

            <!-- Empty Row -->
            <tr v-else-if="shipments.length === 0">
              <td colspan="11" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <span class="fw-medium">No shipments found.</span>
              </td>
            </tr>

            <!-- Dynamic Shipment Rows -->
            <tr v-for="(item, index) in shipments" :key="item.id || item.shipment_no">
              <!-- SR No -->
              <td class="text-center fw-semibold text-secondary">
                {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
              </td>

              <!-- Awb Number -->
              <td class="fw-bold text-dark text-nowrap">
                <span class="cursor-pointer text-primary" @click="viewDetails(item)"
                  :title="'View ' + (item.shipment_no || item.awb_number)">
                  {{ item.shipment_no || item.awb_number || '-' }}
                </span>
              </td>
              <!-- Shipper -->
              <td class="text-uppercase text-nowrap">
                <div class="fw-bold text-dark">{{ (item.sender_name || item.sender_company_name || '-').toUpperCase() }}
                </div>
                <div
                  v-if="item.sender_company_name && item.sender_name && item.sender_company_name !== item.sender_name"
                  class="text-muted small">
                  {{ item.sender_company_name }}
                </div>
              </td>

              <!-- Consignee -->
              <td class="text-uppercase text-nowrap">
                <div class="fw-bold text-dark">{{ (item.consignee_company_name || item.consignee_name ||
                  '-').toUpperCase() }}</div>
                <div
                  v-if="item.consignee_name && item.consignee_company_name && item.consignee_name !== item.consignee_company_name"
                  class="text-muted small">
                  {{ item.consignee_name }}
                </div>
              </td>

              <!-- Booking Date -->
              <td class="text-nowrap text-secondary">
                {{ formatDate(item.booking_date || item.created_at) }}
              </td>

              <!-- Forwarding Number -->
              <td class="font-monospace text-secondary text-nowrap">
                {{ item.reference_no || '-' }}
              </td>

              <!-- Service -->
              <td class="text-uppercase fw-semibold text-primary text-nowrap">
                {{ item.service_partner?.name || item.servicePartner?.name || item.package_type || '-' }}
              </td>

              <!-- Origin Hub -->
              <td class="text-uppercase fw-medium text-secondary text-nowrap">
                {{ (item.sender_country || 'India').toUpperCase() }}
              </td>

              <!-- Destination -->
              <td class="text-uppercase fw-bold text-dark text-nowrap">
                {{ (item.destination_country?.name || item.destinationCountry?.name || item.consignee_country ||
                  '-').toUpperCase() }}
              </td>

              <!-- Added By -->
              <td class="text-uppercase text-nowrap">
                <div class="fw-bold text-dark">{{ item.user?.name }}
                </div>
                <div v-if="item.user.role" class="text-muted small">
                  {{ item.user?.role }}
                </div>
              </td>


              <!-- Actions -->
              <td class="text-center action-cell">
                <div class="dropdown">
                  <button class="btn-action-dots" type="button" :id="'actionMenu_' + (item.id || index)"
                    data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end action-dropdown-menu shadow-lg"
                    :aria-labelledby="'actionMenu_' + (item.id || index)">
                    <!-- 1. View Details -->
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center"
                        @click="viewDetails(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                        </span>
                        <span class="action-item-text">View Details</span>
                      </button>
                    </li>
                    <!-- 2. Track Order -->
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center"
                        @click="handleTrackOrder(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path
                              d="M16.5 9.4 7.55 4.24a1.78 1.78 0 0 0-1.8 0l-3.3 1.9A1.78 1.78 0 0 0 1.5 7.7v7.6a1.78 1.78 0 0 0 .95 1.56l3.3 1.9a1.78 1.78 0 0 0 1.8 0l3.45-2" />
                            <polyline points="1.7 7.4 6.65 10.25 16.2 4.7" />
                            <line x1="6.65" y1="10.25" x2="6.65" y2="19" />
                            <path
                              d="M18.5 13a3.5 3.5 0 0 0-3.5 3.5c0 2.62 3.5 6.5 3.5 6.5s3.5-3.88 3.5-6.5a3.5 3.5 0 0 0-3.5-3.5Z" />
                            <circle cx="18.5" cy="16.5" r="1" fill="currentColor" />
                          </svg>
                        </span>
                        <span class="action-item-text">Track Order</span>
                      </button>
                    </li>
                    <!-- 3. Order Cloning -->
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center"
                        @click="handleOrderCloning(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="13" height="13" x="8" y="8" rx="2.5" ry="2.5" />
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                          </svg>
                        </span>
                        <span class="action-item-text">Order Cloning</span>
                      </button>
                    </li>
                    <!-- 4. Print Invoice -->
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center">
                        <!-- @click="handlePrintInvoice(item)" -->
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <rect width="12" height="8" x="6" y="14" rx="1" />
                          </svg>
                        </span>
                        <span class="action-item-text">Print Invoice</span>
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination & Footer Info -->
      <div
        class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-3 pt-3 border-top gap-2 text-muted small">
        <div>
          Showing <strong>{{ pagination.from || (shipments.length > 0 ? 1 : 0) }}</strong> to
          <strong>{{ pagination.to || shipments.length }}</strong> of
          <strong>{{ pagination.total }}</strong> shipments
        </div>
        <div class="d-flex align-items-center gap-1" v-if="pagination.last_page > 1">
          <button class="btn btn-sm btn-light border px-2 py-1" :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button v-for="p in pagination.last_page" :key="p" class="btn btn-sm px-3 py-1 fw-bold"
            :class="p === pagination.current_page ? 'btn-primary' : 'btn-light border'" @click="goToPage(p)">
            {{ p }}
          </button>
          <button class="btn btn-sm btn-light border px-2 py-1"
            :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Shipment Details Modal -->
    <div v-if="showDetailModal && selectedShipment" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary fs-6 px-3 py-2">
                AWB: {{ selectedShipment.shipment_no || selectedShipment.awb_number }}
              </span>
              <span class="badge bg-secondary-subtle text-secondary px-3 py-2 border text-uppercase">
                Status: {{ selectedShipment.status }}
              </span>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showDetailModal = false"></button>
          </div>

          <div class="modal-body p-4">
            <!-- Master Info Summary Grid -->
            <div class="row g-3 mb-4">
              <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded-3">
                  <span class="text-muted small d-block">Booking Date</span>
                  <span class="fw-semibold text-dark">{{ formatDate(selectedShipment.booking_date ||
                    selectedShipment.created_at) }}</span>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded-3">
                  <span class="text-muted small d-block">Forwarding / Ref No</span>
                  <span class="fw-semibold text-dark font-monospace">{{ selectedShipment.reference_no || 'N/A' }}</span>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded-3">
                  <span class="text-muted small d-block">Service Partner</span>
                  <span class="fw-semibold text-dark">{{ selectedShipment.service_partner?.name ||
                    selectedShipment.servicePartner?.name || 'N/A' }}</span>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded-3">
                  <span class="text-muted small d-block">Package Type</span>
                  <span class="fw-semibold text-dark">{{ selectedShipment.package_type || 'NONDOC' }}</span>
                </div>
              </div>
            </div>

            <div class="row g-4 mb-4">
              <!-- Shipper Details -->
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-light">
                  <div class="card-header bg-white border-bottom fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-person-fill text-primary"></i> Shipper (Sender) Details
                  </div>
                  <div class="card-body">
                    <p class="mb-1 fw-bold text-dark">{{ selectedShipment.sender_name }}</p>
                    <p class="mb-1 text-muted">{{ selectedShipment.sender_company_name }}</p>
                    <p class="mb-1 small text-secondary">
                      {{ selectedShipment.sender_address_line_1 }}
                      <span v-if="selectedShipment.sender_address_line_2">, {{ selectedShipment.sender_address_line_2
                        }}</span>
                      <span v-if="selectedShipment.sender_city">, {{ selectedShipment.sender_city }}</span>
                      <span v-if="selectedShipment.sender_state">, {{ selectedShipment.sender_state }}</span>
                      <span v-if="selectedShipment.sender_pincode"> - {{ selectedShipment.sender_pincode }}</span>
                    </p>
                    <p class="mb-1 small text-dark"><i class="bi bi-telephone me-1 text-muted"></i> {{
                      selectedShipment.sender_mobile }}</p>
                    <p class="mb-0 small text-dark"><i class="bi bi-envelope me-1 text-muted"></i> {{
                      selectedShipment.sender_email }}</p>
                  </div>
                </div>
              </div>

              <!-- Consignee Details -->
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-light">
                  <div class="card-header bg-white border-bottom fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-danger"></i> Consignee (Receiver) Details
                  </div>
                  <div class="card-body">
                    <p class="mb-1 fw-bold text-dark">{{ selectedShipment.consignee_name }}</p>
                    <p class="mb-1 text-muted">{{ selectedShipment.consignee_company_name }}</p>
                    <p class="mb-1 small text-secondary">
                      {{ selectedShipment.consignee_address_line_1 }}
                      <span v-if="selectedShipment.consignee_address_line_2">, {{
                        selectedShipment.consignee_address_line_2 }}</span>
                      <span v-if="selectedShipment.consignee_city">, {{ selectedShipment.consignee_city }}</span>
                      <span v-if="selectedShipment.consignee_state">, {{ selectedShipment.consignee_state }}</span>
                      <span v-if="selectedShipment.consignee_pincode"> - {{ selectedShipment.consignee_pincode }}</span>
                    </p>
                    <p class="mb-1 small text-dark"><i class="bi bi-telephone me-1 text-muted"></i> {{
                      selectedShipment.consignee_mobile_no }}</p>
                    <p class="mb-0 small text-dark"><i class="bi bi-envelope me-1 text-muted"></i> {{
                      selectedShipment.consignee_email }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Package Boxes Table -->
            <div class="card border-0 shadow-sm mb-4">
              <div
                class="card-header bg-light border-bottom fw-bold text-dark d-flex align-items-center justify-content-between">
                <span><i class="bi bi-boxes me-2 text-warning"></i>Package Boxes ({{ selectedShipment.boxes?.length ||
                  selectedShipment.total_box || 1 }} Pcs)</span>
                <span class="badge bg-primary">Chg. Wt: {{ selectedShipment.total_chargeable_weight }} kg</span>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th class="text-center" style="width: 80px;">Box No</th>
                      <th class="text-center">Dimensions (L × W × H cm)</th>
                      <th class="text-end">Actual Wt (kg)</th>
                      <th class="text-end">Vol. Wt (kg)</th>
                      <th class="text-end">Chargeable Wt (kg)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="b in (selectedShipment.boxes || [])" :key="b.id || b.no_of_box">
                      <td class="text-center fw-bold">{{ b.no_of_box }}</td>
                      <td class="text-center">{{ b.length }} × {{ b.width }} × {{ b.height }}</td>
                      <td class="text-end">{{ b.actual_weight }}</td>
                      <td class="text-end">{{ b.volumetric_weight }}</td>
                      <td class="text-end fw-bold">{{ b.chargeable_weight }}</td>
                    </tr>
                    <tr v-if="!selectedShipment.boxes || selectedShipment.boxes.length === 0">
                      <td class="text-center fw-bold">1</td>
                      <td class="text-center">-</td>
                      <td class="text-end">{{ selectedShipment.total_actual_weight }}</td>
                      <td class="text-end">{{ selectedShipment.total_volume_weight }}</td>
                      <td class="text-end fw-bold">{{ selectedShipment.total_chargeable_weight }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Customs Invoice Details & Items -->
            <div v-if="selectedShipment.invoice" class="card border-0 shadow-sm">
              <div
                class="card-header bg-light border-bottom fw-bold text-dark d-flex align-items-center justify-content-between">
                <span><i class="bi bi-file-earmark-text me-2 text-danger"></i>Customs Invoice: {{
                  selectedShipment.invoice.invoice_no }} ({{ selectedShipment.invoice.invoice_type }})</span>
                <span class="badge bg-success">Total: {{ selectedShipment.invoice.currency }} {{
                  selectedShipment.invoice.total_invoice_amount }}</span>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th class="text-center" style="width: 60px;">Box</th>
                      <th>Description</th>
                      <th class="text-center">HSN Code</th>
                      <th class="text-center">Unit</th>
                      <th class="text-end">Qty</th>
                      <th class="text-end">Rate</th>
                      <th class="text-end">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="it in (selectedShipment.invoice.items || [])" :key="it.id">
                      <td class="text-center fw-bold">{{ it.box_no }}</td>
                      <td class="fw-medium text-dark">{{ it.description }}</td>
                      <td class="text-center font-monospace">{{ it.hsn_code || '-' }}</td>
                      <td class="text-center">{{ it.unit_type }}</td>
                      <td class="text-end">{{ it.quantity }}</td>
                      <td class="text-end">{{ it.unit_price }}</td>
                      <td class="text-end fw-bold">{{ it.total_price }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary rounded-3 px-3" @click="showDetailModal = false">
              Close
            </button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary rounded-3 px-3"
                @click="copyToClipboard(selectedShipment.shipment_no || selectedShipment.awb_number, 'AWB')">
                <i class="bi bi-copy me-1"></i> Copy AWB
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tracking Modal (Track Order) -->
    <div v-if="showTrackModal && trackingShipment" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path
                    d="M16.5 9.4 7.55 4.24a1.78 1.78 0 0 0-1.8 0l-3.3 1.9A1.78 1.78 0 0 0 1.5 7.7v7.6a1.78 1.78 0 0 0 .95 1.56l3.3 1.9a1.78 1.78 0 0 0 1.8 0l3.45-2" />
                  <polyline points="1.7 7.4 6.65 10.25 16.2 4.7" />
                  <line x1="6.65" y1="10.25" x2="6.65" y2="19" />
                  <path
                    d="M18.5 13a3.5 3.5 0 0 0-3.5 3.5c0 2.62 3.5 6.5 3.5 6.5s3.5-3.88 3.5-6.5a3.5 3.5 0 0 0-3.5-3.5Z" />
                  <circle cx="18.5" cy="16.5" r="1" fill="currentColor" />
                </svg>
              </div>
              <div>
                <h5 class="fw-bold mb-0 text-dark">Track Shipment</h5>
                <span class="text-muted small">AWB: <strong class="text-primary">{{ trackingShipment.shipment_no ||
                  trackingShipment.awb_number }}</strong></span>
              </div>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showTrackModal = false"></button>
          </div>

          <div class="modal-body p-4 bg-light">
            <!-- Summary card -->
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
              <div class="row g-3 align-items-center">
                <div class="col-sm-4 border-end">
                  <span class="text-muted small d-block">Service Partner</span>
                  <span class="fw-bold text-dark fs-6">{{ trackingShipment.service_partner?.name ||
                    trackingShipment.servicePartner?.name || 'EXPRESS AIR' }}</span>
                </div>
                <div class="col-sm-4 border-end">
                  <span class="text-muted small d-block">Forwarding No</span>
                  <span class="fw-bold text-dark font-monospace">{{ trackingShipment.reference_no || 'Pending' }}</span>
                </div>
                <div class="col-sm-4">
                  <span class="text-muted small d-block">Current Status</span>
                  <span class="badge bg-primary px-3 py-2 text-uppercase">{{ trackingShipment.status || 'CREATED'
                    }}</span>
                </div>
              </div>
            </div>

            <!-- Route Info -->
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <span class="badge bg-secondary-subtle text-secondary small mb-1">ORIGIN</span>
                  <h6 class="fw-bold text-dark mb-0">{{ (trackingShipment.sender_city || 'MUMBAI').toUpperCase() }}</h6>
                  <span class="text-muted small">India</span>
                </div>
                <div class="flex-grow-1 px-4 text-center">
                  <div class="d-flex align-items-center justify-content-center text-primary">
                    <span class="border-top flex-grow-1 border-primary border-2 border-dashed"></span>
                    <i class="bi bi-airplane-fill fs-5 mx-2"></i>
                    <span class="border-top flex-grow-1 border-primary border-2 border-dashed"></span>
                  </div>
                  <span class="text-muted small">Air Freight Cargo Transit</span>
                </div>
                <div class="text-end">
                  <span class="badge bg-success-subtle text-success small mb-1">DESTINATION</span>
                  <h6 class="fw-bold text-dark mb-0">{{ (trackingShipment.destination_country?.name ||
                    trackingShipment.consignee_country || 'INTERNATIONAL').toUpperCase() }}</h6>
                  <span class="text-muted small">{{ trackingShipment.consignee_city || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Timeline -->
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Tracking Activity
              </h6>
              <div class="tracking-timeline ps-3">
                <div class="timeline-step completed">
                  <div class="step-dot"><i class="bi bi-check2"></i></div>
                  <div class="step-content">
                    <div class="fw-bold text-dark">Shipment Booked & Documented</div>
                    <div class="text-muted small">{{ formatDate(trackingShipment.booking_date ||
                      trackingShipment.created_at) }} - Hub Mumbai</div>
                  </div>
                </div>
                <div class="timeline-step" :class="{ 'completed': trackingShipment.status !== 'created' }">
                  <div class="step-dot"><i class="bi bi-box-seam"></i></div>
                  <div class="step-content">
                    <div class="fw-bold text-dark">Pickup Completed & Sorted</div>
                    <div class="text-muted small">{{ trackingShipment.status !== 'created' ? 'Processed at Hub' :
                      'Carrier assigned' }}</div>
                  </div>
                </div>
                <div class="timeline-step"
                  :class="{ 'completed': ['in_transit', 'delivered'].includes(trackingShipment.status) }">
                  <div class="step-dot"><i class="bi bi-airplane"></i></div>
                  <div class="step-content">
                    <div class="fw-bold text-dark">Departed to Destination Hub</div>
                    <div class="text-muted small">International transit corridor</div>
                  </div>
                </div>
                <div class="timeline-step" :class="{ 'completed': trackingShipment.status === 'delivered' }">
                  <div class="step-dot"><i class="bi bi-house-check"></i></div>
                  <div class="step-content">
                    <div class="fw-bold text-dark">Delivered to Consignee</div>
                    <div class="text-muted small">
                      {{ trackingShipment.status === 'delivered'
                        ? 'Shipment delivered successfully'
                        : 'Pending final delivery'
                      }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary rounded-3 px-3" @click="showTrackModal = false">
              Close
            </button>
            <button type="button" class="btn btn-outline-primary rounded-3 px-3"
              @click="copyToClipboard(trackingShipment.shipment_no || trackingShipment.awb_number, 'AWB')">
              <i class="bi bi-copy me-1"></i> Copy AWB
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Print Invoice Modal (Print Invoice) -->
    <div v-if="showInvoiceModal && invoiceShipment" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-white border-bottom px-4 py-3 no-print">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 6 2 18 2 18 9" />
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                  <rect width="12" height="8" x="6" y="14" rx="1" />
                </svg>
              </div>
              <div>
                <h5 class="fw-bold mb-0 text-dark">Commercial Invoice</h5>
                <span class="text-muted small">AWB: <strong class="text-primary">{{ invoiceShipment.shipment_no ||
                  invoiceShipment.awb_number }}</strong></span>
              </div>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showInvoiceModal = false"></button>
          </div>

          <div class="modal-body p-4 bg-light overflow-auto" style="max-height: 75vh;">
            <!-- Printable Invoice Sheet -->
            <div class="printable-invoice-sheet bg-white p-4 p-md-5 rounded-3 shadow-sm mx-auto"
              style="max-width: 850px;">
              <!-- Header -->
              <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                  <h4 class="fw-bold text-primary mb-1">EXPRESS IT COURIER & CARGO</h4>
                  <div class="text-muted small">International Logistics & Courier Services</div>
                  <div class="text-muted small">Email: support@expressit.in | Web: www.expressit.in</div>
                </div>
                <div class="text-end">
                  <h5 class="fw-bold text-dark text-uppercase mb-1">Commercial Invoice</h5>
                  <div class="small text-muted"><strong>Invoice No:</strong> {{ invoiceShipment.invoice?.invoice_no ||
                    ('INV-' + (invoiceShipment.shipment_no || invoiceShipment.id)) }}</div>
                  <div class="small text-muted"><strong>Date:</strong> {{
                    formatDate(invoiceShipment.invoice?.invoice_date || invoiceShipment.booking_date ||
                      invoiceShipment.created_at) }}</div>
                  <div class="small text-muted"><strong>AWB No:</strong> {{ invoiceShipment.shipment_no ||
                    invoiceShipment.awb_number }}</div>
                  <div class="small text-muted"><strong>Terms of Trade:</strong> {{
                    invoiceShipment.invoice?.term_of_trade || 'DAP' }}</div>
                </div>
              </div>

              <!-- Shipper and Consignee columns -->
              <div class="row g-4 mb-4">
                <div class="col-6">
                  <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                    <h6 class="fw-bold text-primary text-uppercase mb-2 small"><i class="bi bi-person-fill me-1"></i>
                      Shipper (Exporter)</h6>
                    <div class="fw-bold text-dark">{{ invoiceShipment.sender_company_name || invoiceShipment.sender_name
                      }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.sender_address_line_1 }}</div>
                    <div v-if="invoiceShipment.sender_address_line_2" class="small text-secondary">{{
                      invoiceShipment.sender_address_line_2 }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.sender_city }}, {{ invoiceShipment.sender_state
                      }} - {{ invoiceShipment.sender_pincode }}</div>
                    <div class="small text-secondary"><strong>Contact:</strong> {{ invoiceShipment.sender_mobile }}
                    </div>
                    <div class="small text-secondary"><strong>Email:</strong> {{ invoiceShipment.sender_email }}</div>
                    <div v-if="invoiceShipment.sender_kyc_number" class="small text-secondary"><strong>KYC:</strong> {{
                      invoiceShipment.sender_kyc_type }}: {{ invoiceShipment.sender_kyc_number }}</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                    <h6 class="fw-bold text-danger text-uppercase mb-2 small"><i class="bi bi-geo-alt-fill me-1"></i>
                      Consignee (Receiver)</h6>
                    <div class="fw-bold text-dark">{{ invoiceShipment.consignee_company_name ||
                      invoiceShipment.consignee_name }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.consignee_address_line_1 }}</div>
                    <div v-if="invoiceShipment.consignee_address_line_2" class="small text-secondary">{{
                      invoiceShipment.consignee_address_line_2 }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.consignee_city }}, {{
                      invoiceShipment.consignee_state }} - {{ invoiceShipment.consignee_pincode }}</div>
                    <div class="small text-secondary"><strong>Country:</strong> {{
                      invoiceShipment.destination_country?.name || invoiceShipment.consignee_country }}</div>
                    <div class="small text-secondary"><strong>Phone:</strong> {{ invoiceShipment.consignee_mobile_no }}
                    </div>
                    <div class="small text-secondary"><strong>Email:</strong> {{ invoiceShipment.consignee_email }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Items Table -->
              <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle mb-0">
                  <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                      <th class="text-center" style="width: 50px;">Box</th>
                      <th>Item Description</th>
                      <th class="text-center" style="width: 100px;">HSN Code</th>
                      <th class="text-center" style="width: 60px;">Unit</th>
                      <th class="text-end" style="width: 60px;">Qty</th>
                      <th class="text-end" style="width: 90px;">Rate</th>
                      <th class="text-end" style="width: 110px;">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(it, idx) in (invoiceShipment.invoice?.items || defaultInvoiceItems(invoiceShipment))"
                      :key="idx">
                      <td class="text-center fw-bold">{{ it.box_no || 1 }}</td>
                      <td class="fw-medium text-dark">{{ it.description }}</td>
                      <td class="text-center font-monospace">{{ it.hsn_code || '-' }}</td>
                      <td class="text-center">{{ it.unit_type || it.unit || 'PCS' }}</td>
                      <td class="text-end">{{ it.quantity || it.qty || 1 }}</td>
                      <td class="text-end">{{ parseFloat(it.unit_price || it.rate || 0).toFixed(2) }}</td>
                      <td class="text-end fw-bold">{{ parseFloat(it.total_price || it.amount || 0).toFixed(2) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="fw-bold bg-light">
                      <td colspan="6" class="text-end">Total Invoice Value ({{ invoiceShipment.invoice?.currency ||
                        'INR' }}):</td>
                      <td class="text-end text-primary fs-6">{{ invoiceShipment.invoice?.total_invoice_amount ||
                        totalCalculatedInvoice(invoiceShipment) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Declaration & Signature -->
              <div class="border-top pt-3 mt-4">
                <div class="row align-items-end">
                  <div class="col-8">
                    <p class="small text-muted mb-0" style="font-size: 0.75rem;">
                      <strong>Declaration:</strong> We declare that this invoice shows the actual price of the goods
                      described and that all particulars are true and correct. These goods are not meant for commercial
                      re-sale.
                    </p>
                  </div>
                  <div class="col-4 text-center">
                    <div style="height: 45px;"></div>
                    <div class="border-top pt-1 small fw-bold text-dark">Authorized Signatory</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between no-print">
            <button type="button" class="btn btn-outline-secondary rounded-3 px-3" @click="showInvoiceModal = false">
              Close
            </button>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm d-flex align-items-center gap-2"
              @click="printDirectInvoice">
              <i class="bi bi-printer-fill"></i>
              <span>Print Invoice</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Header styling based on Rate Calculator page font color & theme */
.shipment-table thead th {
  background-color: var(--primary-light, #eef2ff) !important;
  color: var(--primary-color, #1b357d) !important;
  font-weight: 700 !important;
  font-size: 0.82rem !important;
  padding: 0.85rem 0.95rem !important;
  border-top: none !important;
  border-bottom: 1px solid rgba(27, 53, 125, 0.15) !important;
  vertical-align: middle !important;
  white-space: nowrap !important;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.shipment-table tbody td {
  padding: 0.75rem 0.95rem !important;
  vertical-align: middle !important;
  border-bottom: 1px solid #f1f5f9 !important;
  color: #334155;
  font-size: 0.85rem;
}

.shipment-table tbody tr {
  transition: background-color 0.15s ease;
}

.shipment-table tbody tr:hover {
  background-color: #fafbfc;
  /* Matches RateCalculator table row hover */
}

/* Action Dropdown Menu matching user uploaded design */
.action-dropdown-menu {
  background: #ffffff !important;
  border: 1px solid #f1f5f9 !important;
  border-radius: 14px !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
  padding: 0.45rem 0.35rem !important;
  min-width: 195px !important;
  z-index: 1055 !important;
}

.action-dropdown-item {
  padding: 0.65rem 0.95rem !important;
  border-radius: 9px !important;
  color: #1e293b !important;
  font-weight: 500 !important;
  font-size: 0.92rem !important;
  gap: 0.85rem !important;
  transition: all 0.15s ease-in-out !important;
  background: transparent;
  border: none;
  width: 100%;
}

.action-dropdown-item:hover,
.action-dropdown-item:focus {
  background-color: #f8fafc !important;
  color: var(--primary-color, #1b357d) !important;
}

.action-dropdown-item:hover .action-icon-wrapper,
.action-dropdown-item:focus .action-icon-wrapper {
  color: var(--primary-color, #1b357d) !important;
}

.action-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: #64748b;
  flex-shrink: 0;
  transition: color 0.15s ease-in-out;
}

.action-item-text {
  font-size: 0.92rem;
  letter-spacing: -0.01em;
}

/* Minimalist 3-dots action button matching the screenshot */
.btn-action-dots {
  background: transparent;
  color: #64748b;
  border: none;
  width: 32px;
  height: 28px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  padding: 0;
}

.btn-action-dots:hover,
.btn-action-dots:focus {
  background-color: #f1f5f9;
  color: var(--primary-color, #1b357d);
}

/* Tracking Timeline Styles */
.tracking-timeline {
  position: relative;
}

.tracking-timeline::before {
  content: '';
  position: absolute;
  top: 10px;
  bottom: 10px;
  left: 24px;
  width: 2px;
  background-color: #e2e8f0;
}

.timeline-step {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.timeline-step:last-child {
  margin-bottom: 0;
}

.step-dot {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #cbd5e1;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  z-index: 2;
  flex-shrink: 0;
}

.timeline-step.completed .step-dot {
  background: var(--primary-color, #1b357d);
  border-color: var(--primary-color, #1b357d);
  color: #ffffff;
}

/* Print Invoice Styling */
@media print {
  body * {
    visibility: hidden;
  }

  .printable-invoice-sheet,
  .printable-invoice-sheet * {
    visibility: visible;
  }

  .printable-invoice-sheet {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0 !important;
    padding: 20px !important;
    box-shadow: none !important;
    border: none !important;
  }

  .no-print {
    display: none !important;
  }
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}
</style>
