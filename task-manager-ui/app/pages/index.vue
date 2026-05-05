<template>
  <div class="landing-page">
    <Navbar />
    <Hero />
    <Footer />
  </div>
</template>

<script setup lang="ts">
import Navbar from '~/modules/Landing/components/Navbar.vue'
import Hero from '~/modules/Landing/components/Hero.vue'
import Footer from '~/modules/Landing/components/Footer.vue'

definePageMeta({
  layout: false
})
</script>

<style scoped>
.landing-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
</style>
