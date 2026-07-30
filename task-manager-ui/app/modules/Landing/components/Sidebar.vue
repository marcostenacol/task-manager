<template>
  <aside class="sidebar">
    <div class="brand">
      <img src="/favicon.svg" alt="TaskMaster" class="brand-icon">
      <span class="brand-text">TaskMaster</span>
    </div>

    <div v-if="memberships.length > 0" class="org-switcher">
      <label class="org-label" for="org-select">Organization</label>
      <select id="org-select" v-model="selectedOrganizationId" class="org-select" @change="handleSwitch">
        <option v-for="membership in memberships" :key="membership.organization.id" :value="membership.organization.id">
          {{ membership.organization.name }}
        </option>
      </select>
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
        <LogOut :size="16" /> {{ t('nav.logout') }}
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { LogOut } from 'lucide-vue-next'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useProfile } from '~/modules/social/hooks/useProfile'
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations'

const route = useRoute()
const { t } = useI18n()
const { user, logout } = useAuth()
const { profile, fetchProfile } = useProfile()
const { memberships, fetchMine, switchActive } = useOrganizations()

if (!profile.value) {
  fetchProfile()
}

const selectedOrganizationId = ref('')

onMounted(async () => {
  if (user.value?.organization) {
    await fetchMine()
    selectedOrganizationId.value = user.value.organization.id
  }
})

async function handleSwitch() {
  const result = await switchActive(selectedOrganizationId.value)
  if (!result.success) {
    window.alert(result.message)
    return
  }
  window.location.reload()
}

const isGlobalAdmin = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false)
const canViewAuditLogs = computed(() => user.value?.permissions?.includes('admin.audit-logs.list') ?? false)
const canManageRoles = computed(() => user.value?.permissions?.includes('admin.roles.manage') ?? false)
const canManageSettings = computed(() => user.value?.permissions?.includes('admin.settings.manage') ?? false)
const canManageMembers = computed(() => user.value?.permissions?.includes('admin.organizations.manage-members') ?? false)

const navItems = computed(() => {
  const items = [
    { label: t('nav.tasks'), to: '/tasks' },
    { label: t('nav.profile'), to: '/profile' },
  ]
  if (isGlobalAdmin.value) {
    items.push({ label: t('nav.users'), to: '/admin/users' })
  }
  if (canManageMembers.value) {
    items.push({ label: t('nav.members'), to: '/admin/organizations' })
  }
  if (canManageRoles.value) {
    items.push({ label: t('nav.roles'), to: '/admin/roles' })
  }
  if (canViewAuditLogs.value) {
    items.push({ label: t('nav.auditLogs'), to: '/admin/audit-logs' })
  }
  if (canManageSettings.value) {
    items.push({ label: t('nav.platformSettings'), to: '/admin/settings' })
  }
  items.push({ label: t('nav.settings'), to: '/settings' })
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
  width: 20px;
  height: 20px;
}

.org-switcher {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.org-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
}

.org-select {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 8px 10px;
  color: var(--ink);
  font-size: 0.85rem;
}

.org-select option {
  background: var(--surface);
  color: var(--ink);
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

@media (max-width: 768px) {
  .sidebar {
    width: 100%;
    height: auto;
    position: static;
  }

  .nav {
    flex-direction: row;
    flex-wrap: wrap;
  }

  .sidebar-footer {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .user-info {
    min-width: 0;
  }
}
</style>
