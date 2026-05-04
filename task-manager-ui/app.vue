<template>
  <div class="app-container">
    <nav class="navbar">
      <div class="logo">
        <span class="logo-icon">🚀</span>
        <span class="logo-text">TaskMaster</span>
      </div>
      <div class="nav-links">
        <a href="#" class="nav-link">Dashboard</a>
        <a href="#" class="nav-link">Projetos</a>
        <a href="#" class="nav-link active">Tarefas</a>
      </div>
      <div class="user-profile">
        <div class="avatar">M</div>
      </div>
    </nav>

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

    <footer class="footer">
      <p>&copy; 2026 TaskMaster. Built with Antigravity AI.</p>
    </footer>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'TaskMaster - Gestão Inteligente de Tarefas',
  meta: [
    { name: 'description', content: 'A plataforma definitiva para gerenciar suas tarefas e projetos com máxima eficiência.' }
  ]
})
</script>

<style>
:root {
  --bg-primary: #0f172a;
  --bg-secondary: #1e293b;
  --text-primary: #f8fafc;
  --text-secondary: #94a3b8;
  --accent-primary: #38bdf8;
  --accent-secondary: #818cf8;
  --glass-bg: rgba(30, 41, 59, 0.7);
  --glass-border: rgba(255, 255, 255, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.6;
  overflow-x: hidden;
}

.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Navbar Styling */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 5%;
  background: var(--glass-bg);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--glass-border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.logo-text {
  background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav-links {
  display: flex;
  gap: 2rem;
}

.nav-link {
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.active {
  color: var(--accent-primary);
}

.avatar {
  width: 40px;
  height: 40px;
  background: var(--accent-secondary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  box-shadow: 0 0 15px rgba(129, 140, 248, 0.4);
}

/* Hero Section */
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

/* Feature Cards */
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

/* Animations */
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

.floating {
  animation: float 6s ease-in-out infinite;
}

.delay-1 { animation-delay: 1s; }
.delay-2 { animation-delay: 2s; }

/* Footer */
.footer {
  padding: 2rem;
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.9rem;
  border-top: 1px solid var(--glass-border);
}

@media (max-width: 768px) {
  .nav-links { display: none; }
  .hero-title { font-size: 2.5rem; }
}
</style>
