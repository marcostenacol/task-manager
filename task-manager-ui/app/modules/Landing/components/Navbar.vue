<template>
  <nav class="navbar">
    <div class="logo" @click="navigateTo('/')">
      <img src="/favicon.svg" alt="TaskMaster" class="logo-icon">
      <span class="logo-text">TaskMaster</span>
    </div>
    
    <div v-if="isAuthenticated" class="nav-links">
      <NuxtLink to="/tasks" class="nav-link">Tarefas</NuxtLink>
      <NuxtLink v-if="isAdmin" to="/admin/users" class="nav-link">Usuários</NuxtLink>
    </div>

    <div class="auth-group">
      <template v-if="!isAuthenticated">
        <NuxtLink to="/login" class="nav-link">Entrar</NuxtLink>
        <NuxtLink to="/register" class="btn btn-register">Cadastrar</NuxtLink>
      </template>
      <template v-else>
        <div class="user-info">
          <span class="user-name">{{ user?.name }}</span>
          <div class="user-menu-container">
            <div class="avatar" @click="toggleMenu">
              <img v-if="profile?.avatar_path" :src="profile.avatar_path" alt="Avatar" class="avatar-image">
              <template v-else>{{ user?.name?.charAt(0) || 'U' }}</template>
            </div>
            <div v-if="showMenu" class="user-dropdown glass">
              <NuxtLink to="/profile" class="dropdown-item" @click="showMenu = false">
                <User class="icon" :size="18" /> Perfil
              </NuxtLink>
              <button class="dropdown-item logout" @click="handleLogout">
                <LogOut class="icon" :size="18" /> Sair
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { LogOut, User } from 'lucide-vue-next'
import { useAuth } from '~/modules/auth/hooks/useAuth'
import { useProfile } from '~/modules/social/hooks/useProfile'

const { user, isAuthenticated, logout } = useAuth()
const { profile, fetchProfile } = useProfile()
const showMenu = ref(false)

const isAdmin = computed(() => user.value?.permissions?.includes('admin.users.list') ?? false)

watchEffect(() => {
  if (isAuthenticated.value && !profile.value) {
    fetchProfile()
  }
})

const toggleMenu = () => {
  showMenu.value = !showMenu.value
}

const handleLogout = () => {
  showMenu.value = false
  logout()
}
</script>

<style scoped>
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 5%;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
  cursor: pointer;
}

.logo-icon {
  width: 24px;
  height: 24px;
}

.icon {
  flex-shrink: 0;
}

.logo-text {
  color: var(--ink);
}

.nav-links {
  display: flex;
  gap: 2rem;
}

.nav-link {
  color: var(--muted);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.router-link-active {
  color: var(--accent);
}

.auth-group {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.btn-register {
  padding: 0.5rem 1.2rem;
  background: var(--accent);
  color: var(--accent-ink);
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}

.btn-register:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--muted);
}

.avatar {
  width: 38px;
  height: 38px;
  background: var(--accent-soft);
  color: var(--accent);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.3s;
  overflow: hidden;
}

.avatar:hover {
  transform: scale(1.1);
}

.avatar-image {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.user-menu-container {
  position: relative;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 180px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.5rem;
  box-shadow: var(--shadow);
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.8rem 1rem;
  color: var(--ink);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  border-radius: 8px;
  transition: background 0.2s;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
  cursor: pointer;
}

.dropdown-item:hover {
  background: var(--surface-2);
  color: var(--accent);
}

.dropdown-item.logout:hover {
  color: var(--danger);
  background: var(--surface-2);
}

@media (max-width: 768px) {
  .nav-links, .user-name { display: none; }
}
</style>
