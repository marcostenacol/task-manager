<template>
  <div class="app-container">
    <Navbar />
    <Hero />
    <Footer />
  </div>
</template>

<script setup lang="ts">
import Navbar from '~/modules/Landing/components/Navbar.vue'
import Hero from '~/modules/Landing/components/Hero.vue'
import Footer from '~/modules/Landing/components/Footer.vue'

useHead({
  title: 'TaskMaster - Gestão Inteligente de Tarefas',
  meta: [
    { name: 'description', content: 'A plataforma definitiva para gerenciar suas tarefas e projetos com máxima eficiência.' }
  ]
})
</script>

<style scoped>
.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
</style>
