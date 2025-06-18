<?php

namespace MamunHoque\CrudBuilder\Tests\Feature;

use MamunHoque\CrudBuilder\CrudBuilder;
use MamunHoque\CrudBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class CrudBuilderTest extends TestCase
{
    protected CrudBuilder $crudBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crudBuilder = new CrudBuilder();
    }

    /** @test */
    public function it_can_build_complete_crud()
    {
        $options = [
            'model' => true,
            'controller' => true,
            'service' => true,
            'requests' => true,
            'resource' => true,
            'factory' => true,
            'tests' => true,
            'routes' => false, // Skip routes to avoid file modification
            'force' => true,
        ];

        $results = $this->crudBuilder->build('TestModel', $options);

        // Check all components were generated
        $this->assertArrayHasKey('model', $results);
        $this->assertArrayHasKey('controller', $results);
        $this->assertArrayHasKey('service', $results);
        $this->assertArrayHasKey('requests', $results);
        $this->assertArrayHasKey('resource', $results);
        $this->assertArrayHasKey('factory', $results);
        $this->assertArrayHasKey('tests', $results);

        // Check files exist
        $this->assertFileExists($results['model']);
        $this->assertFileExists($results['controller']);
        $this->assertFileExists($results['service']);
        $this->assertFileExists($results['resource']);
        $this->assertFileExists($results['factory']);

        // Check request files
        $this->assertIsArray($results['requests']);
        foreach ($results['requests'] as $requestFile) {
            $this->assertFileExists($requestFile);
        }

        // Check test files
        $this->assertIsArray($results['tests']);
        foreach ($results['tests'] as $testFile) {
            $this->assertFileExists($testFile);
        }

        // Clean up
        $this->cleanupGeneratedFiles($results);
    }

    /** @test */
    public function it_can_build_selective_components()
    {
        $options = [
            'model' => true,
            'controller' => false,
            'service' => true,
            'requests' => false,
            'resource' => false,
            'factory' => false,
            'tests' => false,
            'routes' => false,
            'force' => true,
        ];

        $results = $this->crudBuilder->build('TestModel', $options);

        // Check only selected components were generated
        $this->assertArrayHasKey('model', $results);
        $this->assertArrayHasKey('service', $results);
        $this->assertArrayNotHasKey('controller', $results);
        $this->assertArrayNotHasKey('requests', $results);
        $this->assertArrayNotHasKey('resource', $results);

        // Check files exist
        $this->assertFileExists($results['model']);
        $this->assertFileExists($results['service']);

        // Clean up
        $this->cleanupGeneratedFiles($results);
    }

    /** @test */
    public function it_throws_exception_for_non_existent_migration()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Migration file for table 'non_existent_models' not found");

        $this->crudBuilder->build('NonExistentModel');
    }

    /** @test */
    public function it_can_get_migration_parser()
    {
        $parser = $this->crudBuilder->getMigrationParser();
        
        $this->assertInstanceOf(\MamunHoque\CrudBuilder\Parsers\MigrationParser::class, $parser);
    }

    /** @test */
    public function it_can_get_generators()
    {
        $generators = $this->crudBuilder->getGenerators();
        
        $this->assertIsArray($generators);
        $this->assertArrayHasKey('model', $generators);
        $this->assertArrayHasKey('controller', $generators);
        $this->assertArrayHasKey('service', $generators);
        $this->assertArrayHasKey('request', $generators);
        $this->assertArrayHasKey('resource', $generators);
        $this->assertArrayHasKey('test', $generators);
        $this->assertArrayHasKey('route', $generators);
        $this->assertArrayHasKey('factory', $generators);
    }

    /** @test */
    public function generated_files_have_correct_content()
    {
        $options = [
            'model' => true,
            'controller' => true,
            'service' => true,
            'force' => true,
        ];

        $results = $this->crudBuilder->build('TestModel', $options);

        // Check model content
        $modelContent = File::get($results['model']);
        $this->assertStringContainsString('namespace App\Models', $modelContent);
        $this->assertStringContainsString('class TestModel extends Model', $modelContent);
        $this->assertStringContainsString('use SoftDeletes', $modelContent);
        $this->assertStringContainsString("'name'", $modelContent);
        $this->assertStringContainsString("'email'", $modelContent);

        // Check controller content
        $controllerContent = File::get($results['controller']);
        $this->assertStringContainsString('namespace App\Http\Controllers', $controllerContent);
        $this->assertStringContainsString('class TestModelController extends Controller', $controllerContent);
        $this->assertStringContainsString('TestModelService', $controllerContent);

        // Check service content
        $serviceContent = File::get($results['service']);
        $this->assertStringContainsString('namespace App\Services', $serviceContent);
        $this->assertStringContainsString('class TestModelService', $serviceContent);
        $this->assertStringContainsString('use HelperTrait', $serviceContent);

        // Clean up
        $this->cleanupGeneratedFiles($results);
    }

    protected function cleanupGeneratedFiles(array $results): void
    {
        foreach ($results as $component => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (File::exists($file)) {
                        File::delete($file);
                    }
                }
            } elseif (is_string($files) && File::exists($files)) {
                File::delete($files);
            }
        }
    }
}
