# 🎨 TaskMaster UI - Frontend

Este é o módulo de interface do usuário do Sistema de Gestão de Tarefas, construído com **Nuxt 4**.

## 💡 Motivação

Este frontend existe para provar, na prática, que a API modular do `task-manager-api` é consumível por um cliente real — e para servir de vitrine de portfólio de um consumo de API "cru", sem framework de estado (Pinia) nem UI kit, apenas Nuxt/Vue puro e um composable de fetch com interceptor próprio (`useApi.ts`) cuidando de autenticação, refresh de token e erros. A organização por módulos de domínio (`auth`, `tasks`, `social`, `admin`) espelha a modularidade do backend propositalmente, para deixar explícito que a mesma disciplina de separação por domínio vale nas duas pontas do sistema.

## 🚀 Tecnologias Utilizadas

- **Framework**: [Nuxt 4](https://nuxt.com/) (Vue 3)
- **Estilização**: CSS Puro (Vanilla CSS) com variáveis globais e design system modular.
- **Tipografia**: Inter (Google Fonts).
- **Icons**: [lucide-vue-next](https://lucide.dev/) em toda a aplicação (nav, forms, ações de tabela).

## 📁 Estrutura de Pastas (Modular)

Segue a estrutura padrão do Nuxt 4 (`srcDir: app/`):

- `app/modules/`: Domínios isolados da aplicação.
- `app/composables/`: Lógica de consumo da API Laravel e estado compartilhado.
- `app/pages/`: Rotas da aplicação.
- `app/app.vue`: Ponto de entrada com o layout base premium.

## 🛠️ Desenvolvimento

Para rodar o servidor de desenvolvimento:

```bash
npm run dev
```

O servidor estará disponível na porta definida em `PORT` no `.env` (padrão: `http://localhost:25565`).

## 🎨 Guia de Estilo

- **Cores**: Paleta Slate (Tailwind-like) para o modo escuro.
- **Efeitos**: Glassmorphism nas barras de navegação e cartões flutuantes.
- **Animações**: Micro-animações em CSS puro para feedback visual.

---
Built with ❤️.
