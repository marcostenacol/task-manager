import { type FetchOptions } from 'ofetch'

export const useApi = async <T>(url: string, options: FetchOptions = {}) => {
  const config = useRuntimeConfig()
  const token = useCookie('auth_token')
  const refreshToken = useCookie('refresh_token')

  const defaults: FetchOptions = {
    baseURL: config.public.apiBase || 'http://localhost:8000/api',
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
    
    async onResponseError({ response }) {
      if (response.status === 401 && refreshToken.value) {
        // Tenta fazer o refresh
        try {
          const { data } = await $fetch<any>('/v1/auth/refresh', {
            baseURL: config.public.apiBase || 'http://localhost:8000/api',
            method: 'POST',
            body: { refresh_token: refreshToken.value }
          })

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
