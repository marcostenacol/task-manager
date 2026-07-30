<template>
  <div class="auth-card glass">
    <div class="auth-header">
      <h2 class="font-outfit">{{ t('auth.registerTitle') }}</h2>
      <p>{{ t('auth.registerSubtitle') }}</p>
    </div>

    <form class="auth-form" @submit.prevent="handleSubmit">
      <div class="input-group">
        <label for="name">{{ t('auth.name') }}</label>
        <div class="input-wrapper">
          <User class="icon" :size="18" />
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Seu nome"
            required
          >
        </div>
      </div>

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
        <label for="cpf">{{ t('auth.cpf') }}</label>
        <div class="input-wrapper">
          <IdCard class="icon" :size="18" />
          <input
            id="cpf"
            v-model="form.cpf"
            type="text"
            placeholder="Somente números"
            maxlength="11"
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
            placeholder="Mínimo 8 caracteres"
            required
          >
        </div>
      </div>

      <div class="input-group">
        <label for="password_confirmation">{{ t('auth.passwordConfirmation') }}</label>
        <div class="input-wrapper">
          <Lock class="icon" :size="18" />
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            placeholder="Repita a senha"
            required
          >
        </div>
      </div>

      <button type="submit" class="btn-auth" :disabled="loading">
        <span v-if="!loading">{{ t('auth.registerButton') }}</span>
        <span v-else class="loader"/>
      </button>

      <div v-if="error" class="error-message">
        {{ error }}
      </div>
    </form>

    <div class="auth-footer">
      <p>{{ t('auth.hasAccount') }} <NuxtLink to="/login">{{ t('auth.signIn') }}</NuxtLink></p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { IdCard, Lock, Mail, User } from 'lucide-vue-next'
import { AuthService } from '../services/AuthService'

const { t } = useI18n()
const loading = ref(false)
const error = ref('')
const form = reactive({
  name: '',
  email: '',
  cpf: '',
  password: '',
  password_confirmation: ''
})

async function handleSubmit() {
  loading.value = true
  error.value = ''
  try {
    const response = await AuthService.register(form)
    if (response.success) {
      // Após registrar, redireciona para login (ou faz login automático)
      // Vamos para login para simplificar e garantir que o usuário saiba as credenciais
      navigateTo('/login?registered=true')
    } else {
      error.value = response.message || 'Erro ao realizar cadastro.'
    }
  } catch (err: any) {
    console.error('Register error detail:', err)
    const firstFieldError = Object.values(err?.data?.data?.errors || {})[0] as string[] | undefined
    error.value = err?.data?.message || firstFieldError?.[0] || 'Falha na conexão com o servidor.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Mesmos estilos do LoginForm para consistência */
.auth-card {
  width: 100%;
  max-width: 450px;
  padding: 3rem;
  border-radius: 28px;
  background: var(--glass-bg);
  backdrop-filter: blur(12px);
  border: 1px solid var(--glass-border);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.auth-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.auth-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.auth-header p {
  color: var(--text-secondary);
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
  color: var(--text-secondary);
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
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  color: #fff;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.input-wrapper input:focus {
  outline: none;
  border-color: var(--accent-primary);
  box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
}

.btn-auth {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
  color: #000;
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
  box-shadow: 0 10px 25px rgba(56, 189, 248, 0.4);
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

.auth-footer {
  margin-top: 2rem;
  text-align: center;
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.auth-footer a {
  color: var(--accent-primary);
  text-decoration: none;
  font-weight: 600;
}

.loader {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top-color: #000;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
