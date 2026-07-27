// @ts-check
import withNuxt from './.nuxt/eslint.config.mjs'

export default withNuxt(
  {
    rules: {
      // Este projeto não usa uma camada de client HTTP tipada (sem axios/Pinia,
      // ver .claude/CLAUDE.md) — Services/hooks trafegam `any` para payloads/respostas
      // da API deliberadamente hoje. Proibir `any` exigiria modelar todo o contrato
      // de request/response da API só para introduzir o lint, fora do escopo desta
      // tarefa (adicionar ESLint). Reavaliar se/quando a camada de tipos for introduzida.
      '@typescript-eslint/no-explicit-any': 'off',
    },
  },
)
