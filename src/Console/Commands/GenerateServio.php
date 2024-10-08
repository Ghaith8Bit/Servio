<?php

namespace Mrclutch\Servio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateServio extends Command
{
    protected $filesystem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:servio {serviceName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new service with the given name';

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceName = $this->argument('serviceName');

        $config = $this->loadServiceConfig($serviceName);

        if (!$config) {
            $this->error("Configuration for service $serviceName not found.");
            return;
        }

        // Define file paths
        $files = [
            "Migration.stub" => "database/migrations/" . date('Y_m_d_His') . "_create_{$config['table']}_table.php",
            "Model.stub" => "app/Services/$serviceName/Models/{$serviceName}.php",
            "{Service}Controller.stub" => "app/Services/$serviceName/Controllers/{$serviceName}Controller.php",
            "{Service}Collection.stub" => "app/Services/$serviceName/Resources/{$serviceName}Collection.php",
            "{Service}DTO.stub" => "app/Services/$serviceName/DTOs/{$serviceName}DTO.php",
            "{Service}Facade.stub" => "app/Services/$serviceName/Facades/{$serviceName}Facade.php",
            "{Service}Repository.stub" => "app/Services/$serviceName/Repositories/{$serviceName}Repository.php",
            "{Service}RepositoryContract.stub" => "app/Services/$serviceName/Contracts/{$serviceName}RepositoryContract.php",
            "{Service}Resource.stub" => "app/Services/$serviceName/Resources/{$serviceName}Resource.php",
            "{Service}Service.stub" => "app/Services/$serviceName/Services/{$serviceName}Service.php",
            "{Service}ServiceContract.stub" => "app/Services/$serviceName/Contracts/{$serviceName}ServiceContract.php",
            "{Service}ServiceProvider.stub" => "app/Services/$serviceName/Providers/{$serviceName}ServiceProvider.php",
        ];

        foreach ($files as $stub => $destination) {
            $this->generateFile($serviceName, $stub, $destination, $config);
        }

        $this->addRoutes($serviceName);

        $this->info("Service $serviceName generated successfully!");
    }

    /**
     * Load the service configuration from the PHP file.
     */
    protected function loadServiceConfig($serviceName)
    {
        $configPath = base_path('config/servio.php');
        if (!$this->filesystem->exists($configPath)) {
            $this->error("Configuration file not found at $configPath");
            return null;
        }
        $services = require $configPath;
        return $services[$serviceName] ?? null;
    }

    /**
     * Generate the file based on the stub and configuration.
     */
    protected function generateFile($serviceName, $stub, $destination, $config)
    {
        // Update stub path
        $stubPath = __DIR__ . "/../../Resources/$stub";

        if (!$this->filesystem->exists($stubPath)) {
            $this->error("Stub file not found: $stubPath");
            return;
        }

        $content = $this->filesystem->get($stubPath);

        // Replace placeholders in the stub
        $content = str_replace('{{serviceName}}', $serviceName, $content);

        if (strpos($stub, 'DTO') !== false) {
            $properties = $this->generateDtoProperties($config['dto']['fields']);
            $rules = $this->generateValidationRules($config['dto']['fields']);
            $content = str_replace(['{{properties}}', '{{rules}}'], [$properties, $rules], $content);
        }

        if (strpos($stub, 'Resource') !== false) {
            $transformation = $this->generateResourceTransformation($config['resource']['fields']);
            $content = str_replace('{{transformation}}', $transformation, $content);
        }

        if (strpos($stub, 'Collection') !== false) {
            $tableName = $config['table'];
            $content = str_replace('{{tableName}}', $tableName, $content);
        }

        if (strpos($stub, 'Migration') !== false) {
            $tableName = $config['table'];
            $content = str_replace('{{tableName}}', $tableName, $content);
        }

        if (strpos($stub, 'Model') !== false) {
            $imageMutator = '';
            $imageTraitName = '';
            $imageTraitNameSpace = '';
            if ($config['image'] ?? false) {
                $imageMutator =  $this->getModelImageMutator();
                $imageTraitName = ', HandlesUploadedImage';
                $imageTraitNameSpace = 'use Mrclutch\Servio\Supports\Traits\HandlesUploadedImage;';
            }
            $content = str_replace(['{{ImageMutator}}', '{{ImageTraitName}}', '{{ImageTraitNameSpace}}'], [$imageMutator, $imageTraitName, $imageTraitNameSpace], $content);
        }

        if (strpos($stub, 'Repository') !== false) {
            $deleteImage = '';
            $deleteMethod = '';
            if ($config['image'] ?? false) {
                $deleteImage = $this->deleteRepositoryImage();
                $deleteMethod = $this->deleteRepositoryImageMethod();
            }
            $content = str_replace(['{{DeleteImage}}', '{{DeleteMethod}}'], [$deleteImage, $deleteMethod], $content);
        }

        $destination = str_replace('{Service}', $serviceName, $destination);
        $this->filesystem->ensureDirectoryExists(dirname($destination));
        $this->filesystem->put($destination, $content);
    }

    /**
     * Generate public properties for the DTO.
     */
    protected function generateDtoProperties($fields)
    {
        $properties = [];

        foreach ($fields as $name => $validation) {
            $properties[] = "public \$$name;";
        }

        return implode("\n    ", $properties);
    }

    /**
     * Generate validation rules for the DTO.
     */
    protected function generateValidationRules($fields)
    {
        $rules = [];

        foreach ($fields as $name => $validation) {
            $rules[] = "'$name' => '$validation'";
        }

        return implode(",\n            ", $rules);
    }

    /**
     * Generate resource transformation.
     */
    protected function generateResourceTransformation($fields)
    {
        $transformation = [];

        foreach ($fields as $name) {
            $transformation[] = "'$name' => \$this->$name";
        }

        return implode(",\n            ", $transformation);
    }

    /**
     * Generate migration columns.
     */
    protected function generateMigrationColumns($columns)
    {
        $migrationColumns = [];

        foreach ($columns as $name => $type) {
            $migrationColumns[] = "\$table->$type('$name');";
        }

        return implode("\n            ", $migrationColumns);
    }

    protected function addRoutes($serviceName)
    {
        $routesPath = base_path('routes/api.php');

        // Convert the service name to lowercase and pluralize it
        $pluralServiceName = Str::lower(Str::plural($serviceName));

        // Define the routes with the correct prefix
        $routes = <<<EOT

    // Routes for {$serviceName}
    Route::prefix('{$pluralServiceName}')->group(function () {
    Route::get('/', [App\Services\\{$serviceName}\Controllers\\{$serviceName}Controller::class, 'index']);
    Route::post('/', [App\Services\\{$serviceName}\Controllers\\{$serviceName}Controller::class, 'store']);
    Route::get('/{id}', [App\Services\\{$serviceName}\Controllers\\{$serviceName}Controller::class, 'show']);
    Route::patch('/{id}', [App\Services\\{$serviceName}\Controllers\\{$serviceName}Controller::class, 'update']);
    Route::delete('/{id}', [App\Services\\{$serviceName}\Controllers\\{$serviceName}Controller::class, 'destroy']);
});

EOT;

        // Append the routes to the api.php file
        $this->filesystem->append($routesPath, $routes);

        $this->info("Routes for $serviceName added to api.php");
    }

    protected function getModelImageMutator()
    {
        return <<<EOT

    /**
     * Set the image attribute, store the uploaded file.
     *
     * @param \Illuminate\Http\UploadedFile|null \$file
     * @return void
     */
    public function setImageAttribute(?UploadedFile \$file): void
    {
        if (\$file) {
            // Use the saveUploadedFile method to handle the file upload
            \$this->attributes['image'] = \$this->saveUploadedFile(\$file);
        } else {
            \$this->attributes['image'] = null;
        }
    }
EOT;
    }

    protected function deleteRepositoryImage()
    {
        return <<<EOT

    // Check if there is an existing image and delete it
    if (isset(\$data['image'])) {
        \$this->deleteExistingImage(\$record);
    }
EOT;
    }
    protected function deleteRepositoryImageMethod()
    {
        return <<<EOT

    /**
     * Delete the existing image from storage.
     *
     * @param \Illuminate\Database\Eloquent\Model \$record
     * @return void
     */
    protected function deleteExistingImage(\$record): void
    {
        // Get the raw original image path from the database
        \$imagePath = \$record->getRawOriginal('image');

        if (\$imagePath) {  // Checks if there is an image path in the database
            \Illuminate\Support\Facades\Storage::disk('public')->delete(\$imagePath);
        }
    }
EOT;
    }
}
