.PHONY: up down help cria_agendamento verifica_instalacao .env .modulo.env prerequisites-up prepare-upload-tmp test-functional-estatisticas install-phpunit-vendor vendor

base=mysql
TESTS_FUNC_DIR=tests_estatisticas

-include $(TESTS_FUNC_DIR)/.env
-include $(TESTS_FUNC_DIR)/.modulo.env

ifndef HOST_URL
 HOST_URL=http://org-http:8000
endif

ifeq (, $(shell groups |grep docker))
 CMD_DOCKER_SUDO=sudo
else
 CMD_DOCKER_SUDO=
endif

ifeq (, $(shell which docker-compose))
 CMD_DOCKER_COMPOSE=$(CMD_DOCKER_SUDO) docker compose 
 CMD_COMPOSE_FUNC = $(CMD_DOCKER_COMPOSE) -f $(TESTS_FUNC_DIR)/docker-compose.yml --env-file $(TESTS_FUNC_DIR)/.env
else
 CMD_DOCKER_COMPOSE=$(CMD_DOCKER_SUDO) docker-compose
 CMD_COMPOSE_FUNC = $(CMD_DOCKER_COMPOSE) -f $(TESTS_FUNC_DIR)/docker-compose.yml --env-file $(TESTS_FUNC_DIR)/.env
endif

FILE_VENDOR_FUNCIONAL=$(TESTS_FUNC_DIR)/vendor/autoload.php

prepare-upload-tmp:
	@if [ ! -d "$(TESTS_FUNC_DIR)/.tmp" ]; then \
		echo "Criando diretório .tmp..."; \
		mkdir -p "$(TESTS_FUNC_DIR)/.tmp"; \
		chmod -R 777 "$(TESTS_FUNC_DIR)/.tmp"; \
	fi

up: prepare-upload-tmp prerequisites-up
	$(CMD_COMPOSE_FUNC) up -d

prerequisites-up: .env .modulo.env

down: 
	$(CMD_COMPOSE_FUNC) down

help:
	@echo "Usage: make [target] ... \n"
	@grep -E '^[a-zA-Z_-]+[[:space:]]*:.*?## .*$$' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

verifica_instalacao: 
	$(CMD_COMPOSE_FUNC) exec httpd /bin/bash -c "php -c /etc/php.ini /opt/sei/web/modulos/mod-sei-estatisticas/scripts/verifica_instalacao.php"

cria_agendamento: 
	$(CMD_COMPOSE_FUNC) exec httpd /bin/bash -c "php -c /etc/php.ini /opt/sei/web/modulos/mod-sei-estatisticas/scripts/cria_agendamento.php"

.env:
	@if [ ! -f "$(TESTS_FUNC_DIR)/.env" ]; then \
		cp envs/$(base).env $(TESTS_FUNC_DIR)/.env; \
		echo "Arquivo $(TESTS_FUNC_DIR)/.env nao existia. Copiado o arquivo default da pasta envs."; \
		echo "Se for o caso, faca as alteracoes nele antes de subir o ambiente."; \
		echo ""; sleep 5; \
	fi;

.modulo.env:
	@if [ ! -f "$(TESTS_FUNC_DIR)/.modulo.env" ]; then \
		cp envs/modulo.env $(TESTS_FUNC_DIR)/.modulo.env; \
		echo "Arquivo $(TESTS_FUNC_DIR)/.modulo.env nao existia. Copiado o arquivo default da pasta envs."; \
		echo "Se for o caso, faca as alteracoes nele antes de subir o ambiente."; \
		echo ""; sleep 5; \
	fi;

test-functional-estatisticas: .env .modulo.env $(FILE_VENDOR_FUNCIONAL) up vendor
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/phpunit -c /tests/phpunit.xml --testdox /tests/tests/$(addsuffix .php,$(teste)) ;

$(FILE_VENDOR_FUNCIONAL):
	make install-phpunit-vendor

install-phpunit-vendor:
	$(CMD_COMPOSE_FUNC) run --rm -w /tests php-test-functional bash -c './composer.phar install'

vendor: $(TESTS_FUNC_DIR)/composer.json
	$(CMD_COMPOSE_FUNC) run --rm -w /tests php-test-functional bash -c './composer.phar install'