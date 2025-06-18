<?php

namespace MamunHoque\CrudBuilder;

use Illuminate\Support\Str;
use MamunHoque\CrudBuilder\Generators\ControllerGenerator;
use MamunHoque\CrudBuilder\Generators\FactoryGenerator;
use MamunHoque\CrudBuilder\Generators\ModelGenerator;
use MamunHoque\CrudBuilder\Generators\RequestGenerator;
use MamunHoque\CrudBuilder\Generators\ResourceGenerator;
use MamunHoque\CrudBuilder\Generators\RouteGenerator;
use MamunHoque\CrudBuilder\Generators\ServiceGenerator;
use MamunHoque\CrudBuilder\Generators\TestGenerator;
use MamunHoque\CrudBuilder\Parsers\MigrationParser;

class CrudBuilder
{
    protected MigrationParser $migrationParser;

    protected ModelGenerator $modelGenerator;

    protected ControllerGenerator $controllerGenerator;

    protected ServiceGenerator $serviceGenerator;

    protected RequestGenerator $requestGenerator;

    protected ResourceGenerator $resourceGenerator;

    protected TestGenerator $testGenerator;

    protected RouteGenerator $routeGenerator;

    protected FactoryGenerator $factoryGenerator;

    public function __construct()
    {
        $this->migrationParser = new MigrationParser;
        $this->modelGenerator = new ModelGenerator;
        $this->controllerGenerator = new ControllerGenerator;
        $this->serviceGenerator = new ServiceGenerator;
        $this->requestGenerator = new RequestGenerator;
        $this->resourceGenerator = new ResourceGenerator;
        $this->testGenerator = new TestGenerator;
        $this->routeGenerator = new RouteGenerator;
        $this->factoryGenerator = new FactoryGenerator;
    }

    /**
     * Build complete CRUD for a given model name
     */
    public function build(string $modelName, array $options = []): array
    {
        $tableName = Str::plural(Str::snake($modelName));

        // Parse migration to get table structure
        $migrationData = $this->migrationParser->parse($tableName);

        if (! $migrationData) {
            throw new \Exception("Migration file for table '{$tableName}' not found.");
        }

        $results = [];

        // Generate Model
        if ($options['model'] ?? true) {
            $results['model'] = $this->modelGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Service
        if ($options['service'] ?? true) {
            $results['service'] = $this->serviceGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Controller
        if ($options['controller'] ?? true) {
            $results['controller'] = $this->controllerGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Requests
        if ($options['requests'] ?? true) {
            $results['requests'] = $this->requestGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Resource
        if ($options['resource'] ?? true) {
            $results['resource'] = $this->resourceGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Tests
        if ($options['tests'] ?? true) {
            $results['tests'] = $this->testGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Routes
        if ($options['routes'] ?? true) {
            $results['routes'] = $this->routeGenerator->generate($modelName, $migrationData, $options);
        }

        // Generate Factory
        if ($options['factory'] ?? true) {
            $results['factory'] = $this->factoryGenerator->generate($modelName, $migrationData, $options);
        }

        return $results;
    }

    /**
     * Get migration parser instance
     */
    public function getMigrationParser(): MigrationParser
    {
        return $this->migrationParser;
    }

    /**
     * Get available generators
     */
    public function getGenerators(): array
    {
        return [
            'model' => $this->modelGenerator,
            'controller' => $this->controllerGenerator,
            'service' => $this->serviceGenerator,
            'request' => $this->requestGenerator,
            'resource' => $this->resourceGenerator,
            'test' => $this->testGenerator,
            'route' => $this->routeGenerator,
            'factory' => $this->factoryGenerator,
        ];
    }
}
