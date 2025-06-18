<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeRequestCommand extends Command
{
    protected $signature = 'make:crud-request {name} {--force}';
    protected $description = 'Generate request classes with intelligent validation rules';

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
                'requests' => true,
                'resource' => false,
                'tests' => false,
                'routes' => false,
                'force' => $this->option('force'),
            ];

            $results = $this->crudBuilder->build($modelName, $options);
            
            $this->info("✅ Request classes generated successfully:");
            foreach ($results['requests'] as $request) {
                $this->line("   - {$request}");
            }
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
