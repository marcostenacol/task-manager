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
          <h1 class="page-title">Usuários</h1>
          <p class="page-subtitle">Gerencie os usuários da plataforma.</p>
        </div>
        <button class="btn-new-user" @click="openCreateModal">
          <Plus class="btn-icon" :size="20" />
          Novo Usuário
        </button>
      </div>
      <UserTable
        class="user-table-wrap"
        :users="users"
        :loading="loading"
        :current-user-id="user?.id"
        :current-user-level="currentUserLevel"
        @ban="handleBan"
        @activate="handleActivate"
        @edit="openEditModal"
        @delete="handleDelete"
      />
      <UserFormModal
        :show="showModal"
        :user="selectedUser"
        :roles="roles"
        @close="showModal = false"
        @saved="fetchUsers"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watchEffect } from 'vue'
import { Plus } from 'lucide-vue-next'
import UserTable from '~/modules/admin/components/UserTable.vue'
import UserFormModal from '~/modules/admin/components/UserFormModal.vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useUsers } from '~/modules/admin/hooks/useUsers'
import type { AdminUser } from '~/modules/admin/models/admin'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { users, roles, loading, fetchUsers, fetchRoles, banUser, activateUser, deleteUser } = useUsers()

const showModal = ref(false)
const selectedUser = ref<AdminUser | null>(null)

const accessDenied = computed(() => {
  if (!user.value) return true
  const isAdmin = user.value.role?.slug === 'admin'
  const hasPermission = user.value.permissions?.includes('admin.users.list')
  return !isAdmin && !hasPermission
})

const currentUserLevel = computed(() => {
  const currentRole = roles.value.find((role) => role.slug === user.value?.role_slug)
  return currentRole?.level ?? null
})

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchUsers()
    fetchRoles()
  }
})

function handleBan(targetUser: { id: string }) {
  const reason = window.prompt('Motivo do banimento:')
  if (reason) {
    banUser(targetUser.id, reason)
  }
}

function handleActivate(targetUser: { id: string }) {
  activateUser(targetUser.id)
}

function handleDelete(targetUser: { id: string; name: string }) {
  if (window.confirm(`Excluir o usuário "${targetUser.name}"? Essa ação não pode ser desfeita pela tela.`)) {
    deleteUser(targetUser.id)
  }
}

function openCreateModal() {
  selectedUser.value = null
  showModal.value = true
}

function openEditModal(targetUser: AdminUser) {
  selectedUser.value = targetUser
  showModal.value = true
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

.btn-new-user {
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

.btn-new-user:hover {
  opacity: 0.9;
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.user-table-wrap {
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
