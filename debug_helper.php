<?php
require __DIR__ . '/app/Helpers/NumberHelper.php';
use App\Helpers\NumberHelper;

echo "PHP NumberHelper Test:
";
echo "12,5 -> " . NumberHelper::parse("12,5") . "
";
echo "0,5 -> " . NumberHelper::parse("0,5") . "
";
echo "1.234,56 -> " . NumberHelper::parse("1.234,56") . "
";
echo "1,234.56 -> " . NumberHelper::parse("1,234.56") . "
";
echo "1.234 -> " . NumberHelper::parse("1.234") . "
";
