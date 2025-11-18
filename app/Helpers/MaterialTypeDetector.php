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
            
            // Skip models yang bukan material
            if (in_array($filename, ['User', 'Unit', 'Material'])) {
                continue;
            }
            
            $className = "App\\Models\\{$filename}";
            
            // Cek apakah class exists dan punya field package_unit
            if (class_exists($className)) {
                try {
                    $instance = new $className;
                    
                    // Cek apakah model punya fillable 'package_unit'
                    if (in_array('package_unit', $instance->getFillable())) {
                        // Convert nama model ke lowercase untuk material_type
                        // Cement → cement, Cat → cat, Sand → sand
                        $models[] = strtolower($filename);
                    }
                } catch (\Exception $e) {
                    // Skip jika ada error
                    continue;
                }
            }
        }
        
        sort($models); // Sort alphabetically
        
        return $models;
    }

    /**
     * Mendapatkan label yang human-readable dari material type
     * 
     * @param string $type
     * @return string
     */
    public static function getLabel(string $type): string
    {
        // Capitalize first letter
        // cat → Cat, cement → Cement
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