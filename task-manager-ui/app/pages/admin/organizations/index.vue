<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <h1 class="page-title">Organizations</h1>
      <p class="page-subtitle">
        {{ canListAll ? 'Gerencie todas as organizations da plataforma.' : 'Gerencie a sua organization.' }}
      </p>

      <div class="layout">
        <div v-if="canListAll" class="org-list">
          <h3 class="section-title">Todas as organizations</h3>
          <button
            v-for="org in allOrganizations"
            :key="org.id"
            class="org-item"
            :class="{ active: org.id === selectedOrganizationId }"
            @click="selectOrganization(org.id)"
          >
            <span>{{ org.name }}</span>
            <span class="org-item-count">{{ org.members_count }} membro(s)</span>
          </button>

          <button class="btn-secondary btn-new-org" @click="showCreateForm = !showCreateForm">
            {{ showCreateForm ? 'Cancelar' : '+ Nova Organization' }}
          </button>

          <form v-if="showCreateForm" class="create-form" @submit.prevent="handleCreate">
            <input v-model="newOrgName" type="text" class="field-input" placeholder="Nome da organization" required>
            <select v-model="newOrgParentId" class="field-input">
              <option value="">Sem organization-pai</option>
              <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
                {{ org.name }}
              </option>
            </select>
            <input v-model="newOrgOwnerCpf" type="text" class="field-input" placeholder="CPF do responsável (opcional)" maxlength="11">
            <button type="submit" class="btn-add" :disabled="creating">
              {{ creating ? 'Criando...' : 'Criar' }}
            </button>
          </form>
        </div>

        <div v-if="selectedOrganizationId" class="org-detail">
          <form class="rename-form" @submit.prevent="handleRename">
            <input v-model="orgName" type="text" class="field-input" placeholder="Nome da organization">
            <button type="submit" class="btn-secondary" :disabled="renaming">
              {{ renaming ? 'Salvando...' : 'Renomear' }}
            </button>
          </form>

          <template v-if="!canListAll">
            <h3 class="section-title">Fundar outra organization</h3>
            <p class="section-hint">Você vira administrador dela e ela passa a ser sua organization ativa.</p>
            <form class="rename-form" @submit.prevent="handleFound">
              <input v-model="newOrgName" type="text" class="field-input" placeholder="Nome da nova organization" required>
              <button type="submit" class="btn-secondary" :disabled="founding">
                {{ founding ? 'Criando...' : 'Fundar' }}
              </button>
            </form>
            <p v-if="foundError" class="error-message">{{ foundError }}</p>
          </template>

          <h3 class="section-title">Membros</h3>
          <table class="members-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Role</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="member in members" :key="member.user_id">
                <td>{{ member.name }}</td>
                <td class="cell-muted">{{ member.email }}</td>
                <td>{{ member.role.name }}</td>
              </tr>
            </tbody>
          </table>

          <h3 class="section-title">Adicionar membro por CPF</h3>
          <form class="lookup-form" @submit.prevent="handleLookup">
            <input v-model="cpf" type="text" class="field-input" placeholder="CPF (somente números)" maxlength="11">
            <button type="submit" class="btn-secondary" :disabled="loadingLookup">
              {{ loadingLookup ? 'Buscando...' : 'Buscar' }}
            </button>
          </form>

          <p v-if="lookupError" class="error-message">{{ lookupError }}</p>

          <div v-if="searched && !lookupResult && !lookupError" class="result-card">
            <p class="empty-message">Nenhum usuário encontrado com esse CPF. Você pode criar um novo usuário já com esse CPF.</p>

            <form class="create-form" @submit.prevent="handleCreateMember">
              <input v-model="newMemberName" type="text" class="field-input" placeholder="Nome completo" required>
              <input v-model="newMemberEmail" type="email" class="field-input" placeholder="E-mail" required>

              <select v-model="newMemberRoleId" class="field-input role-select">
                <option value="" disabled>Selecione a role...</option>
                <option v-for="role in organizationRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>

              <p class="section-hint">A senha inicial do usuário será o próprio CPF.</p>

              <button type="submit" class="btn-add" :disabled="!newMemberRoleId || creatingMember">
                {{ creatingMember ? 'Criando...' : 'Criar e adicionar à organization' }}
              </button>
            </form>
          </div>

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
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch, watchEffect } from 'vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'
import { useRoles } from '~/modules/admin/hooks/useRoles'
import type { MemberLookupResult } from '~/modules/organizations/models/organization'

definePageMeta({
  middleware: 'auth'
})

const { user, restoreSession } = useAuth()
const {
  allOrganizations,
  members,
  fetchAllOrganizations,
  fetchMembers,
  lookupMember,
  addMember,
  updateOrganization,
  createOrganization,
  createMember,
  onboard
} = useOrganizations()
const { roles, fetchRoles } = useRoles()

const canListAll = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false)
const accessDenied = computed(() => !user.value?.permissions?.includes('admin.organizations.manage-members'))

const organizationRoles = computed(() => roles.value.filter((role) => role.scope === 'organization'))

const selectedOrganizationId = ref('')
const orgName = ref('')
const renaming = ref(false)

const cpf = ref('')
const lookupResult = ref<MemberLookupResult | null>(null)
const lookupError = ref('')
const searched = ref(false)
const loadingLookup = ref(false)
const selectedRoleId = ref('')
const addingMember = ref(false)

const newMemberName = ref('')
const newMemberEmail = ref('')
const newMemberRoleId = ref('')
const creatingMember = ref(false)

const showCreateForm = ref(false)
const newOrgName = ref('')
const newOrgParentId = ref('')
const newOrgOwnerCpf = ref('')
const creating = ref(false)

const founding = ref(false)
const foundError = ref('')

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(async () => {
  if (accessDenied.value) return

  fetchRoles()

  if (canListAll.value) {
    await fetchAllOrganizations()
    return
  }

  if (user.value?.organization) {
    selectOrganization(user.value.organization.id)
  }
})

async function selectOrganization(organizationId: string) {
  selectedOrganizationId.value = organizationId
  const org = allOrganizations.value.find((item) => item.id === organizationId)
  orgName.value = org?.name || user.value?.organization?.name || ''
  await fetchMembers(organizationId)
}

watch(() => user.value?.organization, (organization) => {
  if (!canListAll.value && organization) {
    selectOrganization(organization.id)
  }
})

async function handleRename() {
  renaming.value = true
  try {
    const result = await updateOrganization(selectedOrganizationId.value, orgName.value)
    if (!result.success) {
      window.alert(result.message)
      return
    }
    if (canListAll.value) {
      await fetchAllOrganizations()
    }
  } finally {
    renaming.value = false
  }
}

async function handleCreate() {
  creating.value = true
  try {
    const result = await createOrganization(newOrgName.value, newOrgParentId.value || undefined, newOrgOwnerCpf.value || undefined)
    if (!result.success) {
      window.alert(result.message)
      return
    }
    newOrgName.value = ''
    newOrgParentId.value = ''
    newOrgOwnerCpf.value = ''
    showCreateForm.value = false
    await fetchAllOrganizations()
  } finally {
    creating.value = false
  }
}

async function handleFound() {
  founding.value = true
  foundError.value = ''
  try {
    const result = await onboard(newOrgName.value)
    if (!result.success) {
      foundError.value = result.message || 'Não foi possível fundar a organization.'
      return
    }
    newOrgName.value = ''
    await restoreSession()
    if (user.value?.organization) {
      selectOrganization(user.value.organization.id)
    }
  } finally {
    founding.value = false
  }
}

async function handleLookup() {
  loadingLookup.value = true
  lookupError.value = ''
  lookupResult.value = null
  searched.value = false
  try {
    const result = await lookupMember(cpf.value, canListAll.value ? selectedOrganizationId.value : undefined)
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

async function handleCreateMember() {
  if (!newMemberRoleId.value) return

  creatingMember.value = true
  try {
    const result = await createMember(
      newMemberName.value,
      newMemberEmail.value,
      cpf.value,
      newMemberRoleId.value,
      canListAll.value ? selectedOrganizationId.value : undefined
    )
    if (!result.success) {
      window.alert(result.message)
      return
    }
    window.alert('Usuário criado e adicionado com sucesso.')
    newMemberName.value = ''
    newMemberEmail.value = ''
    newMemberRoleId.value = ''
    cpf.value = ''
    searched.value = false
    await fetchMembers(selectedOrganizationId.value)
  } finally {
    creatingMember.value = false
  }
}

async function handleAdd() {
  if (!lookupResult.value || !selectedRoleId.value) return

  addingMember.value = true
  try {
    const result = await addMember(
      lookupResult.value.user_id,
      selectedRoleId.value,
      canListAll.value ? selectedOrganizationId.value : undefined
    )
    if (!result.success) {
      window.alert(result.message)
      return
    }
    window.alert('Membro adicionado com sucesso.')
    lookupResult.value = null
    cpf.value = ''
    selectedRoleId.value = ''
    searched.value = false
    await fetchMembers(selectedOrganizationId.value)
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

.layout {
  display: flex;
  gap: 2rem;
  margin-top: 1.5rem;
  align-items: flex-start;
}

.org-list {
  width: 16rem;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.org-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  align-items: flex-start;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  color: var(--ink);
  cursor: pointer;
  text-align: left;
  transition: background 0.2s, border-color 0.2s;
}

.org-item:hover {
  background: var(--surface-2);
}

.org-item.active {
  border-color: var(--accent);
  background: var(--accent-soft);
}

.org-item-count {
  font-size: 0.75rem;
  color: var(--muted);
}

.btn-new-org {
  margin-top: 0.5rem;
}

.create-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.org-detail {
  flex: 1;
  min-width: 0;
}

.section-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 1.5rem 0 0.75rem;
}

.rename-form, .lookup-form {
  display: flex;
  gap: 0.75rem;
  max-width: 28rem;
}

.section-hint {
  color: var(--muted);
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
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

.btn-secondary, .btn-add {
  padding: 0.65rem 1.25rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  white-space: nowrap;
}

.btn-secondary:hover:not(:disabled), .btn-add:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-secondary:disabled, .btn-add:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.members-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}

.members-table th {
  text-align: left;
  padding: 0.65rem 1rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
  border-bottom: 1px solid var(--border);
}

.members-table td {
  padding: 0.65rem 1rem;
  border-bottom: 1px solid var(--border);
  color: var(--ink);
}

.cell-muted {
  color: var(--muted);
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
