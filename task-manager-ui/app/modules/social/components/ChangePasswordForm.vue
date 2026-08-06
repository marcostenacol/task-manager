<template>
  <form class="password-form glass" @submit.prevent="handleSubmit">
    <h3 class="form-title">{{ t('profile.changePasswordTitle') }}</h3>

    <div class="form-group">
      <label for="current_password">{{ t('profile.currentPasswordLabel') }}</label>
      <input
        id="current_password"
        v-model="formData.current_password"
        type="password"
        :placeholder="t('profile.currentPasswordPlaceholder')"
        required
        class="form-input"
      >
    </div>

    <div class="form-group">
      <label for="new_password">{{ t('profile.newPasswordLabel') }}</label>
      <input
        id="new_password"
        v-model="formData.new_password"
        type="password"
        :placeholder="t('profile.newPasswordPlaceholder', { min: 8 })"
        minlength="8"
        required
        class="form-input"
      >
    </div>

    <div class="form-group">
      <label for="new_password_confirmation">{{ t('profile.confirmPasswordLabel') }}</label>
      <input
        id="new_password_confirmation"
        v-model="formData.new_password_confirmation"
        type="password"
        :placeholder="t('profile.confirmPasswordPlaceholder')"
        minlength="8"
        required
        class="form-input"
      >
    </div>

    <p v-if="errorMessage" class="form-message form-message-error">{{ errorMessage }}</p>
    <p v-if="successMessage" class="form-message form-message-success">{{ successMessage }}</p>

    <div class="form-actions">
      <button type="submit" :disabled="submitting" class="btn-submit">
        <span v-if="submitting">{{ t('profile.saving') }}</span>
        <span v-else>{{ t('profile.changePasswordButton') }}</span>
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'

const { t } = useI18n()

const props = defineProps<{
  onChangePassword: (data: { current_password: string; new_password: string; new_password_confirmation: string }) => Promise<{ success: boolean; message?: string }>
}>()

const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const formData = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

function resetForm() {
  formData.current_password = ''
  formData.new_password = ''
  formData.new_password_confirmation = ''
}

async function handleSubmit() {
  errorMessage.value = ''
  successMessage.value = ''

  if (formData.new_password !== formData.new_password_confirmation) {
    errorMessage.value = t('profile.passwordMismatch')
    return
  }

  submitting.value = true
  try {
    const result = await props.onChangePassword({ ...formData })
    if (result?.success) {
      successMessage.value = t('profile.passwordChangedSuccess')
      resetForm()
      return
    }
    errorMessage.value = result?.message || t('profile.passwordChangeFailed')
  } catch (err) {
    console.error('Erro inesperado ao trocar senha:', err)
    errorMessage.value = t('profile.passwordChangeUnexpectedError')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.password-form {
  padding: 2rem;
  border-radius: 24px;
  width: 100%;
}

.glass {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.form-title {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
  font-weight: 700;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-input {
  width: 100%;
  padding: 0.8rem 1rem;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid var(--glass-border);
  border-radius: 12px;
  color: var(--text-primary);
  font-family: inherit;
  transition: all 0.3s;
}

.form-input:focus {
  outline: none;
  border-color: var(--accent-primary);
  box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}

.form-message {
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.form-message-error {
  color: var(--danger);
}

.form-message-success {
  color: var(--success);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-submit {
  padding: 0.8rem 1.5rem;
  background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
  border: none;
  border-radius: 12px;
  color: var(--accent-ink);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
