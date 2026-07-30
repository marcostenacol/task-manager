<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <h1 class="page-title">Membros da Organization</h1>
      <p class="page-subtitle">Adicione um usuário já existente à sua organization buscando pelo CPF.</p>

      <form class="lookup-form" @submit.prevent="handleLookup">
        <input v-model="cpf" type="text" class="field-input" placeholder="CPF (somente números)" maxlength="11">
        <button type="submit" class="btn-lookup" :disabled="loadingLookup">
          {{ loadingLookup ? 'Buscando...' : 'Buscar' }}
        </button>
      </form>

      <p v-if="lookupError" class="error-message">{{ lookupError }}</p>
      <p v-if="searched && !lookupResult && !lookupError" class="empty-message">Nenhum usuário encontrado com esse CPF.</p>

      <div v-if="lookupResult" class="result-card">
        <div class="result-info">
          <strong>{{ lookupResult.name }}</strong>
          <span class="result-email">{{ lookupResult.email }}</span>
        </div>

        <select v-model="selectedRoleId" class="field-input role-select">
          <option value="" disabled>Selecione a role...</option>
          <option v-for="role in organizationRoles" :key="role.id" :value="role.id">
            {{ role.name }}
          </option>
        </select>

        <button class="btn-add" :disabled="!selectedRoleId || addingMember" @click="handleAdd">
          {{ addingMember ? 'Adicionando...' : 'Adicionar à organization' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watchEffect } from 'vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'
import { useRoles } from '~/modules/admin/hooks/useRoles'
import type { MemberLookupResult } from '~/modules/organizations/models/organization'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { lookupMember, addMember } = useOrganizations()
const { roles, fetchRoles } = useRoles()

const accessDenied = computed(() => !user.value?.permissions?.includes('admin.organizations.manage-members'))

const organizationRoles = computed(() => roles.value.filter((role) => role.scope === 'organization'))

const cpf = ref('')
const lookupResult = ref<MemberLookupResult | null>(null)
const lookupError = ref('')
const searched = ref(false)
const loadingLookup = ref(false)
const selectedRoleId = ref('')
const addingMember = ref(false)

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchRoles()
  }
})

async function handleLookup() {
  loadingLookup.value = true
  lookupError.value = ''
  lookupResult.value = null
  searched.value = false
  try {
    const result = await lookupMember(cpf.value)
    searched.value = true
    if (!result.success) {
      lookupError.value = result.message || 'Erro ao buscar usuário.'
      return
    }
    lookupResult.value = result.result ?? null
  } finally {
    loadingLookup.value = false
  }
}

async function handleAdd() {
  if (!lookupResult.value || !selectedRoleId.value) return

  addingMember.value = true
  try {
    const result = await addMember(lookupResult.value.user_id, selectedRoleId.value)
    if (!result.success) {
      window.alert(result.message)
      return
    }
    window.alert('Membro adicionado com sucesso.')
    lookupResult.value = null
    cpf.value = ''
    selectedRoleId.value = ''
    searched.value = false
  } finally {
    addingMember.value = false
  }
}
</script>

<style scoped>
.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink);
}

.page-subtitle {
  color: var(--muted);
  margin-top: 0.25rem;
}

.lookup-form {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
  max-width: 28rem;
}

.field-input {
  flex: 1;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.65rem 0.9rem;
  color: var(--ink);
}

.field-input:focus {
  outline: none;
  border-color: var(--accent);
}

.btn-lookup, .btn-add {
  padding: 0.65rem 1.25rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-lookup:hover:not(:disabled), .btn-add:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-lookup:disabled, .btn-add:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-message {
  color: var(--danger);
  margin-top: 1rem;
  font-size: 0.9rem;
}

.empty-message {
  color: var(--muted);
  margin-top: 1rem;
  font-style: italic;
}

.result-card {
  margin-top: 1.5rem;
  max-width: 28rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.result-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.result-info strong {
  color: var(--ink);
}

.result-email {
  color: var(--muted);
  font-size: 0.85rem;
}

.role-select option {
  background: var(--surface);
  color: var(--ink);
}

.access-denied {
  text-align: center;
  padding: 5rem 0;
  color: var(--muted);
}

.access-denied h2 {
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.access-denied a {
  color: var(--accent);
}
</style>
