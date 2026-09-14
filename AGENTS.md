# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Novel Echoes is a database application for literary research, built with:
- **Backend**: PHP 8.1 + Symfony 5.4 framework
- **Database**: MariaDB with Eloquent ORM (via WouterJEloquentBundle)
- **Search**: Elasticsearch 7.x for full-text search
- **Frontend**: Vue 3 + TypeScript with Vite build system
- **Styling**: Bootstrap 5 + UGent Huisstijl (custom theme)
- **Package Manager**: pnpm for Node.js dependencies, Composer for PHP

## Development Environment

The project runs in Docker containers. Three main services:
- `symfony`: PHP/Symfony backend (port 8080)
- `elasticsearch`: Search engine (port 9200)
- `node`: Vite dev server for hot module replacement (port 5173)

### Essential Commands

**Start all services:**
```sh
docker compose up --build
```

**Access the application:**
- Main app: http://localhost:8080
- Vite dev server: http://localhost:5173

**Build frontend assets:**
```sh
# Inside node container or locally with pnpm installed
pnpm install
pnpm run build
```

**Development mode (with HMR):**
```sh
pnpm run dev
```

**Run tests:**
```sh
# JavaScript/Vue tests
pnpm test

# PHP tests (inside symfony container)
docker exec -it novel-echoes-dev-symfony-1 bin/phpunit
```

**Elasticsearch indexing:**
```sh
# Index text records (specify max limit)
docker exec -it novel-echoes-dev-symfony-1 bin/console app:elasticsearch:index text [max_limit]

# Test Elasticsearch connection
docker exec -it novel-echoes-dev-symfony-1 bin/console app:elasticsearch:test
```

**Clear Symfony cache:**
```sh
docker exec -it novel-echoes-dev-symfony-1 bin/console cache:clear
```

**Composer commands:**
```sh
docker exec -it novel-echoes-dev-symfony-1 composer install
docker exec -it novel-echoes-dev-symfony-1 composer update
```

## Architecture

### Backend Structure

**MVC-style architecture with Eloquent ORM:**
- `src/Controller/`: Symfony controllers handling HTTP requests
- `src/Model/`: Eloquent models (NOT Doctrine entities) representing database tables
- `src/Repository/`: Custom repository classes for complex queries
- `src/Service/ElasticSearch/`: Elasticsearch integration services
- `src/Resource/`: Resource classes for API responses and data transformation
- `src/Command/`: Symfony console commands (e.g., indexing)

**Key architectural notes:**
- Uses Eloquent ORM instead of Doctrine (via `wouterj/eloquent-bundle`)
- Models extend `AbstractModel` with custom schema traits
- Elasticsearch services are manually configured in `config/services.yaml`
- Three main Elasticsearch services: `Client`, `TextIndexService`, `TextSearchService`

### Frontend Structure

**Vue 3 applications with separate entry points:**
- `assets/js/apps/main.js`: Base application
- `assets/js/apps/text-search.js`: Search interface
- `assets/js/apps/text-view.js`: Text viewing interface

**Frontend organization:**
- `assets/js/components/`: Reusable Vue components
- `assets/js/composables/`: Vue composables (composition API)
- `assets/js/helpers/`: Utility functions
- `assets/js/locales/`: i18n translation files
- `assets/js/repositories/`: API client code
- `assets/scss/`: Sass stylesheets

**Build system:**
- Vite with `vite-plugin-symfony` for Symfony integration
- TypeScript support with Vue SFC
- Vue I18n for internationalization
- Path aliases: `@` → `assets/js`, `@assets` → `assets`

### Database

**Initial setup:**
- SQL scripts in `initdb/` folder run on first container creation
- Minimum test dataset included
- Migrations in `app/migrations/`

**Eloquent configuration:**
- Connection configured in `config/packages/eloquent.yaml`
- Uses environment variables from `.env` file
- Models use Laravel's Eloquent query builder and relationships

### Elasticsearch Integration

**Index lifecycle:**
- Indices created automatically on first run (100 records max)
- Manual indexing via console command for larger datasets
- Index prefix configured via `ELASTICSEARCH_INDEX_PREFIX` env var
- Text index follows pattern: `{prefix}_text`

**Service architecture:**
- `Client`: Base Elastica client wrapper
- `TextIndexService`: Handles index creation and document indexing
- `TextSearchService`: Executes search queries and aggregations

## Development Workflow

**When adding new features:**
1. Backend: Create/modify controllers in `src/Controller/`
2. Add Eloquent models in `src/Model/` if new tables needed
3. Frontend: Add Vue components in `assets/js/components/`
4. Update Vite config if adding new app entry points
5. Run migrations if database schema changes
6. Re-index Elasticsearch if search fields modified

**When modifying frontend:**
- Vite dev server provides HMR; changes appear immediately
- Production build creates assets in `public/build/`
- Symfony's Vite bundle handles asset inclusion in Twig templates

**When modifying search:**
- Update index mappings in `TextIndexService`
- Modify search logic in `TextSearchService`
- Re-run indexing command to apply changes
- Clear Elasticsearch index if mapping changes: delete via Elasticsearch API

## Environment Configuration

Key environment variables in `.env`:
- Database connection: `DATABASE_*` variables
- Elasticsearch: `ELASTICSEARCH_*` variables (host, port, index prefix, debug)
- Symfony: `APP_ENV`, `APP_SECRET`

Check `example.env` for reference configuration.

## Testing

**Frontend tests:**
- Vitest with jsdom environment
- Test files should use `.test.ts` or `.test.js` extension
- Global test configuration in `vite.config.mjs`

**Backend tests:**
- PHPUnit via Symfony bridge
- Test files in `tests/` directory
- Configuration in `phpunit.xml.dist`

## Common Patterns

**Eloquent relationships:**
- Models use Laravel relationship methods (`hasMany`, `belongsTo`, etc.)
- Pivot models extend `AbstractPivot`
- Eager loading with `with()` and power joins package

**Symfony services:**
- Auto-wiring enabled in `services.yaml`
- Elasticsearch services registered explicitly for public access
- Repository classes auto-configured

**Vue composition API:**
- Prefer composition API over options API
- Use composables for shared logic
- TypeScript for type safety
