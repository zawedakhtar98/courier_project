<script setup>
import { ref } from 'vue'
import { RouterView } from 'vue-router'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import AdminHeader from '@/components/layout/AdminHeader.vue'

const isCollapsed = ref(false)
const isMobileOpen = ref(false)
const showNewShipmentModal = ref(false)

// New Shipment Form State
const newShipment = ref({
  recipientName: '',
  recipientAddress: '',
  destinationCity: 'New York, NY',
  packageWeight: 2.5,
  serviceType: 'Express Next-Day',
  codAmount: 0,
})

const handleToggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

const handleToggleMobileSidebar = () => {
  isMobileOpen.value = !isMobileOpen.value
}

const handleCloseMobile = () => {
  isMobileOpen.value = false
}

const handleOpenNewShipment = () => {
  showNewShipmentModal.value = true
}

const submitNewShipment = () => {
  alert(`Shipment created successfully for ${newShipment.value.recipientName || 'Recipient'}!`)
  showNewShipmentModal.value = false
  // Reset
  newShipment.value = {
    recipientName: '',
    recipientAddress: '',
    destinationCity: 'New York, NY',
    packageWeight: 2.5,
    serviceType: 'Express Next-Day',
    codAmount: 0,
  }
}
</script>

<template>
  <div class="admin-wrapper">
    <!-- Backdrop Overlay for Mobile Sidebar -->
    <div class="sidebar-overlay" :class="{ active: isMobileOpen }" @click="handleCloseMobile"></div>

    <!-- Aside Navigation Bar -->
    <AdminSidebar :is-collapsed="isCollapsed" :is-mobile-open="isMobileOpen" @toggle-collapse="handleToggleCollapse"
      @close-mobile="handleCloseMobile" />

    <!-- Main Content Container -->
    <div class="admin-main" :class="{ expanded: isCollapsed }">
      <!-- Top Navigation Header -->
      <AdminHeader @toggle-mobile-sidebar="handleToggleMobileSidebar" @open-new-shipment="handleOpenNewShipment" />

      <!-- Page Content Outlet -->
      <main class="admin-content">
        <RouterView />
      </main>

      <!-- Footer Note -->
      <footer class="text-center py-3 text-muted small border-top bg-white">
        &copy; 2026 ExpressIt Courier & Logistics Suite. All rights reserved.
      </footer>
    </div>

    <!-- Global New Shipment Modal -->
    <div v-if="showNewShipmentModal" class="modal fade show d-block" tabindex="-1"
      style="background: rgba(15, 23, 42, 0.6);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
          <div class="modal-header bg-primary text-white p-3 px-4">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-box-seam me-2"></i> Book New Parcel Dispatch
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showNewShipmentModal = false"
              aria-label="Close"></button>
          </div>
          <form @submit.prevent="submitNewShipment">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Recipient Full Name</label>
                  <input v-model="newShipment.recipientName" type="text" class="form-control"
                    placeholder="e.g. John Doe" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Destination City / State</label>
                  <input v-model="newShipment.destinationCity" type="text" class="form-control"
                    placeholder="e.g. Chicago, IL" required />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Delivery Street Address</label>
                  <input v-model="newShipment.recipientAddress" type="text" class="form-control"
                    placeholder="e.g. 742 Evergreen Terrace, Suite 100" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Weight (kg)</label>
                  <input v-model="newShipment.packageWeight" type="number" step="0.1" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Delivery Speed</label>
                  <select v-model="newShipment.serviceType" class="form-select">
                    <option>Same-Day Rush</option>
                    <option>Express Next-Day</option>
                    <option>Standard Ground (2-3 Days)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">COD Amount ($)</label>
                  <input v-model="newShipment.codAmount" type="number" class="form-control"
                    placeholder="0 for prepaid" />
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light p-3 px-4">
              <button type="button" class="btn btn-outline-secondary" @click="showNewShipmentModal = false">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary px-4 fw-semibold">
                Confirm & Print Label
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
