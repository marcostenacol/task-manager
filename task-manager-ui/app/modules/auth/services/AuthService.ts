import type { LoginResponse } from '../models/auth'

export const AuthService = {
  async login(credentials: any): Promise<LoginResponse> {
    return useApi<LoginResponse>('/v1/auth/login', {
      method: 'POST',
      body: credentials
    })
  },

  async register(data: any): Promise<any> {
    return useApi('/v1/auth/register', {
      method: 'POST',
      body: data
    })
  },

  async logout(): Promise<void> {
    await useApi('/v1/auth/logout', { method: 'POST' })
  }
}
