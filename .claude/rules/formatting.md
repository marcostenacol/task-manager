# Formatação de código PHP

O projeto roda em containers Docker. Nunca execute comandos PHP diretamente no host.

Após editar ou criar qualquer arquivo `.php`, execute o Pint no arquivo modificado:

```bash
docker compose -f docker-compose.local.yml exec task-manager-php-fpm ./vendor/bin/pint {caminho/do/arquivo.php}
```

(Nome do serviço/container confirmado no `Makefile`: `DOCKER_SERVICE_PHP_FPM := task-manager-php-fpm`.)

Múltiplos arquivos:

```bash
docker compose -f docker-compose.local.yml exec task-manager-php-fpm ./vendor/bin/pint arquivo1.php arquivo2.php
```

`laravel/pint: ^1.27` está no `composer.json` (`require-dev`). Não foi encontrado `pint.json` na raiz do `task-manager-api` neste harness — se não existir, o Pint usa o preset padrão `laravel`; confirme a existência do arquivo antes de assumir uma config customizada.

**Nunca** commitar ou dar push antes de rodar o Pint nos arquivos alterados.
