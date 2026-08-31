<script setup>
import { ref, computed } from 'vue'

const filterRole = ref('All')
const staffList = ref([
  {
    id: 'STF-01',
    name: 'Michael Chen',
    role: 'Van Courier',
    email: 'm.chen@expressit.com',
    phone: '+1 (555) 912-3456',
    deliveriesCompleted: 1420,
    status: 'On Route',
    rating: 4.9,
  },
  {
    id: 'STF-02',
    name: 'Elena Rostova',
    role: 'Bike Courier',
    email: 'e.rostova@expressit.com',
    phone: '+1 (555) 823-4567',
    deliveriesCompleted: 980,
    status: 'Active',
    rating: 5.0,
  },
  {
    id: 'STF-03',
    name: 'David Ross',
    role: 'Van Courier',
    email: 'd.ross@expressit.com',
    phone: '+1 (555) 734-5678',
    deliveriesCompleted: 2150,
    status: 'Active',
    rating: 4.8,
  },
  {
    id: 'STF-04',
    name: 'Sarah Connor',
    role: 'Hub Dispatcher',
    email: 's.connor@expressit.com',
    phone: '+1 (555) 645-6789',
    deliveriesCompleted: 0,
    status: 'Active',
    rating: 4.9,
  },
  {
    id: 'STF-05',
    name: 'James Wilson',
    role: 'Logistics Lead',
    email: 'j.wilson@expressit.com',
    phone: '+1 (555) 556-7890',
    deliveriesCompleted: 430,
    status: 'Off Duty',
    rating: 4.7,
  },
])

const filteredStaff = computed(() => {
  if (filterRole.value === 'All') return staffList.value
  return staffList.value.filter((s) => s.role === filterRole.value)
})

const getStatusBadge = (status) => {
  switch (status) {
    case 'Active':
      return 'bg-success text-white'
    case 'On Route':
      return 'bg-primary text-white'
    case 'Off Duty':
      return 'bg-secondary text-white'
    default:
      return 'bg-secondary text-white'
  }
}
</script>

<template>
  <div class="container-fluid p-0 d-none">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h4 class="fw-bold mb-1 text-dark">Staff & Driver Fleet Directory</h4>
        <p class="text-muted mb-0 small">Manage dispatchers, route operators, and delivery personnel.</p>
      </div>
      <button class="btn btn-primary btn-sm rounded-3 px-3 py-2 shadow-sm">
        <i class="bi bi-person-plus me-1"></i> Add Team Member
      </button>
    </div>

    <!-- Filters & Cards Grid -->
    <div class="custom-card p-4">
      <div class="d-flex flex-wrap gap-2 mb-4">
        <button v-for="role in ['All', 'Van Courier', 'Bike Courier', 'Hub Dispatcher', 'Logistics Lead']" :key="role"
          type="button" class="btn btn-sm rounded-pill px-3"
          :class="filterRole === role ? 'btn-primary' : 'btn-light text-secondary'" @click="filterRole = role">
          {{ role }}
        </button>
      </div>

      <!-- Staff Grid -->
      <div class="row g-3">
        <div v-for="staff in filteredStaff" :key="staff.id" class="col-12 col-md-6 col-xl-4">
          <div class="border rounded-4 p-3 bg-white h-100 shadow-sm d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-4 fw-bold text-center"
                    style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    {{ staff.name.charAt(0) }}
                  </div>
                  <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ staff.name }}</h6>
                    <span class="text-primary small fw-semibold">{{ staff.role }}</span>
                  </div>
                </div>
                <span class="badge rounded-pill small" :class="getStatusBadge(staff.status)">
                  {{ staff.status }}
                </span>
              </div>

              <div class="small text-muted mb-2">
                <div class="mb-1"><i class="bi bi-envelope me-2"></i>{{ staff.email }}</div>
                <div><i class="bi bi-telephone me-2"></i>{{ staff.phone }}</div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
              <div>
                <span class="text-muted small">Deliveries: </span>
                <span class="fw-bold text-dark">{{ staff.deliveriesCompleted }}</span>
              </div>
              <div class="d-flex align-items-center gap-1 text-warning small">
                <i class="bi bi-star-fill"></i>
                <span class="fw-bold text-dark">{{ staff.rating }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
