<?php

use App\Console\Commands\MaterialGeneratorCommand;
use Tests\TestCase;

uses(TestCase::class);

test('material generator loads template fields from config profile', function () {
    $command = new MaterialGeneratorCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('getTemplateFields');
    $method->setAccessible(true);

    $fields = $method->invoke($command, 'ceramic');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveKey('brand')
        ->and($fields['brand']['type'])->toBe('string');
});
