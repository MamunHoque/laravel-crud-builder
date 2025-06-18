<?php

namespace MamunHoque\CrudBuilder\Tests;

use MamunHoque\CrudBuilder\CrudBuilderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CrudBuilderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up package configuration
        config()->set('crud-builder', [
            'defaults' => [
                'generate_model' => true,
                'generate_controller' => true,
                'generate_service' => true,
                'generate_requests' => true,
                'generate_resource' => true,
                'generate_tests' => true,
                'generate_routes' => true,
                'generate_factory' => true,
            ],
            'paths' => [
                'models' => 'app/Models',
                'controllers' => 'app/Http/Controllers',
                'services' => 'app/Services',
                'requests' => 'app/Http/Requests',
                'resources' => 'app/Http/Resources',
                'tests' => 'tests/Feature',
                'unit_tests' => 'tests/Unit',
                'factories' => 'database/factories',
            ],
            'namespaces' => [
                'models' => 'App\\Models',
                'controllers' => 'App\\Http\\Controllers',
                'services' => 'App\\Services',
                'requests' => 'App\\Http\\Requests',
                'resources' => 'App\\Http\\Resources',
                'tests' => 'Tests\\Feature',
                'unit_tests' => 'Tests\\Unit',
            ],
        ]);
    }

    protected function setUpDatabase(): void
    {
        // Create test migration files
        $this->createTestMigrations();
    }

    protected function createTestMigrations(): void
    {
        $migrationsPath = database_path('migrations');

        if (! is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        // Create a test migration file
        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 8, 2)->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_models');
    }
};
PHP;

        file_put_contents(
            $migrationsPath.'/2024_01_01_000000_create_test_models_table.php',
            $migrationContent
        );

        // Create categories migration for foreign key testing
        $categoriesMigrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
PHP;

        file_put_contents(
            $migrationsPath.'/2024_01_01_000001_create_categories_table.php',
            $categoriesMigrationContent
        );
    }

    protected function tearDown(): void
    {
        // Clean up test migration files
        $migrationsPath = database_path('migrations');
        if (is_dir($migrationsPath)) {
            $files = glob($migrationsPath.'/*.php');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
