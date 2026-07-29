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
        <button class="btn-new-role" @click="openCreateModal">
          <Plus class="btn-icon" :size="20" />
          Nova Role
        </button>
      </div>
      <RoleTable
        class="role-table-wrap"
        :roles="roles"
        :loading="loading"
        :current-role-id="currentRole?.id"
        :current-role-level="currentRole?.level"
        @edit="openEditModal"
        @delete="handleDelete"
      />
      <RoleFormModal
        :show="showModal"
        :role="selectedRole"
        :permissions="permissions"
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
import type { Role } from '~/modules/admin/models/admin'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { roles, permissions, loading, fetchRoles, fetchPermissions, deleteRole } = useRoles()

const showModal = ref(false)
const selectedRole = ref<Role | null>(null)

const accessDenied = computed(() => {
  if (!user.value) return true
  return !user.value.permissions?.includes('admin.roles.manage')
})

const currentRole = computed(() => roles.value.find((role) => role.slug === user.value?.role?.slug) ?? null)

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchRoles()
    fetchPermissions()
  }
})

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
