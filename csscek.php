<?php

$css = file_get_contents('public/css/global.css');

preg_match_all('/([^{]+)\{[^}]+\}/', $css, $matches);

$selectors = array_map('trim', $matches[1]);
$duplicates = array_diff_assoc($selectors, array_unique($selectors));

if (empty($duplicates)) {
    echo "Tidak ada selector duplikat\n";
} else {
    echo "Selector duplikat:\n";
    foreach ($duplicates as $sel) {
        echo "- $sel\n";
    }
}
