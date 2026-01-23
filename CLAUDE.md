# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 construction material calculator application (DevMaterial). It calculates material requirements and costs for various construction work types (brick walls, plastering, tile installation, painting, etc.) using configurable formulas.

## Commands

```bash
# Setup (install deps, create .env, migrate, build assets)
composer run setup

# Development (starts PHP server, queue listener, and Vite concurrently)
composer run dev

# Run tests
composer test
# or: php artisan test
# Single test: php artisan test --filter TestName

# Format code
npm run format              # JS/CSS/JSON/PHP/Blade via Prettier
vendor/bin/pint             # PHP only (PSR-12)

# Production build
npm run build
```

## Architecture

### Formula System (app/Services/Formula/)

The calculation engine uses an auto-discovery pattern:

- **FormulaInterface**: Contract all formulas implement (`getCode()`, `getName()`, `getMaterialRequirements()`, `calculate()`, `trace()`)
- **FormulaRegistry**: Auto-discovers formula classes in `app/Services/Formula/`, provides lookup by code
- Formula classes: `BrickHalfFormula`, `BrickFullFormula`, `TileInstallationFormula`, `WallPlasteringFormula`, etc.

To add a new work type, create a class implementing `FormulaInterface` in `app/Services/Formula/`. It will be auto-registered.

### Calculation Services (app/Services/Calculation/)

- **CalculationOrchestrationService**: Main coordinator for calculation workflows, generates material combinations, handles comparisons
- **MaterialSelectionService**: Selects materials based on price filters (cheapest, medium, expensive, best)
- **CombinationGenerationService**: Generates and merges material combinations with duplicate detection

### Repository Pattern

- **BaseRepository**: Abstract class with common CRUD operations
- **BaseService**: Abstract class wrapping repository methods
- Material-specific repositories/services: `BrickRepository`, `CementRepository`, `SandRepository`, `CeramicRepository`, `CatRepository`

### Number Formatting (app/Helpers/NumberHelper.php)

Critical helper for consistent number display throughout the app:
- `NumberHelper::format()`: Display formatting with Indonesian locale (comma decimal, dot thousands)
- `NumberHelper::normalize()`: Calculation normalization (no rounding, truncation only)
- `NumberHelper::currency()`: Rupiah formatting

Use `NumberHelper` for all numeric display and calculations to maintain consistency.

### API Structure

- **Web routes** (`routes/web.php`): Resource controllers for CRUD, material calculator endpoints
- **API v1** (`routes/api.php`): RESTful API with `/api/v1/` prefix for materials, calculations, work items, units

### Models

Core domain models in `app/Models/`:
- Materials: `Brick`, `Cement`, `Sand`, `Ceramic`, `Cat` (paint)
- Configuration: `BrickInstallationType`, `MortarFormula`, `Unit`, `WorkItem`
- Calculations: `BrickCalculation`, `RecommendedCombination`
- Stores: `Store`, `StoreLocation`, `StoreMaterialAvailability`

### Frontend

- Blade templates in `resources/views/`
- Tailwind CSS 4 via Vite
- Alpine.js for interactivity

## Coding Conventions

- PHP follows PSR-12 (use `vendor/bin/pint`)
- 4-space indentation, LF line endings, UTF-8
- Controllers: `*Controller.php`
- Models: Singular PascalCase
- Migrations: Timestamped and descriptive

## Testing

Tests use Pest PHP framework with in-memory SQLite database. Test files in `tests/Unit/` and `tests/Feature/`.
