<script setup>
import { ref, computed } from 'vue'

const filterStatus = ref('All')
const searchQuery = ref('')

const shipments = ref([
  {
    id: '1',
    trackingNo: 'EXP-889021',
    sender: 'Acme Electronics',
    recipient: 'Sarah Jenkins',
    city: 'San Francisco, CA',
    status: 'In Transit',
    date: 'Aug 15, 2026',
    amount: '$42.50',
    driver: 'Michael Chen',
  },
  {
    id: '2',
    trackingNo: 'EXP-889022',
    sender: 'Metro Apparel',
    recipient: 'Robert Taylor',
    city: 'Seattle, WA',
    status: 'Delivered',
    date: 'Aug 15, 2026',
    amount: '$18.00',
    driver: 'David Ross',
  },
  {
    id: '3',
    trackingNo: 'EXP-889023',
    sender: 'BioMed Labs',
    recipient: 'St. Jude Hospital',
    city: 'Austin, TX',
    status: 'In Transit',
    date: 'Aug 15, 2026',
    amount: '$120.00',
    driver: 'Elena Rostova',
  },
  {
    id: '4',
    trackingNo: 'EXP-889024',
    sender: 'TechGiant Corp',
    recipient: 'Alex Rivera',
    city: 'Denver, CO',
    status: 'Pending',
    date: 'Aug 15, 2026',
    amount: '$65.00',
    driver: 'Unassigned',
  },
  {
    id: '5',
    trackingNo: 'EXP-889025',
    sender: 'Urban Furnishings',
    recipient: 'Claire Dubois',
    city: 'Chicago, IL',
    status: 'Delivered',
    date: 'Aug 14, 2026',
    amount: '$210.00',
    driver: 'Michael Chen',
  },
  {
    id: '6',
    trackingNo: 'EXP-889026',
    sender: 'OmniParts Inc',
    recipient: 'Jason Miller',
    city: 'Miami, FL',
    status: 'Cancelled',
    date: 'Aug 14, 2026',
    amount: '$35.00',
    driver: 'David Ross',
  },
])

const filteredShipments = computed(() => {
  return shipments.value.filter((item) => {
    const matchesStatus =
      filterStatus.value === 'All' || item.status === filterStatus.value
    const matchesSearch =
      item.trackingNo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.recipient.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.city.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesStatus && matchesSearch
  })
})

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Delivered':
      return 'badge-delivered'
    case 'In Transit':
      return 'badge-in-transit'
    case 'Pending':
      return 'badge-pending'
    case 'Cancelled':
      return 'badge-cancelled'
    default:
      return 'bg-secondary text-white'
  }
}

const updateStatus = (shipment, newStatus) => {
  shipment.status = newStatus
}
</script>

<template>
  <div class="container-fluid p-0 d-none">
    <!-- Welcome Greeting & Quick Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h4 class="fw-bold mb-1 text-dark">Courier Operations Dashboard</h4>
        <p class="text-muted mb-0 small">
          Real-time parcel delivery tracking, fleet metrics & dispatch logistics.
        </p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 bg-white shadow-sm">
          <i class="bi bi-download me-1"></i> Export Manifest
        </button>
        <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm">
          <i class="bi bi-upc-scan me-1"></i> Scan Inbound
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
      <!-- Card 1: Total Shipments -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card custom-card-hover p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="text-muted small fw-semibold">Total Dispatched</span>
              <h3 class="fw-bold text-dark mt-1 mb-0">1,482</h3>
            </div>
            <div class="stat-icon-wrapper stat-icon-blue">
              <i class="bi bi-box-seam"></i>
            </div>
          </div>
          <div class="d-flex align-items-center gap-1">
            <span class="stat-trend-up d-flex align-items-center">
              <i class="bi bi-arrow-up-short fs-5"></i> +12.4%
            </span>
            <span class="text-muted small">vs last week</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Active In Transit -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card custom-card-hover p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="text-muted small fw-semibold">Active In-Transit</span>
              <h3 class="fw-bold text-dark mt-1 mb-0">328</h3>
            </div>
            <div class="stat-icon-wrapper stat-icon-purple">
              <i class="bi bi-truck"></i>
            </div>
          </div>
          <div class="d-flex align-items-center gap-1">
            <span class="stat-trend-up d-flex align-items-center">
              <i class="bi bi-arrow-up-short fs-5"></i> +5.8%
            </span>
            <span class="text-muted small">fleet at 92% capacity</span>
          </div>
        </div>
      </div>

      <!-- Card 3: Delivered Rate -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card custom-card-hover p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="text-muted small fw-semibold">Delivered Today</span>
              <h3 class="fw-bold text-dark mt-1 mb-0">94.6%</h3>
            </div>
            <div class="stat-icon-wrapper stat-icon-green">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="d-flex align-items-center gap-1">
            <span class="stat-trend-up d-flex align-items-center">
              <i class="bi bi-arrow-up-short fs-5"></i> +1.2%
            </span>
            <span class="text-muted small">on-time guarantee met</span>
          </div>
        </div>
      </div>

      <!-- Card 4: Revenue / COD -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="custom-card custom-card-hover p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="text-muted small fw-semibold">Daily Gross Revenue</span>
              <h3 class="fw-bold text-dark mt-1 mb-0">$28,450</h3>
            </div>
            <div class="stat-icon-wrapper stat-icon-amber">
              <i class="bi bi-currency-dollar"></i>
            </div>
          </div>
          <div class="d-flex align-items-center gap-1">
            <span class="stat-trend-up d-flex align-items-center">
              <i class="bi bi-arrow-up-short fs-5"></i> +18.2%
            </span>
            <span class="text-muted small">vs yesterday</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Row: Shipments Table & Side Fleet Widget -->
    <div class="row g-3">
      <!-- Left 8 Columns: Live Shipments Table -->
      <div class="col-12 col-xl-12">
        <div class="custom-card p-4">
          <!-- Table Controls Header -->
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
              <h5 class="fw-bold mb-1 text-dark">Live Shipment Dispatches</h5>
              <p class="text-muted small mb-0">Monitor parcel status across all express routes.</p>
            </div>

            <!-- Filter Status Badges -->
            <div class="d-flex flex-wrap gap-1">
              <button v-for="status in ['All', 'In Transit', 'Delivered', 'Pending']" :key="status" type="button"
                class="btn btn-sm rounded-pill px-3"
                :class="filterStatus === status ? 'btn-primary' : 'btn-light text-secondary'"
                @click="filterStatus = status">
                {{ status }}
              </button>
            </div>
          </div>

          <!-- Search Filter Bar -->
          <div class="input-group mb-3">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input v-model="searchQuery" type="text" class="form-control border-start-0 bg-light"
              placeholder="Search table by tracking number, recipient name, or city..." />
          </div>

          <!-- Table -->
          <div class="table-responsive">
            <table class="table custom-table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Tracking #</th>
                  <th>Recipient / City</th>
                  <th>Driver</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredShipments" :key="item.id">
                  <td>
                    <div class="fw-bold text-primary">{{ item.trackingNo }}</div>
                    <span class="text-muted" style="font-size: 0.75rem;">{{ item.date }}</span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark">{{ item.recipient }}</div>
                    <div class="text-muted small">
                      <i class="bi bi-geo-alt me-1 text-danger"></i>{{ item.city }}
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="rounded-circle bg-light border p-1 text-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-person-fill text-secondary small"></i>
                      </div>
                      <span class="small fw-medium">{{ item.driver }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="fw-semibold text-dark">{{ item.amount }}</span>
                  </td>
                  <td>
                    <span class="badge-status" :class="getStatusBadgeClass(item.status)">
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="dropdown">
                      <button class="btn btn-sm btn-light rounded-circle p-1" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li>
                          <a class="dropdown-item small" href="#" @click.prevent="updateStatus(item, 'In Transit')">
                            <i class="bi bi-truck text-primary me-2"></i> Mark In Transit
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item small" href="#" @click.prevent="updateStatus(item, 'Delivered')">
                            <i class="bi bi-check-circle text-success me-2"></i> Mark Delivered
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item small" href="#" @click.prevent="updateStatus(item, 'Pending')">
                            <i class="bi bi-clock-history text-warning me-2"></i> Mark Pending
                          </a>
                        </li>
                        <li>
                          <hr class="dropdown-divider" />
                        </li>
                        <li>
                          <a class="dropdown-item small text-danger" href="#"
                            @click.prevent="updateStatus(item, 'Cancelled')">
                            <i class="bi bi-x-circle me-2"></i> Cancel Shipment
                          </a>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredShipments.length === 0">
                  <td colspan="6" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-2 d-block text-secondary mb-2"></i>
                    No shipments match the selected criteria.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
