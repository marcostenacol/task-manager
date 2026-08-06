<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>{{ t('admin.accessDenied') }}</h2>
      <p>{{ t('admin.accessDeniedMessage') }}</p>
      <NuxtLink to="/tasks">{{ t('admin.backToTasks') }}</NuxtLink>
    </div>
    <div v-else>
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ t('admin.usersTitle') }}</h1>
          <p class="page-subtitle">{{ t('admin.usersSubtitle') }}</p>
        </div>
        <button class="btn-new-user" @click="openCreateModal">
          <Plus class="btn-icon" :size="20" />
          {{ t('admin.newUser') }}
        </button>
      </div>

      <form class="filters" @submit.prevent="applyFilters">
        <input v-model="filters.search" type="text" class="filter-input" :placeholder="t('admin.searchUsersPlaceholder')">
        <select v-model="filters.role_id" class="filter-input">
          <option value="">{{ t('admin.allRoles') }}</option>
          <option v-for="role in roles" :key="role.id" :value="role.id">
            {{ role.name }}
          </option>
        </select>
        <select v-model="filters.status_id" class="filter-input">
          <option value="">{{ t('admin.allStatuses') }}</option>
          <option v-for="status in userStatuses" :key="status.id" :value="status.id">
            {{ status.name }}
          </option>
        </select>
        <select v-if="isGlobalActor" v-model="filters.organization_id" class="filter-input">
          <option value="">{{ t('admin.allOrganizations') }}</option>
          <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
            {{ org.name }}
          </option>
        </select>
        <button type="submit" class="btn-filter">{{ t('admin.filter') }}</button>
      </form>

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
        @reset-password="handleResetPassword"
      />

      <div v-if="!loading && users.length > 0 && meta" class="pagination">
        <button class="btn-page" :disabled="meta.current_page <= 1" @click="previousPage">{{ t('admin.previousPage') }}</button>
        <span class="page-info">{{ t('admin.usersPageInfo', { current: meta.current_page, last: meta.last_page, total: meta.total }) }}</span>
        <button class="btn-page" :disabled="meta.current_page >= meta.last_page" @click="nextPage">{{ t('admin.nextPage') }}</button>
      </div>

      <UserFormModal
        :show="showModal"
        :user="selectedUser"
        :roles="roles"
        :current-user-level="currentUserLevel"
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
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'
import type { AdminUser } from '~/modules/admin/models/admin'

definePageMeta({
  middleware: 'auth'
})

const { t } = useI18n()
const { user } = useAuth()
const { users, roles, userStatuses, meta, filters, loading, fetchUsers, fetchRoles, fetchUserStatuses, applyFilters, banUser, activateUser, deleteUser, resetPassword } = useUsers()
const { allOrganizations, fetchAllOrganizations } = useOrganizations()

const showModal = ref(false)
const selectedUser = ref<AdminUser | null>(null)

const accessDenied = computed(() => {
  if (!user.value) return true
  const isAdmin = user.value.role?.slug === 'admin'
  const hasPermission = user.value.permissions?.includes('admin.users.list')
  return !isAdmin && !hasPermission
})

const isGlobalActor = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false)

function nextPage() {
  filters.page += 1
  fetchUsers()
}

function previousPage() {
  filters.page -= 1
  fetchUsers()
}

const currentUserLevel = computed(() => {
  const currentRole = roles.value.find((role) => role.slug === user.value?.role?.slug)
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
    fetchUserStatuses()
    if (isGlobalActor.value) {
      fetchAllOrganizations()
    }
  }
})

function handleBan(targetUser: { id: string }) {
  const reason = window.prompt(t('admin.banReasonPrompt'))
  if (reason) {
    banUser(targetUser.id, reason)
  }
}

function handleActivate(targetUser: { id: string }) {
  activateUser(targetUser.id)
}

function handleDelete(targetUser: { id: string; name: string }) {
  if (window.confirm(t('admin.confirmDeleteUser', { name: targetUser.name }))) {
    deleteUser(targetUser.id)
  }
}

async function handleResetPassword(targetUser: { id: string; name: string }) {
  const password = window.prompt(t('admin.resetPasswordPrompt', { name: targetUser.name }))
  if (!password) return

  const result = await resetPassword(targetUser.id, password)
  if (!result.success) {
    window.alert(result.message)
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

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.filter-input {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.6rem 0.85rem;
  color: var(--ink);
  font-size: 0.875rem;
}

.filter-input:focus {
  outline: none;
  border-color: var(--accent);
}

.filter-input option {
  background: var(--surface);
  color: var(--ink);
}

.btn-filter {
  padding: 0.6rem 1.25rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-filter:hover {
  opacity: 0.9;
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
