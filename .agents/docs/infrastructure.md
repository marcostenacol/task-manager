# 🏗️ Infraestrutura e Setup (Docker)

O projeto utiliza um ambiente totalmente dockerizado para garantir consistência entre desenvolvimento, staging e produção.

## 🔌 Serviços e Contêineres

A stack é composta pelos seguintes serviços:

- **`task-manager-api`**: PHP 8.3 com FPM, rodando a lógica Laravel Modular.
- **`task-manager-nginx`**: Servidor web servindo a API e estáticos.
- **`task-manager-postgres`**: Banco de dados PostgreSQL com as extensões `pgcrypto`.
- **`task-manager-redis`**: Cache em memória para performance de listagens.

---

## 🌐 Rede Interna

- **`task-manager-network`**: Rede interna (bridge) para comunicação isolada entre os serviços. Apenas o Nginx possui exposição direta para o host.

---

## 🛠️ Comandos de Gestão (Makefile)

O projeto utiliza um `Makefile` para abstrair a complexidade do Docker:

- `make up`: Inicia todos os serviços.
- `make down`: Para os serviços.
- `make db-recreate`: Reseta o banco de dados principal.
- `make db-testing`: Prepara o banco isolado para testes com Pest.
- `make test`: Executa a suíte completa de testes.

---
> [!IMPORTANT]
> Certifique-se de que o arquivo `.env` contenha as chaves corretas para a conexão com o Redis e o Postgres dentro da rede `task-manager-network`.
