<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;
use MamunHoque\CrudBuilder\Data\ColumnData;

class ServiceGenerator extends BaseGenerator
{
    /**
     * Generate service file
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (!$this->shouldGenerate('service', $options)) {
            return [];
        }

        $stub = $this->getStub('service');
        
        $serviceName = $migrationData->getServiceName();
        $searchableColumns = $migrationData->getSearchableColumns();
        $eagerLoadRelationships = $migrationData->getEagerLoadRelationships();
        
        $replacements = [
            'namespace' => $this->getNamespace('services'),
            'className' => $serviceName,
            'modelName' => $modelName,
            'modelVariable' => $this->camel($modelName),
            'searchKeys' => $this->generateSearchKeys($searchableColumns),
            'eagerLoad' => $this->generateEagerLoad($eagerLoadRelationships),
            'filters' => $this->generateFilters($migrationData),
            'prepareDataMethod' => $this->generatePrepareDataMethod($modelName, $migrationData),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$serviceName}.php";
        $filePath = $this->getFilePath('services', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate search keys array
     */
    protected function generateSearchKeys(array $searchableColumns): string
    {
        if (empty($searchableColumns)) {
            return "['name']"; // Default fallback
        }
        
        $searchKeys = array_map(fn(ColumnData $column) => $column->name, $searchableColumns);
        
        return $this->arrayToString($searchKeys, 0);
    }

    /**
     * Generate eager load relationships
     */
    protected function generateEagerLoad(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }
        
        $relationshipNames = array_map(fn($rel) => $rel->relationshipName, $relationships);
        $relationshipsStr = "'" . implode("', '", $relationshipNames) . "'";
        
        return "\n        \$query->with([{$relationshipsStr}]);";
    }

    /**
     * Generate filter methods
     */
    protected function generateFilters(MigrationData $migrationData): string
    {
        $filters = [];
        
        // Generate filters for foreign key relationships
        foreach ($migrationData->getBelongsToRelationships() as $relationship) {
            $filters[] = $this->generateRelationshipFilter($relationship);
        }
        
        // Generate filters for enum columns
        foreach ($migrationData->getColumns() as $column) {
            if ($column->enumValues) {
                $filters[] = $this->generateEnumFilter($column);
            }
        }
        
        if (empty($filters)) {
            return '';
        }
        
        return "\n\n        // Apply filters\n" . implode("\n", $filters);
    }

    /**
     * Generate relationship filter
     */
    protected function generateRelationshipFilter($relationship): string
    {
        $filterParam = $relationship->localKey;
        
        return "        if (\$request->filled('{$filterParam}')) {\n" .
               "            \$query->where('{$relationship->localKey}', \$request->input('{$filterParam}'));\n" .
               "        }";
    }

    /**
     * Generate enum filter
     */
    protected function generateEnumFilter(ColumnData $column): string
    {
        return "        if (\$request->filled('{$column->name}')) {\n" .
               "            \$query->where('{$column->name}', \$request->input('{$column->name}'));\n" .
               "        }";
    }

    /**
     * Generate prepare data method
     */
    protected function generatePrepareDataMethod(string $modelName, MigrationData $migrationData): string
    {
        $methodName = "prepare{$modelName}Data";
        $variableName = $this->camel($modelName);
        $fillableColumns = $migrationData->getFillableColumns();
        
        $fileUploadHandling = $this->generateFileUploadHandling($fillableColumns);
        
        return "    private function {$methodName}(\$request, bool \$isNew = true): array\n" .
               "    {\n" .
               "        \$validated = \$request->validated();\n" .
               "        // Get the fillable fields from the model\n" .
               "        \$fillable = (new {$modelName}())->getFillable();\n\n" .
               "        // Extract relevant fields from the request dynamically\n" .
               "        \$data = array_intersect_key(\$validated, array_flip(\$fillable));\n\n" .
               $fileUploadHandling .
               "\n        // Add created_by and created_at fields for new records\n" .
               "        if (\$isNew) {\n" .
               "            \$data['user_id'] = auth()->user()->id ?? null;\n" .
               "            \$data['created_at'] = now();\n" .
               "        }\n\n" .
               "        return \$data;\n" .
               "    }";
    }

    /**
     * Generate file upload handling code
     */
    protected function generateFileUploadHandling(array $columns): string
    {
        $fileColumns = array_filter($columns, fn(ColumnData $column) => $column->shouldHaveAccessor());
        
        if (empty($fileColumns)) {
            return "        // Handle file uploads\n" .
                   "        // \$data['featured_image'] = \$this->s3FileUpload(\$request, 'featured_image', '{$this->snake($this->modelName)}')['path'] ?? null;";
        }
        
        $uploadCode = "        // Handle file uploads\n";
        foreach ($fileColumns as $column) {
            $uploadCode .= "        \$data['{$column->name}'] = \$this->s3FileUpload(\$request, '{$column->name}', '{$this->snake($this->modelName)}')['path'] ?? null;\n";
        }
        
        return $uploadCode;
    }
}
