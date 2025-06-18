<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\ColumnData;
use MamunHoque\CrudBuilder\Data\MigrationData;

class ModelGenerator extends BaseGenerator
{
    /**
     * Generate model file
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (! $this->shouldGenerate('model', $options)) {
            return [];
        }

        $stub = $this->getStub('model');

        $replacements = [
            'namespace' => $this->getNamespace('models'),
            'className' => $modelName,
            'uses' => $this->generateUseStatements($migrationData->getModelUseStatements()),
            'traits' => $this->generateTraits($migrationData->getModelTraits()),
            'fillable' => $this->generateFillable($migrationData->getFillableColumns()),
            'casts' => $this->generateCasts($migrationData->getCastableColumns()),
            'relationships' => $this->generateRelationships($migrationData->getRelationships()),
            'accessors' => $this->generateAccessors($migrationData->getAccessorColumns()),
            'appends' => $this->generateAppends($migrationData->getAccessorColumns()),
            'tableName' => $migrationData->tableName,
        ];

        $content = $this->replacePlaceholders($stub, $replacements);

        $fileName = "{$modelName}.php";
        $filePath = $this->getFilePath('models', $fileName);

        $this->writeFile($filePath, $content, $options['force'] ?? false);

        return $filePath;
    }

    /**
     * Generate traits usage
     */
    protected function generateTraits(array $traits): string
    {
        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', $traits).';';
    }

    /**
     * Generate fillable array
     */
    protected function generateFillable(array $columns): string
    {
        if (empty($columns)) {
            return '[]';
        }

        $fillableFields = array_map(fn (ColumnData $column) => $column->name, $columns);

        return $this->arrayToString($fillableFields, 8);
    }

    /**
     * Generate casts array
     */
    protected function generateCasts(array $columns): string
    {
        if (empty($columns)) {
            return '[]';
        }

        $casts = [];
        foreach ($columns as $column) {
            $castType = $column->getCastType();
            if ($castType) {
                $casts[$column->name] = $castType;
            }
        }

        if (empty($casts)) {
            return '[]';
        }

        return $this->arrayToString($casts, 8);
    }

    /**
     * Generate relationship methods
     */
    protected function generateRelationships(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }

        $methods = [];
        foreach ($relationships as $relationship) {
            $methods[] = '    /**'."\n".
                        '     * '.ucfirst($relationship->type).' relationship with '.$relationship->relatedModel."\n".
                        '     */'."\n".
                        '    '.$relationship->getRelationshipMethod();
        }

        return implode("\n\n", $methods);
    }

    /**
     * Generate accessor methods
     */
    protected function generateAccessors(array $columns): string
    {
        if (empty($columns)) {
            return '';
        }

        $accessors = [];
        foreach ($columns as $column) {
            if ($column->shouldHaveAccessor()) {
                $accessors[] = $this->generateAccessorMethod($column);
            }
        }

        return implode("\n\n", $accessors);
    }

    /**
     * Generate single accessor method
     */
    protected function generateAccessorMethod(ColumnData $column): string
    {
        $methodName = 'get'.$this->studly($column->name).'UrlAttribute';
        $configKey = 'filesystems.disks.s3.url';

        return "    /**\n".
               "     * Get the URL for {$column->name}\n".
               "     */\n".
               "    public function {$methodName}(): ?string\n".
               "    {\n".
               "        if (\$this->{$column->name}) {\n".
               "            return config('{$configKey}') . '/' . \$this->{$column->name};\n".
               "        }\n".
               "        return null;\n".
               '    }';
    }

    /**
     * Generate appends array for accessors
     */
    protected function generateAppends(array $columns): string
    {
        $appendFields = [];

        foreach ($columns as $column) {
            if ($column->shouldHaveAccessor()) {
                $appendFields[] = $this->snake($column->name).'_url';
            }
        }

        if (empty($appendFields)) {
            return '[]';
        }

        return $this->arrayToString($appendFields, 8);
    }
}
