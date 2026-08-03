.PHONY: up down help config cria_agendamento verifica_instalacao install update check-super-isalive .env .modulo.env prerequisites-up prepare-upload-tmp test-functional-estatisticas install-phpunit-vendor vendor

base=mysql
TESTS_FUNC_DIR=tests_estatisticas
MODULO_PASTAS_CONFIG=mod-sei-estatisticas
MODULO_NOME=mod-sei-estatisticas

-include $(TESTS_FUNC_DIR)/.env
-include $(TESTS_FUNC_DIR)/.modulo.env

ifndef HOST_URL
 HOST_URL=http://org-http:8000
endif

ifndef SEI_HOST
 SEI_HOST=$(HOST_URL)
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

CMD_INSTALACAO_SEI = echo -ne '$(SEI_DATABASE_USER)\n$(SEI_DATABASE_PASSWORD)\n' | php atualizar_versao_sei.php
CMD_INSTALACAO_SIP = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php atualizar_versao_sip.php
CMD_INSTALACAO_RECURSOS_SEI = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php atualizar_recursos_sei.php
CMD_INSTALACAO_SEI_MODULO = echo -ne '$(SEI_DATABASE_USER)\n$(SEI_DATABASE_PASSWORD)\n' | php sei_atualizar_versao_modulo_estatisticas.php
CMD_INSTALACAO_SIP_MODULO = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php sip_atualizar_versao_modulo_estatisticas.php

CMD_CURL_SUPER_LOGIN = curl -s -L http://localhost:8000/sei | grep -q "input.*txtUsuario.*"

ERROR=\033[0;31m
SUCCESS=\033[0;32m
WARNING=\033[1;33m
NC=\033[0m

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

destroy:
	$(CMD_COMPOSE_FUNC) down --volumes

help:
	@echo "Usage: make [target] ... \n"
	@grep -E '^[a-zA-Z_-]+[[:space:]]*:.*?## .*$$' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

verifica_instalacao: 
	$(CMD_COMPOSE_FUNC) exec httpd /bin/bash -c "php -c /etc/php.ini /opt/sei/web/modulos/mod-sei-estatisticas/scripts/verifica_instalacao.php"

check-super-isalive: ## Aguarda o SEI responder na tela de login
	@echo ""
	@echo "$(WARNING)Aguardando inicializacao do ambiente...$(NC)"
	@for i in `seq 1 5`; do \
	    echo "Tentativa $$i/5"; \
		if $(CMD_CURL_SUPER_LOGIN); then \
				echo 'Pagina de login carregada!' ; \
				break ; \
		fi; \
		sleep 5; \
	done; \
	if ! $(CMD_CURL_SUPER_LOGIN); then echo '$(ERROR)Ambiente nao pode ser carregado corretamente.$(NC)'; exit 1 ; fi;

install: check-super-isalive ## Instala e atualiza as tabelas/parametros do modulo no SEI e SIP
	$(CMD_COMPOSE_FUNC) exec -T -w /opt/sei/scripts/$(MODULO_PASTAS_CONFIG) httpd bash -c "$(CMD_INSTALACAO_SEI_MODULO)";
	$(CMD_COMPOSE_FUNC) exec -T -w /opt/sip/scripts/$(MODULO_PASTAS_CONFIG) httpd bash -c "$(CMD_INSTALACAO_SIP_MODULO)";
	@echo "==================================================================================================="
	@echo ""
	@echo "Fim da instalacao do modulo"

update: ## Atualiza banco de dados atraves dos scripts de atualizacao do SEI/SIP
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sei/scripts/ httpd bash -c "$(CMD_INSTALACAO_SEI)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sip/scripts/ httpd bash -c "$(CMD_INSTALACAO_SIP)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sip/scripts/ httpd bash -c "$(CMD_INSTALACAO_RECURSOS_SEI)"; true

config: ## Configura o ambiente para outro banco (mysql|sqlserver|oracle|postgresql). Ex: make config base=oracle
	@cp -f envs/$(base).env $(TESTS_FUNC_DIR)/.env;
	@echo "$(SUCCESS)Ambiente configurado para utilizar a base de dados $(base).$(NC)"

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

test-functional-estatisticas: .env .modulo.env $(FILE_VENDOR_FUNCIONAL) up vendor ## Executa os testes funcionais
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/phpunit -c /tests/phpunit.xml --testdox /tests/tests/$(addsuffix .php,$(teste)) ;

$(FILE_VENDOR_FUNCIONAL):
	make install-phpunit-vendor

install-phpunit-vendor:
	$(CMD_COMPOSE_FUNC) run --rm -w /tests php-test-functional bash -c './composer.phar install'

vendor: $(TESTS_FUNC_DIR)/composer.json
	$(CMD_COMPOSE_FUNC) run --rm -w /tests php-test-functional bash -c './composer.phar install'