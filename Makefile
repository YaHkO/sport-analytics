.PHONY: help install start stop restart logs db-reset sync test build

help: ## Affiche cette aide
	@echo "Commandes disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Installation complète du projet
	@echo "🚀 Installation du projet Sports Analytics..."
	# Démarrer MySQL et Redis
	docker-compose up -d
	# Vérifier si Symfony existe déjà
	@if [ ! -f "backend/composer.json" ]; then \
		echo "📦 Création du projet Symfony..."; \
		rm -rf backend/* backend/.[!.]* 2>/dev/null || true; \
		cd backend && symfony new . --version="7.3.*" --webapp; \
	fi
	# Installer les dépendances
	cd backend && composer require doctrine/orm doctrine/doctrine-bundle
	cd backend && composer require symfony/http-client symfony/console symfony/serializer
	cd backend && composer require api-platform/core nelmio/cors-bundle
	cd backend && composer require --dev symfony/test-pack phpunit/phpunit
	# Base de données
	cd backend && symfony console doctrine:database:create --if-not-exists
	cd backend && symfony console doctrine:migrations:migrate --no-interaction || true
	# Frontend
	cd frontend && npm install
	@echo "✅ Installation terminée !"

start: ## Démarre tous les services
	@echo "🚀 Démarrage des services..."
	docker-compose up -d
	cd backend && symfony server:start -d --port=8000
	cd frontend && npm start &
	@echo "✅ Services démarrés !"
	@echo "📱 Frontend: http://localhost:3000"
	@echo "🔧 Backend API: http://localhost:8000/api"

stop: ## Arrête tous les services
	@echo "🛑 Arrêt des services..."
	cd backend && symfony server:stop || true
	pkill -f "npm start" || true
	docker-compose down
	@echo "✅ Services arrêtés !"

restart: ## Redémarre tous les services
	make stop
	sleep 2
	make start

sync: ## Synchronise les activités Strava
	@echo "🔄 Synchronisation des activités Strava..."
	cd backend && symfony console app:sync-activities

analyze: ## Analyse les performances du mois
	@echo "📊 Analyse des performances..."
	cd backend && symfony console app:analyze-performance --period=month

test: ## Lance les tests
	@echo "🧪 Lancement des tests..."
	cd backend && php bin/phpunit

build: ## Build du frontend pour la production
	@echo "🏗️  Build de production..."
	cd frontend && npm run build

dev: ## Mode développement complet
	@echo "🚀 Mode développement activé..."
	make start
	@echo "📱 Ouvre ton navigateur sur http://localhost:3000"
