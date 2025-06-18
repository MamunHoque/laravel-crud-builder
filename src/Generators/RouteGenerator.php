<?php

namespace MamunHoque\CrudBuilder\Generators;

use MamunHoque\CrudBuilder\Data\MigrationData;

class RouteGenerator extends BaseGenerator
{
    /**
     * Generate routes
     */
    public function generate(string $modelName, MigrationData $migrationData, array $options = []): array|string
    {
        if (! $this->shouldGenerate('routes', $options)) {
            return [];
        }

        $routeFile = $this->config['routes']['file'] ?? 'routes/api.php';
        $routeFilePath = base_path($routeFile);

        if (! $this->filesystem->exists($routeFilePath)) {
            throw new \Exception("Route file not found: {$routeFilePath}");
        }

        $controllerName = $migrationData->getControllerName();
        $middleware = $options['middleware'] ?? $this->config['routes']['default_middleware'] ?? 'admin';
        $prefix = $options['prefix'] ?? $this->getRoutePrefix($middleware);

        $this->addUseStatement($routeFilePath, $controllerName);
        $this->addRoute($routeFilePath, $modelName, $controllerName, $middleware, $prefix);

        return "Added routes to {$routeFile}";
    }

    /**
     * Add use statement to route file
     */
    protected function addUseStatement(string $routeFilePath, string $controllerName): void
    {
        $content = $this->filesystem->get($routeFilePath);
        $controllerNamespace = $this->getNamespace('controllers');
        $useStatement = "use {$controllerNamespace}\\{$controllerName};";

        // Check if use statement already exists
        if (str_contains($content, $useStatement)) {
            return;
        }

        // Find the last use statement and add after it
        $lines = explode("\n", $content);
        $lastUseIndex = -1;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ') && str_ends_with(trim($line), ';')) {
                $lastUseIndex = $index;
            }
        }

        if ($lastUseIndex >= 0) {
            array_splice($lines, $lastUseIndex + 1, 0, $useStatement);
            $this->filesystem->put($routeFilePath, implode("\n", $lines));
        }
    }

    /**
     * Add route to route file
     */
    protected function addRoute(string $routeFilePath, string $modelName, string $controllerName, string $middleware, ?string $prefix): void
    {
        $content = $this->filesystem->get($routeFilePath);

        $resourceName = $this->plural($this->kebab($modelName));
        $routeLine = $this->generateRouteLine($resourceName, $controllerName, $prefix);

        // Find the appropriate middleware group
        $middlewareConfig = $this->config['routes']['middleware_groups'][$middleware] ?? [];
        $middlewarePattern = $this->getMiddlewarePattern($middleware, $middlewareConfig);

        if ($middlewarePattern && str_contains($content, $middlewarePattern)) {
            $content = $this->insertRouteInMiddlewareGroup($content, $routeLine, $middlewarePattern);
        } else {
            // Add at the end of the file
            $content = $this->appendRoute($content, $routeLine, $middleware, $middlewareConfig);
        }

        $this->filesystem->put($routeFilePath, $content);
    }

    /**
     * Generate route line
     */
    protected function generateRouteLine(string $resourceName, string $controllerName, ?string $prefix): string
    {
        $indentation = '        ';

        if ($prefix) {
            return "{$indentation}Route::apiResource('{$prefix}/{$resourceName}', {$controllerName}::class);";
        }

        return "{$indentation}Route::apiResource('{$resourceName}', {$controllerName}::class);";
    }

    /**
     * Get middleware pattern for finding the group
     */
    protected function getMiddlewarePattern(string $middleware, array $middlewareConfig): ?string
    {
        switch ($middleware) {
            case 'admin':
                return "Route::prefix('admin')->middleware('role:admin')->group(function () {";
            case 'auth':
                return "Route::middleware('auth:api')->group(function () {";
            case 'public':
                return null; // No middleware group needed
            default:
                if (! empty($middlewareConfig)) {
                    $middlewareStr = implode("', '", $middlewareConfig);

                    return "Route::middleware(['{$middlewareStr}'])->group(function () {";
                }

                return null;
        }
    }

    /**
     * Insert route in existing middleware group
     */
    protected function insertRouteInMiddlewareGroup(string $content, string $routeLine, string $middlewarePattern): string
    {
        $position = strpos($content, $middlewarePattern);

        if ($position !== false) {
            $bracePosition = strpos($content, '{', $position);
            if ($bracePosition !== false) {
                $insertionPoint = $bracePosition + 1;
                $content = substr_replace($content, "\n{$routeLine}", $insertionPoint, 0);
            }
        }

        return $content;
    }

    /**
     * Append route to end of file
     */
    protected function appendRoute(string $content, string $routeLine, string $middleware, array $middlewareConfig): string
    {
        if ($middleware === 'public') {
            // Add as a simple route without middleware
            return $content."\n".str_replace('        ', '', $routeLine)."\n";
        }

        // Create new middleware group
        $middlewareStr = implode("', '", $middlewareConfig);
        $newGroup = "\n// Generated routes for {$middleware}\n";
        $newGroup .= "Route::middleware(['{$middlewareStr}'])->group(function () {\n";
        $newGroup .= "{$routeLine}\n";
        $newGroup .= "});\n";

        return $content.$newGroup;
    }

    /**
     * Get route prefix for middleware
     */
    protected function getRoutePrefix(string $middleware): ?string
    {
        $prefixes = $this->config['routes']['prefix'] ?? [];

        return $prefixes[$middleware] ?? null;
    }
}
