<template>
  <div class="register-page">
    <div class="bg-decoration">
      <div class="circle circle-1"/>
      <div class="circle circle-2"/>
    </div>
    
    <NuxtLink to="/" class="back-home">
      <span>←</span> {{ t('auth.backHome') }}
    </NuxtLink>

    <div class="content-wrapper">
      <RegisterForm />
    </div>
  </div>
</template>

<script setup lang="ts">
import RegisterForm from '~/modules/auth/components/RegisterForm.vue'

const { t } = useI18n()

definePageMeta({
  layout: false,
  middleware: ['auth']
})
</script>

<style scoped>
/* Mesmos estilos do login.vue para consistência */
.register-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  background-color: var(--bg);
  padding: 2rem;
}

.content-wrapper {
  position: relative;
  z-index: 10;
  width: 100%;
  display: flex;
  justify-content: center;
}

.back-home {
  position: absolute;
  top: 2rem;
  left: 2rem;
  z-index: 20;
  color: var(--muted);
  text-decoration: none;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: color 0.3s;
}

.back-home:hover {
  color: var(--accent-primary);
}

.bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.circle {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.15;
}

.circle-1 {
  width: 500px;
  height: 500px;
  background: var(--accent-primary);
  top: -100px;
  right: -100px;
  animation: float 20s infinite alternate;
}

.circle-2 {
  width: 400px;
  height: 400px;
  background: var(--accent-secondary);
  bottom: -50px;
  left: -50px;
  animation: float 15s infinite alternate-reverse;
}

@keyframes float {
  from { transform: translate(0, 0); }
  to { transform: translate(50px, 50px); }
}
</style>
