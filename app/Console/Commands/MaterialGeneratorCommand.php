<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MaterialGeneratorCommand extends Command
{
    protected $signature = 'make:material
                            {name? : Material name (singular, e.g., Tile)}
                            {--delete : Delete/rollback a generated material}
                            {--force : Force overwrite existing files}';

    protected $description = 'Generate complete material CRUD (Model, Controller, Service, Repository, Views, JS)';

    protected $materialName;
    protected $materialNamePlural;
    protected $materialLabel;
    protected $materialIcon;
    protected $hasPackageUnit = false;
    protected $fields = [];
    protected $generatedFiles = [];

    public function handle()
    {
        $this->info('🚀 Material Generator - KANGGO Database');
        $this->newLine();

        // Check if delete mode
        if ($this->option('delete')) {
            return $this->deleteMaterial();
        }

        // Get material name
        $this->materialName = $this->argument('name') ?: $this->ask('Material name (singular, e.g., Tile)');

        if (!$this->materialName) {
            $this->error('Material name is required!');
            return 1;
        }

        // Capitalize first letter
        $this->materialName = ucfirst(Str::camel($this->materialName));
        $this->materialNamePlural = Str::plural(Str::snake($this->materialName));

        $this->info("📦 Material: {$this->materialName}");
        $this->info("📦 Table: {$this->materialNamePlural}");
        $this->newLine();

        // Check if files already exist
        if (!$this->option('force') && $this->checkExistingFiles()) {
            if (!$this->confirm('Some files already exist. Do you want to overwrite?', false)) {
                $this->warn('Operation cancelled.');
                return 1;
            }
        }

        // Collect material info
        $this->collectMaterialInfo();

        // Confirm generation
        $this->displaySummary();

        if (!$this->confirm('Generate material files?', true)) {
            $this->warn('Operation cancelled.');
            return 1;
        }

        // Start generation
        $this->newLine();
        $this->info('🔨 Generating files...');
        $this->newLine();

        try {
            $this->generateMigration();
            $this->generateModel();
            $this->generateRepository();
            $this->generateService();
            $this->generateController();
            $this->generateFormRequests();
            $this->generateResource();
            $this->generateSeeder();
            $this->generateViews();
            $this->generateJavaScript();
            $this->updateRoutes();
            $this->updateMaterialSetting();
            $this->updateDatabaseSeeder();

            $this->newLine();
            $this->info('✅ Material generated successfully!');
            $this->newLine();
            $this->displayNextSteps();

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Generation failed: ' . $e->getMessage());
            $this->warn('Rolling back generated files...');
            $this->rollback();
            return 1;
        }
    }

    protected function collectMaterialInfo()
    {
        $this->materialLabel = $this->ask('Indonesian label (e.g., Ubin)', $this->materialName);
        $this->materialIcon = $this->ask('Icon emoji (e.g., 🟦)', '📦');

        $this->hasPackageUnit = $this->confirm(
            'Does this material have packageUnit relationship to Units table?',
            false,
        );

        $this->info('Define fields (press Enter with empty name to finish):');
        $this->defineFields();
    }

    protected function defineFields()
    {
        // Use Ceramic as default template
        $useTemplate = $this->choice(
            'Use existing material as template?',
            ['None (manual)', 'Ceramic', 'Brick', 'Cat', 'Cement', 'Sand'],
            0,
        );

        if ($useTemplate !== 'None (manual)') {
            $this->fields = $this->getTemplateFields(strtolower($useTemplate));
            $this->info('Loaded ' . count($this->fields) . ' fields from ' . $useTemplate . ' template.');
            return;
        }

        // Manual field definition
        while (true) {
            $fieldName = $this->ask('Field name (or press Enter to finish)');

            if (empty($fieldName)) {
                break;
            }

            $fieldType = $this->choice(
                'Field type',
                ['string', 'text', 'integer', 'decimal:2', 'boolean', 'date', 'datetime', 'file'],
                0,
            );

            $nullable = $this->confirm('Nullable?', true);
            $hasAutocomplete = in_array($fieldType, ['string', 'text']) && $this->confirm('Enable autocomplete?', true);

            $this->fields[$fieldName] = [
                'type' => $fieldType,
                'nullable' => $nullable,
                'autocomplete' => $hasAutocomplete,
            ];

            $this->info("✅ Added: {$fieldName} ({$fieldType})");
        }
    }

    protected function getTemplateFields($template)
    {
        $templates = [
            'ceramic' => [
                'type' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'brand' => ['type' => 'string', 'nullable' => false, 'autocomplete' => true],
                'sub_brand' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'code' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'color' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'form' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'packaging' => ['type' => 'string', 'nullable' => true, 'autocomplete' => false],
                'pieces_per_package' => ['type' => 'integer', 'nullable' => false],
                'dimension_length' => ['type' => 'decimal:2', 'nullable' => false],
                'dimension_width' => ['type' => 'decimal:2', 'nullable' => false],
                'dimension_thickness' => ['type' => 'decimal:2', 'nullable' => true],
                'price_per_package' => ['type' => 'decimal:2', 'nullable' => false],
                'store' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'address' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'photo' => ['type' => 'file', 'nullable' => true],
            ],
            'brick' => [
                'type' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'brand' => ['type' => 'string', 'nullable' => false, 'autocomplete' => true],
                'form' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'dimension_length' => ['type' => 'decimal:2', 'nullable' => false],
                'dimension_width' => ['type' => 'decimal:2', 'nullable' => false],
                'dimension_height' => ['type' => 'decimal:2', 'nullable' => false],
                'price_per_piece' => ['type' => 'decimal:2', 'nullable' => false],
                'store' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'address' => ['type' => 'string', 'nullable' => true, 'autocomplete' => true],
                'photo' => ['type' => 'file', 'nullable' => true],
            ],
        ];

        return $templates[$template] ?? [];
    }

    protected function checkExistingFiles()
    {
        $modelPath = app_path("Models/{$this->materialName}.php");
        $controllerPath = app_path("Http/Controllers/{$this->materialName}Controller.php");

        return File::exists($modelPath) || File::exists($controllerPath);
    }

    protected function displaySummary()
    {
        $this->newLine();
        $this->info('📋 Generation Summary:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Material Name', $this->materialName],
                ['Table Name', $this->materialNamePlural],
                ['Label', $this->materialLabel],
                ['Icon', $this->materialIcon],
                ['Has packageUnit', $this->hasPackageUnit ? 'Yes' : 'No'],
                ['Fields Count', count($this->fields)],
            ],
        );
    }

    protected function displayNextSteps()
    {
        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';

        $this->newLine();
        $this->info('🎉 Material berhasil di-generate!');
        $this->newLine();
        $this->info('📝 Next steps:');
        $this->line('1. php artisan migrate');
        $this->line("2. php artisan db:seed --class={$seederClass}");
        $this->line('3. php artisan db:seed --class=MaterialSettingSeeder');
        $this->line("4. Visit: /{$this->materialNamePlural}");
        $this->newLine();
        $this->comment('💡 Tips:');
        $this->line('   - MaterialSettingSeeder dan DatabaseSeeder sudah auto-update');
        $this->line('   - Seeder sudah include sample data untuk testing');
        $this->line('   - Untuk delete: php artisan make:material --delete');
        $this->newLine();
    }

    protected function generateMigration()
    {
        $timestamp = date('Y_m_d_His');
        $className = 'Create' . Str::plural($this->materialName) . 'Table';
        $filename = "{$timestamp}_create_{$this->materialNamePlural}_table.php";
        $path = database_path("migrations/{$filename}");

        $stub = $this->getMigrationStub();
        File::put($path, $stub);

        $this->generatedFiles[] = $path;
        $this->line("✅ Created: {$path}");
    }

    protected function getMigrationStub()
    {
        $fields = '';
        foreach ($this->fields as $name => $config) {
            $type = $config['type'];
            $nullable = $config['nullable'] ? '->nullable()' : '';

            if ($type === 'file') {
                $fields .= "            \$table->string('{$name}'){$nullable};\n";
            } elseif (Str::startsWith($type, 'decimal')) {
                $precision = str_replace('decimal:', '', $type);
                $fields .= "            \$table->decimal('{$name}', 10, {$precision}){$nullable};\n";
            } else {
                $fields .= "            \$table->{$type}('{$name}'){$nullable};\n";
            }
        }

        return <<<PHP
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$this->materialNamePlural}', function (Blueprint \$table) {
                    \$table->id();
        {$fields}
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$this->materialNamePlural}');
            }
        };
        PHP;
    }

    protected function generateModel()
    {
        $path = app_path("Models/{$this->materialName}.php");
        $stub = $this->getModelStub();
        File::put($path, $stub);

        $this->generatedFiles[] = $path;
        $this->line("✅ Created: {$path}");
    }

    protected function getModelStub()
    {
        $fillable = collect($this->fields)->keys()->map(fn($k) => "'{$k}'")->join(",\n        ");

        $casts = [];
        foreach ($this->fields as $name => $config) {
            $type = $config['type'];
            if (Str::startsWith($type, 'decimal')) {
                $precision = str_replace('decimal:', '', $type);
                $casts[] = "'{$name}' => 'decimal:{$precision}'";
            } elseif ($type === 'integer') {
                $casts[] = "'{$name}' => 'integer'";
            } elseif ($type === 'boolean') {
                $casts[] = "'{$name}' => 'boolean'";
            }
        }
        $castsStr = !empty($casts) ? implode(",\n            ", $casts) : '';

        $packageUnitRelation = $this->hasPackageUnit
            ? <<<'PHP'

                /**
                 * Relationship to Unit for package_unit
                 */
                public function packageUnit()
                {
                    return $this->belongsTo(Unit::class, 'package_unit', 'code')->whereHas('materialTypes', function ($q) {
                        $q->where('material_type', self::getMaterialType());
                    });
                }
            PHP
            : '';

        return <<<PHP
        <?php

        namespace App\Models;

        use Illuminate\Database\Eloquent\Factories\HasFactory;
        use Illuminate\Database\Eloquent\Model;

        class {$this->materialName} extends Model
        {
            use HasFactory;

            protected \$fillable = [
                {$fillable}
            ];

            protected function casts(): array
            {
                return [
                    {$castsStr}
                ];
            }

            public static function getMaterialType(): string
            {
                return '" . Str::snake($this->materialName) . "';
            }
        {$packageUnitRelation}
        }
        PHP;
    }

    protected function generateRepository()
    {
        $path = app_path("Repositories/Material/{$this->materialName}Repository.php");
        File::ensureDirectoryExists(dirname($path));

        $stub = $this->getRepositoryStub();
        File::put($path, $stub);

        $this->generatedFiles[] = $path;
        $this->line("✅ Created: {$path}");
    }

    protected function getRepositoryStub()
    {
        return <<<PHP
        <?php

        namespace App\Repositories\Material;

        use App\Models\\{$this->materialName};
        use App\Repositories\BaseRepository;

        class {$this->materialName}Repository extends BaseRepository
        {
            public function __construct({$this->materialName} \$model)
            {
                parent::__construct(\$model);
            }

            public function paginateWithSort(\$perPage = 15, \$sortBy = 'created_at', \$sortDirection = 'desc')
            {
                return \$this->model
                    ->orderBy(\$sortBy, \$sortDirection)
                    ->paginate(\$perPage);
            }

            public function search(\$keyword, \$perPage = 15, \$sortBy = 'created_at', \$sortDirection = 'desc')
            {
                return \$this->model
                    ->where('brand', 'like', "%{\$keyword}%")
                    ->orWhere('store', 'like', "%{\$keyword}%")
                    ->orderBy(\$sortBy, \$sortDirection)
                    ->paginate(\$perPage);
            }
        }
        PHP;
    }

    protected function generateService()
    {
        $path = app_path("Services/Material/{$this->materialName}Service.php");
        File::ensureDirectoryExists(dirname($path));

        $stub = $this->getServiceStub();
        File::put($path, $stub);

        $this->generatedFiles[] = $path;
        $this->line("✅ Created: {$path}");
    }

    protected function getServiceStub()
    {
        $photoHandling = isset($this->fields['photo'])
            ? <<<'PHP'

                    // Handle photo upload
                    if ($photo) {
                        $data['photo'] = $this->handlePhotoUpload($photo, $data);
                    }
            PHP
            : '';

        return <<<PHP
        <?php

        namespace App\Services\Material;

        use App\Repositories\Material\\{$this->materialName}Repository;
        use Illuminate\Http\UploadedFile;
        use Illuminate\Support\Facades\Storage;

        class {$this->materialName}Service
        {
            protected \$repository;

            public function __construct({$this->materialName}Repository \$repository)
            {
                \$this->repository = \$repository;
            }

            public function paginateWithSort(\$perPage = 15, \$sortBy = 'created_at', \$sortDirection = 'desc')
            {
                return \$this->repository->paginateWithSort(\$perPage, \$sortBy, \$sortDirection);
            }

            public function search(\$keyword, \$perPage = 15, \$sortBy = 'created_at', \$sortDirection = 'desc')
            {
                return \$this->repository->search(\$keyword, \$perPage, \$sortBy, \$sortDirection);
            }

            public function create(array \$data, ?UploadedFile \$photo = null)
            {
        {$photoHandling}
                return \$this->repository->create(\$data);
            }

            public function update(\$id, array \$data, ?UploadedFile \$photo = null)
            {
        {$photoHandling}
                return \$this->repository->update(\$id, \$data);
            }

            public function delete(\$id)
            {
                return \$this->repository->delete(\$id);
            }

            protected function handlePhotoUpload(UploadedFile \$photo, array &\$data): string
            {
                \$path = \$photo->store('{$this->materialNamePlural}', 'public');
                return \$path;
            }
        }
        PHP;
    }

    protected function generateController()
    {
        $path = app_path("Http/Controllers/{$this->materialName}Controller.php");
        $stub = $this->getControllerStub();
        File::put($path, $stub);

        $this->generatedFiles[] = $path;
        $this->line("✅ Created: {$path}");
    }

    protected function getControllerStub()
    {
        return <<<PHP
        <?php

        namespace App\Http\Controllers;

        use App\Models\\{$this->materialName};
        use App\Services\Material\\{$this->materialName}Service;
        use Illuminate\Http\Request;

        class {$this->materialName}Controller extends Controller
        {
            protected \$service;

            public function __construct({$this->materialName}Service \$service)
            {
                \$this->service = \$service;
            }

            public function index(Request \$request)
            {
                \$search = \$request->input('search', '');
                \$sortBy = \$request->input('sort_by', 'created_at');
                \$sortDirection = \$request->input('sort_direction', 'desc');
                \$perPage = \$request->input('per_page', 15);

                \${$this->materialNamePlural} = \$search
                    ? \$this->service->search(\$search, \$perPage, \$sortBy, \$sortDirection)
                    : \$this->service->paginateWithSort(\$perPage, \$sortBy, \$sortDirection);

                \${$this->materialNamePlural}->appends(\$request->all());

                return view('{$this->materialNamePlural}.index', compact('{$this->materialNamePlural}'));
            }

            public function create()
            {
                return view('{$this->materialNamePlural}.create');
            }

            public function store(Request \$request)
            {
                \$data = \$request->all();
                \$this->service->create(\$data, \$request->file('photo'));

                return redirect()->route('{$this->materialNamePlural}.index')->with('success', 'Data berhasil disimpan');
            }

            public function show({$this->materialName} \${$this->materialNameSingular()})
            {
                return view('{$this->materialNamePlural}.show', compact('{$this->materialNameSingular()}'));
            }

            public function edit({$this->materialName} \${$this->materialNameSingular()})
            {
                return view('{$this->materialNamePlural}.edit', compact('{$this->materialNameSingular()}'));
            }

            public function update(Request \$request, {$this->materialName} \${$this->materialNameSingular()})
            {
                \$data = \$request->all();
                \$this->service->update(\${$this->materialNameSingular()}->id, \$data, \$request->file('photo'));

                return redirect()->route('{$this->materialNamePlural}.index')->with('success', 'Data berhasil diperbarui');
            }

            public function destroy({$this->materialName} \${$this->materialNameSingular()})
            {
                \$this->service->delete(\${$this->materialNameSingular()}->id);
                return redirect()->route('{$this->materialNamePlural}.index')->with('success', 'Data berhasil dihapus');
            }
        }
        PHP;
    }

    protected function materialNameSingular()
    {
        return Str::camel($this->materialName);
    }

    protected function generateFormRequests()
    {
        // Placeholder - will implement later
        $this->line('⏭️  Skipped: FormRequests (will be added in next iteration)');
    }

    protected function generateResource()
    {
        // Placeholder - will implement later
        $this->line('⏭️  Skipped: Resource (will be added in next iteration)');
    }

    protected function generateSeeder()
    {
        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';
        $path = database_path("seeders/{$seederClass}.php");

        $stub = $this->getSeederStub();
        File::put($path, $stub);

        $this->generatedFiles[] = $path;
        $this->line("✅ Created: {$path}");
    }

    protected function getSeederStub()
    {
        $modelName = $this->materialName;
        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';

        // Build sample data based on fields
        $sampleData = $this->generateSampleData();

        return <<<PHP
        <?php

        namespace Database\Seeders;

        use Illuminate\Database\Seeder;
        use App\Models\\{$modelName};

        class {$seederClass} extends Seeder
        {
            /**
             * Run the database seeds.
             */
            public function run(): void
            {
                // Sample data for {$this->materialLabel}
                \$data = {$sampleData};

                foreach (\$data as \$item) {
                    {$modelName}::create(\$item);
                }
            }
        }
        PHP;
    }

    protected function generateSampleData()
    {
        $samples = [];

        // Generate 2 sample records
        for ($i = 1; $i <= 2; $i++) {
            $sample = [];
            foreach ($this->fields as $name => $config) {
                $type = $config['type'];

                if ($name === 'photo') {
                    continue; // Skip photo for sample data
                }

                if (Str::startsWith($type, 'decimal')) {
                    $sample[$name] = $i * 10 + 0.5;
                } elseif ($type === 'integer') {
                    $sample[$name] = $i * 10;
                } elseif ($type === 'boolean') {
                    $sample[$name] = true;
                } elseif (in_array($name, ['brand', 'type', 'color', 'form'])) {
                    $sample[$name] = ucfirst($name) . ' ' . chr(64 + $i); // "Brand A", "Brand B"
                } elseif ($name === 'code') {
                    $sample[$name] = 'CODE-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                } elseif (Str::startsWith($name, 'price_')) {
                    $sample[$name] = $i * 50000;
                } elseif ($name === 'store') {
                    $sample[$name] = 'Toko ' . chr(64 + $i);
                } elseif ($name === 'address') {
                    $sample[$name] = 'Jl. Sample No. ' . $i;
                } else {
                    $sample[$name] = ucfirst(str_replace('_', ' ', $name)) . ' ' . $i;
                }
            }
            $samples[] = $sample;
        }

        // Format as PHP array string
        $output = "[\n";
        foreach ($samples as $sample) {
            $output .= "            [\n";
            foreach ($sample as $key => $value) {
                if (is_string($value)) {
                    $output .= "                '{$key}' => '{$value}',\n";
                } elseif (is_bool($value)) {
                    $output .= "                '{$key}' => " . ($value ? 'true' : 'false') . ",\n";
                } else {
                    $output .= "                '{$key}' => {$value},\n";
                }
            }
            $output .= "            ],\n";
        }
        $output .= '        ]';

        return $output;
    }

    protected function generateViews()
    {
        $viewsPath = resource_path("views/{$this->materialNamePlural}");
        File::ensureDirectoryExists($viewsPath);

        // Generate index.blade.php
        $indexPath = "{$viewsPath}/index.blade.php";
        File::put($indexPath, $this->getIndexViewStub());
        $this->generatedFiles[] = $indexPath;
        $this->line("✅ Created: {$indexPath}");

        // Generate create.blade.php
        $createPath = "{$viewsPath}/create.blade.php";
        File::put($createPath, $this->getCreateViewStub());
        $this->generatedFiles[] = $createPath;
        $this->line("✅ Created: {$createPath}");

        // Generate edit.blade.php
        $editPath = "{$viewsPath}/edit.blade.php";
        File::put($editPath, $this->getEditViewStub());
        $this->generatedFiles[] = $editPath;
        $this->line("✅ Created: {$editPath}");
    }

    protected function generateJavaScript()
    {
        $jsPath = public_path("js/{$this->materialNamePlural}-form.js");
        File::ensureDirectoryExists(dirname($jsPath));

        File::put($jsPath, $this->getJavaScriptStub());
        $this->generatedFiles[] = $jsPath;
        $this->line("✅ Created: {$jsPath}");
    }

    protected function updateRoutes()
    {
        $routePath = base_path('routes/web.php');
        $route = "Route::resource('{$this->materialNamePlural}', {$this->materialName}Controller::class);";

        $content = File::get($routePath);

        if (!Str::contains($content, $route)) {
            $content .= "\n{$route}\n";
            File::put($routePath, $content);
            $this->line('✅ Updated: routes/web.php');
        } else {
            $this->line('⏭️  Route already exists in web.php');
        }
    }

    protected function updateMaterialSetting()
    {
        $seederPath = database_path('seeders/MaterialSettingSeeder.php');

        if (!File::exists($seederPath)) {
            $this->warn('⚠️  MaterialSettingSeeder.php not found, skipping...');
            return;
        }

        $content = File::get($seederPath);
        $materialType = Str::snake($this->materialName);

        // Check if already exists
        if (Str::contains($content, "'material_type' => '{$materialType}'")) {
            $this->line('⏭️  Material already exists in MaterialSettingSeeder');
            return;
        }

        // Find the last display_order
        preg_match_all("/'display_order' => (\d+)/", $content, $matches);
        $nextOrder = !empty($matches[1]) ? max($matches[1]) + 1 : 1;

        // Create new material entry (multi-line format to match existing style)
        $newMaterial = <<<PHP
                    [
                        'material_type' => '{$materialType}',
                        'is_visible' => true,
                        'display_order' => {$nextOrder},
                    ],
        PHP;

        // Find the position to insert (before the closing ];)
        // Pattern matches the closing bracket of the array
        $pattern = '/(\s*\];.*?foreach)/s';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[1][1];
            $content = substr_replace(
                $content,
                "\n{$newMaterial}" . $matches[1][0],
                $insertPosition,
                strlen($matches[1][0]),
            );

            File::put($seederPath, $content);
            $this->line("✅ Updated: MaterialSettingSeeder.php (added {$materialType})");
        } else {
            $this->warn('⚠️  Could not find insertion point in MaterialSettingSeeder');
        }
    }

    protected function updateDatabaseSeeder()
    {
        $seederPath = database_path('seeders/DatabaseSeeder.php');

        if (!File::exists($seederPath)) {
            $this->warn('⚠️  DatabaseSeeder.php not found, skipping...');
            return;
        }

        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';
        $content = File::get($seederPath);

        // Check if already exists
        if (Str::contains($content, "{$seederClass}::class")) {
            $this->line("⏭️  {$seederClass} already registered in DatabaseSeeder");
            return;
        }

        // Find the position to insert (after MaterialSettingSeeder, before BrickInstallationTypeSeeder)
        $pattern = '/(MaterialSettingSeeder::class,\s*\n)/';

        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            // Insert after MaterialSettingSeeder
            $insertPosition = $matches[1][1] + strlen($matches[1][0]);

            // Find the indentation
            preg_match('/^(\s*)MaterialSettingSeeder/m', $content, $indentMatch);
            $indent = $indentMatch[1] ?? '            ';

            $newSeeder = "{$indent}{$seederClass}::class,\n";
            $content = substr_replace($content, $newSeeder, $insertPosition, 0);

            File::put($seederPath, $content);
            $this->line("✅ Updated: DatabaseSeeder.php (registered {$seederClass})");
        } else {
            $this->warn('⚠️  Could not find insertion point in DatabaseSeeder');
        }
    }

    // ============================================
    // SMART FIELD DETECTION HELPERS
    // ============================================

    protected function getIndexColumns()
    {
        // Filter out fields that shouldn't appear in table
        $excludedFields = ['photo', 'address', 'description'];

        $columns = [];
        foreach ($this->fields as $name => $config) {
            if (!in_array($name, $excludedFields) && $config['type'] !== 'text') {
                $columns[$name] = $config;
            }
        }

        return $columns;
    }

    protected function getSortableColumns()
    {
        $sortable = [];
        foreach ($this->getIndexColumns() as $name => $config) {
            // All columns are sortable except file types
            if ($config['type'] !== 'file') {
                $sortable[] = $name;
            }
        }
        return $sortable;
    }

    protected function getFieldLabel($fieldName)
    {
        // Convert field names to Indonesian labels
        $labels = [
            'type' => 'Jenis',
            'brand' => 'Merek',
            'sub_brand' => 'Sub Merek',
            'code' => 'Kode',
            'color' => 'Warna',
            'form' => 'Bentuk',
            'packaging' => 'Kemasan',
            'pieces_per_package' => 'Volume',
            'dimension_length' => 'Panjang',
            'dimension_width' => 'Lebar',
            'dimension_height' => 'Tinggi',
            'dimension_thickness' => 'Tebal',
            'price_per_package' => 'Harga / Kemasan',
            'price_per_piece' => 'Harga / Piece',
            'store' => 'Toko',
            'address' => 'Alamat',
            'photo' => 'Foto',
        ];

        return $labels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
    }

    protected function getDimensionFields()
    {
        $dimensions = [];
        foreach ($this->fields as $name => $config) {
            if (Str::startsWith($name, 'dimension_')) {
                $dimensions[$name] = $config;
            }
        }
        return $dimensions;
    }

    protected function getPriceFields()
    {
        $prices = [];
        foreach ($this->fields as $name => $config) {
            if (Str::startsWith($name, 'price_')) {
                $prices[$name] = $config;
            }
        }
        return $prices;
    }

    protected function getRegularFields()
    {
        $excluded = array_merge(array_keys($this->getDimensionFields()), array_keys($this->getPriceFields()), [
            'photo',
        ]);

        $regular = [];
        foreach ($this->fields as $name => $config) {
            if (!in_array($name, $excluded)) {
                $regular[$name] = $config;
            }
        }
        return $regular;
    }

    // ============================================
    // VIEW STUB GENERATORS
    // ============================================

    protected function getIndexViewStub()
    {
        $columns = $this->getIndexColumns();
        $sortableColumns = $this->getSortableColumns();

        // Generate table headers
        $tableHeaders = '';
        foreach ($columns as $name => $config) {
            $label = $this->getFieldLabel($name);
            $isSortable = in_array($name, $sortableColumns) ? 'sortable' : '';
            $tableHeaders .= "                <th class=\"{$isSortable}\" data-sort=\"{$name}\">{$label}</th>\n";
        }

        // Generate table body cells
        $tableCells = '';
        foreach ($columns as $name => $config) {
            $type = $config['type'];

            if (Str::startsWith($type, 'decimal')) {
                // Numeric formatting for decimals
                $tableCells .= "                    <td style=\"text-align: right;\">{{ number_format(\$item->{$name}, 2, ',', '.') }}</td>\n";
            } elseif ($type === 'integer') {
                // Numeric formatting for integers
                $tableCells .= "                    <td style=\"text-align: right;\">{{ number_format(\$item->{$name}, 0, ',', '.') }}</td>\n";
            } else {
                // Regular text
                $tableCells .= "                    <td>{{ \$item->{$name} ?? '-' }}</td>\n";
            }
        }

        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Data {$this->materialLabel}</h5>
                            <a href="{{ route('{$this->materialNamePlural}.create') }}" class="btn btn-primary-glossy  btn-sm">
                                {$this->materialIcon} Tambah {$this->materialLabel}
                            </a>
                        </div>

                        <div class="card-body">
                            <!-- Search Form -->
                            <form method="GET" action="{{ route('{$this->materialNamePlural}.index') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                           placeholder="Cari {$this->materialLabel}..."
                                           value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">Cari</button>
                                </div>
                            </form>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">No</th>
        {$tableHeaders}
                                            <th style="width: 150px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(\${$this->materialNamePlural} as \$index => \$item)
                                        <tr>
                                            <td>{{ \${$this->materialNamePlural}->firstItem() + \$index }}</td>
        {$tableCells}
                                            <td>
                                                <a href="{{ route('{$this->materialNamePlural}.edit', \$item->id) }}"
                                                   class="btn btn-sm btn-warning">Edit</a>
                                                <form action="{{ route('{$this->materialNamePlural}.destroy', \$item->id) }}"
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ count(\$columns) + 2 }}" class="text-center">Tidak ada data</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-center">
                                {{ \${$this->materialNamePlural}->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Simple table sorting
        document.querySelectorAll('.sortable').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const sortBy = this.dataset.sort;
                const url = new URL(window.location.href);
                url.searchParams.set('sort_by', sortBy);

                const currentDirection = url.searchParams.get('sort_direction') || 'asc';
                url.searchParams.set('sort_direction', currentDirection === 'asc' ? 'desc' : 'asc');

                window.location.href = url.toString();
            });
        });
        </script>
        @endsection
        BLADE;
    }

    protected function getCreateViewStub()
    {
        $formFields = $this->generateFormFields();

        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-header">
                            <h5>Tambah {$this->materialLabel}</h5>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('{$this->materialNamePlural}.store') }}" method="POST" enctype="multipart/form-data" id="{$this->materialNamePlural}-form">
                                @csrf

                                <div class="row">
                                    <!-- Left Column: Form Fields -->
                                    <div class="col-md-8">
        {$formFields}
                                    </div>

                                    <!-- Right Column: Photo Upload -->
                                    <div class="col-md-4">
                                        {$this->generatePhotoUploadSection()}
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-glossy ">Simpan</button>
                                        <a href="{{ route('{$this->materialNamePlural}.index') }}" class="btn btn-secondary">Batal</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/{$this->materialNamePlural}-form.js') }}"></script>
        @endsection
        BLADE;
    }

    protected function getEditViewStub()
    {
        $formFields = $this->generateFormFields(true); // true = edit mode

        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-header">
                            <h5>Edit {$this->materialLabel}</h5>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('{$this->materialNamePlural}.update', \${$this->materialNameSingular()}->id) }}" method="POST" enctype="multipart/form-data" id="{$this->materialNamePlural}-form">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <!-- Left Column: Form Fields -->
                                    <div class="col-md-8">
        {$formFields}
                                    </div>

                                    <!-- Right Column: Photo Upload -->
                                    <div class="col-md-4">
                                        {$this->generatePhotoUploadSection(true)}
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-glossy ">Update</button>
                                        <a href="{{ route('{$this->materialNamePlural}.index') }}" class="btn btn-secondary">Batal</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/{$this->materialNamePlural}-form.js') }}"></script>
        @endsection
        BLADE;
    }

    protected function generateFormFields($isEdit = false)
    {
        $html = '';

        // 1. Regular Fields
        $regularFields = $this->getRegularFields();
        foreach ($regularFields as $name => $config) {
            $html .= $this->generateFieldHtml($name, $config, $isEdit);
        }

        // 2. Dimension Fields (grouped)
        $dimensionFields = $this->getDimensionFields();
        if (!empty($dimensionFields)) {
            $html .= "                                <div class=\"mb-3\">\n";
            $html .= "                                    <label class=\"form-label\">Dimensi (cm)</label>\n";
            $html .= "                                    <div class=\"row\">\n";

            foreach ($dimensionFields as $name => $config) {
                $label = $this->getFieldLabel($name);
                $shortLabel = str_replace('dimension_', '', $name);
                $shortLabel = ucfirst($shortLabel);
                $required = $config['nullable'] ? '' : 'required';
                $value = $isEdit ? "{{ \${$this->materialNameSingular()}->{$name} }}" : "{{ old('{$name}') }}";

                $html .= "                                        <div class=\"col-md-4\">\n";
                $html .= "                                            <label class=\"form-label\">{$shortLabel}</label>\n";
                $html .= "                                            <input type=\"number\" step=\"0.01\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
                $html .= "                                        </div>\n";
            }

            $html .= "                                    </div>\n";
            $html .= "                                </div>\n\n";
        }

        // 3. Price Fields (grouped)
        $priceFields = $this->getPriceFields();
        foreach ($priceFields as $name => $config) {
            $label = $this->getFieldLabel($name);
            $required = $config['nullable'] ? '' : 'required';
            $value = $isEdit ? "{{ \${$this->materialNameSingular()}->{$name} }}" : "{{ old('{$name}') }}";

            $html .= "                                <div class=\"mb-3\">\n";
            $html .= "                                    <label class=\"form-label\">{$label}</label>\n";
            $html .= "                                    <div class=\"input-group\">\n";
            $html .= "                                        <span class=\"input-group-text\">Rp</span>\n";
            $html .= "                                        <input type=\"number\" step=\"0.01\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
            $html .= "                                    </div>\n";
            $html .= "                                </div>\n\n";
        }

        return $html;
    }

    protected function generateFieldHtml($name, $config, $isEdit = false)
    {
        $label = $this->getFieldLabel($name);
        $required = $config['nullable'] ? '' : 'required';
        $type = $config['type'];
        $value = $isEdit ? "{{ \${$this->materialNameSingular()}->{$name} }}" : "{{ old('{$name}') }}";

        $html = "                                <div class=\"mb-3\">\n";
        $html .= "                                    <label class=\"form-label\">{$label}</label>\n";

        if ($config['autocomplete'] ?? false) {
            // Autocomplete field
            $html .= "                                    <input type=\"text\" class=\"form-control autocomplete-{$name}\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
        } elseif ($type === 'text') {
            // Textarea
            $html .= "                                    <textarea class=\"form-control\" name=\"{$name}\" rows=\"3\" {$required}>{$value}</textarea>\n";
        } elseif ($type === 'integer') {
            // Integer input
            $html .= "                                    <input type=\"number\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
        } else {
            // Default text input
            $html .= "                                    <input type=\"text\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
        }

        $html .= "                                </div>\n\n";

        return $html;
    }

    protected function generatePhotoUploadSection($isEdit = false)
    {
        if (!isset($this->fields['photo'])) {
            return '';
        }

        $previewImage = $isEdit
            ? "{{ \${$this->materialNameSingular()}->photo ? asset('storage/' . \${$this->materialNameSingular()}->photo) : asset('images/no-image.png') }}"
            : "{{ asset('images/no-image.png') }}";

        return <<<HTML
                                        <div class="mb-3">
                                            <label class="form-label">Foto</label>
                                            <div class="text-center">
                                                <img id="photo-preview" src="{$previewImage}"
                                                     alt="Preview" class="img-thumbnail mb-2" style="max-width: 100%; max-height: 300px;">
                                            </div>
                                            <input type="file" class="form-control" name="photo" id="photo-input" accept="image/*">
                                            <small class="text-muted">Format: JPG, PNG, GIF. Max: 2MB</small>
                                        </div>
        HTML;
    }

    protected function getJavaScriptStub()
    {
        // Get autocomplete fields
        $autocompleteFields = [];
        foreach ($this->fields as $name => $config) {
            if ($config['autocomplete'] ?? false) {
                $autocompleteFields[] = $name;
            }
        }

        $autocompleteSetup = '';
        foreach ($autocompleteFields as $field) {
            $autocompleteSetup .= <<<JS

                // Autocomplete for {$field}
                setupAutocomplete('.autocomplete-{$field}', '{$field}');

            JS;
        }

        $photoPreview = isset($this->fields['photo'])
            ? <<<'JS'

                // Photo preview
                const photoInput = document.getElementById('photo-input');
                const photoPreview = document.getElementById('photo-preview');

                if (photoInput && photoPreview) {
                    photoInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                photoPreview.src = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            JS
            : '';

        return <<<JS
        document.addEventListener('DOMContentLoaded', function() {{$autocompleteSetup}{$photoPreview}
        });

        // Autocomplete helper function
        function setupAutocomplete(selector, fieldName) {
            const input = document.querySelector(selector);
            if (!input) return;

            // You can fetch existing values from API or database
            // For now, this is a placeholder for autocomplete functionality
            // Implement with your preferred autocomplete library (e.g., Bootstrap Autocomplete, jQuery UI)
        }
        JS;
    }

    protected function deleteMaterial()
    {
        $this->warn('🗑️  Delete Material Feature');
        $this->newLine();

        $name = $this->ask('Material name to delete (e.g., Tile)');

        if (!$name) {
            $this->error('Material name is required!');
            return 1;
        }

        $this->materialName = ucfirst(Str::camel($name));
        $this->materialNamePlural = Str::plural(Str::snake($this->materialName));

        if (!$this->confirm("Are you sure you want to delete ALL files for '{$this->materialName}'?", false)) {
            $this->warn('Operation cancelled.');
            return 1;
        }

        $this->rollback();
        $this->info('✅ Material deleted successfully!');

        return 0;
    }

    protected function rollback()
    {
        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';

        $filesToDelete = [
            app_path("Models/{$this->materialName}.php"),
            app_path("Http/Controllers/{$this->materialName}Controller.php"),
            app_path("Repositories/Material/{$this->materialName}Repository.php"),
            app_path("Services/Material/{$this->materialName}Service.php"),
            public_path("js/{$this->materialNamePlural}-form.js"),
            database_path("seeders/{$seederClass}.php"),
        ];

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->line("🗑️  Deleted: {$file}");
            }
        }

        // Delete migration files
        $migrations = File::glob(database_path("migrations/*_create_{$this->materialNamePlural}_table.php"));
        foreach ($migrations as $migration) {
            File::delete($migration);
            $this->line("🗑️  Deleted: {$migration}");
        }

        // Delete view directory
        $viewsPath = resource_path("views/{$this->materialNamePlural}");
        if (File::isDirectory($viewsPath)) {
            File::deleteDirectory($viewsPath);
            $this->line("🗑️  Deleted: {$viewsPath}");
        }

        // Remove from MaterialSettingSeeder
        $this->removeFromMaterialSettingSeeder();

        // Remove from DatabaseSeeder
        $this->removeFromDatabaseSeeder();

        $this->warn('⚠️  Manual cleanup required:');
        $this->line('  - Remove route from routes/web.php');
    }

    protected function removeFromMaterialSettingSeeder()
    {
        $seederPath = database_path('seeders/MaterialSettingSeeder.php');
        if (!File::exists($seederPath)) {
            return;
        }

        $content = File::get($seederPath);
        $materialType = Str::snake($this->materialName);

        // Remove the line with this material
        $pattern = "/\s*\['material_type' => '{$materialType}'[^\]]*\],\n/";
        $content = preg_replace($pattern, '', $content);

        File::put($seederPath, $content);
        $this->line('🗑️  Removed from MaterialSettingSeeder');
    }

    protected function removeFromDatabaseSeeder()
    {
        $seederPath = database_path('seeders/DatabaseSeeder.php');
        if (!File::exists($seederPath)) {
            return;
        }

        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';
        $content = File::get($seederPath);

        // Remove the line with this seeder
        $pattern = "/\s*{$seederClass}::class,\n/";
        $content = preg_replace($pattern, '', $content);

        File::put($seederPath, $content);
        $this->line('🗑️  Removed from DatabaseSeeder');
    }
}
