<template>
  <main class="hero-section">
    <div class="hero-content">
      <span class="wk-stencil hero-eyebrow">Oficina de Produtividade — Lote 2026</span>
      <h1 class="hero-title">Gerencie suas tarefas com <span class="gradient-text">precisão</span>.</h1>
      <hr class="wk-hazard-rule hero-rule">
      <p class="hero-subtitle">Aumente sua produtividade com a nossa plataforma inteligente de gestão de fluxo de trabalho.</p>
      <div class="cta-group">
        <button class="btn btn-primary wk-panel" @click="navigateTo('/register')">Começar Agora</button>
      </div>
    </div>

    <div class="feature-cards">
      <div class="card floating wk-panel wk-brackets">
        <span class="wk-stencil card-index">01</span>
        <Target class="card-icon" :size="32" />
        <h3>Foco Total</h3>
        <p>Priorize o que importa com o nosso sistema de tags inteligentes.</p>
      </div>
      <div class="card floating delay-1 wk-panel wk-brackets">
        <span class="wk-stencil card-index">02</span>
        <BarChart3 class="card-icon" :size="32" />
        <h3>Métricas Reais</h3>
        <p>Acompanhe seu desempenho com gráficos detalhados em tempo real.</p>
      </div>
      <div class="card floating delay-2 wk-panel wk-brackets">
        <span class="wk-stencil card-index">03</span>
        <Zap class="card-icon" :size="32" />
        <h3>Performance</h3>
        <p>Backend robusto em Laravel garantindo respostas em milissegundos.</p>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { BarChart3, Target, Zap } from 'lucide-vue-next'
</script>

<style scoped>
.hero-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 5rem 5%;
  background: var(--bg);
}

.hero-content {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hero-eyebrow {
  margin-bottom: 1.25rem;
  color: var(--accent);
}

.hero-rule {
  width: 140px;
  margin: 0 0 2rem;
}

.hero-title {
  text-transform: uppercase;
  letter-spacing: 0.01em;
  font-size: clamp(2.5rem, 8vw, 4.5rem);
  font-weight: 800;
  margin-bottom: 1.5rem;
  max-width: 900px;
}

.gradient-text {
  color: var(--accent);
}

.hero-subtitle {
  font-size: 1.25rem;
  color: var(--muted);
  max-width: 600px;
  margin-bottom: 3rem;
}

.cta-group {
  display: flex;
  gap: 1rem;
  margin-bottom: 5rem;
}

.btn {
  padding: 0.9rem 2.5rem;
  font-family: var(--font-secondary);
  font-size: 1.0625rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: none;
}

.btn-primary {
  background: var(--accent);
  color: var(--accent-ink);
  box-shadow: var(--shadow);
}

.btn-primary:hover {
  transform: translateY(-2px);
}

.feature-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  width: 100%;
  max-width: 1100px;
}

.card {
  position: relative;
  background: var(--surface);
  padding: 2.5rem;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  text-align: left;
  transition: all 0.4s ease;
}

.card:hover {
  transform: translateY(-10px);
  border-color: var(--accent);
}

.card-index {
  display: block;
  margin-bottom: 1.25rem;
  color: var(--accent);
}

.card h3 {
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.card-icon {
  margin-bottom: 1.5rem;
  color: var(--accent);
}

.card h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.card p {
  color: var(--muted);
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

.floating {
  animation: float 6s ease-in-out infinite;
}

.delay-1 { animation-delay: 1s; }
.delay-2 { animation-delay: 2s; }

@media (max-width: 768px) {
  .hero-title { font-size: 2.5rem; }
}
</style>
