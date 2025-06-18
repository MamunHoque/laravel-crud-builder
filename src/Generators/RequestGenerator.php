<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;

class RequestGenerator extends BaseGenerator
{
    /**
     * Generate request files
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (!$this->shouldGenerate('requests', $options)) {
            return [];
        }

        $requestNames = $migrationData->getRequestNames();
        $generatedFiles = [];

        // Generate Store Request
        $storeRequestPath = $this->generateStoreRequest(
            $requestNames['store'], 
            $migrationData, 
            $options
        );
        $generatedFiles[] = $storeRequestPath;

        // Generate Update Request
        $updateRequestPath = $this->generateUpdateRequest(
            $requestNames['update'], 
            $migrationData, 
            $options
        );
        $generatedFiles[] = $updateRequestPath;

        return $generatedFiles;
    }

    /**
     * Generate store request file
     */
    protected function generateStoreRequest(string $requestName, MigrationData $migrationData, array $options): string
    {
        $stub = $this->getStub('store-request');
        
        $validationRules = $migrationData->getStoreValidationRules();
        
        $replacements = [
            'namespace' => $this->getNamespace('requests'),
            'className' => $requestName,
            'rules' => $this->validationRulesToString($validationRules),
            'modelName' => $migrationData->getModelName(),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$requestName}.php";
        $filePath = $this->getFilePath('requests', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate update request file
     */
    protected function generateUpdateRequest(string $requestName, MigrationData $migrationData, array $options): string
    {
        $stub = $this->getStub('update-request');
        
        $validationRules = $this->getUpdateValidationRules($migrationData);
        
        $replacements = [
            'namespace' => $this->getNamespace('requests'),
            'className' => $requestName,
            'rules' => $this->validationRulesToString($validationRules),
            'modelName' => $migrationData->getModelName(),
            'tableName' => $migrationData->tableName,
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$requestName}.php";
        $filePath = $this->getFilePath('requests', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Get update validation rules with unique rule modifications
     */
    protected function getUpdateValidationRules(MigrationData $migrationData): array
    {
        $rules = $migrationData->getUpdateValidationRules();
        
        // Modify unique rules for update requests
        foreach ($rules as $field => &$fieldRules) {
            if (is_array($fieldRules)) {
                foreach ($fieldRules as &$rule) {
                    if (str_starts_with($rule, 'unique:')) {
                        // Add ignore current record for unique rules
                        $rule = str_replace(
                            "unique:{$migrationData->tableName},{$field}",
                            "unique:{$migrationData->tableName},{$field},' . \$this->route('id')",
                            $rule
                        );
                    }
                }
            }
        }
        
        return $rules;
    }
}
