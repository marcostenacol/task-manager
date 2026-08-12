import type { FetchOptions } from 'ofetch'

export const useApi = async <T>(url: string, options: FetchOptions = {}) => {
  const config = useRuntimeConfig()
  const token = useCookie('auth_token')
  const refreshToken = useCookie('refresh_token')
  const apiBase = config.public.apiBase || 'http://localhost:8000/api'

  const defaults: FetchOptions = {
    baseURL: apiBase,
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
    // Sem timeout, uma request travada no backend (ex.: loop infinito sem
    // timeout de rede) ficava pendente indefinidamente no cliente também.
    timeout: 15000,

    async onResponseError({ request, response }) {
      console.error(`API Error [${response.status}] on ${request}:`, response._data)
      if (response.status === 401 && refreshToken.value) {
        // Tenta fazer o refresh
        try {
          const { data } = await $fetch<any>('/v1/auth/refresh', {
            baseURL: apiBase,
            method: 'POST',
            timeout: 15000,
            body: { refresh_token: refreshToken.value }
          })

          if (data && data.access_token) {
            token.value = data.access_token.token
            refreshToken.value = data.refresh_token.token
            
            // Recarrega a página ou repete a requisição (simplificado: recarrega)
            window.location.reload()
          }
        } catch {
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
