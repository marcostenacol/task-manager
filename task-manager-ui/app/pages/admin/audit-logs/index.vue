<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <h1 class="page-title">Auditoria</h1>
      <p class="page-subtitle">Histórico de ações administrativas na plataforma.</p>
      <AuditLogTable class="log-table-wrap" :logs="logs" :loading="loading" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watchEffect } from 'vue'
import AuditLogTable from '~/modules/admin/components/AuditLogTable.vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useAuditLogs } from '~/modules/admin/hooks/useAuditLogs'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { logs, loading, fetchLogs } = useAuditLogs()

const accessDenied = computed(() => {
  if (!user.value) return true
  return !user.value.permissions?.includes('admin.audit-logs.list')
})

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchLogs()
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

.log-table-wrap {
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
