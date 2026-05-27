# 🎨 TaskMaster UI - Frontend

Este é o módulo de interface do usuário do Sistema de Gestão de Tarefas, construído com **Nuxt 3**.

## 🚀 Tecnologias Utilizadas

- **Framework**: [Nuxt 3](https://nuxt.com/) (Vue 3)
- **Estilização**: CSS Puro (Vanilla CSS) com variáveis globais e design system modular.
- **Tipografia**: Inter (Google Fonts).
- **Icons**: Emoji-based (para performance e estética limpa).

## 📁 Estrutura de Pastas (Modular)

Seguindo a arquitetura definida no `GEMINI.md`:

- `modules/`: Domínios isolados da aplicação.
- `components/`: Componentes atômicos reutilizáveis.
- `services/`: Lógica de consumo da API Laravel.
- `app.vue`: Ponto de entrada com o layout base premium.

## 🛠️ Desenvolvimento

Para rodar o servidor de desenvolvimento:

```bash
npm run dev
```

O servidor estará disponível em `http://localhost:3000`.

## 🎨 Guia de Estilo

- **Cores**: Paleta Slate (Tailwind-like) para o modo escuro.
- **Efeitos**: Glassmorphism nas barras de navegação e cartões flutuantes.
- **Animações**: Micro-animações em CSS puro para feedback visual.

---
Built with ❤️.
