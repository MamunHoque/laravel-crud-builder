<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeModelCommand extends Command
{
    protected $signature = 'make:crud-model {name} {--force}';
    protected $description = 'Generate model with intelligent migration parsing';

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
                'model' => true,
                'controller' => false,
                'service' => false,
                'requests' => false,
                'resource' => false,
                'tests' => false,
                'routes' => false,
                'force' => $this->option('force'),
            ];

            $results = $this->crudBuilder->build($modelName, $options);
            
            $this->info("✅ Model generated successfully: {$results['model']}");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
