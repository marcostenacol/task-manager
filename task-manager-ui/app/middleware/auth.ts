import { useAuth } from '~/modules/auth/hooks/useAuth'

export default defineNuxtRouteMiddleware((to) => {
  const token = useCookie('auth_token')

  if (!token.value && to.path !== '/login' && to.path !== '/register' && to.path !== '/') {
    return navigateTo('/login')
  }

  if (token.value && (to.path === '/login' || to.path === '/register' || to.path === '/')) {
    return navigateTo('/tasks')
  }

  const { user } = useAuth()
  const needsOnboarding = user.value?.role?.slug === 'user' && !user.value?.organization

  if (needsOnboarding && to.path !== '/organizations/onboarding') {
    return navigateTo('/organizations/onboarding')
  }
})
