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
        <div class="card-icon">🎯</div>
        <h3>Foco Total</h3>
        <p>Priorize o que importa com o nosso sistema de tags inteligentes.</p>
      </div>
      <div class="card floating delay-1">
        <div class="card-icon">📊</div>
        <h3>Métricas Reais</h3>
        <p>Acompanhe seu desempenho com gráficos detalhados em tempo real.</p>
      </div>
      <div class="card floating delay-2">
        <div class="card-icon">⚡</div>
        <h3>Performance</h3>
        <p>Backend robusto em Laravel garantindo respostas em milissegundos.</p>
      </div>
    </div>
  </main>
</template>

<style scoped>
.hero-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 5rem 5%;
  background: radial-gradient(circle at top right, rgba(56, 189, 248, 0.1), transparent),
              radial-gradient(circle at bottom left, rgba(129, 140, 248, 0.1), transparent);
}

.hero-title {
  font-size: clamp(2.5rem, 8vw, 4.5rem);
  font-weight: 800;
  margin-bottom: 1.5rem;
  max-width: 900px;
}

.gradient-text {
  background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: var(--text-secondary);
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
  background: var(--accent-primary);
  color: #000;
  box-shadow: 0 4px 20px rgba(56, 189, 248, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(56, 189, 248, 0.5);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
  border: 1px solid var(--glass-border);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
}

.feature-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  width: 100%;
  max-width: 1100px;
}

.card {
  background: var(--bg-secondary);
  padding: 2.5rem;
  border-radius: 24px;
  border: 1px solid var(--glass-border);
  text-align: left;
  transition: all 0.4s ease;
}

.card:hover {
  transform: translateY(-10px);
  border-color: var(--accent-primary);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.card-icon {
  font-size: 2rem;
  margin-bottom: 1.5rem;
}

.card h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.card p {
  color: var(--text-secondary);
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
