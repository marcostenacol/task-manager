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
  </aside>
</template>

<script setup lang="ts">
import { Rocket } from 'lucide-vue-next'

const route = useRoute()

const navItems = [
  { label: 'Minhas tarefas', to: '/tasks' },
  { label: 'Meu perfil', to: '/profile' },
]

function isActive(to: string): boolean {
  return route.path === to || route.path.startsWith(`${to}/`)
}
</script>

<style scoped>
.sidebar {
  width: 208px;
  flex-shrink: 0;
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
</style>
