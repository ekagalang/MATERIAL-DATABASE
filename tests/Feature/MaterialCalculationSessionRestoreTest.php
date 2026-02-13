<?php

use Illuminate\Support\Facades\File;

test('material calculation session restore keeps hidden boolean fallback inputs untouched', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('const checkableFields = fieldList.filter(field => field.type === \'checkbox\' || field.type === \'radio\');')
        ->and($content)->toContain('Keep hidden fallback inputs (value="0") untouched when same name also has checkboxes.')
        ->and($content)->toContain('if (checkableFields.length > 0)')
        ->and($content)->toContain('return;');
});
