<template>
  <aside class="sidebar">
    <div class="brand">
      <Rocket class="brand-icon" :size="20" />
      <span class="brand-text">TaskMaster</span>
    </div>

    <nav class="nav">
      <NuxtLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: isActive(item.to) }"
      >
        {{ item.label }}
      </NuxtLink>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="avatar">
          <img v-if="profile?.avatar_path" :src="profile.avatar_path" alt="Avatar" class="avatar-image">
          <template v-else>{{ user?.name?.charAt(0) || 'U' }}</template>
        </div>
        <span class="user-name">{{ user?.name }}</span>
      </div>
      <button class="logout-btn" @click="handleLogout">
        <LogOut :size="16" /> Sair
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { LogOut, Rocket } from 'lucide-vue-next'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useProfile } from '~/modules/social/hooks/useProfile'

const route = useRoute()
const { user, logout } = useAuth()
const { profile, fetchProfile } = useProfile()

if (!profile.value) {
  fetchProfile()
}

const isAdmin = computed(() => user.value?.permissions?.includes('admin.users.list') ?? false)
const canViewAuditLogs = computed(() => user.value?.permissions?.includes('admin.audit-logs.list') ?? false)
const canManageRoles = computed(() => user.value?.permissions?.includes('admin.roles.manage') ?? false)

const navItems = computed(() => {
  const items = [
    { label: 'Minhas tarefas', to: '/tasks' },
    { label: 'Meu perfil', to: '/profile' },
  ]
  if (isAdmin.value) {
    items.push({ label: 'Usuários', to: '/admin/users' })
  }
  if (canManageRoles.value) {
    items.push({ label: 'Roles', to: '/admin/roles' })
  }
  if (canViewAuditLogs.value) {
    items.push({ label: 'Auditoria', to: '/admin/audit-logs' })
  }
  return items
})

function isActive(to: string): boolean {
  return route.path === to || route.path.startsWith(`${to}/`)
}

function handleLogout() {
  logout()
}
</script>

<style scoped>
.sidebar {
  width: 208px;
  flex-shrink: 0;
  height: calc(100vh - 32px);
  position: sticky;
  top: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 18px;
  box-shadow: var(--shadow);
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--ink);
  font-weight: 600;
  font-size: 1rem;
}

.brand-icon {
  color: var(--accent);
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-item {
  display: block;
  padding: 10px 12px;
  border-radius: 10px;
  color: var(--muted);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.nav-item:hover {
  background: var(--surface-2);
}

.nav-item.active {
  background: var(--accent-soft);
  color: var(--accent);
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar {
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--accent-soft);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.85rem;
  overflow: hidden;
}

.avatar-image {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.user-name {
  flex: 1;
  min-width: 0;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--muted);
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: color 0.15s ease, border-color 0.15s ease;
}

.logout-btn:hover {
  color: var(--danger);
  border-color: var(--danger);
}
</style>
