<?php

namespace App\Services\Calculation;

use Illuminate\Support\Collection;

class StoreProximityService
{
    protected function resolveLocationAddress($location): ?string
    {
        if (!$location) {
            return null;
        }

        $formatted = trim((string) ($location->formatted_address ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        $parts = array_filter([
            trim((string) ($location->address ?? '')),
            trim((string) ($location->district ?? '')),
            trim((string) ($location->city ?? '')),
            trim((string) ($location->province ?? '')),
        ], static fn($part) => $part !== '');

        if (!empty($parts)) {
            return implode(', ', $parts);
        }

        return null;
    }

    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    public function sortLocationsByDistance(
        Collection $locations,
        float $projectLat,
        float $projectLng,
        bool $withinServiceRadiusOnly = false,
        ?float $distanceLimitKm = null,
    ): Collection
    {
        $ranked = $locations
            ->filter(function ($location) {
                return is_numeric($location->latitude ?? null) && is_numeric($location->longitude ?? null);
            })
            ->map(function ($location) use ($projectLat, $projectLng) {
                $distanceKm = $this->haversineKm(
                    $projectLat,
                    $projectLng,
                    (float) $location->latitude,
                    (float) $location->longitude,
                );

                return [
                    'location' => $location,
                    'distance_km' => $distanceKm,
                    'service_radius_km' => is_numeric($location->service_radius_km ?? null)
                        ? max(0.0, (float) $location->service_radius_km)
                        : null,
                ];
            })
            ->sortBy('distance_km')
            ->values();

        if ($withinServiceRadiusOnly) {
            $limitKm = is_numeric($distanceLimitKm) ? max(0.0, (float) $distanceLimitKm) : null;
            if ($limitKm === null) {
                return $ranked;
            }
            $ranked = $ranked
                ->filter(function (array $row) {
                    return isset($row['distance_km']) && is_numeric($row['distance_km']);
                })
                ->filter(function (array $row) use ($limitKm) {
                    return (float) $row['distance_km'] <= $limitKm;
                })
                ->values();
        }

        return $ranked;
    }

    public function sortReachableLocations(
        Collection $locations,
        float $projectLat,
        float $projectLng,
        ?float $projectRadiusKm = null,
    ): Collection
    {
        return $this->sortLocationsByDistance($locations, $projectLat, $projectLng, true, $projectRadiusKm);
    }

    public function buildNearestCoveragePlan(
        Collection $preparedLocations,
        array $requiredMaterials,
        bool $requiresBrick,
    ): array {
        $requiredNonBrick = array_values(
            array_filter($requiredMaterials, function (string $material): bool {
                return $material !== 'brick';
            }),
        );

        $selectedMaterials = [
            'cement' => collect(),
            'sand' => collect(),
            'cat' => collect(),
            'ceramic' => collect(),
            'nat' => collect(),
        ];

        $hasBrickCovered = !$requiresBrick;
        $selectedBrick = null;
        $coveredTypes = [];
        $storePlan = [];

        foreach ($preparedLocations as $row) {
            $location = $row['location'] ?? null;
            $materials = $row['materials'] ?? [];
            $distanceKm = is_numeric($row['distance_km'] ?? null)
                ? (float) ($row['distance_km'] ?? null)
                : null;

            $provided = [];

            if (!$hasBrickCovered && !empty($row['has_brick'])) {
                $hasBrickCovered = true;
                $selectedBrick = $row['brick'] ?? null;
                $coveredTypes[] = 'brick';
                $provided[] = 'brick';
            }

            foreach ($requiredNonBrick as $materialType) {
                if (!array_key_exists($materialType, $selectedMaterials)) {
                    continue;
                }
                if ($selectedMaterials[$materialType]->isNotEmpty()) {
                    continue;
                }

                $bucket = $materials[$materialType] ?? collect();
                if ($bucket instanceof Collection && $bucket->isNotEmpty()) {
                    $selectedMaterials[$materialType] = $bucket->values();
                    $coveredTypes[] = $materialType;
                    $provided[] = $materialType;
                }
            }

            if (!empty($provided) && $location) {
                $storePlan[] = [
                    'store_location_id' => $location->id ?? null,
                    'store_name' => $location->store->name ?? 'Unknown',
                    'city' => $location->city ?? null,
                    'address' => $this->resolveLocationAddress($location),
                    'distance_km' => $distanceKm !== null ? round($distanceKm, 3) : null,
                    'service_radius_km' => $location->service_radius_km ?? null,
                    'provided_materials' => array_values(array_unique($provided)),
                ];
            }

            $hasAllNonBrick = true;
            foreach ($requiredNonBrick as $materialType) {
                if (!array_key_exists($materialType, $selectedMaterials)) {
                    continue;
                }
                if ($selectedMaterials[$materialType]->isEmpty()) {
                    $hasAllNonBrick = false;
                    break;
                }
            }

            if ($hasBrickCovered && $hasAllNonBrick) {
                break;
            }
        }

        $missing = [];
        if (!$hasBrickCovered) {
            $missing[] = 'brick';
        }
        foreach ($requiredNonBrick as $materialType) {
            if (!array_key_exists($materialType, $selectedMaterials)) {
                continue;
            }
            if ($selectedMaterials[$materialType]->isEmpty()) {
                $missing[] = $materialType;
            }
        }

        return [
            'is_complete' => empty($missing),
            'missing_materials' => array_values(array_unique($missing)),
            'selected_materials' => $selectedMaterials,
            'selected_brick' => $selectedBrick,
            'store_plan' => $storePlan,
            'covered_materials' => array_values(array_unique($coveredTypes)),
        ];
    }
}
