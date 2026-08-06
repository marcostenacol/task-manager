<template>
  <div class="onboarding-page">
    <div class="card">
      <h1 class="title">{{ t('orgs.onboardingTitle') }}</h1>
      <p class="subtitle">
        {{ t('orgs.onboardingSubtitle') }}
      </p>

      <form class="form" @submit.prevent="handleSubmit">
        <label class="field-label" for="org-name">{{ t('orgs.nameLabel') }}</label>
        <input
          id="org-name"
          v-model="name"
          type="text"
          class="field-input"
          :placeholder="t('orgs.onboardingNamePlaceholder')"
          required
        >

        <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>

        <button type="submit" class="btn-submit" :disabled="loading">
          {{ loading ? t('orgs.creating') : t('orgs.createOrganization') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'
import { useAuth } from '~/modules/auth/hooks/useAuth'

definePageMeta({
  middleware: 'auth'
})

const { t } = useI18n()
const { onboard } = useOrganizations()
const { restoreSession } = useAuth()

const name = ref('')
const loading = ref(false)
const errorMessage = ref('')

async function handleSubmit() {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await onboard(name.value)
    if (!result.success) {
      errorMessage.value = result.message || t('orgs.createOrganizationError')
      return
    }
    await restoreSession()
    navigateTo('/tasks')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.onboarding-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.card {
  width: 100%;
  max-width: 28rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  box-shadow: var(--shadow);
  padding: 2.5rem;
}

.title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.subtitle {
  color: var(--muted);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--muted);
}

.field-input {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.field-input:focus {
  outline: none;
  border-color: var(--accent);
}

.error-message {
  color: var(--danger);
  font-size: 0.85rem;
}

.btn-submit {
  margin-top: 0.5rem;
  padding: 0.85rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-submit:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
