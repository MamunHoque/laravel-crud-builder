<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeResourceCommand extends Command
{
    protected $signature = 'make:crud-resource {name} {--force}';

    protected $description = 'Generate API resource with intelligent field mapping';

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
                'service' => false,
                'requests' => false,
                'resource' => true,
                'tests' => false,
                'routes' => false,
                'force' => $this->option('force'),
            ];

            $results = $this->crudBuilder->build($modelName, $options);

            $this->info("✅ Resource generated successfully: {$results['resource']}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
