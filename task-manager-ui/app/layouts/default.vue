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
  gap: 16px;
  padding: 16px;
}

.app-main {
  flex: 1;
  max-width: 960px;
  margin: 0 auto;
}
</style>
