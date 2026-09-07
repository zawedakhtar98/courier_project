import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = createRouter({

  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/test',
      name: 'test',
      component: () => import('@/views/Test.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: { name: 'admin-dashboard' },
        },
        {
          path: 'dashboard',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/DashboardView.vue'),
          meta: { title: 'Dashboard Overview' },
        },
        {
          path: 'shipments',
          name: 'admin-shipments',
          component: () => import('@/views/admin/ShipmentsView.vue'),
          meta: { title: 'Shipment Management' },
        },
        {
          path: 'add-new-shipment',
          name: 'add-new-shipment',
          component: () => import('@/views/admin/ShipmentsForm.vue'),
          meta: { title: 'Shipment Management' },
        },
        {
          path: 'rate-calculator',
          name: 'rate-calculator',
          component: () => import('@/views/admin/RateCalculatorView.vue'),
          meta: { title: 'Rate Calculator' },
        },
        {
          path: 'add-new-shipment',
          name: 'admin-add-new-shipment',
          component: () => import('@/views/admin/AddNewShipmentView.vue'),
          meta: { title: 'Add New Shipment' },
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/UsersView.vue'),
          meta: { title: 'User Management' },
        },
        {
          path: 'settings',
          name: 'admin-settings',
          component: () => import('@/views/admin/SettingsView.vue'),
          meta: { title: 'System Settings' },
        },
        {
          path: 'settings/zones',
          alias: 'zones',
          name: 'admin-zones',
          component: () => import('@/views/admin/ZoneView.vue'),
          meta: { title: 'Zone Management' },
        },
        {
          path: 'settings/countries',
          alias: 'countries',
          name: 'admin-countries',
          component: () => import('@/views/admin/CountryView.vue'),
          meta: { title: 'Country List' },
        },
        {
          path: 'settings/servicepartner',
          alias: 'servicepartner',
          name: 'service-partner',
          component: () => import('@/views/admin/ServicePartner.vue'),
          meta: { title: 'Service Partner List' },
        },
        {
          path: 'settings/service-partner-rate-slab',
          alias: 'service-partner-rate-slab',
          name: 'service-partner-rate-slab',
          component: () => import('@/views/admin/ServicePartnerRateSlab.vue'),
          meta: { title: 'Service Partner Rate Slab List' },
        },
      ],
    }
  ],
})

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  // console.log(authStore.isReady, "asdsdas"); return false
  // On first load, check if user is already authenticated
  if (!authStore.isReady) {
    debugger
    await authStore.checkAuth()
  }

  if (to.meta.requiresAuth && !authStore.isLogin) {
    next({ name: 'login' })
  } else if (to.meta.requiresGuest && authStore.isLogin) {
    next({ name: 'admin-dashboard' })
  } else {
    next()
  }
})

export default router
