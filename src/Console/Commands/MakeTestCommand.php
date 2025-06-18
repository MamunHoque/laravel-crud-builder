<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use MamunHoque\CrudBuilder\CrudBuilder;

class MakeTestCommand extends Command
{
    protected $signature = 'make:crud-test {name} {--force}';
    protected $description = 'Generate comprehensive test files for CRUD operations';

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
                'resource' => false,
                'tests' => true,
                'routes' => false,
                'force' => $this->option('force'),
            ];

            $results = $this->crudBuilder->build($modelName, $options);
            
            $this->info("✅ Test files generated successfully:");
            foreach ($results['tests'] as $test) {
                $this->line("   - {$test}");
            }
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
