<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>{{ t('admin.accessDenied') }}</h2>
      <p>{{ t('admin.accessDeniedMessage') }}</p>
      <NuxtLink to="/tasks">{{ t('admin.backToTasks') }}</NuxtLink>
    </div>
    <div v-else>
      <h1 class="page-title">{{ t('admin.auditTitle') }}</h1>
      <p class="page-subtitle">{{ t('admin.auditSubtitle') }}</p>

      <form class="filters" @submit.prevent="applyFilters">
        <select v-model="filters.action" class="filter-input">
          <option value="">{{ t('admin.allActions') }}</option>
          <option v-for="(label, action) in ACTION_LABELS" :key="action" :value="action">
            {{ label }}
          </option>
        </select>
        <select v-if="isGlobalActor" v-model="filters.organization_id" class="filter-input">
          <option value="">{{ t('admin.allOrganizations') }}</option>
          <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
            {{ org.name }}
          </option>
        </select>
        <input v-model="filters.actor_name" type="text" class="filter-input" :placeholder="t('admin.actorNamePlaceholder')">
        <input v-model="filters.date_from" type="date" class="filter-input">
        <input v-model="filters.date_to" type="date" class="filter-input">
        <button type="submit" class="btn-filter">{{ t('admin.filter') }}</button>
      </form>

      <AuditLogTable class="log-table-wrap" :logs="logs" :loading="loading" />

      <div v-if="!loading && logs.length > 0" class="pagination">
        <button class="btn-page" :disabled="currentPage <= 1" @click="previousPage">{{ t('admin.previousPage') }}</button>
        <span class="page-info">{{ t('admin.auditPageInfo', { current: currentPage, last: lastPage, total }) }}</span>
        <button class="btn-page" :disabled="currentPage >= lastPage" @click="nextPage">{{ t('admin.nextPage') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watchEffect } from 'vue'
import AuditLogTable from '~/modules/admin/components/AuditLogTable.vue'
import { ACTION_LABELS } from '~/modules/admin/constants/auditLogActions'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useAuditLogs } from '~/modules/admin/hooks/useAuditLogs'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'

definePageMeta({
  middleware: 'auth'
})

const { t } = useI18n()
const { user } = useAuth()
const { logs, loading, currentPage, lastPage, total, filters, fetchLogs, applyFilters, nextPage, previousPage } = useAuditLogs()
const { allOrganizations, fetchAllOrganizations } = useOrganizations()

const accessDenied = computed(() => {
  if (!user.value) return true
  return !user.value.permissions?.includes('admin.audit-logs.list')
})

const isGlobalActor = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false)

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchLogs()
    if (isGlobalActor.value) {
      fetchAllOrganizations()
    }
  }
})
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

.log-table-wrap {
  display: block;
  margin-top: 1.5rem;
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
