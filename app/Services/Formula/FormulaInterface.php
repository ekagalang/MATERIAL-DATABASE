<?php

namespace App\Services\Formula;

interface FormulaInterface
{
    /**
     * Get the unique code/identifier for this formula type
     */
    public static function getCode(): string;

    /**
     * Get the display name for this formula type
     */
    public static function getName(): string;

    /**
     * Get description of what this formula calculates
     */
    public static function getDescription(): string;

    /**
     * Calculate materials needed based on input parameters
     *
     * @param  array  $params  Input parameters
     * @return array Calculation results
     */
    public function calculate(array $params): array;

    /**
     * Generate step-by-step trace of the calculation
     *
     * @param  array  $params  Input parameters
     * @return array Trace data with steps
     */
    public function trace(array $params): array;

    /**
     * Validate input parameters
     *
     * @param  array  $params  Input parameters
     */
    public function validate(array $params): bool;
}
