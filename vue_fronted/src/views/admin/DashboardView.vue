<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { getDashboardStats } from '@/services/admin/shipmentService'

const router = useRouter()

// ----------------------------------------------------
// State Management
// ----------------------------------------------------
const isLoading = ref(true)
const toastMessage = ref(null)

// Filter Bar States
const timeframe = ref('all')
const fromDate = ref('')
const toDate = ref('')

// Dashboard Data
const stats = ref({
  total_shipments: 0,
  delivered: 0,
  in_transit: 0,
  cancelled: 0,
  created: 0,
  total_weight: 0,
  total_boxes: 0,
  total_invoice_amount: 0,
})

const recentShipments = ref([])
const activeStatusFilter = ref('all')

// Modals
const selectedShipment = ref(null)
const showDetailModal = ref(false)
const trackingShipment = ref(null)
const showTrackModal = ref(false)
const invoiceShipment = ref(null)
const showInvoiceModal = ref(false)
const showSupportModal = ref(false)

// ----------------------------------------------------
// Toast & Helpers
// ----------------------------------------------------
const showToast = (message) => {
  toastMessage.value = message
  setTimeout(() => {
    toastMessage.value = null
  }, 3500)
}

const copyToClipboard = (text, label = 'AWB') => {
  if (!text) return
  navigator.clipboard.writeText(text)
  showToast(`${label} ${text} copied to clipboard!`)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return dateStr
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}/${month}/${year}`
}

const formatCurrency = (amount, currency = 'INR') => {
  const num = parseFloat(amount || 0)
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: currency,
    maximumFractionDigits: 2,
  }).format(num)
}

// ----------------------------------------------------
// Timeframe Change Handler
// ----------------------------------------------------
const handleTimeframeChange = () => {
  const today = new Date()
  const formatDateStr = (d) => d.toISOString().split('T')[0]

  if (timeframe.value === 'today') {
    fromDate.value = formatDateStr(today)
    toDate.value = formatDateStr(today)
  } else if (timeframe.value === 'yesterday') {
    const yest = new Date()
    yest.setDate(yest.getDate() - 1)
    fromDate.value = formatDateStr(yest)
    toDate.value = formatDateStr(yest)
  } else if (timeframe.value === 'this_week') {
    const monday = new Date()
    const day = monday.getDay() || 7
    monday.setDate(monday.getDate() - day + 1)
    fromDate.value = formatDateStr(monday)
    toDate.value = formatDateStr(today)
  } else if (timeframe.value === 'this_month') {
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
    fromDate.value = formatDateStr(firstDay)
    toDate.value = formatDateStr(today)
  } else if (timeframe.value === 'all') {
    fromDate.value = ''
    toDate.value = ''
  }
  fetchDashboardData()
}

// ----------------------------------------------------
// Fetch Dashboard Data from Backend API
// ----------------------------------------------------
const fetchDashboardData = async () => {
  isLoading.value = true
  try {
    const params = {
      timeframe: timeframe.value,
      from_date: fromDate.value || undefined,
      to_date: toDate.value || undefined,
    }
    const res = await getDashboardStats(params)
    if (res && res.status === 'success') {
      const data = res.data
      stats.value = data.summary || {
        total_shipments: 0,
        delivered: 0,
        in_transit: 0,
        cancelled: 0,
        created: 0,
        total_weight: 0,
        total_boxes: 0,
        total_invoice_amount: 0,
      }
      recentShipments.value = data.recent_shipments || []
    }
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error)
    showToast('Failed to load dashboard metrics from server.')
  } finally {
    isLoading.value = false
  }
}

// Reset all filters to default
const resetFilters = () => {
  timeframe.value = 'all'
  fromDate.value = ''
  toDate.value = ''
  activeStatusFilter.value = 'all'
  fetchDashboardData()
}

// Filter recent shipments list by card status click
const filteredRecentShipments = computed(() => {
  if (activeStatusFilter.value === 'all') {
    return recentShipments.value
  }
  if (activeStatusFilter.value === 'in_transit') {
    return recentShipments.value.filter((s) =>
      ['in_transit', 'pickup_in_progress', 'ready_to_ship'].includes(s.status)
    )
  }
  if (activeStatusFilter.value === 'cancelled') {
    return recentShipments.value.filter((s) =>
      ['cancel', 'cancelled'].includes(s.status)
    )
  }
  return recentShipments.value.filter((s) => s.status === activeStatusFilter.value)
})

// Quick Action Handlers
const openRateCalculator = () => {
  router.push({ name: 'rate-calculator' })
}

const openAllOrders = () => {
  router.push({ name: 'admin-shipments' })
}

// Track Order Quick Action & Search
const trackSearchQuery = ref('')
const trackSearchError = ref('')

const openTracking = (prefillAwb = '') => {
  trackSearchError.value = ''
  if (prefillAwb) {
    trackSearchQuery.value = prefillAwb
  } else if (!trackSearchQuery.value && recentShipments.value.length > 0) {
    trackSearchQuery.value = recentShipments.value[0].shipment_no || recentShipments.value[0].awb_number || ''
  }

  if (trackSearchQuery.value) {
    searchAndTrackOrder()
  } else if (recentShipments.value.length > 0) {
    trackingShipment.value = recentShipments.value[0]
  } else {
    trackingShipment.value = null
  }
  showTrackModal.value = true
}

const searchAndTrackOrder = () => {
  trackSearchError.value = ''
  const q = (trackSearchQuery.value || '').trim().toLowerCase()
  if (!q) {
    trackSearchError.value = 'Please enter an AWB or Shipment number'
    return
  }

  const found = recentShipments.value.find((s) => {
    const awb = (s.shipment_no || s.awb_number || '').toLowerCase()
    const fwd = (s.forwarding_number || '').toLowerCase()
    return awb.includes(q) || fwd.includes(q) || String(s.id) === q
  })

  if (found) {
    trackingShipment.value = found
  } else {
    trackSearchError.value = `No active shipment found matching "${trackSearchQuery.value}". Please check the AWB number.`
  }
}

const openInvoices = () => {
  if (recentShipments.value.length > 0) {
    const withInv = recentShipments.value.find((s) => s.invoice) || recentShipments.value[0]
    invoiceShipment.value = withInv
    showInvoiceModal.value = true
  } else {
    router.push({ name: 'admin-shipments' })
  }
}

const openSupport = () => {
  showSupportModal.value = true
}

// Action Handlers from Table
const viewDetails = (item) => {
  selectedShipment.value = item
  showDetailModal.value = true
}

const handleTrackOrder = (item) => {
  trackingShipment.value = item
  trackSearchQuery.value = item.shipment_no || item.awb_number || ''
  trackSearchError.value = ''
  showTrackModal.value = true
}

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
      description: shipment?.package_type === 'DOC' ? 'DOCUMENTS / SAMPLES' : 'COMMERCIAL SHIPMENT GOODS',
      hsn_code: '49019900',
      unit_type: 'PCS',
      quantity: shipment?.total_box || 1,
      unit_price: 100,
      total_price: (shipment?.total_box || 1) * 100
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

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="dashboard-page-container">
    <!-- Toast Alert Notification -->
    <div v-if="toastMessage"
      class="alert alert-dark alert-dismissible fade show position-fixed top-0 end-0 m-4 shadow-lg text-white"
      style="z-index: 1070; min-width: 320px; background-color: #1e293b;" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill text-info fs-5"></i>
        <div>{{ toastMessage }}</div>
      </div>
      <button type="button" class="btn-close btn-close-white" aria-label="Close" @click="toastMessage = null"></button>
    </div>

    <!-- =========================================================================
         1. TOP HEADER & FILTER BAR (Based on Screenshot Controls)
         ========================================================================= -->
    <div class="custom-card p-3 mb-4 bg-white shadow-xs border">
      <div class="row g-2 align-items-center justify-content-between">
        <!-- Left: Filters (Timeframe + Date Pickers) -->
        <div class="col-12 col-xl-8">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Timeframe dropdown -->
            <div class="timeframe-wrapper">
              <select v-model="timeframe" @change="handleTimeframeChange"
                class="form-select form-select-sm custom-filter-select">
                <option value="all">Timeframe: All-time</option>
                <option value="today">Timeframe: Today</option>
                <option value="yesterday">Timeframe: Yesterday</option>
                <option value="this_week">Timeframe: This Week</option>
                <option value="this_month">Timeframe: This Month</option>
                <option value="custom">Timeframe: Custom Range</option>
              </select>
            </div>

            <!-- From Date -->
            <div class="date-input-wrapper">
              <span class="date-icon"><i class="bi bi-calendar3"></i></span>
              <input v-model="fromDate" @change="fetchDashboardData" type="date"
                class="form-control form-control-sm custom-date-input" placeholder="From Date" />
            </div>

            <!-- To Date -->
            <div class="date-input-wrapper">
              <span class="date-icon"><i class="bi bi-calendar3"></i></span>
              <input v-model="toDate" @change="fetchDashboardData" type="date"
                class="form-control form-control-sm custom-date-input" placeholder="To Date" />
            </div>

            <!-- Reset Filters -->
            <button v-if="timeframe !== 'all' || fromDate || toDate" @click="resetFilters" type="button"
              class="btn btn-sm btn-light border text-muted px-2 py-1" title="Reset Filters">
              <i class="bi bi-x-circle me-1"></i> Reset
            </button>
          </div>
        </div>

        <!-- Right: Actions (Refresh + Create Order) -->
        <div class="col-12 col-xl-4 d-flex justify-content-xl-end align-items-center gap-2">
          <button @click="fetchDashboardData" type="button"
            class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-2 d-flex align-items-center gap-2"
            :disabled="isLoading" title="Refresh Dashboard Stats">
            <i class="bi bi-arrow-clockwise" :class="{ 'spin-icon': isLoading }"></i>
            <span class="d-none d-sm-inline">Refresh</span>
          </button>

          <!-- Create Order Button (Primary Brand Navy matching AddNewShipmentView) -->
          <button @click="router.push({ name: 'admin-add-new-shipment' })" type="button"
            class="btn btn-create-order fw-bold px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm text-uppercase">
            <i class="bi bi-plus-lg"></i>
            <span>Create Order</span>
          </button>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         2. STATUS KPI CARDS ROW (4 Cards Based on Screenshot, Elevated Design)
         ========================================================================= -->
    <div class="row g-3 mb-4">
      <!-- Card 1: Total Shipment Booked -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-card-booked p-4 h-100 cursor-pointer"
          :class="{ 'stat-card-active': activeStatusFilter === 'all' }"
          @click="activeStatusFilter = 'all'">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="stat-title text-uppercase">Total Shipment Booked</span>
            <div class="stat-icon-badge bg-primary-subtle text-primary">
              <i class="bi bi-box-seam"></i>
            </div>
          </div>
          <div class="stat-number text-dark">{{ stats.total_shipments }}</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="stat-subtitle text-muted">{{ timeframe === 'all' ? 'All Time' : 'Selected Period' }}</span>
            <span class="badge bg-light text-primary border border-primary-subtle small fw-semibold">
              {{ stats.total_weight }} KG
            </span>
          </div>
        </div>
      </div>

      <!-- Card 2: Shipments Delivered -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-card-delivered p-4 h-100 cursor-pointer"
          :class="{ 'stat-card-active': activeStatusFilter === 'delivered' }"
          @click="activeStatusFilter = 'delivered'">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="stat-title text-uppercase">Shipments Delivered</span>
            <div class="stat-icon-badge bg-success-subtle text-success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
          </div>
          <div class="stat-number text-dark">{{ stats.delivered }}</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="stat-subtitle text-muted">{{ timeframe === 'all' ? 'All Time' : 'Selected Period' }}</span>
            <span class="badge bg-light text-success border border-success-subtle small fw-semibold">
              {{ stats.total_shipments > 0 ? Math.round((stats.delivered / stats.total_shipments) * 100) : 0 }}% Delivered
            </span>
          </div>
        </div>
      </div>

      <!-- Card 3: Shipments In-Transit -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-card-transit p-4 h-100 cursor-pointer"
          :class="{ 'stat-card-active': activeStatusFilter === 'in_transit' }"
          @click="activeStatusFilter = 'in_transit'">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="stat-title text-uppercase">Shipments In-Transit</span>
            <div class="stat-icon-badge bg-warning-subtle text-warning">
              <i class="bi bi-truck"></i>
            </div>
          </div>
          <div class="stat-number text-dark">{{ stats.in_transit }}</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="stat-subtitle text-muted">{{ timeframe === 'all' ? 'All Time' : 'Selected Period' }}</span>
            <span class="badge bg-light text-warning border border-warning-subtle small fw-semibold">
              Active Transit
            </span>
          </div>
        </div>
      </div>

      <!-- Card 4: Shipments Cancelled -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-card-cancelled p-4 h-100 cursor-pointer"
          :class="{ 'stat-card-active': activeStatusFilter === 'cancelled' }"
          @click="activeStatusFilter = 'cancelled'">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="stat-title text-uppercase">Shipments Cancelled</span>
            <div class="stat-icon-badge bg-danger-subtle text-danger">
              <i class="bi bi-x-circle-fill"></i>
            </div>
          </div>
          <div class="stat-number text-dark">{{ stats.cancelled }}</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="stat-subtitle text-muted">{{ timeframe === 'all' ? 'All Time' : 'Selected Period' }}</span>
            <span class="badge bg-light text-danger border border-danger-subtle small fw-semibold">
              Void AWB
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         3. QUICK ACTIONS ROW (Refined Palette Matching AddNewShipmentView)
         ========================================================================= -->
    <div class="custom-card p-4 mb-4 bg-white shadow-xs border">
      <!-- Header with pulsing primary indicator -->
      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="pulsing-primary-dot"></span>
        <h6 class="fw-bold mb-0 text-dark-accent">Quick Actions</h6>
      </div>

      <!-- 4 Quick Action Action Tiles -->
      <div class="row g-3 quick-actions-grid">
        <!-- Tile 1: Rate Calculator -->
        <div class="col-6 col-md-3">
          <div class="quick-action-tile h-100 text-center p-3 rounded-3" @click="openRateCalculator">
            <div class="quick-action-icon-circle">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect width="16" height="20" x="4" y="2" rx="2" />
                <line x1="8" x2="16" y1="6" y2="6" />
                <line x1="16" x2="16" y1="14" y2="18" />
                <path d="M16 10h.01" />
                <path d="M12 10h.01" />
                <path d="M8 10h.01" />
                <path d="M12 14h.01" />
                <path d="M8 14h.01" />
                <path d="M12 18h.01" />
                <path d="M8 18h.01" />
              </svg>
            </div>
            <div class="quick-action-label">Rate Calculator</div>
            <span class="quick-action-desc text-muted small d-none d-sm-block">Check quotes & slabs</span>
          </div>
        </div>

        <!-- Tile 2: All Orders -->
        <div class="col-6 col-md-3">
          <div class="quick-action-tile h-100 text-center p-3 rounded-3" @click="openAllOrders">
            <div class="quick-action-icon-circle">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" x2="8" y1="13" y2="13" />
                <line x1="16" x2="8" y1="17" y2="17" />
                <line x1="10" x2="8" y1="9" y2="9" />
              </svg>
            </div>
            <div class="quick-action-label">All Orders</div>
            <span class="quick-action-desc text-muted small d-none d-sm-block">Manage {{ stats.total_shipments }} Bookings</span>
          </div>
        </div>

        <!-- Tile 3: Track Order (Tracking Related) -->
        <div class="col-6 col-md-3">
          <div class="quick-action-tile h-100 text-center p-3 rounded-3" @click="openTracking()">
            <div class="quick-action-icon-circle">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16.5 9.4 7.55 4.24a1.78 1.78 0 0 0-1.8 0l-3.3 1.9A1.78 1.78 0 0 0 1.5 7.7v7.6a1.78 1.78 0 0 0 .95 1.56l3.3 1.9a1.78 1.78 0 0 0 1.8 0l3.45-2" />
                <polyline points="1.7 7.4 6.65 10.25 16.2 4.7" />
                <line x1="6.65" y1="10.25" x2="6.65" y2="19" />
                <path d="M18.5 13a3.5 3.5 0 0 0-3.5 3.5c0 2.62 3.5 6.5 3.5 6.5s3.5-3.88 3.5-6.5a3.5 3.5 0 0 0-3.5-3.5Z" />
                <circle cx="18.5" cy="16.5" r="1" fill="currentColor" />
              </svg>
            </div>
            <div class="quick-action-label">Track Order</div>
            <span class="quick-action-desc text-muted small d-none d-sm-block">Live AWB Tracking</span>
          </div>
        </div>

        <!-- Tile 4: Get Support -->
        <div class="col-6 col-md-3">
          <div class="quick-action-tile h-100 text-center p-3 rounded-3" @click="openSupport">
            <div class="quick-action-icon-circle">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a9 9 0 0 1 18 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" />
                <path d="M21 16v2a4 4 0 0 1-4 4h-5" />
              </svg>
            </div>
            <div class="quick-action-label">Get Support</div>
            <span class="quick-action-desc text-muted small d-none d-sm-block">24/7 Courier Hotline</span>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         4. RECENT SHIPMENTS OVERVIEW (Live Shipment Details Section)
         ========================================================================= -->
    <div class="custom-card p-4 bg-white shadow-xs border">
      <!-- Section Title & Navigation -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
        <div>
          <h5 class="fw-bold mb-0 text-dark-accent">
            <i class="bi bi-clock-history me-2 text-primary"></i>Recent Shipment Details
          </h5>
          <span class="text-muted small">Live bookings synced with database & warehouse dispatch</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span v-if="activeStatusFilter !== 'all'" class="badge bg-primary-subtle text-primary border border-primary-subtle small px-2 py-1">
            Filtered: {{ activeStatusFilter }}
            <i class="bi bi-x ms-1 cursor-pointer" @click="activeStatusFilter = 'all'"></i>
          </span>
          <button @click="openAllOrders" type="button" class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1">
            View All Shipments <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>
      </div>

      <!-- Recent Shipments Table -->
      <div class="table-responsive shipment-table-wrapper border rounded-3">
        <table class="table shipment-table align-middle mb-0">
          <thead>
            <tr>
              <th scope="col" class="text-center" style="width: 50px;">#</th>
              <th scope="col">AWB Number</th>
              <th scope="col">Booking Date</th>
              <th scope="col">Customer / Shipper</th>
              <th scope="col">Destination</th>
              <th scope="col">Service</th>
              <th scope="col" class="text-end">Chg. Wt</th>
              <th scope="col" class="text-end">Invoice Amount</th>
              <th scope="col" class="text-center">Status</th>
              <th scope="col" class="text-center" style="width: 70px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading Row -->
            <tr v-if="isLoading">
              <td colspan="10" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span>Loading shipment statistics...</span>
              </td>
            </tr>

            <!-- Empty Row -->
            <tr v-else-if="filteredRecentShipments.length === 0">
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <span class="fw-medium">No shipments recorded for the selected timeframe.</span>
                <div class="mt-2">
                  <button @click="resetFilters" class="btn btn-sm btn-outline-secondary">Reset Filters</button>
                </div>
              </td>
            </tr>

            <!-- Dynamic Shipment Rows -->
            <tr v-for="(item, idx) in filteredRecentShipments" :key="item.id || item.shipment_no">
              <!-- Index -->
              <td class="text-center fw-semibold text-secondary">{{ idx + 1 }}</td>

              <!-- AWB -->
              <td class="fw-bold text-nowrap">
                <span class="cursor-pointer text-primary" @click="viewDetails(item)"
                  :title="'View ' + (item.shipment_no || item.awb_number)">
                  {{ item.shipment_no || item.awb_number || '-' }}
                </span>
              </td>

              <!-- Booking Date -->
              <td class="text-nowrap text-secondary">
                {{ formatDate(item.booking_date || item.created_at) }}
              </td>

              <!-- Customer / Shipper -->
              <td class="text-uppercase text-nowrap">
                <div class="fw-semibold text-dark">{{ item.sender_company_name || item.sender_name || 'EXPRESS ITS' }}</div>
                <div v-if="item.sender_name && item.sender_company_name && item.sender_name !== item.sender_company_name" class="text-muted small">
                  {{ item.sender_name }}
                </div>
              </td>

              <!-- Destination Country -->
              <td class="text-uppercase fw-semibold text-dark text-nowrap">
                {{ (item.destination_country?.name || item.consignee_country || '-').toUpperCase() }}
              </td>

              <!-- Service Partner -->
              <td class="text-uppercase fw-semibold text-primary text-nowrap">
                {{ item.service_partner?.name || item.servicePartner?.name || item.package_type || '-' }}
              </td>

              <!-- Chargeable Weight -->
              <td class="text-end fw-bold text-dark text-nowrap">
                {{ parseFloat(item.total_chargeable_weight || 0).toFixed(2) }} kg
              </td>

              <!-- Invoice Amount -->
              <td class="text-end fw-semibold text-dark text-nowrap">
                {{ formatCurrency(item.total_invoice_value || item.booking_amount, item.currency || 'INR') }}
              </td>

              <!-- Status Badge -->
              <td class="text-center text-nowrap">
                <span class="badge text-uppercase px-3 py-1"
                  :class="{
                    'bg-success-subtle text-success border border-success-subtle': item.status === 'delivered',
                    'bg-warning-subtle text-warning border border-warning-subtle': ['in_transit', 'pickup_in_progress', 'ready_to_ship'].includes(item.status),
                    'bg-danger-subtle text-danger border border-danger-subtle': ['cancel', 'cancelled'].includes(item.status),
                    'bg-primary-subtle text-primary border border-primary-subtle': item.status === 'created' || !item.status
                  }">
                  {{ item.status || 'CREATED' }}
                </span>
              </td>

              <!-- Actions Dropdown -->
              <td class="text-center action-cell">
                <div class="dropdown">
                  <button class="btn-action-dots" type="button" :id="'dashAction_' + item.id"
                    data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end action-dropdown-menu shadow-lg" :aria-labelledby="'dashAction_' + item.id">
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center" @click="viewDetails(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                          </svg>
                        </span>
                        <span class="action-item-text">View Details</span>
                      </button>
                    </li>
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center" @click="handleTrackOrder(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16.5 9.4 7.55 4.24a1.78 1.78 0 0 0-1.8 0l-3.3 1.9A1.78 1.78 0 0 0 1.5 7.7v7.6a1.78 1.78 0 0 0 .95 1.56l3.3 1.9a1.78 1.78 0 0 0 1.8 0l3.45-2" />
                            <polyline points="1.7 7.4 6.65 10.25 16.2 4.7" />
                            <line x1="6.65" y1="10.25" x2="6.65" y2="19" />
                            <path d="M18.5 13a3.5 3.5 0 0 0-3.5 3.5c0 2.62 3.5 6.5 3.5 6.5s3.5-3.88 3.5-6.5a3.5 3.5 0 0 0-3.5-3.5Z" />
                            <circle cx="18.5" cy="16.5" r="1" fill="currentColor" />
                          </svg>
                        </span>
                        <span class="action-item-text">Track Order</span>
                      </button>
                    </li>
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center" @click="handleOrderCloning(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="13" height="13" x="8" y="8" rx="2.5" ry="2.5" />
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                          </svg>
                        </span>
                        <span class="action-item-text">Order Cloning</span>
                      </button>
                    </li>
                    <li>
                      <button class="dropdown-item action-dropdown-item d-flex align-items-center" @click="handlePrintInvoice(item)">
                        <span class="action-icon-wrapper">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
    </div>

    <!-- =========================================================================
         5. MODALS: DETAIL, TRACKING, INVOICE & SUPPORT
         ========================================================================= -->

    <!-- Modal 1: Shipment Details -->
    <div v-if="showDetailModal && selectedShipment" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary fs-6 px-3 py-2">
                AWB: {{ selectedShipment.shipment_no || selectedShipment.awb_number }}
              </span>
              <span class="badge bg-secondary-subtle text-secondary px-3 py-2 border text-uppercase">
                Status: {{ selectedShipment.status || 'CREATED' }}
              </span>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showDetailModal = false"></button>
          </div>

          <div class="modal-body p-4">
            <!-- Shipper & Consignee Summary -->
            <div class="row g-4 mb-4">
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-light p-3">
                  <h6 class="fw-bold text-primary mb-2"><i class="bi bi-person-fill me-1"></i> Shipper Details</h6>
                  <p class="mb-1 fw-bold text-dark">{{ selectedShipment.sender_name }}</p>
                  <p class="mb-1 text-muted">{{ selectedShipment.sender_company_name }}</p>
                  <p class="mb-1 small text-secondary">{{ selectedShipment.sender_address_line_1 }}</p>
                  <p class="mb-0 small text-dark"><i class="bi bi-telephone me-1"></i> {{ selectedShipment.sender_mobile }}</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm bg-light p-3">
                  <h6 class="fw-bold text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i> Consignee Details</h6>
                  <p class="mb-1 fw-bold text-dark">{{ selectedShipment.consignee_name }}</p>
                  <p class="mb-1 text-muted">{{ selectedShipment.consignee_company_name }}</p>
                  <p class="mb-1 small text-secondary">{{ selectedShipment.consignee_address_line_1 }}</p>
                  <p class="mb-0 small text-dark"><i class="bi bi-telephone me-1"></i> {{ selectedShipment.consignee_mobile_no }}</p>
                </div>
              </div>
            </div>

            <!-- Packages / Box Details -->
            <div class="card border-0 shadow-sm p-3 mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-boxes me-1 text-warning"></i> Package Boxes</h6>
                <span class="badge bg-primary">Chg Wt: {{ selectedShipment.total_chargeable_weight }} KG</span>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="text-center">Box</th>
                      <th class="text-center">Dimensions (L × W × H)</th>
                      <th class="text-end">Actual Wt</th>
                      <th class="text-end">Vol Wt</th>
                      <th class="text-end">Chargeable Wt</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="b in (selectedShipment.boxes || [])" :key="b.id">
                      <td class="text-center fw-bold">{{ b.no_of_box }}</td>
                      <td class="text-center">{{ b.length }} × {{ b.width }} × {{ b.height }} cm</td>
                      <td class="text-end">{{ b.actual_weight }} kg</td>
                      <td class="text-end">{{ b.volumetric_weight }} kg</td>
                      <td class="text-end fw-bold">{{ b.chargeable_weight }} kg</td>
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
            <button type="button" class="btn btn-outline-primary rounded-3 px-3"
              @click="copyToClipboard(selectedShipment.shipment_no || selectedShipment.awb_number, 'AWB')">
              <i class="bi bi-copy me-1"></i> Copy AWB
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal 2: Track Order Timeline (Interactive Live Tracker) -->
    <div v-if="showTrackModal" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                <i class="bi bi-geo-alt-fill fs-5"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0 text-dark">Track Shipment</h5>
                <span v-if="trackingShipment" class="text-muted small">AWB: <strong class="text-primary">{{ trackingShipment.shipment_no || trackingShipment.awb_number }}</strong></span>
                <span v-else class="text-muted small">Enter AWB or Tracking Number</span>
              </div>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showTrackModal = false"></button>
          </div>

          <div class="modal-body p-4 bg-light">
            <!-- Search AWB Bar inside Modal -->
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
              <label class="form-label small fw-bold text-secondary mb-1">Track by AWB or Forwarding Number</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i class="bi bi-search"></i>
                </span>
                <input v-model="trackSearchQuery" @keyup.enter="searchAndTrackOrder" type="text"
                  class="form-control border-start-0" placeholder="e.g. EXP-1001, 152000000010..." />
                <button @click="searchAndTrackOrder" class="btn btn-primary px-3 fw-semibold">
                  Track
                </button>
              </div>
              <div v-if="trackSearchError" class="text-danger small mt-2 d-flex align-items-center gap-1">
                <i class="bi bi-exclamation-circle-fill"></i> {{ trackSearchError }}
              </div>
            </div>

            <!-- Route Banner & Timeline if trackingShipment is available -->
            <div v-if="trackingShipment">
              <!-- Route Banner -->
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
                    <span class="text-muted small">Air Freight Transit</span>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-success-subtle text-success small mb-1">DESTINATION</span>
                    <h6 class="fw-bold text-dark mb-0">{{ (trackingShipment.destination_country?.name || trackingShipment.consignee_country || 'INTERNATIONAL').toUpperCase() }}</h6>
                    <span class="text-muted small">{{ trackingShipment.consignee_city || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Activity Timeline -->
              <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Tracking Activity</h6>
                  <span class="badge text-uppercase px-3 py-1"
                    :class="{
                      'bg-success-subtle text-success border border-success-subtle': trackingShipment.status === 'delivered',
                      'bg-warning-subtle text-warning border border-warning-subtle': ['in_transit', 'pickup_in_progress', 'ready_to_ship'].includes(trackingShipment.status),
                      'bg-danger-subtle text-danger border border-danger-subtle': ['cancel', 'cancelled'].includes(trackingShipment.status),
                      'bg-primary-subtle text-primary border border-primary-subtle': trackingShipment.status === 'created' || !trackingShipment.status
                    }">
                    {{ trackingShipment.status || 'CREATED' }}
                  </span>
                </div>
                <div class="tracking-timeline ps-3">
                  <div class="timeline-step completed">
                    <div class="step-dot"><i class="bi bi-check2"></i></div>
                    <div class="step-content">
                      <div class="fw-bold text-dark">Shipment Booked & Registered</div>
                      <div class="text-muted small">{{ formatDate(trackingShipment.booking_date || trackingShipment.created_at) }} - Hub Mumbai</div>
                    </div>
                  </div>
                  <div class="timeline-step" :class="{ 'completed': trackingShipment.status !== 'created' }">
                    <div class="step-dot"><i class="bi bi-box-seam"></i></div>
                    <div class="step-content">
                      <div class="fw-bold text-dark">Pickup & Manifest Verification</div>
                      <div class="text-muted small">{{ trackingShipment.status !== 'created' ? 'Processed at Hub' : 'Scheduled' }}</div>
                    </div>
                  </div>
                  <div class="timeline-step" :class="{ 'completed': ['in_transit', 'delivered'].includes(trackingShipment.status) }">
                    <div class="step-dot"><i class="bi bi-airplane"></i></div>
                    <div class="step-content">
                      <div class="fw-bold text-dark">Departed to Destination Country</div>
                      <div class="text-muted small">Transit Flight corridor</div>
                    </div>
                  </div>
                  <div class="timeline-step" :class="{ 'completed': trackingShipment.status === 'delivered' }">
                    <div class="step-dot"><i class="bi bi-house-check"></i></div>
                    <div class="step-content">
                      <div class="fw-bold text-dark">Delivered to Consignee</div>
                      <div class="text-muted small">{{ trackingShipment.status === 'delivered' ? 'Completed' : 'Pending final delivery' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty Prompt if no shipment matched yet -->
            <div v-else class="text-center py-5 text-muted bg-white rounded-3">
              <i class="bi bi-search fs-1 d-block mb-2 text-primary opacity-50"></i>
              <h6 class="fw-bold text-dark">Ready to Track</h6>
              <p class="small text-muted mb-0">Enter any AWB or Forwarding Number in the search box above to view real-time tracking.</p>
            </div>
          </div>

          <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary rounded-3 px-3" @click="showTrackModal = false">
              Close
            </button>
            <button v-if="trackingShipment" type="button" class="btn btn-outline-primary rounded-3 px-3"
              @click="copyToClipboard(trackingShipment.shipment_no || trackingShipment.awb_number, 'AWB')">
              <i class="bi bi-copy me-1"></i> Copy AWB
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal 3: Printable Customs Commercial Invoice -->
    <div v-if="showInvoiceModal && invoiceShipment" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-white border-bottom px-4 py-3 no-print">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                <i class="bi bi-receipt fs-5"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0 text-dark">Commercial Customs Invoice</h5>
                <span class="text-muted small">AWB: <strong class="text-primary">{{ invoiceShipment.shipment_no || invoiceShipment.awb_number }}</strong></span>
              </div>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showInvoiceModal = false"></button>
          </div>

          <div class="modal-body p-4 bg-light overflow-auto" style="max-height: 75vh;">
            <div class="printable-invoice-sheet bg-white p-4 p-md-5 rounded-3 shadow-sm mx-auto" style="max-width: 850px;">
              <!-- Header -->
              <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                  <h4 class="fw-bold text-primary mb-1">EXPRESS IT COURIER & CARGO</h4>
                  <div class="text-muted small">International Logistics & Courier Services</div>
                  <div class="text-muted small">Email: support@expressit.in | Web: www.expressit.in</div>
                </div>
                <div class="text-end">
                  <h5 class="fw-bold text-dark text-uppercase mb-1">Commercial Invoice</h5>
                  <div class="small text-muted"><strong>Invoice No:</strong> {{ invoiceShipment.invoice?.invoice_no || ('INV-' + (invoiceShipment.shipment_no || invoiceShipment.id)) }}</div>
                  <div class="small text-muted"><strong>Date:</strong> {{ formatDate(invoiceShipment.invoice?.invoice_date || invoiceShipment.booking_date || invoiceShipment.created_at) }}</div>
                  <div class="small text-muted"><strong>AWB No:</strong> {{ invoiceShipment.shipment_no || invoiceShipment.awb_number }}</div>
                  <div class="small text-muted"><strong>Terms:</strong> {{ invoiceShipment.invoice?.term_of_trade || 'DAP' }}</div>
                </div>
              </div>

              <!-- Parties -->
              <div class="row g-4 mb-4">
                <div class="col-6">
                  <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                    <h6 class="fw-bold text-primary text-uppercase mb-2 small">Shipper (Exporter)</h6>
                    <div class="fw-bold text-dark">{{ invoiceShipment.sender_company_name || invoiceShipment.sender_name }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.sender_address_line_1 }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.sender_city }}, {{ invoiceShipment.sender_state }}</div>
                    <div class="small text-secondary"><strong>Mobile:</strong> {{ invoiceShipment.sender_mobile }}</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                    <h6 class="fw-bold text-primary text-uppercase mb-2 small">Consignee (Receiver)</h6>
                    <div class="fw-bold text-dark">{{ invoiceShipment.consignee_company_name || invoiceShipment.consignee_name }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.consignee_address_line_1 }}</div>
                    <div class="small text-secondary">{{ invoiceShipment.destination_country?.name || invoiceShipment.consignee_country }}</div>
                    <div class="small text-secondary"><strong>Mobile:</strong> {{ invoiceShipment.consignee_mobile_no }}</div>
                  </div>
                </div>
              </div>

              <!-- Items Table -->
              <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle mb-0">
                  <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                      <th class="text-center" style="width: 50px;">Box</th>
                      <th>Description</th>
                      <th class="text-center" style="width: 100px;">HSN</th>
                      <th class="text-center" style="width: 60px;">Unit</th>
                      <th class="text-end" style="width: 60px;">Qty</th>
                      <th class="text-end" style="width: 90px;">Rate</th>
                      <th class="text-end" style="width: 110px;">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(it, idx) in (invoiceShipment.invoice?.items || defaultInvoiceItems(invoiceShipment))" :key="idx">
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
                      <td colspan="6" class="text-end">Total Invoice Value ({{ invoiceShipment.invoice?.currency || 'INR' }}):</td>
                      <td class="text-end text-primary fs-6">{{ invoiceShipment.invoice?.total_invoice_amount || totalCalculatedInvoice(invoiceShipment) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Declaration -->
              <div class="border-top pt-3 mt-4">
                <div class="row align-items-end">
                  <div class="col-8">
                    <p class="small text-muted mb-0" style="font-size: 0.75rem;">
                      <strong>Declaration:</strong> We declare that this invoice shows the actual value of the goods described and that all particulars are true and correct.
                    </p>
                  </div>
                  <div class="col-4 text-center">
                    <div style="height: 40px;"></div>
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

    <!-- Modal 4: Support & Helpdesk -->
    <div v-if="showSupportModal" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(3px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                <i class="bi bi-headset fs-5"></i>
              </div>
              <h5 class="fw-bold mb-0 text-dark">ExpressIt Support Desk</h5>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="showSupportModal = false"></button>
          </div>

          <div class="modal-body p-4 bg-light">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                  <i class="bi bi-telephone-fill fs-4"></i>
                </div>
                <div>
                  <span class="text-muted small d-block">24/7 Dispatch Hotline</span>
                  <h6 class="fw-bold text-dark mb-0">+91 9699 440 156 / 1800-EXPRESS</h6>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-success-subtle text-success p-3 rounded-circle">
                  <i class="bi bi-envelope-fill fs-4"></i>
                </div>
                <div>
                  <span class="text-muted small d-block">Operations & Customs Email</span>
                  <h6 class="fw-bold text-dark mb-0">support@expressit.in</h6>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                  <i class="bi bi-clock-fill fs-4"></i>
                </div>
                <div>
                  <span class="text-muted small d-block">Operating Hours</span>
                  <h6 class="fw-bold text-dark mb-0">Monday – Saturday: 08:00 AM – 10:00 PM IST</h6>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-white border-top px-4 py-3 text-end">
            <button type="button" class="btn btn-outline-secondary rounded-3 px-4" @click="showSupportModal = false">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page-container {
  width: 100%;
  max-width: 100%;
}

.text-dark-accent {
  color: var(--primary-color, #1b357d) !important;
}

/* =========================================================================
   Filter Bar & Header Controls
   ========================================================================= */
.timeframe-wrapper {
  min-width: 200px;
}

.custom-filter-select {
  border-radius: 8px;
  border: 1px solid #dcdcdc;
  padding: 0.5rem 0.85rem;
  font-size: 0.88rem;
  font-weight: 600;
  color: #334155;
  background-color: #fcfcfc;
}

.custom-filter-select:focus {
  border-color: var(--primary-color, #1b357d);
  box-shadow: 0 0 0 3px rgba(27, 53, 125, 0.12);
}

.date-input-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.date-icon {
  position: absolute;
  left: 10px;
  color: var(--primary-color, #1b357d);
  pointer-events: none;
  font-size: 0.85rem;
}

.custom-date-input {
  padding-left: 2rem;
  border-radius: 8px;
  border: 1px solid #dcdcdc;
  font-size: 0.88rem;
  font-weight: 500;
  color: #334155;
  background-color: #fcfcfc;
  min-width: 150px;
}

.custom-date-input:focus {
  border-color: var(--primary-color, #1b357d);
  box-shadow: 0 0 0 3px rgba(27, 53, 125, 0.12);
}

/* Create Order Button - matches AddNewShipmentView.vue primary buttons */
.btn-create-order {
  background-color: var(--primary-color, #1b357d);
  border: none;
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 4px 10px rgba(27, 53, 125, 0.2);
}

.btn-create-order:hover {
  background-color: var(--primary-hover, #14275e);
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(27, 53, 125, 0.3);
}

/* =========================================================================
   Status KPI Cards (Elevated Palette matching AddNewShipmentView)
   ========================================================================= */
.stat-card {
  background: #ffffff;
  border: 1px solid #eef2f6;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  transition: all 0.2s ease-in-out;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: transparent;
  transition: background 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

.stat-card-booked::before {
  background: var(--primary-color, #1b357d);
}

.stat-card-delivered::before {
  background: #10b981;
}

.stat-card-transit::before {
  background: #f59e0b;
}

.stat-card-cancelled::before {
  background: var(--secondary-color, #d01a24);
}

.stat-card-active {
  border-color: var(--primary-color, #1b357d);
  background-color: #fafbfc;
  box-shadow: 0 0 0 2px rgba(27, 53, 125, 0.15) !important;
}

.stat-title {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--primary-color, #1b357d);
}

.stat-icon-badge {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.stat-number {
  font-size: 2.25rem;
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
}

.stat-subtitle {
  font-size: 0.82rem;
  font-weight: 500;
}

/* =========================================================================
   Quick Actions Row (Matches AddNewShipmentView Interactive Accents)
   ========================================================================= */
.pulsing-primary-dot {
  width: 10px;
  height: 10px;
  background-color: var(--primary-color, #1b357d);
  border-radius: 50%;
  display: inline-block;
  box-shadow: 0 0 0 0 rgba(27, 53, 125, 0.7);
  animation: pulse-dot 1.8s infinite;
}

@keyframes pulse-dot {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(27, 53, 125, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 6px rgba(27, 53, 125, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(27, 53, 125, 0);
  }
}

.quick-actions-grid {
  border-top: 1px solid #f1f5f9;
  padding-top: 0.75rem;
}

.quick-action-tile {
  background: #ffffff;
  border: 1px solid #f1f5f9;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

.quick-action-tile:hover {
  background: var(--primary-light, #eef2ff);
  border-color: rgba(27, 53, 125, 0.22);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(27, 53, 125, 0.08);
}

.quick-action-icon-circle {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background-color: var(--primary-light, #eef2ff);
  color: var(--primary-color, #1b357d);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.65rem;
  transition: all 0.2s ease-in-out;
}

.quick-action-tile:hover .quick-action-icon-circle {
  background-color: var(--primary-color, #1b357d);
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(27, 53, 125, 0.25);
}

.quick-action-label {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: -0.01em;
  transition: color 0.15s ease;
}

.quick-action-tile:hover .quick-action-label {
  color: var(--primary-color, #1b357d);
}

.quick-action-desc {
  font-size: 0.75rem;
  margin-top: 2px;
}

/* =========================================================================
   Shipment Table & Action Menu
   ========================================================================= */
.shipment-table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

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

/* =========================================================================
   Tracking Timeline Styles
   ========================================================================= */
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

/* =========================================================================
   Print Invoice Styling
   ========================================================================= */
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
