<?php

namespace MamunHoque\CrudBuilder\Tests\Feature;

use MamunHoque\CrudBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class CommandsTest extends TestCase
{
    /** @test */
    public function make_advanced_crud_command_works()
    {
        $this->artisan('make:advanced-crud', [
            'name' => 'TestModel',
            '--force' => true,
            '--no-interaction' => true,
        ])
        ->expectsConfirmation('Do you want to proceed with the generation?', 'yes')
        ->assertExitCode(0);
    }

    /** @test */
    public function make_crud_model_command_works()
    {
        $this->artisan('make:crud-model', [
            'name' => 'TestModel',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('✅ Model generated successfully:');
    }

    /** @test */
    public function make_crud_service_command_works()
    {
        $this->artisan('make:crud-service', [
            'name' => 'TestModel',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('✅ Service generated successfully:');
    }

    /** @test */
    public function make_crud_controller_command_works()
    {
        $this->artisan('make:crud-controller', [
            'name' => 'TestModel',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('✅ Controller generated successfully:');
    }

    /** @test */
    public function make_crud_request_command_works()
    {
        $this->artisan('make:crud-request', [
            'name' => 'TestModel',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('✅ Request classes generated successfully:');
    }

    /** @test */
    public function make_crud_resource_command_works()
    {
        $this->artisan('make:crud-resource', [
            'name' => 'TestModel',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('✅ Resource generated successfully:');
    }

    /** @test */
    public function make_crud_test_command_works()
    {
        $this->artisan('make:crud-test', [
            'name' => 'TestModel',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('✅ Test files generated successfully:');
    }

    /** @test */
    public function commands_handle_non_existent_migration()
    {
        $this->artisan('make:crud-model', [
            'name' => 'NonExistentModel',
            '--force' => true,
        ])
        ->assertExitCode(1)
        ->expectsOutput('❌ Error: Migration file for table \'non_existent_models\' not found.');
    }

    /** @test */
    public function advanced_crud_command_with_options()
    {
        $this->artisan('make:advanced-crud', [
            'name' => 'TestModel',
            '--model' => true,
            '--service' => true,
            '--controller' => false,
            '--middleware' => 'public',
            '--force' => true,
            '--no-interaction' => true,
        ])
        ->expectsConfirmation('Do you want to proceed with the generation?', 'yes')
        ->assertExitCode(0);
    }

    /** @test */
    public function advanced_crud_command_can_be_cancelled()
    {
        $this->artisan('make:advanced-crud', [
            'name' => 'TestModel',
            '--no-interaction' => true,
        ])
        ->expectsConfirmation('Do you want to proceed with the generation?', 'no')
        ->expectsOutput('❌ CRUD generation cancelled.')
        ->assertExitCode(1);
    }

    protected function tearDown(): void
    {
        // Clean up any generated files
        $paths = [
            app_path('Models/TestModel.php'),
            app_path('Http/Controllers/TestModelController.php'),
            app_path('Services/TestModelService.php'),
            app_path('Http/Requests/StoreTestModelRequest.php'),
            app_path('Http/Requests/UpdateTestModelRequest.php'),
            app_path('Http/Resources/TestModelResource.php'),
            database_path('factories/TestModelFactory.php'),
            base_path('tests/Feature/TestModelControllerTest.php'),
            base_path('tests/Unit/TestModelTest.php'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }
}
