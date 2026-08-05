<template>
  <div class="auth-card glass wk-panel wk-brackets">
    <div class="auth-header">
      <span class="wk-stencil auth-eyebrow">Acesso / Portaria</span>
      <h2 class="font-outfit">{{ t('auth.loginTitle') }}</h2>
      <p>{{ t('auth.loginSubtitle') }}</p>
    </div>

    <form class="auth-form" @submit.prevent="handleSubmit">
      <div class="input-group">
        <label for="email">{{ t('auth.email') }}</label>
        <div class="input-wrapper">
          <Mail class="icon" :size="18" />
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="seu@email.com"
            required
          >
        </div>
      </div>

      <div class="input-group">
        <label for="password">{{ t('auth.password') }}</label>
        <div class="input-wrapper">
          <Lock class="icon" :size="18" />
          <input
            id="password"
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            required
          >
        </div>
      </div>

      <div class="auth-actions">
        <label class="remember-me">
          <input v-model="form.remember" type="checkbox" >
          <span>{{ t('auth.rememberMe') }}</span>
        </label>
        <button type="button" class="forgot-password" @click="showForgotPasswordInfo = true">
          {{ t('auth.forgotPassword') }}
        </button>
      </div>

      <button type="submit" class="btn-auth" :disabled="loading">
        <span v-if="!loading">{{ t('auth.loginButton') }}</span>
        <span v-else class="loader"/>
      </button>

      <div v-if="error" class="error-message">
        {{ error }}
      </div>

      <div v-if="showForgotPasswordInfo" class="info-message">
        {{ t('auth.forgotPasswordInfo') }}
      </div>
    </form>

    <div class="auth-footer">
      <p>{{ t('auth.noAccount') }} <NuxtLink to="/register">{{ t('auth.signUp') }}</NuxtLink></p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Lock, Mail } from 'lucide-vue-next'
import { useAuth } from '../hooks/useAuth'

const { t } = useI18n()
const { login, loading } = useAuth()
const form = reactive({
  email: '',
  password: '',
  remember: false
})
const error = ref('')
const showForgotPasswordInfo = ref(false)

async function handleSubmit() {
  error.value = ''
  try {
    const response = await login(form)
    if (!response.success) {
      error.value = response.message || 'Erro ao realizar login.'
    }
  } catch (err: any) {
    console.error('Login error detail:', err)
    error.value = err?.data?.message || 'Falha na conexão com o servidor.'
  }
}
</script>

<style scoped>
.auth-card {
  width: 100%;
  max-width: 450px;
  padding: 3rem;
  background: var(--glass-bg);
  backdrop-filter: blur(12px);
  border: 1px solid var(--glass-border);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.auth-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.auth-eyebrow {
  display: block;
  margin-bottom: 0.75rem;
  color: var(--accent);
}

.auth-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--ink);
}

.auth-header p {
  color: var(--muted);
  font-size: 0.95rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-group label {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--muted);
  margin-left: 0.5rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper .icon {
  position: absolute;
  left: 1rem;
  opacity: 0.7;
}

.input-wrapper input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  background: var(--surface-2);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  color: var(--ink);
  font-size: 1rem;
  transition: all 0.3s ease;
}

.input-wrapper input:focus {
  outline: none;
  border-color: var(--accent-primary);
  box-shadow: 0 0 0 4px var(--accent-soft);
  background: var(--surface);
}

.auth-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
}

.remember-me {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: var(--muted);
}

.forgot-password {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  color: var(--accent-primary);
  text-decoration: none;
  cursor: pointer;
  transition: opacity 0.2s;
}

.forgot-password:hover {
  opacity: 0.8;
}

.btn-auth {
  font-family: var(--font-secondary);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 0;
  background: var(--accent);
  color: var(--accent-ink);
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  border: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  justify-content: center;
  align-items: center;
}

.btn-auth:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.btn-auth:active:not(:disabled) {
  transform: translateY(0);
}

.btn-auth:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-message {
  padding: 0.8rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 10px;
  color: #f87171;
  font-size: 0.85rem;
  text-align: center;
}

.info-message {
  padding: 0.8rem;
  background: var(--accent-soft);
  border: 1px solid var(--accent-soft);
  border-radius: 10px;
  color: var(--accent-primary);
  font-size: 0.85rem;
  text-align: center;
}

.auth-footer {
  margin-top: 2rem;
  text-align: center;
  font-size: 0.9rem;
  color: var(--muted);
}

.auth-footer a {
  color: var(--accent-primary);
  text-decoration: none;
  font-weight: 600;
}

/* Loader style */
.loader {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top-color: var(--accent-ink);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
