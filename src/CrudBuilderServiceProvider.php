<?php

namespace MamunHoque\CrudBuilder;

use Illuminate\Support\ServiceProvider;
use MamunHoque\CrudBuilder\Console\Commands\MakeAdvancedCrudCommand;
use MamunHoque\CrudBuilder\Console\Commands\MakeControllerCommand;
use MamunHoque\CrudBuilder\Console\Commands\MakeModelCommand;
use MamunHoque\CrudBuilder\Console\Commands\MakeRequestCommand;
use MamunHoque\CrudBuilder\Console\Commands\MakeResourceCommand;
use MamunHoque\CrudBuilder\Console\Commands\MakeServiceCommand;
use MamunHoque\CrudBuilder\Console\Commands\MakeTestCommand;
use MamunHoque\CrudBuilder\Console\Commands\PublishHelperTraitCommand;

class CrudBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/crud-builder.php',
            'crud-builder'
        );

        $this->app->singleton('crud-builder', function ($app) {
            return new CrudBuilder();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/crud-builder.php' => config_path('crud-builder.php'),
        ], 'crud-builder-config');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/../stubs' => resource_path('stubs/crud-builder'),
        ], 'crud-builder-stubs');

        // Publish HelperTrait
        $this->publishes([
            __DIR__ . '/Traits/HelperTrait.php' => app_path('Traits/HelperTrait.php'),
        ], 'crud-builder-traits');

        // Auto-publish HelperTrait if it doesn't exist
        $this->autoPublishHelperTrait();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAdvancedCrudCommand::class,
                MakeControllerCommand::class,
                MakeModelCommand::class,
                MakeRequestCommand::class,
                MakeResourceCommand::class,
                MakeServiceCommand::class,
                MakeTestCommand::class,
                PublishHelperTraitCommand::class,
            ]);
        }
    }

    /**
     * Auto-publish HelperTrait if it doesn't exist
     */
    protected function autoPublishHelperTrait(): void
    {
        $targetPath = app_path('Traits/HelperTrait.php');

        // Check if HelperTrait already exists
        if (!file_exists($targetPath)) {
            // Create the Traits directory if it doesn't exist
            $traitsDir = app_path('Traits');
            if (!is_dir($traitsDir)) {
                mkdir($traitsDir, 0755, true);
            }

            // Copy the HelperTrait to the application
            $sourcePath = __DIR__ . '/Traits/HelperTrait.php';
            $content = file_get_contents($sourcePath);

            // Update the namespace to match the application
            $content = str_replace(
                'namespace MamunHoque\CrudBuilder\Traits;',
                'namespace App\Traits;',
                $content
            );

            file_put_contents($targetPath, $content);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['crud-builder'];
    }
}
