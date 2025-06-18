<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:crud-service {name} {--force}';

    protected $description = 'Generate service with intelligent migration parsing';

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
                'controller' => false,
                'service' => true,
                'requests' => false,
                'resource' => false,
                'tests' => false,
                'routes' => false,
                'force' => $this->option('force'),
            ];

            $results = $this->crudBuilder->build($modelName, $options);

            $this->info("✅ Service generated successfully: {$results['service']}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
