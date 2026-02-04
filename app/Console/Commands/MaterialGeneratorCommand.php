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
                            {--force : Force overwrite existing files}
                            {--profile= : Use material profile key (e.g., ceramic, brick) for non-interactive scaffold}
                            {--dry-run : Preview generated/updated files without writing anything}';

    protected $description = 'Generate complete material CRUD (Model, Controller, Service, Repository, Views, JS)';

    protected $materialName;
    protected $materialNamePlural;
    protected $materialLabel;
    protected $materialIcon;
    protected $hasPackageUnit = false;
    protected $selectedTemplate = null;
    protected $fields = [];
    protected $generatedFiles = [];
    protected $fileBackups = [];
    protected $dryRun = false;
    protected $manifestRunId;
    protected $manifestFiles = [];

    public function handle()
    {
        $this->info('🚀 Material Generator - KANGGO Database');
        $this->newLine();

        $this->dryRun = (bool) $this->option('dry-run');

        // Check if delete mode
        if ($this->option('delete')) {
            return $this->deleteMaterial();
        }

        // Get material name
        $nameArg = $this->argument('name');
        if ($nameArg) {
            $this->materialName = $nameArg;
        } elseif ($this->input->isInteractive()) {
            $this->materialName = $this->ask('Material name (singular, e.g., Tile)');
        }

        if (!$this->materialName) {
            $this->error('Material name is required!');
            return 1;
        }

        // Capitalize first letter
        $this->materialName = ucfirst(Str::camel($this->materialName));
        $this->materialNamePlural = Str::plural(Str::snake($this->materialName));

        $this->info("📦 Material: {$this->materialName}");
        $this->info("📦 Table: {$this->materialNamePlural}");
        if ($this->dryRun) {
            $this->comment('DRY-RUN mode: no files will be changed.');
        }
        $this->newLine();

        // Check if files already exist
        if (!$this->option('force') && $this->checkExistingFiles()) {
            if (!$this->input->isInteractive() || !$this->confirm('Some files already exist. Do you want to overwrite?', false)) {
                $this->warn('Operation cancelled.');
                return 1;
            }
        }

        $this->initializeGenerationSession();

        // Collect material info (interactive/non-interactive)
        if ($this->input->isInteractive()) {
            $this->collectMaterialInfo();
        } else {
            if (!$this->collectMaterialInfoNonInteractive()) {
                return 1;
            }
        }

        // Confirm generation
        $this->displaySummary();

        if ($this->input->isInteractive() && !$this->confirm('Generate material files?', true)) {
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
            $this->generateCalculationStrategy();
            $this->generateService();
            $this->generateController();
            $this->generateFormRequests();
            $this->generateResource();
            $this->generateSeeder();
            $this->generateViews();
            $this->generateJavaScript();
            $this->generateFeatureTest();
            $this->updateRoutes();
            $this->updateMaterialSetting();
            $this->updateDatabaseSeeder();
            $this->updateMaterialProfileRegistry();
            $this->updateMaterialCalculationStrategyRegistry();

            $this->newLine();
            if ($this->dryRun) {
                $this->info('✅ Dry-run completed. No files were changed.');
                $this->displayDryRunSummary();
            } else {
                $this->writeGenerationManifest();
                $this->info('✅ Material generated successfully!');
                $this->newLine();
                $this->displayNextSteps();
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Generation failed: ' . $e->getMessage());
            if (!$this->dryRun) {
                $this->warn('Rolling back generated files...');
                $this->rollbackGeneratedChanges();
            }
            return 1;
        }
    }

    protected function initializeGenerationSession(): void
    {
        $this->manifestRunId = date('Ymd_His');
        $this->manifestFiles = [];
        $this->generatedFiles = [];
        $this->fileBackups = [];
    }

    protected function collectMaterialInfoNonInteractive(): bool
    {
        $profileKey = $this->resolveRequestedProfileKey();

        if (!$profileKey) {
            $profileKey = Str::snake($this->materialName);
        }

        $profile = $this->getMaterialProfile($profileKey);
        if (!$profile) {
            $this->error('Non-interactive mode requires a valid --profile option.');
            return false;
        }

        $this->selectedTemplate = $profileKey;
        $this->fields = $this->getTemplateFields($profileKey);

        if (empty($this->fields)) {
            $this->error("Profile '{$profileKey}' has no fields defined.");
            return false;
        }

        $this->materialLabel = $profile['label'] ?? $this->materialName;
        $this->materialIcon = $profile['icon'] ?? '📦';
        $this->hasPackageUnit = (bool) ($profile['has_package_unit'] ?? false);

        if ($this->hasPackageUnit && !isset($this->fields['package_unit'])) {
            $this->fields['package_unit'] = [
                'type' => 'string',
                'nullable' => true,
                'autocomplete' => false,
            ];
        }

        return true;
    }

    protected function collectMaterialInfo()
    {
        $this->info('Define fields (press Enter with empty name to finish):');
        $this->defineFields();

        $profile = $this->getMaterialProfile($this->selectedTemplate) ??
            $this->getMaterialProfile(Str::snake($this->materialName));

        $defaultLabel = $profile['label'] ?? $this->materialName;
        $defaultIcon = $profile['icon'] ?? '📦';
        $defaultHasPackageUnit = (bool) ($profile['has_package_unit'] ?? false);

        $this->materialLabel = $this->ask('Indonesian label (e.g., Ubin)', $defaultLabel);
        $this->materialIcon = $this->ask('Icon emoji (e.g., 📦)', $defaultIcon);

        $this->hasPackageUnit = $this->confirm(
            'Does this material have packageUnit relationship to Units table?',
            $defaultHasPackageUnit,
        );

        if ($this->hasPackageUnit && !isset($this->fields['package_unit'])) {
            $this->fields['package_unit'] = [
                'type' => 'string',
                'nullable' => true,
                'autocomplete' => false,
            ];
            $this->line('package_unit auto-added because packageUnit relation is enabled.');
        }
    }

    protected function defineFields()
    {
        $requestedProfile = $this->resolveRequestedProfileKey();
        if ($requestedProfile) {
            $this->selectedTemplate = $requestedProfile;
            $this->fields = $this->getTemplateFields($requestedProfile);
            $this->info('Loaded ' . count($this->fields) . ' fields from ' . Str::headline($requestedProfile) . ' profile.');
            return;
        }

        $choiceMap = ['None (manual)' => null];
        foreach ($this->getMaterialProfileDefinitions() as $profileKey => $profile) {
            $choiceMap[$profile['display_name'] ?? Str::headline($profileKey)] = $profileKey;
        }

        $useTemplate = $this->choice(
            'Use existing material as template?',
            array_keys($choiceMap),
            0,
        );

        $this->selectedTemplate = $choiceMap[$useTemplate] ?? null;

        if ($this->selectedTemplate !== null) {
            $this->fields = $this->getTemplateFields($this->selectedTemplate);
            $this->info('Loaded ' . count($this->fields) . ' fields from ' . $useTemplate . ' template.');
            return;
        }

        // Manual field definition
        while (true) {
            $fieldName = trim((string) $this->ask('Field name (or press Enter to finish)'));

            if (empty($fieldName)) {
                break;
            }

            $fieldName = Str::snake($fieldName);

            if (!preg_match('/^[a-z][a-z0-9_]*$/', $fieldName)) {
                $this->warn('Invalid field name. Use snake_case format (example: price_per_package).');
                continue;
            }

            if (isset($this->fields[$fieldName])) {
                $this->warn("Field '{$fieldName}' already exists, skipped.");
                continue;
            }

            if (in_array($fieldName, ['id', 'created_at', 'updated_at'], true)) {
                $this->warn("Field '{$fieldName}' is reserved by Laravel, skipped.");
                continue;
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

            $this->info("Added: {$fieldName} ({$fieldType})");
        }
    }

    protected function getTemplateFields($template)
    {
        $profile = $this->getMaterialProfile($template);
        $fields = $profile['fields'] ?? [];

        if (!is_array($fields)) {
            return [];
        }

        return $fields;
    }

    protected function getMaterialProfileDefinitions(): array
    {
        $profiles = config('material_profiles.profiles', []);
        return is_array($profiles) ? $profiles : [];
    }

    protected function getMaterialProfile(?string $key): ?array
    {
        if (!is_string($key) || $key === '') {
            return null;
        }

        $profiles = $this->getMaterialProfileDefinitions();
        $profile = $profiles[$key] ?? null;

        return is_array($profile) ? $profile : null;
    }

    protected function resolveRequestedProfileKey(): ?string
    {
        $requested = trim((string) $this->option('profile'));
        if ($requested === '') {
            return null;
        }

        $profileKey = Str::snake($requested);
        if (!$this->getMaterialProfile($profileKey)) {
            throw new \InvalidArgumentException("Profile '{$requested}' was not found in config/material_profiles.php");
        }

        return $profileKey;
    }

    protected function checkExistingFiles()
    {
        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';

        $paths = [
            app_path("Models/{$this->materialName}.php"),
            app_path("Http/Controllers/{$this->materialName}Controller.php"),
            app_path("Repositories/Material/{$this->materialName}Repository.php"),
            app_path("Services/Material/{$this->materialName}Service.php"),
            app_path("Services/Material/Calculations/{$this->materialName}CalculationStrategy.php"),
            app_path("Http/Requests/Store{$this->materialName}Request.php"),
            app_path("Http/Requests/Update{$this->materialName}Request.php"),
            app_path("Http/Resources/{$this->materialName}Resource.php"),
            database_path("seeders/{$seederClass}.php"),
            base_path("tests/Feature/Materials/{$this->materialName}CrudScaffoldTest.php"),
            public_path("js/{$this->materialNamePlural}-form.js"),
            resource_path("views/{$this->materialNamePlural}"),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                return true;
            }
        }

        return !empty(File::glob(database_path("migrations/*_create_{$this->materialNamePlural}_table.php")));
    }

    protected function writeGeneratedFile(string $path, string $content): void
    {
        $exists = File::exists($path);
        $backupPath = null;

        if ($exists && !array_key_exists($path, $this->fileBackups)) {
            $original = File::get($path);
            $this->fileBackups[$path] = $original;
            $backupPath = $this->getBackupPathForFile($path);

            if (!$this->dryRun) {
                File::ensureDirectoryExists(dirname($backupPath));
                File::put($backupPath, $original);
            }
        } elseif (isset($this->manifestFiles[$path]['backup_path'])) {
            $backupPath = $this->manifestFiles[$path]['backup_path'];
        }

        $this->manifestFiles[$path] = [
            'path' => $path,
            'action' => $exists ? 'overwrite' : 'create',
            'backup_path' => $backupPath,
        ];

        if ($this->dryRun) {
            if (!in_array($path, $this->generatedFiles, true)) {
                $this->generatedFiles[] = $path;
            }
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        if (!in_array($path, $this->generatedFiles, true)) {
            $this->generatedFiles[] = $path;
        }
    }

    protected function rollbackGeneratedChanges(): void
    {
        foreach (array_reverse($this->generatedFiles) as $path) {
            if (array_key_exists($path, $this->fileBackups)) {
                File::put($path, $this->fileBackups[$path]);
                $this->line("↩️  Restored: {$path}");
                continue;
            }

            if (File::exists($path)) {
                File::delete($path);
                $this->line("🗑️  Deleted: {$path}");
            }
        }

        $viewsPath = resource_path("views/{$this->materialNamePlural}");
        if (File::isDirectory($viewsPath) && empty(File::files($viewsPath))) {
            File::deleteDirectory($viewsPath);
        }
    }

    protected function getMaterialType(): string
    {
        return Str::snake($this->materialName);
    }

    protected function getManifestPathForMaterial(string $materialType): string
    {
        return base_path(".material-generator/manifests/{$materialType}.json");
    }

    protected function getBackupPathForFile(string $path): string
    {
        $materialType = $this->getMaterialType();
        $safeName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', str_replace(['\\', '/'], '__', $path));
        return base_path(".material-generator/backups/{$materialType}/{$this->manifestRunId}/{$safeName}.bak");
    }

    protected function writeGenerationManifest(): void
    {
        $materialType = $this->getMaterialType();
        $manifestPath = $this->getManifestPathForMaterial($materialType);
        $manifest = [
            'material_name' => $this->materialName,
            'material_type' => $materialType,
            'profile' => $this->selectedTemplate,
            'generated_at' => date('c'),
            'files' => array_values($this->manifestFiles),
        ];

        File::ensureDirectoryExists(dirname($manifestPath));
        File::put(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    protected function displayDryRunSummary(): void
    {
        $this->newLine();
        $this->info('Dry-run plan:');
        foreach (array_values($this->manifestFiles) as $file) {
            $action = $file['action'] === 'overwrite' ? 'UPDATE' : 'CREATE';
            $this->line("- {$action} {$file['path']}");
        }
        $this->newLine();
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
                ['Profile', $this->selectedTemplate ?: 'manual'],
                ['Dry Run', $this->dryRun ? 'Yes' : 'No'],
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
        $this->line('   - Profile material & strategy registry sudah auto-update');
        $this->line("   - Edit strategy: app/Services/Material/Calculations/{$this->materialName}CalculationStrategy.php");
        $this->line("   - Test scaffold: tests/Feature/Materials/{$this->materialName}CrudScaffoldTest.php");
        $this->line('   - Untuk delete: php artisan make:material --delete');
        $this->newLine();
    }

    protected function generateMigration()
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$this->materialNamePlural}_table.php";
        $path = database_path("migrations/{$filename}");

        $stub = $this->getMigrationStub();
        $this->writeGeneratedFile($path, $stub);
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
        $this->writeGeneratedFile($path, $stub);
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

        $materialType = Str::snake($this->materialName);

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
                return '{$materialType}';
            }
        {$packageUnitRelation}
        }
        PHP;
    }

    protected function generateRepository()
    {
        $path = app_path("Repositories/Material/{$this->materialName}Repository.php");
        $stub = $this->getRepositoryStub();
        $this->writeGeneratedFile($path, $stub);
        $this->line("✅ Created: {$path}");
    }

    protected function getRepositoryStub()
    {
        $searchableFields = collect($this->fields)
            ->filter(function ($config) {
                return in_array($config['type'], ['string', 'text'], true);
            })
            ->keys()
            ->values()
            ->all();
        $hasSearchableFields = !empty($searchableFields);

        $searchWhere = '';
        if ($hasSearchableFields) {
            foreach ($searchableFields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $searchWhere .= "                    \$query->{$method}('{$field}', 'like', \"%{\$keyword}%\");\n";
            }
            $searchFilter = "\n                if (!empty(\$keyword)) {\n"
                . "                    \$query->where(function (\$query) {\n"
                . $searchWhere
                . "                    });\n"
                . "                }\n";
        } else {
            $searchFilter = "\n                // No searchable text columns were defined for this material.\n";
        }

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
                \$query = \$this->model->query();
        {$searchFilter}

                return \$query
                    ->orderBy(\$sortBy, \$sortDirection)
                    ->paginate(\$perPage);
            }
        }
        PHP;
    }

    protected function generateService()
    {
        $path = app_path("Services/Material/{$this->materialName}Service.php");
        $stub = $this->getServiceStub();
        $this->writeGeneratedFile($path, $stub);
        $this->line("✅ Created: {$path}");
    }

    protected function generateCalculationStrategy()
    {
        $path = app_path("Services/Material/Calculations/{$this->materialName}CalculationStrategy.php");
        $materialType = Str::snake($this->materialName);

        $stub = <<<PHP
        <?php

        namespace App\Services\Material\Calculations;

        use Illuminate\Database\Eloquent\Model;

        class {$this->materialName}CalculationStrategy extends BaseMaterialCalculationStrategy
        {
            /**
             * Apply derived-field calculation before persisting model data.
             * Customize this method for '{$materialType}'.
             */
            public function apply(array \$data, ?Model \$existing = null): array
            {
                // Example:
                // if (isset(\$data['price_per_package'], \$data['pieces_per_package']) && (float) \$data['pieces_per_package'] > 0) {
                //     \$data['price_per_piece'] = (float) \$data['price_per_package'] / (float) \$data['pieces_per_package'];
                // }

                return \$data;
            }
        }
        PHP;

        $this->writeGeneratedFile($path, $stub);
        $this->line("✅ Created: {$path}");
    }

    protected function getServiceStub()
    {
        $materialType = Str::snake($this->materialName);
        $hasPhoto = isset($this->fields['photo']);
        $storageImport = $hasPhoto ? "use Illuminate\\Support\\Facades\\Storage;\n" : '';
        $photoHandlingCreate = $hasPhoto
            ? <<<'PHP'

                if ($photo) {
                    $data['photo'] = $this->handlePhotoUpload($photo);
                }
            PHP
            : '';
        $photoHandlingUpdate = $hasPhoto
            ? <<<'PHP'

                if ($photo) {
                    if (!empty($existing->photo)) {
                        Storage::disk('public')->delete($existing->photo);
                    }
                    $data['photo'] = $this->handlePhotoUpload($photo);
                }
            PHP
            : '';
        $photoUploadMethod = $hasPhoto
            ? <<<PHP

            protected function handlePhotoUpload(UploadedFile \$photo): string
            {
                return \$photo->store('{$this->materialNamePlural}', 'public');
            }
        PHP
            : '';

        return <<<PHP
        <?php

        namespace App\Services\Material;

        use App\Repositories\Material\\{$this->materialName}Repository;
        use App\Services\Material\Calculations\MaterialCalculationStrategyRegistry;
        use Illuminate\Http\UploadedFile;
        {$storageImport}

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
                \$data = MaterialCalculationStrategyRegistry::applyFor('{$materialType}', \$data);
        {$photoHandlingCreate}
                return \$this->repository->create(\$data);
            }

            public function update(int \$id, array \$data, ?UploadedFile \$photo = null)
            {
                \$existing = \$this->repository->findOrFail(\$id);
                \$data = MaterialCalculationStrategyRegistry::applyFor('{$materialType}', \$data, \$existing);
        {$photoHandlingUpdate}
                return \$this->repository->update(\$id, \$data);
            }

            public function delete(int \$id): bool
            {
                return \$this->repository->delete(\$id);
            }
        {$photoUploadMethod}
        }
        PHP;
    }

    protected function generateController()
    {
        $path = app_path("Http/Controllers/{$this->materialName}Controller.php");
        $stub = $this->getControllerStub();
        $this->writeGeneratedFile($path, $stub);
        $this->line("✅ Created: {$path}");
    }

    protected function getControllerStub()
    {
        $sortableColumns = array_values(array_unique(array_merge(
            $this->getSortableColumns(),
            ['id', 'created_at', 'updated_at'],
        )));
        $allowedSorts = implode(",\n                    ", array_map(fn($column) => "'{$column}'", $sortableColumns));

        return <<<PHP
        <?php

        namespace App\Http\Controllers;

        use App\Http\Requests\Store{$this->materialName}Request;
        use App\Http\Requests\Update{$this->materialName}Request;
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
                \$search = (string) \$request->input('search', '');
                \$sortBy = (string) \$request->input('sort_by', 'created_at');
                \$sortDirection = strtolower((string) \$request->input('sort_direction', 'desc'));
                \$perPage = (int) \$request->input('per_page', 15);
                \$perPage = max(5, min(\$perPage, 100));

                \$allowedSorts = [
                    {$allowedSorts}
                ];

                if (!in_array(\$sortBy, \$allowedSorts, true)) {
                    \$sortBy = 'created_at';
                }

                if (!in_array(\$sortDirection, ['asc', 'desc'], true)) {
                    \$sortDirection = 'desc';
                }

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

            public function store(Store{$this->materialName}Request \$request)
            {
                \$this->service->create(\$request->validated(), \$request->file('photo'));

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

            public function update(Update{$this->materialName}Request \$request, {$this->materialName} \${$this->materialNameSingular()})
            {
                \$this->service->update(\${$this->materialNameSingular()}->id, \$request->validated(), \$request->file('photo'));

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
        $storePath = app_path("Http/Requests/Store{$this->materialName}Request.php");
        $updatePath = app_path("Http/Requests/Update{$this->materialName}Request.php");
        $storeRules = $this->getValidationRules();
        $updateRules = $this->getValidationRules(true);

        $storeStub = <<<PHP
        <?php

        namespace App\Http\Requests;

        use Illuminate\Foundation\Http\FormRequest;

        class Store{$this->materialName}Request extends FormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    {$storeRules}
                ];
            }
        }
        PHP;

        $updateStub = <<<PHP
        <?php

        namespace App\Http\Requests;

        use Illuminate\Foundation\Http\FormRequest;

        class Update{$this->materialName}Request extends FormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    {$updateRules}
                ];
            }
        }
        PHP;

        $this->writeGeneratedFile($storePath, $storeStub);
        $this->line("✅ Created: {$storePath}");
        $this->writeGeneratedFile($updatePath, $updateStub);
        $this->line("✅ Created: {$updatePath}");
    }

    protected function generateResource()
    {
        $path = app_path("Http/Resources/{$this->materialName}Resource.php");
        $resourceFields = "            'id' => \$this->id,\n";
        foreach ($this->fields as $name => $config) {
            if ($name === 'photo') {
                $resourceFields .= "            'photo_url' => \$this->photo ? Storage::url(\$this->photo) : null,\n";
                continue;
            }
            $resourceFields .= "            '{$name}' => \$this->{$name},\n";
        }
        $resourceFields .= "            'created_at' => \$this->created_at?->toIso8601String(),\n";
        $resourceFields .= "            'updated_at' => \$this->updated_at?->toIso8601String(),\n";

        $storageImport = isset($this->fields['photo']) ? "use Illuminate\\Support\\Facades\\Storage;\n" : '';

        $stub = <<<PHP
        <?php

        namespace App\Http\Resources;

        use Illuminate\Http\Request;
        use Illuminate\Http\Resources\Json\JsonResource;
        {$storageImport}

        class {$this->materialName}Resource extends JsonResource
        {
            public function toArray(Request \$request): array
            {
                return [
                    {$resourceFields}
                ];
            }
        }
        PHP;

        $this->writeGeneratedFile($path, $stub);
        $this->line("✅ Created: {$path}");
    }

    protected function getValidationRules(bool $forUpdate = false): string
    {
        $rules = [];

        foreach ($this->fields as $name => $config) {
            $type = $config['type'];
            $nullableOrRequired = $config['nullable'] || $forUpdate ? 'nullable' : 'required';

            if ($type === 'file') {
                $rules[] = "                    '{$name}' => '{$nullableOrRequired}|image|max:2048',";
                continue;
            }

            if (Str::startsWith($type, 'decimal')) {
                $typeRule = 'numeric';
            } else {
                $typeRule = match ($type) {
                    'string' => 'string|max:255',
                    'text' => 'string',
                    'integer' => 'integer',
                    'boolean' => 'boolean',
                    'date' => 'date',
                    'datetime' => 'date',
                    default => 'string',
                };
            }

            $rules[] = "                    '{$name}' => '{$nullableOrRequired}|{$typeRule}',";
        }

        return implode("\n", $rules);
    }

    protected function generateSeeder()
    {
        $seederClass = ucfirst(Str::camel($this->materialName)) . 'Seeder';
        $path = database_path("seeders/{$seederClass}.php");

        $stub = $this->getSeederStub();
        $this->writeGeneratedFile($path, $stub);
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

        // Generate index.blade.php
        $indexPath = "{$viewsPath}/index.blade.php";
        $this->writeGeneratedFile($indexPath, $this->getIndexViewStub());
        $this->line("✅ Created: {$indexPath}");

        // Generate create.blade.php
        $createPath = "{$viewsPath}/create.blade.php";
        $this->writeGeneratedFile($createPath, $this->getCreateViewStub());
        $this->line("✅ Created: {$createPath}");

        // Generate edit.blade.php
        $editPath = "{$viewsPath}/edit.blade.php";
        $this->writeGeneratedFile($editPath, $this->getEditViewStub());
        $this->line("✅ Created: {$editPath}");

        // Generate show.blade.php
        $showPath = "{$viewsPath}/show.blade.php";
        $this->writeGeneratedFile($showPath, $this->getShowViewStub());
        $this->line("✅ Created: {$showPath}");
    }

    protected function generateJavaScript()
    {
        $jsPath = public_path("js/{$this->materialNamePlural}-form.js");
        $this->writeGeneratedFile($jsPath, $this->getJavaScriptStub());
        $this->line("✅ Created: {$jsPath}");
    }

    protected function generateFeatureTest()
    {
        $path = base_path("tests/Feature/Materials/{$this->materialName}CrudScaffoldTest.php");
        $routeName = "{$this->materialNamePlural}.index";
        $materialType = Str::snake($this->materialName);
        $strategyClass = "App\\Services\\Material\\Calculations\\{$this->materialName}CalculationStrategy";

        $stub = <<<PHP
        <?php

        use Illuminate\\Foundation\\Testing\\RefreshDatabase;
        use Illuminate\\Support\\Facades\\Route;
        use Tests\\TestCase;

        uses(TestCase::class, RefreshDatabase::class);

        test('{$materialType} index route is registered', function () {
            expect(Route::has('{$routeName}'))->toBeTrue();
        });

        test('{$materialType} profile and strategy registry entries exist', function () {
            \$profiles = config('material_profiles.profiles', []);

            expect(\$profiles)->toHaveKey('{$materialType}')
                ->and(config('material_calculation_strategies.{$materialType}'))->toBe('{$strategyClass}');
        });
        PHP;

        $this->writeGeneratedFile($path, $stub);
        $this->line("✅ Created: {$path}");
    }

    protected function updateMaterialProfileRegistry()
    {
        $configPath = config_path('material_profiles.php');
        $config = $this->readPhpConfig($configPath);
        $config['profiles'] = is_array($config['profiles'] ?? null) ? $config['profiles'] : [];
        $materialType = Str::snake($this->materialName);

        if (isset($config['profiles'][$materialType])) {
            $this->line('⏭️  Material profile already exists in config/material_profiles.php');
            return;
        }

        $config['profiles'][$materialType] = [
            'display_name' => $this->materialName,
            'label' => $this->materialLabel,
            'icon' => $this->materialIcon,
            'has_package_unit' => $this->hasPackageUnit,
            'fields' => $this->fields,
        ];

        $this->writePhpConfig($configPath, $config);
        $this->line("✅ Updated: config/material_profiles.php (added {$materialType})");
    }

    protected function updateMaterialCalculationStrategyRegistry()
    {
        $configPath = config_path('material_calculation_strategies.php');
        $config = $this->readPhpConfig($configPath);
        $materialType = Str::snake($this->materialName);
        $className = "App\\\\Services\\\\Material\\\\Calculations\\\\{$this->materialName}CalculationStrategy";

        if (isset($config[$materialType])) {
            $this->line('⏭️  Material calculation strategy already exists in config/material_calculation_strategies.php');
            return;
        }

        $config[$materialType] = $className;
        $this->writePhpConfig($configPath, $config);
        $this->line("✅ Updated: config/material_calculation_strategies.php (added {$materialType})");
    }

    protected function readPhpConfig(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $config = require $path;
        return is_array($config) ? $config : [];
    }

    protected function writePhpConfig(string $path, array $config): void
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        $this->writeGeneratedFile($path, $content);
    }

    protected function updateRoutes()
    {
        $routePath = base_path('routes/web.php');
        $legacyRoute = "Route::resource('{$this->materialNamePlural}', {$this->materialName}Controller::class);";
        $route = "Route::resource('{$this->materialNamePlural}', \\App\\Http\\Controllers\\{$this->materialName}Controller::class);";

        $content = File::get($routePath);

        if (!Str::contains($content, $route) && !Str::contains($content, $legacyRoute)) {
            $content .= "\n{$route}\n";
            $this->writeGeneratedFile($routePath, $content);
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

        $pattern = '/(\$materials\s*=\s*\[)(.*?)(\n\s*\];)/s';
        if (!preg_match($pattern, $content)) {
            $this->warn('⚠️  Could not find $materials array in MaterialSettingSeeder');
            return;
        }

        $updated = preg_replace_callback(
            $pattern,
            function ($matches) use ($newMaterial) {
                $header = $matches[1];
                $body = rtrim($matches[2]);
                $footer = $matches[3];
                return "{$header}{$body}\n{$newMaterial}{$footer}";
            },
            $content,
            1,
        );

        if ($updated === null || $updated === $content) {
            $this->warn('⚠️  Could not update MaterialSettingSeeder');
            return;
        }

        $this->writeGeneratedFile($seederPath, $updated);
        $this->line("✅ Updated: MaterialSettingSeeder.php (added {$materialType})");
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

            $this->writeGeneratedFile($seederPath, $content);
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
            'package_unit' => 'Satuan Kemasan',
            'package_weight_gross' => 'Berat Kotor',
            'package_weight_net' => 'Berat Bersih',
            'package_volume' => 'Volume Kemasan',
            'package_price' => 'Harga / Kemasan',
            'pieces_per_package' => 'Volume',
            'volume' => 'Volume',
            'volume_unit' => 'Satuan Volume',
            'dimension_length' => 'Panjang',
            'dimension_width' => 'Lebar',
            'dimension_height' => 'Tinggi',
            'dimension_thickness' => 'Tebal',
            'price_per_package' => 'Harga / Kemasan',
            'price_per_piece' => 'Harga / Piece',
            'purchase_price' => 'Harga Beli',
            'price_unit' => 'Satuan Harga',
            'comparison_price_per_kg' => 'Harga Komparasi / Kg',
            'comparison_price_per_m3' => 'Harga Komparasi / M3',
            'color_code' => 'Kode Warna',
            'color_name' => 'Nama Warna',
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
        $emptyColspan = count($columns) + 2;

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
                $tableCells .= "                    <td style=\"text-align: right;\">@format(\$item->{$name})</td>\n";
            } elseif ($type === 'integer') {
                // Numeric formatting for integers
                $tableCells .= "                    <td style=\"text-align: right;\">@format(\$item->{$name})</td>\n";
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
                                            <td colspan="{$emptyColspan}" class="text-center">Tidak ada data</td>
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
                                        <a href="{{ route('{$this->materialNamePlural}.index') }}" class="btn btn-secondary-glossy ">Batal</a>
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
                                        <a href="{{ route('{$this->materialNamePlural}.index') }}" class="btn btn-secondary-glossy ">Batal</a>
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

    protected function getShowViewStub()
    {
        $rows = '';
        foreach ($this->fields as $name => $config) {
            if ($name === 'photo') {
                continue;
            }

            $label = $this->getFieldLabel($name);
            $type = $config['type'];
            if (Str::startsWith($type, 'decimal') || $type === 'integer') {
                $value = "@format(\${$this->materialNameSingular()}->{$name})";
            } elseif ($type === 'boolean') {
                $value = "{{ \${$this->materialNameSingular()}->{$name} ? 'Ya' : 'Tidak' }}";
            } else {
                $value = "{{ \${$this->materialNameSingular()}->{$name} ?? '-' }}";
            }

            $rows .= "                                    <tr>\n";
            $rows .= "                                        <th style=\"width: 240px;\">{$label}</th>\n";
            $rows .= "                                        <td>{$value}</td>\n";
            $rows .= "                                    </tr>\n";
        }

        $photoSection = isset($this->fields['photo'])
            ? <<<HTML
                            <div class="col-md-4 mb-3">
                                <label class="form-label d-block">Foto</label>
                                <img src="{{ \${$this->materialNameSingular()}->photo ? asset('storage/' . \${$this->materialNameSingular()}->photo) : asset('images/no-image.png') }}"
                                     alt="Foto {$this->materialLabel}" class="img-thumbnail" style="max-width: 100%; max-height: 360px;">
                            </div>
            HTML
            : '';

        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Detail {$this->materialLabel}</h5>
                            <div>
                                <a href="{{ route('{$this->materialNamePlural}.edit', \${$this->materialNameSingular()}->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <a href="{{ route('{$this->materialNamePlural}.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
        {$photoSection}
                                <div class="col-md-8">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
        {$rows}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        $isRequiredOnCreateOnly = in_array($type, ['file'], true) && !$config['nullable'];
        if ($isEdit && $isRequiredOnCreateOnly) {
            $required = '';
        }
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
        } elseif (Str::startsWith($type, 'decimal')) {
            // Decimal input
            $html .= "                                    <input type=\"number\" step=\"0.01\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
        } elseif ($type === 'boolean') {
            // Boolean select
            $html .= "                                    <select class=\"form-select\" name=\"{$name}\" {$required}>\n";
            $html .= "                                        <option value=\"\">Pilih...</option>\n";
            $html .= "                                        <option value=\"1\" " . ($isEdit ? "{{ \${$this->materialNameSingular()}->{$name} ? 'selected' : '' }}" : "{{ old('{$name}') == '1' ? 'selected' : '' }}") . ">Ya</option>\n";
            $html .= "                                        <option value=\"0\" " . ($isEdit ? "{{ \${$this->materialNameSingular()}->{$name} == 0 ? 'selected' : '' }}" : "{{ old('{$name}') == '0' ? 'selected' : '' }}") . ">Tidak</option>\n";
            $html .= "                                    </select>\n";
        } elseif ($type === 'date') {
            // Date input
            $html .= "                                    <input type=\"date\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
        } elseif ($type === 'datetime') {
            // Datetime input
            $html .= "                                    <input type=\"datetime-local\" class=\"form-control\" name=\"{$name}\" value=\"{$value}\" {$required}>\n";
        } elseif ($type === 'file') {
            // File input
            $html .= "                                    <input type=\"file\" class=\"form-control\" name=\"{$name}\" {$required}>\n";
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

        $name = $this->argument('name');
        if (!$name && $this->input->isInteractive()) {
            $name = $this->ask('Material name to delete (e.g., Tile)');
        }

        if (!$name) {
            $this->error('Material name is required!');
            return 1;
        }

        $this->materialName = ucfirst(Str::camel($name));
        $this->materialNamePlural = Str::plural(Str::snake($this->materialName));

        if ($this->input->isInteractive() && !$this->confirm("Are you sure you want to delete ALL files for '{$this->materialName}'?", false)) {
            $this->warn('Operation cancelled.');
            return 1;
        }

        if (!$this->rollbackFromManifest()) {
            $this->rollback();
        }

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
            app_path("Services/Material/Calculations/{$this->materialName}CalculationStrategy.php"),
            app_path("Http/Requests/Store{$this->materialName}Request.php"),
            app_path("Http/Requests/Update{$this->materialName}Request.php"),
            app_path("Http/Resources/{$this->materialName}Resource.php"),
            public_path("js/{$this->materialNamePlural}-form.js"),
            database_path("seeders/{$seederClass}.php"),
            base_path("tests/Feature/Materials/{$this->materialName}CrudScaffoldTest.php"),
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
        $this->removeFromRoutes();
        $this->removeFromMaterialProfileRegistry();
        $this->removeFromMaterialCalculationStrategyRegistry();
    }

    protected function rollbackFromManifest(): bool
    {
        $manifestPath = $this->getManifestPathForMaterial($this->getMaterialType());
        if (!File::exists($manifestPath)) {
            return false;
        }

        $decoded = json_decode((string) File::get($manifestPath), true);
        if (!is_array($decoded) || !is_array($decoded['files'] ?? null)) {
            $this->warn('Manifest exists but invalid, fallback to legacy rollback.');
            return false;
        }

        $backupFiles = [];
        foreach (array_reverse($decoded['files']) as $file) {
            $path = $file['path'] ?? null;
            $action = $file['action'] ?? null;
            $backupPath = $file['backup_path'] ?? null;

            if (!is_string($path) || $path === '') {
                continue;
            }

            if ($action === 'overwrite' && is_string($backupPath) && File::exists($backupPath)) {
                File::put($path, File::get($backupPath));
                $this->line("↩️  Restored: {$path}");
                $backupFiles[] = $backupPath;
                continue;
            }

            if (File::exists($path)) {
                File::delete($path);
                $this->line("🗑️  Deleted: {$path}");
            }
        }

        // Best-effort cleanup for empty generated view directory
        $viewsPath = resource_path("views/{$this->materialNamePlural}");
        if (File::isDirectory($viewsPath) && empty(File::files($viewsPath))) {
            File::deleteDirectory($viewsPath);
        }

        foreach ($backupFiles as $backupFile) {
            if (File::exists($backupFile)) {
                File::delete($backupFile);
            }
        }

        File::delete($manifestPath);
        $this->line("🗑️  Deleted: {$manifestPath}");

        return true;
    }

    protected function removeFromMaterialSettingSeeder()
    {
        $seederPath = database_path('seeders/MaterialSettingSeeder.php');
        if (!File::exists($seederPath)) {
            return;
        }

        $content = File::get($seederPath);
        $materialType = Str::snake($this->materialName);

        $pattern = "/\s*\[\s*'material_type'\s*=>\s*'{$materialType}'.*?\],\s*/s";
        $content = preg_replace($pattern, '', $content, 1);

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

    protected function removeFromMaterialProfileRegistry()
    {
        $configPath = config_path('material_profiles.php');
        $config = $this->readPhpConfig($configPath);
        $materialType = Str::snake($this->materialName);
        $profiles = is_array($config['profiles'] ?? null) ? $config['profiles'] : [];

        if (!isset($profiles[$materialType])) {
            return;
        }

        unset($profiles[$materialType]);
        $config['profiles'] = $profiles;
        $this->writePhpConfig($configPath, $config);
        $this->line('🗑️  Removed from config/material_profiles.php');
    }

    protected function removeFromMaterialCalculationStrategyRegistry()
    {
        $configPath = config_path('material_calculation_strategies.php');
        $config = $this->readPhpConfig($configPath);
        $materialType = Str::snake($this->materialName);

        if (!isset($config[$materialType])) {
            return;
        }

        unset($config[$materialType]);
        $this->writePhpConfig($configPath, $config);
        $this->line('🗑️  Removed from config/material_calculation_strategies.php');
    }

    protected function removeFromRoutes()
    {
        $routePath = base_path('routes/web.php');
        if (!File::exists($routePath)) {
            return;
        }

        $content = File::get($routePath);
        $patterns = [
            "/^\s*Route::resource\('{$this->materialNamePlural}',\s*{$this->materialName}Controller::class\);\r?\n?/m",
            "/^\s*Route::resource\('{$this->materialNamePlural}',\s*\\\\App\\\\Http\\\\Controllers\\\\{$this->materialName}Controller::class\);\r?\n?/m",
        ];

        $updated = preg_replace($patterns, '', $content);
        if ($updated !== null && $updated !== $content) {
            File::put($routePath, $updated);
            $this->line('🗑️  Removed route from routes/web.php');
        }
    }
}
