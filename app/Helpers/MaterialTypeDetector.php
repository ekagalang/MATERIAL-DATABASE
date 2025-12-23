<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class MaterialTypeDetector
{
    /**
     * Mendapatkan semua material types yang tersedia
     * dengan cara scan Models di folder app/Models
     *
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        $modelPath = app_path('Models');
        $models = [];

        // Scan semua file PHP di folder Models
        if (!File::exists($modelPath)) {
            return [];
        }

        $files = File::files($modelPath);

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();

            // Skip models dasar/sistem
            if (in_array($filename, ['User', 'Unit', 'Material', 'UnitMaterialType'])) {
                continue;
            }

            $className = "App\\Models\\{$filename}";

            // Cek apakah class exists
            if (class_exists($className)) {
                try {
                    // Cek apakah model memiliki method static getMaterialType
                    // Ini cara paling akurat karena semua model material memilikinya
                    if (method_exists($className, 'getMaterialType')) {
                        // Panggil methodnya untuk dapatkan tipe yang valid
                        $type = $className::getMaterialType();
                        if ($type) {
                            $models[] = $type;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip jika ada error
                    continue;
                }
            }
        }

        sort($models); // Sort alphabetically

        return array_unique($models);
    }

    /**
     * Mendapatkan label yang human-readable dari material type
     *
     * @param string $type
     * @return string
     */
    public static function getLabel(string $type): string
    {
        $labels = [
            'brick' => 'Bata',
            'cement' => 'Semen',
            'sand' => 'Pasir',
            'cat' => 'Cat',
        ];

        if (array_key_exists($type, $labels)) {
            return $labels[$type];
        }

        // Fallback: Capitalize first letter
        return ucfirst($type);
    }

    /**
     * Mendapatkan semua material types dengan label
     * Format: ['cat' => 'Cat', 'cement' => 'Cement', ...]
     *
     * @return array
     */
    public static function getTypesWithLabels(): array
    {
        $types = self::getAvailableTypes();
        $result = [];

        foreach ($types as $type) {
            $result[$type] = self::getLabel($type);
        }

        return $result;
    }

    /**
     * Cek apakah material type valid
     *
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAvailableTypes());
    }

    /**
     * Get material type from model class name
     * App\Models\Cat → cat
     *
     * @param string $modelClass
     * @return string|null
     */
    public static function getTypeFromModel(string $modelClass): ?string
    {
        $parts = explode('\\', $modelClass);
        $modelName = end($parts);

        $type = strtolower($modelName);

        return self::isValidType($type) ? $type : null;
    }
}
