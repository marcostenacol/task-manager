<template>
  <div class="auth-card glass">
    <div class="auth-header">
      <h2 class="font-outfit">Bem-vindo de volta</h2>
      <p>Entre com suas credenciais para continuar</p>
    </div>

    <form class="auth-form" @submit.prevent="handleSubmit">
      <div class="input-group">
        <label for="email">E-mail</label>
        <div class="input-wrapper">
          <span class="icon">📧</span>
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
        <label for="password">Senha</label>
        <div class="input-wrapper">
          <span class="icon">🔒</span>
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
          <span>Lembrar de mim</span>
        </label>
        <a href="#" class="forgot-password">Esqueceu a senha?</a>
      </div>

      <button type="submit" class="btn-auth" :disabled="loading">
        <span v-if="!loading">Entrar</span>
        <span v-else class="loader"/>
      </button>

      <div v-if="error" class="error-message">
        {{ error }}
      </div>
    </form>

    <div class="auth-footer">
      <p>Não tem uma conta? <NuxtLink to="/register">Cadastre-se</NuxtLink></p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '../hooks/useAuth'

const { login, loading } = useAuth()
const form = reactive({
  email: '',
  password: '',
  remember: false
})
const error = ref('')

async function handleSubmit() {
  error.value = ''
  try {
    const response = await login(form)
    if (!response.success) {
      error.value = response.message || 'Erro ao realizar login.'
    }
  } catch (err: any) {
    console.error('Login error detail:', err)
    error.value = 'Falha na conexão com o servidor.'
  }
}
</script>

<style scoped>
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
  font-size: 1.1rem;
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
  background: rgba(15, 23, 42, 0.8);
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
  color: var(--text-secondary);
}

.forgot-password {
  color: var(--accent-primary);
  text-decoration: none;
  transition: opacity 0.2s;
}

.forgot-password:hover {
  opacity: 0.8;
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

/* Loader style */
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
