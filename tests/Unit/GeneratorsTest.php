<?php

namespace MamunHoque\CrudBuilder\Tests\Unit;

use MamunHoque\CrudBuilder\Generators\ModelGenerator;
use MamunHoque\CrudBuilder\Generators\ControllerGenerator;
use MamunHoque\CrudBuilder\Generators\ServiceGenerator;
use MamunHoque\CrudBuilder\Generators\RequestGenerator;
use MamunHoque\CrudBuilder\Generators\ResourceGenerator;
use MamunHoque\CrudBuilder\Generators\FactoryGenerator;
use MamunHoque\CrudBuilder\Parsers\MigrationParser;
use MamunHoque\CrudBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class GeneratorsTest extends TestCase
{
    protected MigrationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MigrationParser();
    }

    /** @test */
    public function model_generator_creates_model_file()
    {
        $generator = new ModelGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        $options = ['force' => true];
        $result = $generator->generate('TestModel', $migrationData, $options);

        $this->assertIsString($result);
        $this->assertStringContainsString('TestModel.php', $result);
        
        // Check if file was created
        $this->assertFileExists($result);
        
        // Check file content
        $content = File::get($result);
        $this->assertStringContainsString('class TestModel extends Model', $content);
        $this->assertStringContainsString('use SoftDeletes', $content);
        $this->assertStringContainsString('protected $fillable', $content);
        
        // Clean up
        File::delete($result);
    }

    /** @test */
    public function service_generator_creates_service_file()
    {
        $generator = new ServiceGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        $options = ['force' => true];
        $result = $generator->generate('TestModel', $migrationData, $options);

        $this->assertIsString($result);
        $this->assertStringContainsString('TestModelService.php', $result);
        
        // Check if file was created
        $this->assertFileExists($result);
        
        // Check file content
        $content = File::get($result);
        $this->assertStringContainsString('class TestModelService', $content);
        $this->assertStringContainsString('use HelperTrait', $content);
        $this->assertStringContainsString('public function index', $content);
        $this->assertStringContainsString('public function store', $content);
        
        // Clean up
        File::delete($result);
    }

    /** @test */
    public function controller_generator_creates_controller_file()
    {
        $generator = new ControllerGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        $options = ['force' => true];
        $result = $generator->generate('TestModel', $migrationData, $options);

        $this->assertIsString($result);
        $this->assertStringContainsString('TestModelController.php', $result);
        
        // Check if file was created
        $this->assertFileExists($result);
        
        // Check file content
        $content = File::get($result);
        $this->assertStringContainsString('class TestModelController extends Controller', $content);
        $this->assertStringContainsString('public function index', $content);
        $this->assertStringContainsString('public function store', $content);
        $this->assertStringContainsString('public function show', $content);
        $this->assertStringContainsString('public function update', $content);
        $this->assertStringContainsString('public function destroy', $content);
        
        // Clean up
        File::delete($result);
    }

    /** @test */
    public function request_generator_creates_request_files()
    {
        $generator = new RequestGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        $options = ['force' => true];
        $results = $generator->generate('TestModel', $migrationData, $options);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        
        foreach ($results as $result) {
            $this->assertFileExists($result);
            
            $content = File::get($result);
            $this->assertStringContainsString('extends FormRequest', $content);
            $this->assertStringContainsString('public function rules', $content);
            
            // Clean up
            File::delete($result);
        }
    }

    /** @test */
    public function resource_generator_creates_resource_file()
    {
        $generator = new ResourceGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        $options = ['force' => true];
        $result = $generator->generate('TestModel', $migrationData, $options);

        $this->assertIsString($result);
        $this->assertStringContainsString('TestModelResource.php', $result);
        
        // Check if file was created
        $this->assertFileExists($result);
        
        // Check file content
        $content = File::get($result);
        $this->assertStringContainsString('class TestModelResource extends JsonResource', $content);
        $this->assertStringContainsString('public function toArray', $content);
        
        // Clean up
        File::delete($result);
    }

    /** @test */
    public function factory_generator_creates_factory_file()
    {
        $generator = new FactoryGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        $options = ['force' => true];
        $result = $generator->generate('TestModel', $migrationData, $options);

        $this->assertIsString($result);
        $this->assertStringContainsString('TestModelFactory.php', $result);
        
        // Check if file was created
        $this->assertFileExists($result);
        
        // Check file content
        $content = File::get($result);
        $this->assertStringContainsString('class TestModelFactory extends Factory', $content);
        $this->assertStringContainsString('public function definition', $content);
        
        // Clean up
        File::delete($result);
    }

    /** @test */
    public function generators_respect_force_option()
    {
        $generator = new ModelGenerator();
        $migrationData = $this->parser->parse('test_models');
        
        // Create file first
        $options = ['force' => true];
        $result = $generator->generate('TestModel', $migrationData, $options);
        $this->assertFileExists($result);
        
        // Try to create again without force - should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File already exists');
        
        $options = ['force' => false];
        $generator->generate('TestModel', $migrationData, $options);
        
        // Clean up
        File::delete($result);
    }
}
