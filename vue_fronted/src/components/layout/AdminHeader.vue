<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const emit = defineEmits(['toggle-mobile-sidebar', 'open-new-shipment'])

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const currentUser = authStore.user;
const logout = authStore.logout;

const searchQuery = ref('')
const notifications = ref([
  {
    id: 1,
    title: 'Parcel EXP-9821 Delivered',
    time: '4 mins ago',
    icon: 'bi-check-circle-fill text-success',
    unread: true,
  },
  {
    id: 2,
    title: 'New driver joined Route 4 (Downtown)',
    time: '25 mins ago',
    icon: 'bi-person-plus-fill text-primary',
    unread: true,
  },
  {
    id: 3,
    title: 'Fuel alert for Van #14',
    time: '2 hours ago',
    icon: 'bi-exclamation-circle-fill text-warning',
    unread: false,
  },
])

const handleLogout = () => {
  logout()
  router.push({ name: 'login' })
}

const pageTitle = () => {
  return route.meta?.title || 'Dashboard'
}
</script>

<template>
  <header class="admin-header">
    <!-- Left Section: Mobile Menu Button & Breadcrumb -->
    <div class="d-flex align-items-center gap-3">
      <button type="button" class="btn btn-outline-secondary border-0 p-2 d-lg-none"
        @click="emit('toggle-mobile-sidebar')" aria-label="Open sidebar">
        <i class="bi bi-list fs-4"></i>
      </button>

      <div>
        <h5 class="fw-bold mb-0 text-dark">{{ pageTitle() }}</h5>
        <span class="text-muted small d-none d-sm-inline">
          ExpressIt
        </span>
      </div>
    </div>


    <!-- Right Section: Actions, Notifications & Profile -->
    <div class="d-flex align-items-center gap-2 gap-sm-3">
      <!-- Quick Action Button -->

      <!-- Notifications Dropdown -->
      <div class="dropdown">
        <button type="button" class="btn btn-light position-relative rounded-circle p-2" data-bs-toggle="dropdown"
          aria-expanded="false" title="Notifications">
          <i class="bi bi-bell fs-5 text-secondary"></i>
          <span
            class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
            <span class="visually-hidden">New alerts</span>
          </span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2 mt-2" style="min-width: 300px;">
          <li class="p-2 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold small text-dark">Notifications</span>
            <span class="badge bg-primary-subtle text-primary rounded-pill small">2 Unread</span>
          </li>
          <li v-for="notif in notifications" :key="notif.id">
            <a class="dropdown-item rounded-3 p-2 my-1 d-flex align-items-start gap-2" href="#">
              <i :class="notif.icon" class="fs-5 mt-1"></i>
              <div class="overflow-hidden">
                <p class="mb-0 small fw-medium text-dark text-truncate">{{ notif.title }}</p>
                <span class="text-muted" style="font-size: 0.75rem;">{{ notif.time }}</span>
              </div>
            </a>
          </li>
          <li class="border-top pt-1 text-center">
            <a class="dropdown-item small text-primary fw-semibold rounded-2 py-1" href="#">
              View All Notifications
            </a>
          </li>
        </ul>
      </div>

      <!-- User Profile Dropdown -->
      <div class="dropdown">
        <button type="button" class="btn p-0 border-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown"
          aria-expanded="false">
          <img
            :src="currentUser?.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'"
            alt="Profile Avatar" class="rounded-circle border border-2 border-primary-subtle" width="38" height="38" />
          <div class="d-none d-xl-block text-start lh-1">
            <span class="d-block fw-semibold small text-dark">{{ currentUser?.name || 'Administrator' }}</span>
            <span class="text-muted" style="font-size: 0.72rem;">{{ currentUser?.role || 'Admin' }}</span>
          </div>
          <i class="bi bi-chevron-down text-muted small d-none d-sm-inline"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2 mt-2" style="min-width: 220px;">
          <li class="p-2 border-bottom mb-1">
            <div class="fw-semibold text-dark">{{ currentUser?.name || 'Administrator' }}</div>
            <div class="text-muted small">{{ currentUser?.email || 'admin@expressit.com' }}</div>
          </li>
          <li>
            <router-link :to="{ name: 'admin-settings' }" class="dropdown-item rounded-3 py-2 small">
              <i class="bi bi-gear me-2 text-primary"></i> System Settings
            </router-link>
          </li>
          <li>
            <router-link :to="{ name: 'admin-zones' }" class="dropdown-item rounded-3 py-2 small">
              <i class="bi bi-grid-1x2 me-2 text-primary"></i> Zone Setup
            </router-link>
          </li>
          <li>
            <router-link :to="{ name: 'admin-countries' }" class="dropdown-item rounded-3 py-2 small">
              <i class="bi bi-globe-americas me-2 text-primary"></i> Country Directory
            </router-link>
          </li>
          <li class="border-top my-1"></li>
          <li>
            <button @click="handleLogout" type="button" class="dropdown-item rounded-3 py-2 small text-danger">
              <i class="bi bi-box-arrow-right me-2"></i> Log Out
            </button>
          </li>
        </ul>
      </div>
    </div>
  </header>
  <div id="flash-toast" class="toast-hidden"></div>
</template>

<style>
#flash-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 16px 24px;
  background-color: #4CAF50;
  /* Default green for success */
  color: white;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  font-family: sans-serif;
  z-index: 9999;

  /* Animation setup */
  opacity: 0;
  transform: translateY(-20px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  pointer-events: none;
  /* Prevents clicking when hidden */
}

/* Class added by JavaScript to show the toast */
#flash-toast.toast-visible {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}
</style>