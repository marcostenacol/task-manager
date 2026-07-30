<template>
  <div class="app-shell">
    <Sidebar />
    <main class="app-main">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: var(--bg);
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
}

.app-main {
  flex: 1;
  max-width: 1200px;
  margin: 0 auto;
  min-width: 0;
}

@media (max-width: 768px) {
  .app-shell {
    flex-direction: column;
    padding: 8px;
    gap: 8px;
  }
}
</style>
