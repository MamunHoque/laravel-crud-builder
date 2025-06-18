<?php

namespace MamunHoque\CrudBuilder\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishHelperTraitCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crud-builder:publish-helper-trait {--force : Overwrite existing HelperTrait}';

    /**
     * The console command description.
     */
    protected $description = 'Publish the HelperTrait to your application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetPath = app_path('Traits/HelperTrait.php');
        $sourcePath = __DIR__.'/../../Traits/HelperTrait.php';

        // Check if HelperTrait already exists
        if (File::exists($targetPath) && ! $this->option('force')) {
            $this->warn('HelperTrait already exists at: '.$targetPath);

            if (! $this->confirm('Do you want to overwrite it?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            // Create the Traits directory if it doesn't exist
            $traitsDir = app_path('Traits');
            if (! File::isDirectory($traitsDir)) {
                File::makeDirectory($traitsDir, 0755, true);
                $this->info('Created directory: '.$traitsDir);
            }

            // Read the source file
            $content = File::get($sourcePath);

            // Update the namespace to match the application
            $content = str_replace(
                'namespace MamunHoque\CrudBuilder\Traits;',
                'namespace App\Traits;',
                $content
            );

            // Write the file
            File::put($targetPath, $content);

            $this->info('✅ HelperTrait published successfully to: '.$targetPath);

            // Show usage information
            $this->newLine();
            $this->info('📋 Usage:');
            $this->line('   Add "use App\Traits\HelperTrait;" to your controllers and services');
            $this->line('   Then use "use HelperTrait;" in your class');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to publish HelperTrait: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
