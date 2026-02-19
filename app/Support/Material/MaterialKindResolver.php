<?php

namespace App\Support\Material;

class MaterialKindResolver
{
    public const KIND_CEMENT = 'cement';
    public const KIND_NAT = 'nat';

    /**
     * Infer material kind from "type/jenis" text.
     *
     * Rules:
     * - If type contains nat/grout keyword => nat
     * - Otherwise use fallback
     */
    public static function inferFromType(?string $type, string $fallback = self::KIND_CEMENT): string
    {
        $normalized = self::normalize($type);
        if ($normalized === '') {
            return self::normalizeKind($fallback);
        }

        if (self::containsNatKeyword($normalized)) {
            return self::KIND_NAT;
        }

        return self::KIND_CEMENT;
    }

    public static function labelFromKind(?string $kind): string
    {
        return self::normalizeKind($kind) === self::KIND_NAT ? 'Nat' : 'Semen';
    }

    public static function indexRouteNameFromKind(?string $kind): string
    {
        return self::normalizeKind($kind) === self::KIND_NAT ? 'materials.index' : 'cements.index';
    }

    /**
     * @return 'cement'|'nat'
     */
    public static function normalizeKind(?string $kind): string
    {
        $value = strtolower(trim((string) $kind));

        return $value === self::KIND_NAT ? self::KIND_NAT : self::KIND_CEMENT;
    }

    private static function normalize(?string $value): string
    {
        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? '';

        return trim($text);
    }

    private static function containsNatKeyword(string $normalized): bool
    {
        if ($normalized === '') {
            return false;
        }

        // "nat", "nat epoxy", "grout", "tile grout", etc.
        if (preg_match('/\b(nat|grout|grouting)\b/i', $normalized) === 1) {
            return true;
        }

        // Legacy nat type labels from historical data.
        if (preg_match('/\b(regular|epoxy|sanded|non[\s\-_]?sanded)\b/i', $normalized) === 1) {
            return true;
        }

        // Handle fused words like "natflex" / "natkeramik"
        return str_starts_with($normalized, 'nat');
    }
}
