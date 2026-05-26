<template>
  <div>
    <div v-if="accessDenied" class="access-denied">
      <h2>Acesso Negado</h2>
      <p>Você não tem permissão para acessar esta página.</p>
      <NuxtLink to="/tasks">Voltar para tarefas</NuxtLink>
    </div>
    <div v-else>
      <UserTable />
    </div>
  </div>
</template>

<script setup lang="ts">
import UserTable from '~/modules/admin/components/UserTable.vue'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()

const accessDenied = computed(() => {
  if (!user.value) return true
  const isAdmin = user.value.role_slug === 'admin'
  const hasPermission = user.value.permissions?.includes('admin.users.list')
  return !isAdmin && !hasPermission
})

watchEffect(() => {
  if (accessDenied.value) {
    navigateTo('/tasks')
  }
})
</script>
