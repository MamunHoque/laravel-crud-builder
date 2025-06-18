<?php

namespace MamunHoque\CrudBuilder\Tests\Feature;

use MamunHoque\CrudBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class HelperTraitTest extends TestCase
{
    protected string $targetPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetPath = app_path('Traits/HelperTrait.php');
    }

    /** @test */
    public function helper_trait_is_auto_published_on_boot()
    {
        // Remove the trait if it exists
        if (File::exists($this->targetPath)) {
            File::delete($this->targetPath);
        }

        // Trigger the service provider boot
        $this->app->make(\MamunHoque\CrudBuilder\CrudBuilderServiceProvider::class)->boot();

        // Check if the trait was published
        $this->assertFileExists($this->targetPath);
        
        // Check if the namespace was updated correctly
        $content = File::get($this->targetPath);
        $this->assertStringContainsString('namespace App\Traits;', $content);
        $this->assertStringNotContainsString('namespace MamunHoque\CrudBuilder\Traits;', $content);
    }

    /** @test */
    public function publish_helper_trait_command_works()
    {
        // Remove the trait if it exists
        if (File::exists($this->targetPath)) {
            File::delete($this->targetPath);
        }

        $this->artisan('crud-builder:publish-helper-trait')
            ->assertExitCode(0)
            ->expectsOutput('✅ HelperTrait published successfully to: ' . $this->targetPath);

        $this->assertFileExists($this->targetPath);
        
        // Check content
        $content = File::get($this->targetPath);
        $this->assertStringContainsString('namespace App\Traits;', $content);
        $this->assertStringContainsString('trait HelperTrait', $content);
        $this->assertStringContainsString('applySorting', $content);
        $this->assertStringContainsString('applySearch', $content);
        $this->assertStringContainsString('paginateOrGet', $content);
        $this->assertStringContainsString('successResponse', $content);
        $this->assertStringContainsString('errorResponse', $content);
        $this->assertStringContainsString('s3FileUpload', $content);
    }

    /** @test */
    public function publish_helper_trait_command_asks_for_confirmation_when_file_exists()
    {
        // Ensure the trait exists
        if (!File::exists($this->targetPath)) {
            $this->artisan('crud-builder:publish-helper-trait');
        }

        $this->artisan('crud-builder:publish-helper-trait')
            ->expectsOutput('HelperTrait already exists at: ' . $this->targetPath)
            ->expectsConfirmation('Do you want to overwrite it?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);
    }

    /** @test */
    public function publish_helper_trait_command_with_force_option()
    {
        // Ensure the trait exists
        if (!File::exists($this->targetPath)) {
            $this->artisan('crud-builder:publish-helper-trait');
        }

        $this->artisan('crud-builder:publish-helper-trait', ['--force' => true])
            ->expectsOutput('✅ HelperTrait published successfully to: ' . $this->targetPath)
            ->assertExitCode(0);

        $this->assertFileExists($this->targetPath);
    }

    /** @test */
    public function helper_trait_creates_directory_if_not_exists()
    {
        $traitsDir = app_path('Traits');
        
        // Remove the entire Traits directory
        if (File::isDirectory($traitsDir)) {
            File::deleteDirectory($traitsDir);
        }

        $this->assertDirectoryDoesNotExist($traitsDir);

        $this->artisan('crud-builder:publish-helper-trait')
            ->assertExitCode(0);

        $this->assertDirectoryExists($traitsDir);
        $this->assertFileExists($this->targetPath);
    }

    /** @test */
    public function helper_trait_contains_all_expected_methods()
    {
        $this->artisan('crud-builder:publish-helper-trait', ['--force' => true]);
        
        $content = File::get($this->targetPath);
        
        $expectedMethods = [
            'applySorting',
            'applySearch', 
            'paginateOrGet',
            'applyActive',
            'applyFilters',
            'successResponse',
            'errorResponse',
            's3FileUpload',
            'localFileUpload',
            'uploadMultipleFiles',
            'convertToClassName',
            'modelToSnakeCase',
            'camelToSnakeCase',
            'applyEComFilters',
            'applyEComSorting',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertStringContainsString("function {$method}", $content, "Method {$method} not found in HelperTrait");
        }
    }

    protected function tearDown(): void
    {
        // Clean up the published trait
        if (File::exists($this->targetPath)) {
            File::delete($this->targetPath);
        }

        // Clean up the Traits directory if empty
        $traitsDir = app_path('Traits');
        if (File::isDirectory($traitsDir) && count(File::files($traitsDir)) === 0) {
            File::deleteDirectory($traitsDir);
        }

        parent::tearDown();
    }
}
