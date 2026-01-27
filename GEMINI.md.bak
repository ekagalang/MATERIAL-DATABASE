# GEMINI.md

This file serves as the primary context and instructional guide for the Gemini AI agent working on the **DevMaterial** project.

## 1. Project Overview

**DevMaterial** is a Laravel 12 application designed to calculate construction material requirements and costs. It uses a flexible formula system to estimate materials for various work types (e.g., brick laying, plastering, tiling, painting).

### Key Technologies
*   **Framework:** Laravel 12 (PHP 8.2+)
*   **Frontend:** Blade Templates, Tailwind CSS 4, Alpine.js, Vite
*   **Testing:** Pest PHP
*   **Database:** MySQL (Production/Dev), SQLite (Testing)
*   **Styling:** PostCSS, Tailwind CSS

## 2. Architecture & Patterns

The application follows a structured architecture to separate concerns:

### Formula System (`app/Services/Formula/`)
*   **Core Concept:** The calculation engine is pluggable.
*   **Interface:** `FormulaInterface` defines the contract (`calculate()`, `getMaterialRequirements()`).
*   **Registry:** `FormulaRegistry` auto-discovers formulas.
*   **Implementation:** To add a new work type calculation, create a new class implementing `FormulaInterface` in this directory.

### Calculation Services (`app/Services/Calculation/`)
*   **`CalculationOrchestrationService`**: The main entry point that coordinates the entire calculation workflow.
*   **`MaterialSelectionService`**: Filters and selects materials based on criteria (price, quality).
*   **`CombinationGenerationService`**: Generates valid combinations of materials for a specific work type.

### Repository Pattern (`app/Repositories/`)
*   Used for all database interactions to abstract Eloquent models.
*   Examples: `BrickRepository`, `CementRepository`, `SandRepository`.

### Helpers
*   **`NumberHelper`**: **CRITICAL**. Always use this for number formatting and calculations to ensure consistency with Indonesian locale (comma decimal, dot thousands).

## 3. Key Entities & Models

*   **Materials:** `Brick`, `Cement`, `Sand`, `Ceramic`, `Cat` (Paint).
*   **Configuration:**
    *   `WorkItem`: Represents a type of job (e.g., "Pasangan Bata Merah").
    *   `Unit`: Measurement units.
    *   `MortarFormula`: Mix ratios for cement/sand.
*   **Calculations:** `BrickCalculation`, `RecommendedCombination`.
*   **Stores:** `Store`, `StoreLocation`, `StoreMaterialAvailability` (managing material pricing and availability per store).

## 4. Development Workflow

### Setup & Running
*   **Initial Setup:** `composer run setup` (Installs deps, migrates, builds assets).
*   **Start Dev Server:** `composer run dev` (Starts PHP server, Queue listener, and Vite).

### Testing
*   **Run All Tests:** `composer test` or `php artisan test`
*   **Framework:** Pest PHP. Tests are located in `tests/Unit` and `tests/Feature`.

### Code Style
*   **PHP:** Follows PSR-12. Run `vendor/bin/pint` to fix style issues.
*   **Frontend:** Run `npm run format` (Prettier) for JS, CSS, JSON, and Blade files.

## 5. Current Roadmap & Context

### Material Type Configuration System
A major planned feature is the "Material Type Config System" to map specific material types (e.g., "PCC Cement") to specific work items (e.g., "Brick Wall").
*   **Status:** Planned/In-Progress (Refer to `TODO_MATERIAL_TYPE_CONFIG.txt`).
*   **Goal:** Allow admin to restrict which material types appear for specific calculations.
*   **Logic:**
    1.  New types auto-detected -> Global (allowed everywhere).
    2.  Admin configures -> Restricted to specific Work Items.

### Recent Changes
*   Migration to Laravel 12.
*   Frontend migration to Tailwind CSS 4.

## 6. Directory Structure

*   `app/Services/Formula`: Calculation logic classes.
*   `app/Helpers`: Utility classes (`NumberHelper`).
*   `resources/views`: Blade templates.
*   `routes/web.php`: Web application routes.
*   `routes/api.php`: API endpoints (prefix `/api/v1`).
*   `tests/`: Unit and Feature tests.
