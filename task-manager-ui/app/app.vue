<template>
  <div class="app-root">
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'TaskMaster - Gestão Inteligente de Tarefas',
  meta: [
    { name: 'description', content: 'A plataforma definitiva para gerenciar suas tarefas e projetos com máxima eficiência.' }
  ],
  link: [
    { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
    { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
    { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap' }
  ]
})
</script>

<style>
:root {
  --font-primary: 'Inter', sans-serif;
  --font-secondary: 'Outfit', sans-serif;
}

body {
  font-family: var(--font-primary);
  background-color: var(--bg);
  color: var(--ink);
}

h1, h2, h3, h4, .font-outfit {
  font-family: var(--font-secondary);
}

.app-root {
  min-height: 100vh;
}
</style>
