<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <h1 class="page-title">Membros e Organizations</h1>
      <p class="page-subtitle">
        {{ canListAll ? 'Gerencie todas as organizations da plataforma.' : 'Gerencie a sua organization e seus membros.' }}
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

          <button class="btn-secondary btn-new-org" @click="showCreateOrgModal = true">
            + Nova Organization
          </button>
        </div>

        <div v-if="selectedOrganizationId" class="org-detail">
          <div class="org-header">
            <template v-if="!editingName">
              <h2 class="org-name">{{ orgName }}</h2>
              <button class="icon-btn" title="Renomear" @click="editingName = true">
                <Pencil :size="16" />
              </button>
            </template>
            <form v-else class="rename-form" @submit.prevent="handleRename">
              <input v-model="orgName" type="text" class="field-input" placeholder="Nome da organization">
              <button type="submit" class="btn-secondary" :disabled="renaming">
                {{ renaming ? 'Salvando...' : 'Salvar' }}
              </button>
              <button type="button" class="btn-cancel" @click="editingName = false">Cancelar</button>
            </form>
          </div>

          <div class="actions-row">
            <button v-if="!canListAll" class="btn-secondary" @click="showFoundModal = true">
              Fundar outra organization
            </button>
            <button v-if="!canListAll" class="btn-secondary" @click="showCreateSubOrgModal = true">
              + Criar sub-organization
            </button>
            <button class="btn-secondary" @click="showAddMemberModal = true">
              + Adicionar membro
            </button>
            <button v-if="canTransferOwnership" class="btn-secondary" @click="openTransferModal">
              Transferir titularidade
            </button>
          </div>

          <h3 class="section-title">Membros</h3>
          <div class="table-wrapper">
            <table class="members-table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>E-mail</th>
                  <th>Role</th>
                  <th class="text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="member in members" :key="member.user_id">
                  <td data-label="Nome">{{ member.name }}</td>
                  <td class="cell-muted" data-label="E-mail">{{ member.email }}</td>
                  <td data-label="Role">
                    <select
                      v-if="member.user_id !== user?.id"
                      class="field-input role-select-inline"
                      :value="member.role.id"
                      :disabled="changingRoleUserId === member.user_id"
                      @change="handleChangeRole(member.user_id, ($event.target as HTMLSelectElement).value)"
                    >
                      <option v-for="role in organizationRoles" :key="role.id" :value="role.id">
                        {{ role.name }}
                      </option>
                    </select>
                    <span v-else>{{ member.role.name }}</span>
                  </td>
                  <td class="text-right" data-label="Ações">
                    <button
                      v-if="member.user_id !== user?.id"
                      class="icon-btn icon-btn-danger"
                      title="Remover membro"
                      :disabled="removingUserId === member.user_id"
                      @click="handleRemoveMember(member.user_id)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="membersMeta && membersMeta.last_page > 1" class="pagination">
            <button class="btn-page" :disabled="membersPage <= 1" @click="changeMembersPage(membersPage - 1)">Anterior</button>
            <span class="page-info">Página {{ membersMeta.current_page }} de {{ membersMeta.last_page }} ({{ membersMeta.total }} membros)</span>
            <button class="btn-page" :disabled="membersPage >= membersMeta.last_page" @click="changeMembersPage(membersPage + 1)">Próxima</button>
          </div>
        </div>
      </div>
    </div>

    <FoundOrganizationModal
      :show="showFoundModal"
      @close="showFoundModal = false"
      @founded="handleFounded"
    />

    <CreateSubOrganizationModal
      :show="showCreateSubOrgModal"
      @close="showCreateSubOrgModal = false"
      @created="handleSubOrgCreated"
    />

    <AddMemberModal
      v-if="selectedOrganizationId"
      :show="showAddMemberModal"
      :organization-id="selectedOrganizationId"
      :organization-roles="organizationRoles"
      @close="showAddMemberModal = false"
      @added="fetchMembers(selectedOrganizationId, { page: membersPage })"
    />

    <TransferOwnershipModal
      v-if="selectedOrganizationId"
      :show="showTransferModal"
      :organization-id="selectedOrganizationId"
      :current-user-id="user?.id"
      :members="transferModalMembers"
      @close="showTransferModal = false"
      @transferred="handleTransferred"
    />

    <div v-if="showCreateOrgModal" class="modal-overlay">
      <div class="backdrop" @click="showCreateOrgModal = false" />
      <div class="modal-content">
        <div class="modal-inner">
          <div class="modal-header">
            <h2 class="modal-title">Nova Organization</h2>
            <button class="close-btn" @click="showCreateOrgModal = false">
              <X class="close-icon" :size="24" />
            </button>
          </div>

          <form class="create-form" @submit.prevent="handleCreate">
            <input v-model="newOrgName" type="text" class="field-input" placeholder="Nome da organization" required>
            <select v-model="newOrgParentId" class="field-input">
              <option value="">Sem organization-pai</option>
              <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
                {{ org.name }}
              </option>
            </select>
            <input v-model="newOrgOwnerCpf" type="text" class="field-input" placeholder="CPF do responsável (opcional) — 000.000.000-00" maxlength="14">
            <button type="submit" class="btn-add" :disabled="creating">
              {{ creating ? 'Criando...' : 'Criar' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch, watchEffect } from 'vue'
import { Pencil, Trash2, X } from 'lucide-vue-next'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'
import { OrganizationService } from '~/modules/organizations/services/OrganizationService'
import { useRoles } from '~/modules/admin/hooks/useRoles'
import FoundOrganizationModal from '~/modules/organizations/components/FoundOrganizationModal.vue'
import CreateSubOrganizationModal from '~/modules/organizations/components/CreateSubOrganizationModal.vue'
import AddMemberModal from '~/modules/organizations/components/AddMemberModal.vue'
import TransferOwnershipModal from '~/modules/organizations/components/TransferOwnershipModal.vue'

definePageMeta({
  middleware: 'auth'
})

const { user, restoreSession } = useAuth()
const {
  allOrganizations,
  members,
  membersMeta,
  fetchAllOrganizations,
  fetchMembers,
  updateOrganization,
  createOrganization,
  updateMemberRole,
  removeMember
} = useOrganizations()
const transferModalMembers = ref<typeof members.value>([])
const membersPage = ref(1)
const { roles, fetchRoles } = useRoles()

const changingRoleUserId = ref('')
const removingUserId = ref('')

const canListAll = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false)
const accessDenied = computed(() => !user.value?.permissions?.includes('admin.organizations.manage-members'))
const canTransferOwnership = computed(() => canListAll.value || user.value?.role?.slug === 'org-admin')

const organizationRoles = computed(() => roles.value.filter((role) => role.scope === 'organization'))

const selectedOrganizationId = ref('')
const orgName = ref('')
const renaming = ref(false)
const editingName = ref(false)

const showFoundModal = ref(false)
const showCreateSubOrgModal = ref(false)
const showAddMemberModal = ref(false)
const showTransferModal = ref(false)
const showCreateOrgModal = ref(false)

const newOrgName = ref('')
const newOrgParentId = ref('')
const newOrgOwnerCpf = ref('')
const creating = ref(false)

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
  editingName.value = false
  membersPage.value = 1
  const org = allOrganizations.value.find((item) => item.id === organizationId)
  orgName.value = org?.name || user.value?.organization?.name || ''
  await fetchMembers(organizationId, { page: membersPage.value })
}

async function changeMembersPage(page: number) {
  membersPage.value = page
  await fetchMembers(selectedOrganizationId.value, { page })
}

async function openTransferModal() {
  // O modal precisa de todos os membros pra escolher o novo titular, não só
  // a página atual da tabela — busca uma lista à parte com limite alto.
  const response = await OrganizationService.listMembers(selectedOrganizationId.value, { limit: 500 }) as any
  transferModalMembers.value = response.data?.data || []
  showTransferModal.value = true
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
    editingName.value = false
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
    showCreateOrgModal.value = false
    await fetchAllOrganizations()
  } finally {
    creating.value = false
  }
}

async function handleFounded() {
  await restoreSession()
  if (user.value?.organization) {
    selectOrganization(user.value.organization.id)
  }
}

function handleSubOrgCreated() {
  window.alert('Sub-organization criada com sucesso. Use o seletor de organization no menu lateral pra trocar para ela e gerenciá-la.')
}

async function handleTransferred() {
  await restoreSession()
  if (selectedOrganizationId.value) {
    await fetchMembers(selectedOrganizationId.value, { page: membersPage.value })
  }
}

async function handleChangeRole(userId: string, roleId: string) {
  changingRoleUserId.value = userId
  try {
    const result = await updateMemberRole(userId, roleId, canListAll.value ? selectedOrganizationId.value : undefined)
    if (!result.success) {
      window.alert(result.message)
    }
    await fetchMembers(selectedOrganizationId.value, { page: membersPage.value })
  } finally {
    changingRoleUserId.value = ''
  }
}

async function handleRemoveMember(userId: string) {
  if (!window.confirm('Remover este membro da organization?')) return

  removingUserId.value = userId
  try {
    const result = await removeMember(userId, canListAll.value ? selectedOrganizationId.value : undefined)
    if (!result.success) {
      window.alert(result.message)
      return
    }
    await fetchMembers(selectedOrganizationId.value, { page: membersPage.value })
  } finally {
    removingUserId.value = ''
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

.org-detail {
  flex: 1;
  min-width: 0;
}

@media (max-width: 768px) {
  .layout {
    flex-direction: column;
  }

  .org-list {
    width: 100%;
    flex-direction: row;
    flex-wrap: wrap;
  }
}

.org-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.org-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--ink);
}

.icon-btn {
  display: flex;
  padding: 0.4rem;
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--muted);
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}

.icon-btn:hover {
  color: var(--accent);
  border-color: var(--accent);
}

.icon-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.icon-btn-danger:hover {
  color: var(--danger);
  border-color: var(--danger);
}

.role-select-inline {
  padding: 0.4rem 0.6rem;
  font-size: 0.85rem;
}

.text-right {
  text-align: right;
}

.actions-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.section-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 1.5rem 0 0.75rem;
}

.rename-form {
  display: flex;
  gap: 0.5rem;
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

.field-input option {
  background: var(--surface);
  color: var(--ink);
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

.btn-cancel {
  padding: 0.65rem 1.25rem;
  background: var(--surface-2);
  color: var(--ink);
  border: 1px solid var(--border);
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.page-info {
  color: var(--muted);
  font-size: 0.875rem;
}

.btn-page {
  padding: 0.5rem 1rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--ink);
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-page:hover:not(:disabled) {
  opacity: 0.85;
}

.btn-page:disabled {
  opacity: 0.4;
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

.access-denied {
  text-align: center;
  padding: 5rem 0;
  color: var(--muted);
}

@media (max-width: 640px) {
  .members-table thead {
    display: none;
  }

  .members-table, .members-table tbody, .members-table tr, .members-table td {
    display: block;
    width: 100%;
  }

  .members-table tr {
    padding: 0.5rem 0;
  }

  .members-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 1rem;
    text-align: right;
    border-bottom: none;
  }

  .members-table td::before {
    content: attr(data-label);
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted);
    text-align: left;
    flex-shrink: 0;
  }

  .members-table tr:not(:last-child) {
    border-bottom: 1px solid var(--border);
  }
}

.access-denied h2 {
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.access-denied a {
  color: var(--accent);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
}

.modal-content {
  position: relative;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 24px;
  width: 100%;
  max-width: 28rem;
  overflow: hidden;
  box-shadow: var(--shadow);
}

.modal-inner {
  padding: 2rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-title {
  color: var(--ink);
  font-size: 1.5rem;
  font-weight: 700;
}

.close-btn {
  display: flex;
  color: var(--muted);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
}

.close-icon {
  width: 1.5rem;
  height: 1.5rem;
}

.close-btn:hover {
  color: var(--ink);
}

.create-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
</style>
