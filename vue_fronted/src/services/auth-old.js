import { reactive, computed } from 'vue'

const STORAGE_KEY = 'expressit_auth_user'

const storedUser = localStorage.getItem(STORAGE_KEY)
let initialUser = null

if (storedUser) {
  try {
    initialUser = JSON.parse(storedUser)
  } catch (e) {
    console.error('Failed to parse saved user from localStorage', e)
  }
}

const state = reactive({
  user: initialUser,
})

export const useAuth = () => {
  const isAuthenticated = computed(() => !!state.user)
  const currentUser = computed(() => state.user)

  const login = async (email, password) => {
    // Simulated network delay
    await new Promise((resolve) => setTimeout(resolve, 600))

    if (!email || !password) {
      throw new Error('Please provide both email and password.')
    }

    const namePrefix = email.split('@')[0] ?? 'admin'
    const user = {
      id: 'usr_adm_01',
      name: namePrefix.replace('.', ' ').toUpperCase() || 'Admin User',
      email: email,
      role: 'Admin',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
      department: 'Logistics Operations',
    }

    state.user = user
    localStorage.setItem(STORAGE_KEY, JSON.stringify(user))
    return true
  }

  const logout = () => {
    state.user = null
    localStorage.removeItem(STORAGE_KEY)
  }

  return {
    state,
    isAuthenticated,
    currentUser,
    login,
    logout,
  }
}
