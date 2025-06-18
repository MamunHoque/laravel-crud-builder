<?php

namespace MamunHoque\CrudBuilder\Parsers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use MamunHoque\CrudBuilder\Data\ColumnData;
use MamunHoque\CrudBuilder\Data\MigrationData;
use MamunHoque\CrudBuilder\Data\RelationshipData;

class MigrationParser
{
    protected Filesystem $filesystem;
    protected array $columnTypes = [
        'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'dateTimeTz', 'dateTime',
        'date', 'decimal', 'double', 'enum', 'float', 'foreignId', 'foreignIdFor', 'foreignUlid',
        'foreignUuid', 'geometryCollection', 'geometry', 'id', 'increments', 'integer', 'ipAddress',
        'json', 'jsonb', 'lineString', 'longText', 'macAddress', 'mediumIncrements', 'mediumInteger',
        'mediumText', 'morphs', 'multiLineString', 'multiPoint', 'multiPolygon', 'nullableMorphs',
        'nullableTimestamps', 'nullableUlidMorphs', 'nullableUuidMorphs', 'point', 'polygon',
        'rememberToken', 'set', 'smallIncrements', 'smallInteger', 'softDeletesTz', 'softDeletes',
        'string', 'text', 'timeTz', 'time', 'timestampTz', 'timestamp', 'timestampsTz', 'timestamps',
        'tinyIncrements', 'tinyInteger', 'tinyText', 'unsignedBigInteger', 'unsignedDecimal',
        'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger',
        'ulidMorphs', 'ulid', 'uuidMorphs', 'uuid', 'year'
    ];

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Parse migration file for a given table name
     */
    public function parse(string $tableName): ?MigrationData
    {
        $migrationFile = $this->findMigrationFile($tableName);
        
        if (!$migrationFile) {
            return null;
        }

        $content = $this->filesystem->get($migrationFile);
        
        return $this->parseMigrationContent($content, $tableName);
    }

    /**
     * Find migration file for table
     */
    protected function findMigrationFile(string $tableName): ?string
    {
        $migrationsPath = database_path('migrations');
        
        if (!$this->filesystem->isDirectory($migrationsPath)) {
            return null;
        }

        $files = $this->filesystem->files($migrationsPath);
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            
            // Look for create table migrations
            if (Str::contains($filename, "create_{$tableName}_table")) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Parse migration file content
     */
    protected function parseMigrationContent(string $content, string $tableName): MigrationData
    {
        $migrationData = new MigrationData($tableName);
        
        // Extract the Schema::create block
        $schemaPattern = '/Schema::create\s*\(\s*[\'"]' . preg_quote($tableName) . '[\'"]\s*,\s*function\s*\(\s*\$?table\s*\)\s*\{(.*?)\}\s*\);/s';
        
        if (preg_match($schemaPattern, $content, $matches)) {
            $tableDefinition = $matches[1];
            
            // Parse columns
            $columns = $this->parseColumns($tableDefinition);
            $migrationData->setColumns($columns);
            
            // Parse relationships
            $relationships = $this->parseRelationships($tableDefinition);
            $migrationData->setRelationships($relationships);
            
            // Parse indexes
            $indexes = $this->parseIndexes($tableDefinition);
            $migrationData->setIndexes($indexes);
            
            // Check for soft deletes
            $migrationData->setHasSoftDeletes($this->hasSoftDeletes($tableDefinition));
            
            // Check for timestamps
            $migrationData->setHasTimestamps($this->hasTimestamps($tableDefinition));
        }

        return $migrationData;
    }

    /**
     * Parse column definitions
     */
    protected function parseColumns(string $tableDefinition): array
    {
        $columns = [];
        
        // Enhanced regex to capture column definitions with chained methods
        $pattern = '/\$table->(\w+)\s*\(\s*([^)]*)\s*\)([^;]*);/';
        
        if (preg_match_all($pattern, $tableDefinition, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columnType = $match[1];
                $parameters = $this->parseParameters($match[2]);
                $chainedMethods = $this->parseChainedMethods($match[3]);
                
                // Skip non-column methods
                if (!in_array($columnType, $this->columnTypes)) {
                    continue;
                }
                
                $columnName = $parameters[0] ?? null;
                
                if ($columnName) {
                    $columnData = new ColumnData(
                        name: trim($columnName, '\'"'),
                        type: $columnType,
                        nullable: in_array('nullable', $chainedMethods),
                        default: $this->extractDefault($chainedMethods),
                        length: $this->extractLength($parameters),
                        unsigned: in_array('unsigned', $chainedMethods),
                        unique: in_array('unique', $chainedMethods),
                        index: in_array('index', $chainedMethods),
                        comment: $this->extractComment($chainedMethods),
                        enumValues: $this->extractEnumValues($columnType, $parameters)
                    );
                    
                    $columns[] = $columnData;
                }
            }
        }
        
        return $columns;
    }

    /**
     * Parse method parameters
     */
    protected function parseParameters(string $paramString): array
    {
        if (empty(trim($paramString))) {
            return [];
        }
        
        // Simple parameter parsing - can be enhanced for complex cases
        $params = explode(',', $paramString);
        return array_map('trim', $params);
    }

    /**
     * Parse chained methods
     */
    protected function parseChainedMethods(string $chainString): array
    {
        $methods = [];
        
        // Match chained method calls
        if (preg_match_all('/->(\w+)\s*\(([^)]*)\)/', $chainString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $methods[] = $match[1];
                
                // Store method with parameters for complex parsing
                if (!empty($match[2])) {
                    $methods[$match[1]] = trim($match[2], '\'"');
                }
            }
        }
        
        return $methods;
    }

    /**
     * Parse foreign key relationships
     */
    protected function parseRelationships(string $tableDefinition): array
    {
        $relationships = [];
        
        // Parse foreign key constraints
        $foreignPattern = '/\$table->foreign\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->references\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->on\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';
        
        if (preg_match_all($foreignPattern, $tableDefinition, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $relationships[] = new RelationshipData(
                    localKey: $match[1],
                    foreignKey: $match[2],
                    foreignTable: $match[3],
                    type: 'belongsTo'
                );
            }
        }
        
        // Parse foreignId columns
        $foreignIdPattern = '/\$table->foreignId\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';
        
        if (preg_match_all($foreignIdPattern, $tableDefinition, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columnName = $match[1];
                
                // Infer table name from column name (e.g., user_id -> users)
                if (Str::endsWith($columnName, '_id')) {
                    $relatedTable = Str::plural(Str::before($columnName, '_id'));
                    
                    $relationships[] = new RelationshipData(
                        localKey: $columnName,
                        foreignKey: 'id',
                        foreignTable: $relatedTable,
                        type: 'belongsTo'
                    );
                }
            }
        }
        
        return $relationships;
    }

    /**
     * Parse indexes
     */
    protected function parseIndexes(string $tableDefinition): array
    {
        $indexes = [];
        
        // Parse explicit index definitions
        $indexPattern = '/\$table->index\s*\(\s*([^)]+)\s*\)/';
        
        if (preg_match_all($indexPattern, $tableDefinition, $matches)) {
            foreach ($matches[1] as $indexDef) {
                $indexes[] = [
                    'type' => 'index',
                    'columns' => $this->parseIndexColumns($indexDef)
                ];
            }
        }
        
        return $indexes;
    }

    /**
     * Check if migration has soft deletes
     */
    protected function hasSoftDeletes(string $tableDefinition): bool
    {
        return Str::contains($tableDefinition, '->softDeletes()') || 
               Str::contains($tableDefinition, '->softDeletesTz()');
    }

    /**
     * Check if migration has timestamps
     */
    protected function hasTimestamps(string $tableDefinition): bool
    {
        return Str::contains($tableDefinition, '->timestamps()') || 
               Str::contains($tableDefinition, '->timestampsTz()') ||
               Str::contains($tableDefinition, '->nullableTimestamps()');
    }

    /**
     * Extract default value from chained methods
     */
    protected function extractDefault(array $methods): ?string
    {
        return $methods['default'] ?? null;
    }

    /**
     * Extract length from parameters
     */
    protected function extractLength(array $parameters): ?int
    {
        // For string columns, second parameter is often length
        if (count($parameters) > 1 && is_numeric($parameters[1])) {
            return (int) $parameters[1];
        }
        
        return null;
    }

    /**
     * Extract comment from chained methods
     */
    protected function extractComment(array $methods): ?string
    {
        return $methods['comment'] ?? null;
    }

    /**
     * Extract enum values
     */
    protected function extractEnumValues(string $columnType, array $parameters): ?array
    {
        if ($columnType === 'enum' && count($parameters) > 1) {
            // Parse enum values from parameters
            $enumValues = [];
            for ($i = 1; $i < count($parameters); $i++) {
                $enumValues[] = trim($parameters[$i], '\'"');
            }
            return $enumValues;
        }
        
        return null;
    }

    /**
     * Parse index columns
     */
    protected function parseIndexColumns(string $indexDef): array
    {
        // Simple parsing - can be enhanced
        if (Str::startsWith($indexDef, '[') && Str::endsWith($indexDef, ']')) {
            $columns = trim($indexDef, '[]');
            return array_map(function($col) {
                return trim($col, '\'" ');
            }, explode(',', $columns));
        }
        
        return [trim($indexDef, '\'"')];
    }
}
