<template>
  <main class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Gerencie suas tarefas com <span class="gradient-text">precisão</span>.</h1>
      <p class="hero-subtitle">Aumente sua produtividade com a nossa plataforma inteligente de gestão de fluxo de trabalho.</p>
      <div class="cta-group">
        <button class="btn btn-primary">Começar Agora</button>
        <button class="btn btn-secondary">Ver Demo</button>
      </div>
    </div>

    <div class="feature-cards">
      <div class="card floating">
        <Target class="card-icon" :size="32" />
        <h3>Foco Total</h3>
        <p>Priorize o que importa com o nosso sistema de tags inteligentes.</p>
      </div>
      <div class="card floating delay-1">
        <BarChart3 class="card-icon" :size="32" />
        <h3>Métricas Reais</h3>
        <p>Acompanhe seu desempenho com gráficos detalhados em tempo real.</p>
      </div>
      <div class="card floating delay-2">
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

.hero-title {
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
  padding: 0.8rem 2rem;
  border-radius: 12px;
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

.btn-secondary {
  background: var(--surface);
  color: var(--ink);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background: var(--surface-2);
}

.feature-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  width: 100%;
  max-width: 1100px;
}

.card {
  background: var(--surface);
  padding: 2.5rem;
  border-radius: 18px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  text-align: left;
  transition: all 0.4s ease;
}

.card:hover {
  transform: translateY(-10px);
  border-color: var(--accent);
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
