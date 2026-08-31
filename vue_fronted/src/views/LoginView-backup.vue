<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/services/auth'
import logoImg from '@/assets/images/logo/logo.png'

const router = useRouter()
const { login } = useAuth()

const email = ref('admin@expressit.com')
const password = ref('password123')
const rememberMe = ref(true)
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const showForgotModal = ref(false)
const resetEmail = ref('')
const resetSuccess = ref(false)

const handleLogin = async () => {
  errorMessage.value = ''
  if (!email.value || !password.value) {
    errorMessage.value = 'Please enter both your email address and password.'
    return
  }

  isLoading.value = true
  try {
    await login(email.value, password.value)
    router.push({ name: 'admin-dashboard' })
  } catch (err) {
    errorMessage.value = err.message || 'Login failed. Please verify your credentials.'
  } finally {
    isLoading.value = false
  }
}

const fillDemo = (role) => {
  if (role === 'admin') {
    email.value = 'admin@expressit.com'
    password.value = 'adminPass2026'
  } else {
    email.value = 'manager@expressit.com'
    password.value = 'managerPass2026'
  }
  errorMessage.value = ''
}

const handleResetPassword = () => {
  if (!resetEmail.value) return
  resetSuccess.value = true
  setTimeout(() => {
    resetSuccess.value = false
    showForgotModal.value = false
    resetEmail.value = ''
  }, 2000)
}
</script>

<template>
  <div class="login-body-wrapper">
    <div class="login-card">
      <!-- Header Banner -->
      <div class="login-header-banner">
        <div class="mb-2">
          <img :src="logoImg" alt="ExpressIt Logo" class="logo-img" />
        </div>
        <p class="text-white-50 small mb-0 mt-2">Admin & Logistics Management Portal</p>
      </div>

      <!-- Card Body -->
      <div class="p-4 p-md-5">
        <!-- Error Alert -->
        <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center mb-4 py-2" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
          <div class="small">{{ errorMessage }}</div>
        </div>

        <form @submit.prevent="handleLogin">
          <!-- Email Input -->
          <div class="mb-3">
            <label class="form-label text-secondary small fw-semibold">Email Address</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="bi bi-envelope"></i>
              </span>
              <input
                v-model="email"
                type="email"
                class="form-control border-start-0 ps-0 bg-light"
                placeholder="name@expressit.com"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <!-- Password Input -->
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label text-secondary small fw-semibold mb-0">Password</label>
              <a
                href="#"
                @click.prevent="showForgotModal = true"
                class="text-decoration-none small text-primary fw-medium"
              >
                Forgot password?
              </a>
            </div>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="bi bi-lock"></i>
              </span>
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control border-start-0 border-end-0 ps-0 bg-light"
                placeholder="Enter password"
                required
                autocomplete="current-password"
              />
              <button
                type="button"
                class="input-group-text bg-light border-start-0 text-muted"
                @click="showPassword = !showPassword"
                title="Toggle password visibility"
              >
                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
              </button>
            </div>
          </div>

          <!-- Remember Me Checkbox -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input
                v-model="rememberMe"
                class="form-check-input"
                type="checkbox"
                id="rememberMeCheck"
              />
              <label class="form-check-label small text-secondary" for="rememberMeCheck">
                Remember this device
              </label>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="btn btn-primary-gradient w-100 py-2 fs-6 shadow-sm mb-3 d-flex align-items-center justify-content-center gap-2"
            :disabled="isLoading"
          >
            <span
              v-if="isLoading"
              class="spinner-border spinner-border-sm"
              role="status"
              aria-hidden="true"
            ></span>
            <span v-if="!isLoading">
              Sign In to Dashboard <i class="bi bi-arrow-right ms-1"></i>
            </span>
            <span v-else>Authenticating...</span>
          </button>
        </form>

        <!-- Quick Demo Accounts -->
        <div class="pt-3 border-top">
          <div class="text-center mb-2">
            <span class="badge bg-light text-secondary border px-2 py-1 small fw-normal">
              Quick Demo Fill
            </span>
          </div>
          <div class="d-flex gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm flex-fill"
              @click="fillDemo('admin')"
            >
              <i class="bi bi-shield-check me-1"></i>Admin
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm flex-fill"
              @click="fillDemo('manager')"
            >
              <i class="bi bi-person-badge me-1"></i> Employee
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Forgot Password Modal Backing -->
    <div
      v-if="showForgotModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background: rgba(0, 0, 0, 0.6)"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">Reset Your Password</h5>
            <button
              type="button"
              class="btn-close"
              @click="showForgotModal = false"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body py-4">
            <div v-if="resetSuccess" class="alert alert-success">
              <i class="bi bi-check-circle-fill me-2"></i>
              Password reset link sent! Check your inbox.
            </div>
            <div v-else>
              <p class="text-muted small mb-3">
                Enter your work email address and we'll send you instructions to reset your password.
              </p>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Email address</label>
                <input
                  v-model="resetEmail"
                  type="email"
                  class="form-control"
                  placeholder="name@expressit.com"
                />
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0" v-if="!resetSuccess">
            <button
              type="button"
              class="btn btn-light"
              @click="showForgotModal = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="!resetEmail"
              @click="handleResetPassword"
            >
              Send Reset Link
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tracking-tight {
  letter-spacing: -0.025em;
}

.logo-img {
  max-height: 64px;
  max-width: 220px;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
}
</style>
