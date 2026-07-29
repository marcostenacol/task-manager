import { useAuth } from '~/modules/auth/hooks/useAuth'

export default defineNuxtPlugin(async () => {
  const { user, isAuthenticated, restoreSession } = useAuth()

  if (isAuthenticated.value && !user.value) {
    await restoreSession()
  }
})
