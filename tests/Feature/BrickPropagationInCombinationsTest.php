<?php

use Illuminate\Support\Facades\File;

test('combination generator always carries brick in yielded combination payloads', function () {
    $content = File::get(app_path('Services/Calculation/CombinationGenerationService.php'));

    expect($content)->toContain('protected function yieldCombinations(')
        ->and($content)->toContain('Brick $brick,')
        ->and($content)->toContain("'brick' => \$brick,");
});

test('common combinations path reattaches brick context for brick-required work types', function () {
    $content = File::get(app_path('Services/Calculation/CombinationGenerationService.php'));

    expect($content)->toContain('$attachBrick = function (array $results) use ($brick, $isBrickless): array {')
        ->and($content)->toContain('return $this->injectStoreBrickIntoResults($results, $brick);')
        ->and($content)->toContain('return $attachBrick($results);');
});

