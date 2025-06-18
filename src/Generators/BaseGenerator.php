<?php

namespace MamunHoque\CrudBuilder\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use MamunHoque\CrudBuilder\Data\MigrationData;

abstract class BaseGenerator
{
    protected Filesystem $filesystem;
    protected array $config;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->config = config('crud-builder', []);
    }

    /**
     * Generate the file(s) for this generator
     */
    abstract public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string;

    /**
     * Get the stub content
     */
    protected function getStub(string $stubName): string
    {
        $stubPath = $this->getStubPath($stubName);
        
        if (!$this->filesystem->exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }
        
        return $this->filesystem->get($stubPath);
    }

    /**
     * Get the stub file path
     */
    protected function getStubPath(string $stubName): string
    {
        // Check for published stubs first
        $publishedStubPath = resource_path("stubs/crud-builder/{$stubName}.stub");
        if ($this->filesystem->exists($publishedStubPath)) {
            return $publishedStubPath;
        }
        
        // Fall back to package stubs
        return __DIR__ . "/../../stubs/{$stubName}.stub";
    }

    /**
     * Replace placeholders in stub content
     */
    protected function replacePlaceholders(string $content, array $replacements): string
    {
        foreach ($replacements as $placeholder => $replacement) {
            $content = str_replace("{{ {$placeholder} }}", $replacement, $content);
        }
        
        return $content;
    }

    /**
     * Get the file path for generated file
     */
    protected function getFilePath(string $type, string $fileName): string
    {
        $basePath = base_path();
        $relativePath = $this->config['paths'][$type] ?? "app/{$type}";
        
        return $basePath . '/' . $relativePath . '/' . $fileName;
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists(string $filePath): void
    {
        $directory = dirname($filePath);
        
        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Write file to disk
     */
    protected function writeFile(string $filePath, string $content, bool $force = false): bool
    {
        if (!$force && $this->filesystem->exists($filePath)) {
            throw new \Exception("File already exists: {$filePath}");
        }
        
        $this->ensureDirectoryExists($filePath);
        
        return $this->filesystem->put($filePath, $content) !== false;
    }

    /**
     * Get namespace for given type
     */
    protected function getNamespace(string $type): string
    {
        return $this->config['namespaces'][$type] ?? "App\\{$type}";
    }

    /**
     * Convert array to formatted string for code generation
     */
    protected function arrayToString(array $array, int $indent = 2): string
    {
        if (empty($array)) {
            return '[]';
        }
        
        $indentStr = str_repeat(' ', $indent);
        $items = [];
        
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                if (is_array($value)) {
                    $items[] = $indentStr . $this->arrayToString($value, $indent + 4);
                } else {
                    $items[] = $indentStr . "'{$value}'";
                }
            } else {
                if (is_array($value)) {
                    $items[] = $indentStr . "'{$key}' => " . $this->arrayToString($value, $indent + 4);
                } else {
                    $items[] = $indentStr . "'{$key}' => '{$value}'";
                }
            }
        }
        
        return "[\n" . implode(",\n", $items) . "\n" . str_repeat(' ', $indent - 2) . "]";
    }

    /**
     * Convert validation rules array to string
     */
    protected function validationRulesToString(array $rules): string
    {
        if (empty($rules)) {
            return '[]';
        }
        
        $items = [];
        foreach ($rules as $field => $fieldRules) {
            if (is_array($fieldRules)) {
                $rulesStr = "'" . implode('|', $fieldRules) . "'";
            } else {
                $rulesStr = "'{$fieldRules}'";
            }
            $items[] = "            '{$field}' => {$rulesStr}";
        }
        
        return "[\n" . implode(",\n", $items) . "\n        ]";
    }

    /**
     * Generate use statements
     */
    protected function generateUseStatements(array $uses): string
    {
        if (empty($uses)) {
            return '';
        }
        
        sort($uses);
        $statements = array_map(fn($use) => "use {$use};", $uses);
        
        return implode("\n", $statements);
    }

    /**
     * Get class name from full class path
     */
    protected function getClassName(string $fullClassName): string
    {
        return class_basename($fullClassName);
    }

    /**
     * Convert string to studly case
     */
    protected function studly(string $value): string
    {
        return Str::studly($value);
    }

    /**
     * Convert string to camel case
     */
    protected function camel(string $value): string
    {
        return Str::camel($value);
    }

    /**
     * Convert string to snake case
     */
    protected function snake(string $value): string
    {
        return Str::snake($value);
    }

    /**
     * Convert string to kebab case
     */
    protected function kebab(string $value): string
    {
        return Str::kebab($value);
    }

    /**
     * Convert string to plural
     */
    protected function plural(string $value): string
    {
        return Str::plural($value);
    }

    /**
     * Convert string to singular
     */
    protected function singular(string $value): string
    {
        return Str::singular($value);
    }

    /**
     * Get current timestamp for file headers
     */
    protected function getCurrentTimestamp(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Generate file header comment
     */
    protected function generateFileHeader(string $description = ''): string
    {
        $timestamp = $this->getCurrentTimestamp();
        
        return "<?php\n\n/**\n * Generated by CRUD Builder\n * {$description}\n * Generated at: {$timestamp}\n */\n\n";
    }

    /**
     * Check if file should be generated based on options
     */
    protected function shouldGenerate(string $component, array $options): bool
    {
        return $options[$component] ?? $this->config['defaults']["generate_{$component}"] ?? true;
    }
}
