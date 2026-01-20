<?php

namespace App\Services;

use App\Services\Formula\FormulaInterface;
use Illuminate\Support\Facades\File;

/**
 * Registry untuk auto-discovery formula calculations
 * Scan folder app/Services/Formula/ dan detect semua class yang implement FormulaInterface
 */
class FormulaRegistry
{
    protected static ?array $formulas = null;

    /**
     * Get all available formulas
     */
    public static function all(): array
    {
        if (self::$formulas === null) {
            self::discover();
        }

        return self::$formulas;
    }

    /**
     * Get formula by code
     */
    public static function find(string $code): ?array
    {
        $formulas = self::all();

        foreach ($formulas as $formula) {
            if ($formula['code'] === $code) {
                return $formula;
            }
        }

        return null;
    }

    /**
     * Get required materials by formula code
     */
    public static function materialsFor(string $code): array
    {
        $formula = self::find($code);
        $materials = $formula['materials'] ?? [];

        return is_array($materials) ? $materials : [];
    }

    /**
     * Get formula instance by code
     */
    public static function instance(string $code): ?FormulaInterface
    {
        $formula = self::find($code);

        if ($formula) {
            return new ($formula['class'])();
        }

        return null;
    }

    /**
     * Discover all formula classes in app/Services/Formula/
     */
    protected static function discover(): void
    {
        self::$formulas = [];

        $formulaPath = app_path('Services/Formula');

        if (!File::exists($formulaPath)) {
            return;
        }

        $files = File::files($formulaPath);

        foreach ($files as $file) {
            // Skip interface file
            if ($file->getFilename() === 'FormulaInterface.php') {
                continue;
            }

            // Skip copy files
            if (str_contains($file->getFilename(), ' copy')) {
                continue;
            }

            // Get class name from filename
            $className = 'App\\Services\\Formula\\' . str_replace('.php', '', $file->getFilename());

            // Check if class exists and implements FormulaInterface
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);

                if ($reflection->implementsInterface(FormulaInterface::class) && !$reflection->isAbstract()) {
                    self::$formulas[] = [
                        'code' => $className::getCode(),
                        'name' => $className::getName(),
                        'description' => $className::getDescription(),
                        'materials' => $className::getMaterialRequirements(),
                        'class' => $className,
                    ];
                }
            }
        }

        // Sort formulas alphabetically by name
        usort(self::$formulas, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
    }

    /**
     * Clear cached formulas (useful for testing)
     */
    public static function clear(): void
    {
        self::$formulas = null;
    }

    /**
     * Check if formula exists by code
     */
    public static function has(string $code): bool
    {
        return self::find($code) !== null;
    }

    /**
     * Get formula codes only
     */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }

    /**
     * Get formula names indexed by code
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $formula) {
            $options[$formula['code']] = $formula['name'];
        }

        return $options;
    }
}
