<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import logoImg from '@/assets/images/logo/logo.png'

const props = defineProps({
  isCollapsed: Boolean,
  isMobileOpen: Boolean,
})

const emit = defineEmits(['close-mobile', 'toggle-collapse'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const currentUser = authStore.user;
const logout = authStore.logout;

const isCurrentRoute = (name) => {
  return route.name === name
}

const handleLogout = () => {
  logout()
  router.push({ name: 'login' })
}

const openDropdowns = ref({
  Settings: true,
})

// Auto-expand dropdown if navigating to one of its sub-items
watch(
  () => route.name,
  (currentRouteName) => {
    if (
      [
        'admin-zones',
        'admin-countries',
        'service-partner',
        'service-partner-rate-slab',
        'admin-settings',
      ].includes(currentRouteName)
    ) {
      openDropdowns.value['Settings'] = true
    }
  },
  { immediate: true }
)

const toggleDropdown = (label) => {
  if (props.isCollapsed) {
    emit('toggle-collapse')
    openDropdowns.value[label] = true
  } else {
    openDropdowns.value[label] = !openDropdowns.value[label]
  }
}

const isParentActive = (item) => {
  if (!item.children) return false
  return item.children.some((child) => route.name === child.name)
}

const navSections = [
  {
    title: 'Overview',
    items: [
      {
        name: 'admin-dashboard',
        label: 'Dashboard',
        icon: 'bi-speedometer2',
        badge: null,
      },
    ],
  },
  {
    title: 'Operations',
    items: [
      {
        name: 'rate-calculator',
        label: 'Rate Calculator',
        icon: 'bi-currency-rupee',
        badgeClass: 'text-white',
      },
      {
        name: 'admin-shipments',
        label: 'Shipments Details',
        icon: 'bi-box-seam',
        badgeClass: 'bg-primary text-white',
      },
      {
        name: 'admin-users',
        label: 'Employees',
        icon: 'bi-people',
        badgeClass: 'bg-success text-white',
      },
    ],
  },
  {
    title: 'System',
    items: [
      {
        label: 'Settings',
        icon: 'bi-gear',
        children: [
          {
            name: 'service-partner',
            label: 'Service Partner',
            icon: 'bi bi-people',
          },
          {
            name: 'admin-countries',
            label: 'Country Master',
            icon: 'bi-globe-americas',
          },
          {
            name: 'admin-zones',
            label: 'Zone Master',
            icon: 'bi-grid-1x2',
          },
          {
            name: 'service-partner-rate-slab',
            label: 'Map Partner Rate Slab',
            // icon: 'bi-graph-up-arrow',
            icon: 'bi bi-file-earmark-spreadsheet',
          },
        ],
      },
    ],
  },
]
</script>

<template>
  <aside class="admin-sidebar" :class="{
    collapsed: isCollapsed,
    'mobile-open': isMobileOpen,
  }">
    <!-- Brand Header -->
    <div class="sidebar-header">
      <router-link to="/admin/dashboard" class="sidebar-brand">
        <div class="brand-icon-box">
          <i class="bi bi-calendar4"></i>
        </div>
        <div v-if="!isCollapsed" class="d-flex flex-column">
          <div class="mb-1 mt-3">
            <img :src="logoImg" alt="ExpressIt Logo" class="img-fluid" />
          </div>
          <span class="text-secondary small text-center" style="font-size: 0.72rem;">Global Logistics Service</span>
        </div>
      </router-link>

      <!-- Collapse Toggle Button (Desktop) -->
      <button type="button" class="btn btn-sm btn-link text-secondary p-0 d-none d-lg-block"
        @click="emit('toggle-collapse')" :title="isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'">
        <i :class="isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left'" class="fs-5"></i>
      </button>

      <!-- Close Button (Mobile) -->
      <button type="button" class="btn btn-sm btn-link text-secondary p-0 d-lg-none" @click="emit('close-mobile')">
        <i class="bi bi-x-lg fs-5 text-white"></i>
      </button>
    </div>

    <!-- Navigation List Container -->
    <div class="sidebar-nav-container">
      <div v-for="(section, sIdx) in navSections" :key="sIdx" class="mb-3">
        <div v-if="!isCollapsed" class="nav-section-title">
          {{ section.title }}
        </div>
        <div v-else class="text-center my-2 border-bottom border-secondary border-opacity-25 pb-1"></div>

        <template v-for="item in section.items" :key="item.label">
          <!-- Standard Single Item -->
          <router-link v-if="!item.children && item.name" :to="{ name: item.name }" class="sidebar-nav-item"
            :class="{ active: isCurrentRoute(item.name) }" @click="emit('close-mobile')"
            :title="isCollapsed ? item.label : undefined">
            <i :class="item.icon"></i>
            <span v-if="!isCollapsed" class="flex-fill">{{ item.label }}</span>
          </router-link>

          <!-- Dropdown Parent Item -->
          <div v-else class="sidebar-dropdown-group">
            <button type="button" class="sidebar-dropdown-toggle" :class="{
              'parent-active': isParentActive(item),
              'dropdown-open': openDropdowns[item.label],
            }" @click="toggleDropdown(item.label)" :title="isCollapsed ? item.label : undefined">
              <i :class="item.icon"></i>
              <span v-if="!isCollapsed" class="flex-fill">{{ item.label }}</span>
              <i v-if="!isCollapsed" class="bi bi-chevron-down sidebar-dropdown-chevron"
                :class="{ open: openDropdowns[item.label] }"></i>
            </button>

            <!-- Dropdown Submenu -->
            <transition name="submenu-slide">
              <div v-if="!isCollapsed && openDropdowns[item.label]" class="sidebar-submenu">
                <router-link v-for="sub in item.children" :key="sub.name" :to="{ name: sub.name }"
                  class="sidebar-sub-item" :class="{ active: isCurrentRoute(sub.name) }" @click="emit('close-mobile')">
                  <i :class="sub.icon || 'bi-dot'"></i>
                  <span>{{ sub.label }}</span>
                </router-link>
              </div>
            </transition>
          </div>
        </template>
      </div>
    </div>

    <!-- Sidebar Footer / User Profile -->
    <div class="sidebar-footer">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
          <img
            :src="currentUser?.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'"
            alt="User Avatar" class="rounded-circle border border-primary flex-shrink-0" width="36" height="36" />
          <div v-if="!isCollapsed" class="overflow-hidden">
            <div class="text-white text-truncate fw-semibold small">
              {{ currentUser?.name || 'Zawed Admin' }}
            </div>
            <div class="text-secondary text-truncate" style="font-size: 0.72rem;">
              {{ currentUser?.role || 'Admin' }}
            </div>
          </div>
        </div>

        <button v-if="!isCollapsed" @click="handleLogout" type="button"
          class="btn btn-sm btn-outline-danger border-0 p-1 ms-1 text-danger-emphasis" title="Sign out">
          <i class="bi bi-box-arrow-right fs-5"></i>
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.submenu-slide-enter-active,
.submenu-slide-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  max-height: 200px;
  opacity: 1;
}

.submenu-slide-enter-from,
.submenu-slide-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-6px);
}
</style>
