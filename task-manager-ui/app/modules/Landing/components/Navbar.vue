<template>
  <nav class="navbar">
    <div class="logo" @click="navigateTo('/')">
      <span class="logo-icon">🚀</span>
      <span class="logo-text">TaskMaster</span>
    </div>
    
    <div class="nav-links" v-if="isAuthenticated">
      <NuxtLink to="/tasks" class="nav-link">Dashboard</NuxtLink>
      <NuxtLink to="/" class="nav-link">Projetos</NuxtLink>
      <NuxtLink to="/tasks" class="nav-link active">Tarefas</NuxtLink>
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
              {{ user?.name?.charAt(0) || 'U' }}
            </div>
            <div v-if="showMenu" class="user-dropdown glass">
              <NuxtLink to="/profile" class="dropdown-item" @click="showMenu = false">
                <span class="icon">👤</span> Perfil
              </NuxtLink>
              <button class="dropdown-item logout" @click="handleLogout">
                <span class="icon">🚪</span> Sair
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuth } from '~/modules/auth/hooks/useAuth'

const { user, isAuthenticated, logout } = useAuth()
const showMenu = ref(false)

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
  background: var(--glass-bg);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--glass-border);
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

.logo-text {
  background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav-links {
  display: flex;
  gap: 2rem;
}

.nav-link {
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.router-link-active {
  color: var(--accent-primary);
}

.auth-group {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.btn-register {
  padding: 0.5rem 1.2rem;
  background: var(--accent-primary);
  color: #000;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}

.btn-register:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-secondary);
}

.avatar {
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: #000;
  cursor: pointer;
  transition: transform 0.3s;
}

.avatar:hover {
  transform: scale(1.1);
}

.user-menu-container {
  position: relative;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 180px;
  background: var(--glass-bg);
  backdrop-filter: blur(20px);
  border: 1px solid var(--glass-border);
  border-radius: 12px;
  padding: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.8rem 1rem;
  color: var(--text-primary);
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
  background: rgba(255, 255, 255, 0.05);
  color: var(--accent-primary);
}

.dropdown-item.logout:hover {
  color: #f87171;
  background: rgba(239, 68, 68, 0.05);
}

@media (max-width: 768px) {
  .nav-links, .user-name { display: none; }
}
</style>
