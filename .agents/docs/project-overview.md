# 🚀 Visão Geral do Projeto - Task Manager

O **Task Manager** é uma plataforma de alta performance desenvolvida para a gestão eficiente de projetos e fluxos de trabalho. Diferente de gerenciadores simples, este sistema foi projetado para ser uma vitrine de engenharia, focando em escalabilidade modular, segurança delegada ao banco de dados e performance via cache agressivo.

---

## 🎯 Objetivo do Sistema

Oferecer um ambiente robusto onde equipes possam:
- Gerenciar projetos complexos com múltiplos status.
- Rastrear o progresso de tarefas em tempo real.
- Garantir a segurança dos dados através de uma arquitetura modular inspirada em DDD.
- Experimentar uma interface premium e extremamente rápida.

---

## 🏗️ Domínios de Negócio

O sistema é dividido em domínios lógicos, cada um com seu próprio Schema no PostgreSQL:

### 🛡️ Domínio Admin
Responsável pela infraestrutura de acesso e segurança:
- Gestão de Usuários e Perfis.
- Controle de Acesso Baseado em Funções (RBAC).
- Autenticação via PL/pgSQL para segurança máxima.

### 📋 Domínio Task
O coração da produtividade:
- Projetos e Quadros de Tarefas.
- Ciclo de vida de Tasks (Status dinâmicos).
- Atribuição de responsabilidades e prazos.

### 👥 Domínio Social
Focado na interação e identidade:
- Perfis detalhados de colaboradores.
- Contatos e canais de comunicação integrados.

---

## 🏆 Diferenciais Técnicos (Portfolio Focus)

- **Arquitetura Modular**: Backend desacoplado em Packages, permitindo crescimento infinito sem dívida técnica.
- **Performance SQL**: Uso de CTEs (Common Table Expressions) para relatórios e listagens complexas.
- **Segurança Nativa**: Lógica de login e hashing processada diretamente no PostgreSQL via `pgcrypto`.
- **Experiência de IA**: Documentação preparada para ser consumida por agentes de IA, permitindo automação de 80% do boilerplate.

---
> [!NOTE]
> Este projeto demonstra a capacidade de unir regras de negócio complexas com uma arquitetura de software de nível empresarial.
