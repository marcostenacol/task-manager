<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <h1 class="page-title">Configurações</h1>
      <p class="page-subtitle">Parâmetros globais do sistema.</p>
      <SettingsTable
        class="settings-table-wrap"
        :settings="settings"
        :loading="loading"
        :on-update="updateSetting"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watchEffect } from 'vue'
import SettingsTable from '~/modules/admin/components/SettingsTable.vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useSettings } from '~/modules/admin/hooks/useSettings'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { settings, loading, fetchSettings, updateSetting } = useSettings()

const accessDenied = computed(() => {
  if (!user.value) return true
  return !user.value.permissions?.includes('admin.settings.manage')
})

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})

onMounted(() => {
  if (!accessDenied.value) {
    fetchSettings()
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

.settings-table-wrap {
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
