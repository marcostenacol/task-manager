import { type FetchOptions } from 'ofetch'

export const useApi = async <T>(url: string, options: FetchOptions = {}) => {
  const config = useRuntimeConfig()
  const token = useCookie('auth_token')
  const refreshToken = useCookie('refresh_token')
  const apiBase = config.public.apiBase || 'http://localhost:8000/api'

  const defaults: FetchOptions = {
    baseURL: apiBase,
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
    
    async onResponseError({ request, response }) {
      console.error(`API Error [${response.status}] on ${request}:`, response._data)
      if (response.status === 401 && refreshToken.value) {
        console.log('401 detected, attempting token refresh...')
        // Tenta fazer o refresh
        try {
          const { data } = await $fetch<any>('/v1/auth/refresh', {
            baseURL: apiBase,
            method: 'POST',
            body: { refresh_token: refreshToken.value }
          })
          console.log('Token refresh successful:', data)

          if (data && data.access_token) {
            token.value = data.access_token.token
            refreshToken.value = data.refresh_token.token
            
            // Recarrega a página ou repete a requisição (simplificado: recarrega)
            window.location.reload()
          }
        } catch (err) {
          // Se falhar o refresh, limpa tudo e vai para login
          token.value = null
          refreshToken.value = null
          navigateTo('/login')
        }
      }
    }
  }

  return $fetch<T>(url, { ...defaults, ...options })
}
