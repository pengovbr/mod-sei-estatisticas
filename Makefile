.PHONY: up down help

-include .env

ifeq (, $(shell groups |grep docker))
 CMD_DOCKER_SUDO=sudo
else
 CMD_DOCKER_SUDO=
endif

ifeq (, $(shell which docker-compose))
 CMD_DOCKER_COMPOSE=$(CMD_DOCKER_SUDO) docker compose --env-file .env
else
 CMD_DOCKER_COMPOSE=$(CMD_DOCKER_SUDO) docker-compose --env-file .env
endif

base=mysql

up: ## Inicia o ambiente de desenvolvimento local (docker) em http://localhost:8000
	$(CMD_DOCKER_COMPOSE) up -d

down: ## Interrompe a execucao do ambiente de desenvolvimento local em docker
	$(CMD_DOCKER_COMPOSE) down

help:
	@echo "Usage: make [target] ... \n"
	@grep -E '^[a-zA-Z_-]+[[:space:]]*:.*?## .*$$' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

cria_agendamento: ## Executa o script cria_agendamento.sh no contexto do container
	$(CMD_DOCKER_COMPOSE) exec app /bin/bash -c "./scripts/cria_agendamento.sh"

debug:
	@echo "CMD_DOCKER_COMPOSE: $(CMD_DOCKER_COMPOSE)"

verifica_instalacao: ## Executa o script verifica_instalacao.sh no contexto do container
	$(CMD_DOCKER_COMPOSE) exec httpd /bin/bash -c "php -c /etc/php.ini /opt/sei/web/modulos/mod-sei-estatisticas/scripts/verifica_instalacao.php"

cria_agendamento: ## Executa o script criar_agendamento.sh no contexto do container
	$(CMD_DOCKER_COMPOSE) exec httpd /bin/bash -c "php -c /etc/php.ini /opt/sei/web/modulos/mod-sei-estatisticas/scripts/cria_agendamento.php"

config:  ## Configura o ambiente para outro banco de dados (mysql|sqlserver|oracle|postgresql). Ex: make config base=oracle 
	@cp -f envs/$(base).env .env;
	@echo "$(SUCCESS)Ambiente configurado para utilizar a base de dados $(base).$(NC)"