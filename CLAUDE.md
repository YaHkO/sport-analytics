# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sports Analytics is a full-stack application for tracking and analyzing sports activities. The backend uses **Symfony 7.3** with Domain-Driven Design patterns, while the frontend is built with **React 18**.

## Essential Commands

### Development Environment
```bash
# Start all services (MySQL, Redis, Symfony server, React dev server)
make start

# Stop all services
make stop

# Complete project setup (first time)
make install

# Development mode (starts services and opens browser info)
make dev
```

### Backend Commands
```bash
# Run from backend/ directory
composer install                                    # Install PHP dependencies
symfony server:start --port=8000                   # Start Symfony dev server
symfony console doctrine:migrations:migrate         # Run database migrations
symfony console app:sync-activities                # Sync activities from Strava
symfony console app:analyze-performance --period=month  # Performance analysis

# Testing
php bin/phpunit                                     # Run all tests
php bin/phpunit tests/Unit                         # Run unit tests only
```

### Frontend Commands
```bash
# Run from frontend/ directory
npm install                     # Install dependencies
npm start                      # Start development server (port 3000)
npm run build                  # Build for production
npm test                       # Run tests
```

### Database Operations
```bash
# Run from backend/ directory
symfony console doctrine:database:create           # Create database
symfony console doctrine:schema:update --force     # Update schema
symfony console doctrine:fixtures:load             # Load test data (if fixtures exist)
```

## Architecture

### Backend: Domain-Driven Design with Hexagonal Architecture

**Key Directories:**
- `src/Sports/Domain/` - Business logic, entities, value objects
- `src/Sports/Application/` - Use cases, commands, handlers (CQRS pattern)
- `src/Sports/Infrastructure/` - External integrations, repositories
- `src/Sports/UI/Web/` - HTTP controllers and API endpoints
- `src/Shared/` - Shared kernel components

**Patterns Used:**
- **CQRS**: Commands and queries are separated with dedicated handlers
- **Value Objects**: `Distance`, `Duration`, `Speed`, `HeartRate` encapsulate business rules
- **Repository Pattern**: Data access abstracted through interfaces
- **Aggregate Root**: `Activity` is the main domain aggregate

### Frontend: Component-Based React with Custom Hooks

**Key Directories:**
- `src/components/` - Reusable UI components
- `src/hooks/` - Custom hooks for state management (`useStats`, `useSync`, `useActivities`)
- `src/services/` - API communication layer

### API Communication

**Base URL:** `http://localhost:8000/api`

**Key Endpoints:**
- `GET /activities` - List activities with filtering/pagination
- `POST /activities/sync` - Sync from external sources
- `GET /stats/overview` - Statistics dashboard data
- `GET /stats/chart-data` - Chart visualization data

## Development Patterns

### Backend Conventions
- Use **strict PHP types** and proper type hints
- Follow **PSR-4** autoloading with `App\` namespace
- **Value objects are immutable** - use static factory methods
- **Commands/Queries** have dedicated handlers in Application layer
- **Repository implementations** go in Infrastructure layer

### Frontend Conventions
- **Custom hooks** for data fetching and state management
- **Error boundaries** for graceful error handling
- **Loading states** with consistent UX patterns
- **Optimistic updates** - update UI before server confirmation

### Testing
- **PHPUnit** for backend testing with test environment configuration
- **React Testing Library** (via react-scripts) for frontend testing
- Run `make test` or `php bin/phpunit` for backend tests
- Run `npm test` for frontend tests

### Docker Services
- **MySQL 8.0** on port 3306 (database: `sports_analytics`)
- **Redis 7** on port 6379 (caching)
- Use `docker-compose up -d` to start infrastructure services

### Command Handlers
When creating new features, follow the pattern:
1. Create Command/Query in `Application/UseCase/{FeatureName}/`
2. Implement Handler with business logic
3. Add route in `UI/Web/Controller/`
4. Create corresponding frontend hook in `hooks/`