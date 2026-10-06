ifeq ($(OS),Windows_NT)
$(error Native Windows is not supported. Run Make from WSL)
endif

COMPOSE ?= docker compose
JOOMLA_SERVICE ?= joomla
DB_SERVICE ?= db
JOOMLA_CLI ?= cli/joomla.php
DIST_DIR ?= dist
DIST_DIR_ABS = $(abspath $(DIST_DIR))
PHP_CS_FIXER ?= vendor/bin/php-cs-fixer
CODECEPT ?= vendor/bin/codecept

UNAME_S := $(shell uname -s)
CURRENT_VERSION := $(or $(VERSION),$(shell git describe --tags --always 2>/dev/null || printf 'dev'))

ifeq ($(UNAME_S),Darwin)
SED_INPLACE := sed -i ''
else
SED_INPLACE := sed -i
endif

.DEFAULT_GOAL := help

.PHONY: help build rebuild up up-rebuild up-alone down down-orphans restart down-v joomla-connect db-connect cache-clean install install-dev install-node install-php install-php-dev build-node test test-unit test-functional test-js cs-check cs-fix update-version artifact build-package update-yootheme-plugin-version artifact-yootheme-plugin build-yootheme-plugin

build: ## Собрать контейнеры
	$(COMPOSE) build

rebuild: ## Пересобрать контейнеры без кеша
	$(COMPOSE) build --no-cache

up: ## Запустить контейнеры
	$(COMPOSE) up -d

up-rebuild: ## Запустить контейнеры с пересборкой
	$(COMPOSE) up -d --force-recreate --build

up-alone: ## Запустить контейнеры и удалить потерянные контейнеры проекта
	$(COMPOSE) up -d --remove-orphans

down: ## Остановить и удалить контейнеры текущего проекта
	$(COMPOSE) down

down-orphans: ## Остановить проект и удалить потерянные контейнеры
	$(COMPOSE) down --remove-orphans

restart: ## Перезапустить контейнеры
	$(COMPOSE) restart

down-v: ## Остановить контейнеры и удалить тома с данными
	$(COMPOSE) down -v

joomla-connect: ## Открыть оболочку в Joomla-контейнере
	$(COMPOSE) exec $(JOOMLA_SERVICE) sh

db-connect: ## Открыть оболочку в контейнере базы данных
	$(COMPOSE) exec $(DB_SERVICE) sh

cache-clean: ## Очистить кеш Joomla
	$(COMPOSE) exec -T $(JOOMLA_SERVICE) php $(JOOMLA_CLI) cache:clean

install: install-node install-php ## Установить зависимости для сборки релиза

install-dev: install-node install-php-dev ## Установить зависимости для разработки

install-node:
	@npm ci

install-php:
	@composer install --no-dev --optimize-autoloader

install-php-dev:
	@composer install --optimize-autoloader

build-node: ## Собрать frontend-ресурсы
	@npm run build

test: test-unit test-js ## Запустить быстрые тесты

test-unit: ## Запустить unit-тесты Codeception
	@php $(CODECEPT) run Unit

test-functional: ## Запустить функциональные тесты в Joomla-контейнере
	@$(COMPOSE) exec -T $(JOOMLA_SERVICE) php /var/www/html/$(CODECEPT) run Administrator
	@$(COMPOSE) exec -T $(JOOMLA_SERVICE) php /var/www/html/$(CODECEPT) run Site

test-js: ## Запустить тесты frontend-состояния
	@node --test tests/js/schema-editor-state.test.mjs

cs-check: ## Проверить форматирование PHP-кода
	@$(PHP_CS_FIXER) fix --dry-run --diff --using-cache=no

cs-fix: ## Исправить форматирование PHP-кода
	@$(PHP_CS_FIXER) fix

update-version: ## Установить версию в манифестах расширений и Web Asset Manager
	@echo "Setting version to: $(CURRENT_VERSION)"
	@$(SED_INPLACE) -E 's#<version>[0-9A-Za-z_.-]+#<version>$(CURRENT_VERSION)#' \
		./com_microschema/microschema.xml \
		./mod_microschema/mod_microschema.xml \
		./plugins/system/microschema/microschema.xml \
		./plugins/microschema/content/content.xml \
		./plugins/microschema/contact/contact.xml \
		./pkg_microschema.xml
	@$(SED_INPLACE) -E 's#"version": "[0-9A-Za-z_.-]+"#"version": "$(CURRENT_VERSION)"#g' \
		./com_microschema/media/joomla.asset.json

artifact: ## Создать установочные ZIP-архивы расширений
	@mkdir -p "$(DIST_DIR_ABS)"
	@rm -f \
		"$(DIST_DIR_ABS)/com_microschema-$(CURRENT_VERSION).zip" \
		"$(DIST_DIR_ABS)/mod_microschema-$(CURRENT_VERSION).zip" \
		"$(DIST_DIR_ABS)/plg_system_microschema-$(CURRENT_VERSION).zip" \
		"$(DIST_DIR_ABS)/plg_microschema_content-$(CURRENT_VERSION).zip" \
		"$(DIST_DIR_ABS)/plg_microschema_contact-$(CURRENT_VERSION).zip"
	@cd ./com_microschema && zip -qr "$(DIST_DIR_ABS)/com_microschema-$(CURRENT_VERSION).zip" .
	@cd ./mod_microschema && zip -qr "$(DIST_DIR_ABS)/mod_microschema-$(CURRENT_VERSION).zip" .
	@cd ./plugins/system/microschema && zip -qr "$(DIST_DIR_ABS)/plg_system_microschema-$(CURRENT_VERSION).zip" .
	@cd ./plugins/microschema/content && zip -qr "$(DIST_DIR_ABS)/plg_microschema_content-$(CURRENT_VERSION).zip" .
	@cd ./plugins/microschema/contact && zip -qr "$(DIST_DIR_ABS)/plg_microschema_contact-$(CURRENT_VERSION).zip" .
	@rm -rf "$(DIST_DIR_ABS)/.pkg_microschema"
	@mkdir -p "$(DIST_DIR_ABS)/.pkg_microschema"
	@cp ./pkg_microschema.xml "$(DIST_DIR_ABS)/.pkg_microschema/pkg_microschema.xml"
	@cp -R ./language "$(DIST_DIR_ABS)/.pkg_microschema/language"
	@cp "$(DIST_DIR_ABS)/com_microschema-$(CURRENT_VERSION).zip" "$(DIST_DIR_ABS)/.pkg_microschema/com_microschema.zip"
	@cp "$(DIST_DIR_ABS)/mod_microschema-$(CURRENT_VERSION).zip" "$(DIST_DIR_ABS)/.pkg_microschema/mod_microschema.zip"
	@cp "$(DIST_DIR_ABS)/plg_system_microschema-$(CURRENT_VERSION).zip" "$(DIST_DIR_ABS)/.pkg_microschema/plg_system_microschema.zip"
	@cp "$(DIST_DIR_ABS)/plg_microschema_content-$(CURRENT_VERSION).zip" "$(DIST_DIR_ABS)/.pkg_microschema/plg_microschema_content.zip"
	@cp "$(DIST_DIR_ABS)/plg_microschema_contact-$(CURRENT_VERSION).zip" "$(DIST_DIR_ABS)/.pkg_microschema/plg_microschema_contact.zip"
	@rm -f "$(DIST_DIR_ABS)/pkg_microschema.zip"
	@cd "$(DIST_DIR_ABS)/.pkg_microschema" && zip -qr "../pkg_microschema.zip" .
	@rm -rf "$(DIST_DIR_ABS)/.pkg_microschema"

build-package: install update-version build-node artifact ## Собрать релизные архивы

update-yootheme-plugin-version: ## Установить версию плагина YOOtheme Pro
	@echo "Setting YOOtheme Pro plugin version to: $(CURRENT_VERSION)"
	@$(SED_INPLACE) -E 's#<version>[0-9A-Za-z_.-]+#<version>$(CURRENT_VERSION)#' \
		./plg_system_microschemayootheme/microschemayootheme.xml

artifact-yootheme-plugin: ## Создать установочный ZIP-архив плагина YOOtheme Pro
	@mkdir -p "$(DIST_DIR_ABS)"
	@rm -f "$(DIST_DIR_ABS)/plg_system_microschemayootheme.zip"
	@cd ./plg_system_microschemayootheme && zip -qr "$(DIST_DIR_ABS)/plg_system_microschemayootheme.zip" .

build-yootheme-plugin: update-yootheme-plugin-version artifact-yootheme-plugin ## Собрать плагин YOOtheme Pro

help: ## Показать доступные команды
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' "$(firstword $(MAKEFILE_LIST))" | sort | \
	awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'
