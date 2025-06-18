<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;
use MamunHoque\CrudBuilder\Data\ColumnData;

class ResourceGenerator extends BaseGenerator
{
    /**
     * Generate resource file
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (!$this->shouldGenerate('resource', $options)) {
            return [];
        }

        $stub = $this->getStub('resource');
        
        $resourceName = $migrationData->getResourceName();
        
        $replacements = [
            'namespace' => $this->getNamespace('resources'),
            'className' => $resourceName,
            'modelName' => $modelName,
            'resourceArray' => $this->generateResourceArray($migrationData),
            'relationships' => $this->generateResourceRelationships($migrationData),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$resourceName}.php";
        $filePath = $this->getFilePath('resources', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate resource array
     */
    protected function generateResourceArray(MigrationData $migrationData): string
    {
        $columns = $migrationData->getColumns();
        $resourceFields = [];
        
        foreach ($columns as $column) {
            $resourceFields[] = $this->generateResourceField($column);
        }
        
        // Add common fields
        $resourceFields[] = "            'created_at' => \$this->created_at?->toISOString()";
        $resourceFields[] = "            'updated_at' => \$this->updated_at?->toISOString()";
        
        if ($migrationData->hasSoftDeletes()) {
            $resourceFields[] = "            'deleted_at' => \$this->deleted_at?->toISOString()";
        }
        
        return implode(",\n", $resourceFields);
    }

    /**
     * Generate single resource field
     */
    protected function generateResourceField(ColumnData $column): string
    {
        // Handle special cases
        switch ($column->type) {
            case 'json':
            case 'jsonb':
                return "            '{$column->name}' => \$this->{$column->name} ?? []";
                
            case 'boolean':
                return "            '{$column->name}' => (bool) \$this->{$column->name}";
                
            case 'date':
                return "            '{$column->name}' => \$this->{$column->name}?->toDateString()";
                
            case 'dateTime':
            case 'timestamp':
                return "            '{$column->name}' => \$this->{$column->name}?->toISOString()";
                
            default:
                // Check if it's a file field that should have URL accessor
                if ($column->shouldHaveAccessor()) {
                    $urlField = $this->snake($column->name) . '_url';
                    return "            '{$column->name}' => \$this->{$column->name},\n" .
                           "            '{$urlField}' => \$this->{$urlField}";
                }
                
                return "            '{$column->name}' => \$this->{$column->name}";
        }
    }

    /**
     * Generate resource relationships
     */
    protected function generateResourceRelationships(MigrationData $migrationData): string
    {
        $relationships = $migrationData->getRelationships();
        
        if (empty($relationships)) {
            return '';
        }
        
        $relationshipFields = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship->type === 'belongsTo') {
                $relationshipFields[] = $this->generateBelongsToResource($relationship);
            }
        }
        
        if (empty($relationshipFields)) {
            return '';
        }
        
        return ",\n\n            // Relationships\n" . implode(",\n", $relationshipFields);
    }

    /**
     * Generate belongsTo relationship resource
     */
    protected function generateBelongsToResource($relationship): string
    {
        $relationshipName = $relationship->relationshipName;
        $relatedResourceName = $relationship->relatedModel . 'Resource';
        
        return "            '{$relationshipName}' => \$this->whenLoaded('{$relationshipName}', function () {\n" .
               "                return new {$relatedResourceName}(\$this->{$relationshipName});\n" .
               "            })";
    }
}
