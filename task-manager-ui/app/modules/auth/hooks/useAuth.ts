import { AuthService } from '../services/AuthService'
import type { User } from '../models/auth'

export const useAuth = () => {
  const user = useState<User | null>('auth_user', () => null)
  const token = useCookie('auth_token')
  const refreshToken = useCookie('refresh_token')
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  async function login(credentials: any) {
    loading.value = true
    try {
      const response = await AuthService.login(credentials)
      if (response.success) {
        token.value = response.data.access_token.token
        refreshToken.value = response.data.refresh_token.token
        user.value = response.data.user

        navigateTo('/')
      }
      return response
    } catch (err) {
      console.error('Error in useAuth.login:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await AuthService.logout()
    } finally {
      token.value = null
      refreshToken.value = null
      user.value = null
      navigateTo('/login')
    }
  }

  return {
    user,
    loading,
    isAuthenticated,
    login,
    logout
  }
}
