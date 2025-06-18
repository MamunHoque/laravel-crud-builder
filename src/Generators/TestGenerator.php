<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;

class TestGenerator extends BaseGenerator
{
    /**
     * Generate test files
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (!$this->shouldGenerate('tests', $options)) {
            return [];
        }

        $generatedFiles = [];

        // Generate Feature Test
        if ($this->config['tests']['generate_feature_tests'] ?? true) {
            $featureTestPath = $this->generateFeatureTest($modelName, $migrationData, $options);
            $generatedFiles[] = $featureTestPath;
        }

        // Generate Unit Test
        if ($this->config['tests']['generate_unit_tests'] ?? true) {
            $unitTestPath = $this->generateUnitTest($modelName, $migrationData, $options);
            $generatedFiles[] = $unitTestPath;
        }

        return $generatedFiles;
    }

    /**
     * Generate feature test file
     */
    protected function generateFeatureTest(string $modelName, MigrationData $migrationData, array $options): string
    {
        $stub = $this->getStub('feature-test');
        
        $testClassName = "{$modelName}ControllerTest";
        $controllerName = $migrationData->getControllerName();
        $resourceName = $this->plural($this->kebab($modelName));
        
        $replacements = [
            'namespace' => $this->getNamespace('tests'),
            'className' => $testClassName,
            'modelName' => $modelName,
            'controllerName' => $controllerName,
            'resourceName' => $resourceName,
            'modelVariable' => $this->camel($modelName),
            'factoryData' => $this->generateFactoryData($migrationData),
            'updateData' => $this->generateUpdateData($migrationData),
            'validationTests' => $this->generateValidationTests($migrationData),
            'routePrefix' => $this->getTestRoutePrefix($options),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$testClassName}.php";
        $filePath = $this->getFilePath('tests', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate unit test file
     */
    protected function generateUnitTest(string $modelName, MigrationData $migrationData, array $options): string
    {
        $stub = $this->getStub('unit-test');
        
        $testClassName = "{$modelName}Test";
        
        $replacements = [
            'namespace' => $this->getNamespace('unit_tests'),
            'className' => $testClassName,
            'modelName' => $modelName,
            'modelVariable' => $this->camel($modelName),
            'relationshipTests' => $this->generateRelationshipTests($migrationData),
            'accessorTests' => $this->generateAccessorTests($migrationData),
            'factoryData' => $this->generateFactoryData($migrationData),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$testClassName}.php";
        $filePath = $this->getFilePath('unit_tests', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate factory data for tests
     */
    protected function generateFactoryData(MigrationData $migrationData): string
    {
        $fillableColumns = $migrationData->getFillableColumns();
        $factoryData = [];
        
        foreach ($fillableColumns as $column) {
            $factoryData[$column->name] = $this->generateFactoryValue($column);
        }
        
        return $this->arrayToString($factoryData, 12);
    }

    /**
     * Generate factory value for column
     */
    protected function generateFactoryValue($column): string
    {
        switch ($column->type) {
            case 'string':
            case 'text':
                return 'fake()->sentence()';
            case 'integer':
            case 'bigInteger':
                return 'fake()->numberBetween(1, 100)';
            case 'boolean':
                return 'fake()->boolean()';
            case 'date':
                return 'fake()->date()';
            case 'dateTime':
            case 'timestamp':
                return 'fake()->dateTime()';
            case 'email':
                return 'fake()->email()';
            case 'decimal':
            case 'float':
                return 'fake()->randomFloat(2, 0, 1000)';
            case 'enum':
                if ($column->enumValues) {
                    $values = implode("', '", $column->enumValues);
                    return "fake()->randomElement(['{$values}'])";
                }
                return 'fake()->word()';
            default:
                if ($column->isForeignKey()) {
                    $relatedModel = $this->studly($this->singular($column->getForeignTableName()));
                    return "{$relatedModel}::factory()->create()->id";
                }
                return 'fake()->word()';
        }
    }

    /**
     * Generate update data for tests
     */
    protected function generateUpdateData(MigrationData $migrationData): string
    {
        $fillableColumns = array_slice($migrationData->getFillableColumns(), 0, 3); // Just a few fields
        $updateData = [];
        
        foreach ($fillableColumns as $column) {
            $updateData[$column->name] = $this->generateFactoryValue($column);
        }
        
        return $this->arrayToString($updateData, 12);
    }

    /**
     * Generate validation tests
     */
    protected function generateValidationTests(MigrationData $migrationData): string
    {
        $requiredFields = [];
        
        foreach ($migrationData->getFillableColumns() as $column) {
            $rules = $column->getValidationRules(false);
            if (in_array('required', $rules)) {
                $requiredFields[] = $column->name;
            }
        }
        
        if (empty($requiredFields)) {
            return '';
        }
        
        $tests = [];
        foreach ($requiredFields as $field) {
            $tests[] = "    /** @test */\n" .
                      "    public function it_requires_{$field}_field()\n" .
                      "    {\n" .
                      "        \$response = \$this->postJson(route('{{ routePrefix }}{{ resourceName }}.store'), []);\n" .
                      "        \$response->assertStatus(422)\n" .
                      "                 ->assertJsonValidationErrors('{$field}');\n" .
                      "    }";
        }
        
        return implode("\n\n", $tests);
    }

    /**
     * Generate relationship tests
     */
    protected function generateRelationshipTests(MigrationData $migrationData): string
    {
        $relationships = $migrationData->getRelationships();
        
        if (empty($relationships)) {
            return '';
        }
        
        $tests = [];
        foreach ($relationships as $relationship) {
            $relationshipName = $relationship->relationshipName;
            $relatedModel = $relationship->relatedModel;
            
            $tests[] = "    /** @test */\n" .
                      "    public function it_belongs_to_{$relationshipName}()\n" .
                      "    {\n" .
                      "        \${{ modelVariable }} = {{ modelName }}::factory()->create();\n" .
                      "        \$this->assertInstanceOf({$relatedModel}::class, \${{ modelVariable }}->{$relationshipName});\n" .
                      "    }";
        }
        
        return implode("\n\n", $tests);
    }

    /**
     * Generate accessor tests
     */
    protected function generateAccessorTests(MigrationData $migrationData): string
    {
        $accessorColumns = $migrationData->getAccessorColumns();
        
        if (empty($accessorColumns)) {
            return '';
        }
        
        $tests = [];
        foreach ($accessorColumns as $column) {
            $accessorName = $this->snake($column->name) . '_url';
            
            $tests[] = "    /** @test */\n" .
                      "    public function it_has_{$accessorName}_accessor()\n" .
                      "    {\n" .
                      "        \${{ modelVariable }} = {{ modelName }}::factory()->create([\n" .
                      "            '{$column->name}' => 'test-file.jpg'\n" .
                      "        ]);\n" .
                      "        \n" .
                      "        \$this->assertNotNull(\${{ modelVariable }}->{$accessorName});\n" .
                      "        \$this->assertStringContainsString('test-file.jpg', \${{ modelVariable }}->{$accessorName});\n" .
                      "    }";
        }
        
        return implode("\n\n", $tests);
    }

    /**
     * Get test route prefix
     */
    protected function getTestRoutePrefix(array $options): string
    {
        $middleware = $options['middleware'] ?? 'admin';
        $prefixes = $this->config['routes']['prefix'] ?? [];
        $prefix = $prefixes[$middleware] ?? '';
        
        return $prefix ? $prefix . '.' : '';
    }
}
