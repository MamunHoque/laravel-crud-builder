<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeAdvancedCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:advanced-crud 
                            {name : The name of the model}
                            {--controller : Generate controller}
                            {--service : Generate service}
                            {--model : Generate model}
                            {--requests : Generate form request classes}
                            {--resource : Generate API resource}
                            {--tests : Generate test files}
                            {--routes : Generate routes}
                            {--factory : Generate factory}
                            {--seeder : Generate seeder}
                            {--middleware=admin : Middleware group (admin, public, auth)}
                            {--prefix= : Route prefix}
                            {--force : Overwrite existing files}
                            {--all : Generate all components}';

    /**
     * The console command description.
     */
    protected $description = 'Generate advanced CRUD operations with intelligent migration parsing';

    protected CrudBuilder $crudBuilder;

    public function __construct(CrudBuilder $crudBuilder)
    {
        parent::__construct();
        $this->crudBuilder = $crudBuilder;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('name');

        $this->info("🚀 Starting Advanced CRUD generation for: {$modelName}");
        $this->newLine();

        try {
            $options = $this->buildOptions();

            $this->info('📋 Configuration:');
            $this->displayOptions($options);
            $this->newLine();

            if (! $this->confirm('Do you want to proceed with the generation?', true)) {
                $this->warn('❌ CRUD generation cancelled.');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info('⚡ Generating CRUD components...');

            $results = $this->crudBuilder->build($modelName, $options);

            $this->displayResults($results);

            // Clear caches
            $this->info('🧹 Clearing caches...');
            Artisan::call('route:clear');
            Artisan::call('config:clear');

            $this->newLine();
            $this->info("✅ Advanced CRUD generation completed successfully for: {$modelName}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Build options array from command arguments and config
     */
    protected function buildOptions(): array
    {
        $config = config('crud-builder.defaults', []);

        return [
            'model' => $this->option('model') || $this->option('all') || $config['generate_model'] ?? true,
            'controller' => $this->option('controller') || $this->option('all') || $config['generate_controller'] ?? true,
            'service' => $this->option('service') || $this->option('all') || $config['generate_service'] ?? true,
            'requests' => $this->option('requests') || $this->option('all') || $config['generate_requests'] ?? true,
            'resource' => $this->option('resource') || $this->option('all') || $config['generate_resource'] ?? true,
            'tests' => $this->option('tests') || $this->option('all') || $config['generate_tests'] ?? true,
            'routes' => $this->option('routes') || $this->option('all') || $config['generate_routes'] ?? true,
            'factory' => $this->option('factory') || $this->option('all') || $config['generate_factory'] ?? true,
            'seeder' => $this->option('seeder') || $this->option('all') || $config['generate_seeder'] ?? false,
            'middleware' => $this->option('middleware') ?? config('crud-builder.routes.default_middleware', 'admin'),
            'prefix' => $this->option('prefix'),
            'force' => $this->option('force'),
        ];
    }

    /**
     * Display the configuration options
     */
    protected function displayOptions(array $options): void
    {
        $components = [];
        foreach (['model', 'controller', 'service', 'requests', 'resource', 'tests', 'routes', 'factory', 'seeder'] as $component) {
            if ($options[$component]) {
                $components[] = ucfirst($component);
            }
        }

        $this->line('   Components: '.implode(', ', $components));
        $this->line("   Middleware: {$options['middleware']}");
        if ($options['prefix']) {
            $this->line("   Prefix: {$options['prefix']}");
        }
        $this->line('   Force: '.($options['force'] ? 'Yes' : 'No'));
    }

    /**
     * Display generation results
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📁 Generated files:');

        foreach ($results as $component => $result) {
            if (is_array($result)) {
                foreach ($result as $file) {
                    $this->line("   ✓ {$file}");
                }
            } else {
                $this->line("   ✓ {$result}");
            }
        }
    }
}
