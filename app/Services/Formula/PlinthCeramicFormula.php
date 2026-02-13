<?php

namespace App\Services\Formula;

class PlinthCeramicFormula extends PlinthInstallationFormula implements FormulaInterface
{
    public static function getName(): string
    {
        return 'Pasang Plint Keramik';
    }
}
