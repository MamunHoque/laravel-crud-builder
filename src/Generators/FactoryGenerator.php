<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;
use MamunHoque\CrudBuilder\Data\ColumnData;

class FactoryGenerator extends BaseGenerator
{
    /**
     * Generate factory file
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (!$this->shouldGenerate('factory', $options)) {
            return [];
        }

        $stub = $this->getStub('factory');
        
        $factoryName = "{$modelName}Factory";
        
        $replacements = [
            'namespace' => 'Database\\Factories',
            'className' => $factoryName,
            'modelName' => $modelName,
            'modelNamespace' => $this->getNamespace('models') . '\\' . $modelName,
            'definition' => $this->generateFactoryDefinition($migrationData),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$factoryName}.php";
        $filePath = $this->getFilePath('factories', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate factory definition array
     */
    protected function generateFactoryDefinition(MigrationData $migrationData): string
    {
        $fillableColumns = $migrationData->getFillableColumns();
        $definition = [];
        
        foreach ($fillableColumns as $column) {
            $definition[$column->name] = $this->generateFactoryValue($column);
        }
        
        return $this->arrayToString($definition, 12);
    }

    /**
     * Generate factory value for column
     */
    protected function generateFactoryValue(ColumnData $column): string
    {
        switch ($column->type) {
            case 'string':
                if (str_contains(strtolower($column->name), 'name')) {
                    return 'fake()->name()';
                } elseif (str_contains(strtolower($column->name), 'title')) {
                    return 'fake()->sentence(3)';
                } elseif (str_contains(strtolower($column->name), 'slug')) {
                    return 'fake()->slug()';
                } elseif (str_contains(strtolower($column->name), 'phone')) {
                    return 'fake()->phoneNumber()';
                } elseif (str_contains(strtolower($column->name), 'address')) {
                    return 'fake()->address()';
                } elseif (str_contains(strtolower($column->name), 'city')) {
                    return 'fake()->city()';
                } elseif (str_contains(strtolower($column->name), 'country')) {
                    return 'fake()->country()';
                } elseif (str_contains(strtolower($column->name), 'url')) {
                    return 'fake()->url()';
                } elseif ($column->length && $column->length <= 50) {
                    return 'fake()->word()';
                } else {
                    return 'fake()->sentence()';
                }
                
            case 'text':
            case 'longText':
            case 'mediumText':
                return 'fake()->paragraph()';
                
            case 'tinyText':
                return 'fake()->sentence()';
                
            case 'integer':
            case 'bigInteger':
            case 'mediumInteger':
            case 'smallInteger':
                if ($column->unsigned) {
                    return 'fake()->numberBetween(1, 1000)';
                }
                return 'fake()->numberBetween(-1000, 1000)';
                
            case 'tinyInteger':
                if ($column->unsigned) {
                    return 'fake()->numberBetween(1, 255)';
                }
                return 'fake()->numberBetween(-128, 127)';
                
            case 'boolean':
                return 'fake()->boolean()';
                
            case 'date':
                return 'fake()->date()';
                
            case 'dateTime':
            case 'timestamp':
                return 'fake()->dateTime()';
                
            case 'time':
                return 'fake()->time()';
                
            case 'decimal':
            case 'float':
            case 'double':
                if ($column->unsigned) {
                    return 'fake()->randomFloat(2, 0, 10000)';
                }
                return 'fake()->randomFloat(2, -10000, 10000)';
                
            case 'enum':
                if ($column->enumValues) {
                    $values = implode("', '", $column->enumValues);
                    return "fake()->randomElement(['{$values}'])";
                }
                return 'fake()->word()';
                
            case 'json':
            case 'jsonb':
                return '[]';
                
            case 'uuid':
                return 'fake()->uuid()';
                
            case 'ipAddress':
                return 'fake()->ipv4()';
                
            case 'macAddress':
                return 'fake()->macAddress()';
                
            case 'year':
                return 'fake()->year()';
                
            default:
                // Handle foreign keys
                if ($column->isForeignKey()) {
                    $relatedModel = $this->studly($this->singular($column->getForeignTableName()));
                    return "{$relatedModel}::factory()";
                }
                
                // Handle special field names
                if (str_contains(strtolower($column->name), 'email')) {
                    return 'fake()->email()';
                } elseif (str_contains(strtolower($column->name), 'password')) {
                    return 'bcrypt("password")';
                } elseif (str_contains(strtolower($column->name), 'image') || 
                          str_contains(strtolower($column->name), 'photo') ||
                          str_contains(strtolower($column->name), 'avatar')) {
                    return 'fake()->imageUrl()';
                } elseif (str_contains(strtolower($column->name), 'color')) {
                    return 'fake()->hexColor()';
                }
                
                return 'fake()->word()';
        }
    }
}
