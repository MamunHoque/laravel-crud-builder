<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;

class ControllerGenerator extends BaseGenerator
{
    /**
     * Generate controller file
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (!$this->shouldGenerate('controller', $options)) {
            return [];
        }

        $stub = $this->getStub('controller');
        
        $controllerName = $migrationData->getControllerName();
        $serviceName = $migrationData->getServiceName();
        $requestNames = $migrationData->getRequestNames();
        $resourceName = $migrationData->getResourceName();
        
        $replacements = [
            'namespace' => $this->getNamespace('controllers'),
            'className' => $controllerName,
            'modelName' => $modelName,
            'serviceName' => $serviceName,
            'serviceVariable' => $this->camel($serviceName),
            'storeRequestName' => $requestNames['store'],
            'updateRequestName' => $requestNames['update'],
            'resourceName' => $resourceName,
            'modelVariable' => $this->camel($modelName),
            'modelVariablePlural' => $this->plural($this->camel($modelName)),
            'uses' => $this->generateControllerUses($serviceName, $requestNames, $resourceName),
        ];

        $content = $this->replacePlaceholders($stub, $replacements);
        
        $fileName = "{$controllerName}.php";
        $filePath = $this->getFilePath('controllers', $fileName);
        
        $this->writeFile($filePath, $content, $options['force'] ?? false);
        
        return $filePath;
    }

    /**
     * Generate use statements for controller
     */
    protected function generateControllerUses(string $serviceName, array $requestNames, string $resourceName): string
    {
        $uses = [
            'App\Http\Controllers\Controller',
            $this->getNamespace('requests') . '\\' . $requestNames['store'],
            $this->getNamespace('requests') . '\\' . $requestNames['update'],
            $this->getNamespace('services') . '\\' . $serviceName,
            $this->getNamespace('resources') . '\\' . $resourceName,
            'App\Traits\HelperTrait',
            'Illuminate\Http\JsonResponse',
            'Illuminate\Http\Request',
            'Symfony\Component\HttpFoundation\Response',
        ];

        return $this->generateUseStatements($uses);
    }
}
