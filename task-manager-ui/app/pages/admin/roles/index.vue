<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <div class="page-header">
        <div>
          <h1 class="page-title">Roles</h1>
          <p class="page-subtitle">Gerencie roles e suas permissões.</p>
        </div>
        <div class="header-actions">
          <div v-if="isGlobalActor" class="org-filter">
            <label class="org-filter-label" for="role-scope-select">Escopo</label>
            <select id="role-scope-select" v-model="selectedScope" class="org-filter-select" @change="handleScopeChange">
              <option value="global">Global</option>
              <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
                {{ org.name }}
              </option>
            </select>
          </div>
          <button class="btn-new-role" @click="openCreateModal">
            <Plus class="btn-icon" :size="20" />
            Nova Role
          </button>
        </div>
      </div>
      <RoleTable
        class="role-table-wrap"
        :roles="roles"
        :loading="loading"
        :current-role-id="currentRole?.id"
        :current-role-level="currentRole?.level"
        :is-global-actor="isGlobalActor"
        @edit="openEditModal"
        @delete="handleDelete"
      />
      <RoleFormModal
        :show="showModal"
        :role="selectedRole"
        :permissions="permissions"
        :current-role-level="currentRole?.level"
        :is-global-actor="isGlobalActor"
        @close="showModal = false"
        @saved="fetchRoles"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watchEffect } from 'vue'
import { Plus } from 'lucide-vue-next'
import RoleTable from '~/modules/admin/components/RoleTable.vue'
import RoleFormModal from '~/modules/admin/components/RoleFormModal.vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useRoles } from '~/modules/admin/hooks/useRoles'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'
import type { Role } from '~/modules/admin/models/admin'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { roles, permissions, loading, fetchRoles, fetchPermissions, deleteRole } = useRoles()
const { allOrganizations, fetchAllOrganizations } = useOrganizations()

const showModal = ref(false)
const selectedRole = ref<Role | null>(null)
const selectedScope = ref('global')

const accessDenied = computed(() => {
  if (!user.value) return true
  return !user.value.permissions?.includes('admin.roles.manage')
})

const currentRole = computed(() => roles.value.find((role) => role.slug === user.value?.role?.slug) ?? null)
const isGlobalActor = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false)

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchRoles(isGlobalActor.value ? { scope: 'global' } : undefined)
    fetchPermissions()
    if (isGlobalActor.value) {
      fetchAllOrganizations()
    }
  }
})

function handleScopeChange() {
  if (selectedScope.value === 'global') {
    fetchRoles({ scope: 'global' })
    return
  }
  fetchRoles({ organization_id: selectedScope.value })
}

function openCreateModal() {
  selectedRole.value = null
  showModal.value = true
}

function openEditModal(role: Role) {
  selectedRole.value = role
  showModal.value = true
}

function handleDelete(role: Role) {
  if (window.confirm(`Excluir a role "${role.name}"? Essa ação não pode ser desfeita pela tela.`)) {
    deleteRole(role.id)
  }
}
</script>

<style scoped>
.page-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink);
}

.page-subtitle {
  color: var(--muted);
  margin-top: 0.25rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.org-filter {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.org-filter-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
}

.org-filter-select {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.5rem 0.75rem;
  color: var(--ink);
  font-size: 0.85rem;
}

.btn-new-role {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: var(--shadow);
  transition: opacity 0.2s;
}

.btn-new-role:hover {
  opacity: 0.9;
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.role-table-wrap {
  display: block;
  margin-top: 1.5rem;
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
