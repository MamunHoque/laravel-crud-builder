<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeControllerCommand extends Command
{
    protected $signature = 'make:crud-controller {name} {--force}';

    protected $description = 'Generate controller with intelligent migration parsing';

    protected CrudBuilder $crudBuilder;

    public function __construct(CrudBuilder $crudBuilder)
    {
        parent::__construct();
        $this->crudBuilder = $crudBuilder;
    }

    public function handle(): int
    {
        $modelName = $this->argument('name');

        try {
            $options = [
                'model' => false,
                'controller' => true,
                'service' => false,
                'requests' => false,
                'resource' => false,
                'tests' => false,
                'routes' => false,
                'force' => $this->option('force'),
            ];

            $results = $this->crudBuilder->build($modelName, $options);

            $this->info("✅ Controller generated successfully: {$results['controller']}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
