<template>
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
</template>

<style scoped>
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

@media (max-width: 768px) {
  .nav-links { display: none; }
}
</style>
