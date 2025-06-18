<?php

namespace MamunHoque\CrudBuilder\Data;

class RelationshipData
{
    public function __construct(
        public string $localKey,
        public string $foreignKey,
        public string $foreignTable,
        public string $type,
        public ?string $relationshipName = null,
        public ?string $relatedModel = null
    ) {
        $this->relationshipName = $this->relationshipName ?? $this->generateRelationshipName();
        $this->relatedModel = $this->relatedModel ?? $this->generateRelatedModel();
    }

    /**
     * Generate relationship method name
     */
    protected function generateRelationshipName(): string
    {
        switch ($this->type) {
            case 'belongsTo':
                // user_id -> user
                return str_replace('_id', '', $this->localKey);
                
            case 'hasMany':
                // users table -> users
                return $this->foreignTable;
                
            case 'hasOne':
                // user table -> user
                return str()->singular($this->foreignTable);
                
            case 'belongsToMany':
                // categories -> categories
                return $this->foreignTable;
                
            default:
                return $this->foreignTable;
        }
    }

    /**
     * Generate related model class name
     */
    protected function generateRelatedModel(): string
    {
        $modelName = str()->singular($this->foreignTable);
        return str()->studly($modelName);
    }

    /**
     * Get the relationship method code
     */
    public function getRelationshipMethod(): string
    {
        $methodName = $this->relationshipName;
        $relatedModel = $this->relatedModel;
        
        switch ($this->type) {
            case 'belongsTo':
                return "public function {$methodName}(): BelongsTo\n    {\n        return \$this->belongsTo({$relatedModel}::class, '{$this->localKey}', '{$this->foreignKey}');\n    }";
                
            case 'hasMany':
                return "public function {$methodName}(): HasMany\n    {\n        return \$this->hasMany({$relatedModel}::class, '{$this->localKey}', '{$this->foreignKey}');\n    }";
                
            case 'hasOne':
                return "public function {$methodName}(): HasOne\n    {\n        return \$this->hasOne({$relatedModel}::class, '{$this->localKey}', '{$this->foreignKey}');\n    }";
                
            case 'belongsToMany':
                return "public function {$methodName}(): BelongsToMany\n    {\n        return \$this->belongsToMany({$relatedModel}::class);\n    }";
                
            default:
                return '';
        }
    }

    /**
     * Get required use statements for the relationship
     */
    public function getUseStatements(): array
    {
        $uses = ["Illuminate\\Database\\Eloquent\\Relations\\{$this->getRelationshipClass()}"];
        
        // Add related model use statement if it's in a different namespace
        if ($this->relatedModel !== 'User') { // Assuming User is always available
            $uses[] = "App\\Models\\{$this->relatedModel}";
        }
        
        return $uses;
    }

    /**
     * Get the relationship class name
     */
    protected function getRelationshipClass(): string
    {
        return match ($this->type) {
            'belongsTo' => 'BelongsTo',
            'hasMany' => 'HasMany',
            'hasOne' => 'HasOne',
            'belongsToMany' => 'BelongsToMany',
            default => 'Relation'
        };
    }

    /**
     * Check if this relationship should be eager loaded by default
     */
    public function shouldEagerLoad(): bool
    {
        // Typically belongsTo relationships are good candidates for eager loading
        return $this->type === 'belongsTo';
    }

    /**
     * Get filter method for this relationship
     */
    public function getFilterMethod(): ?string
    {
        if ($this->type !== 'belongsTo') {
            return null;
        }

        $filterName = $this->localKey;
        $methodName = "filter" . str()->studly($filterName);
        
        return "protected function {$methodName}(Builder \$query, \$value): Builder\n    {\n        return \$query->where('{$this->localKey}', \$value);\n    }";
    }

    /**
     * Get the inverse relationship data
     */
    public function getInverseRelationship(string $currentTable): ?RelationshipData
    {
        switch ($this->type) {
            case 'belongsTo':
                return new RelationshipData(
                    localKey: $this->foreignKey,
                    foreignKey: $this->localKey,
                    foreignTable: $currentTable,
                    type: 'hasMany'
                );
                
            case 'hasMany':
                return new RelationshipData(
                    localKey: $this->foreignKey,
                    foreignKey: $this->localKey,
                    foreignTable: $currentTable,
                    type: 'belongsTo'
                );
                
            case 'hasOne':
                return new RelationshipData(
                    localKey: $this->foreignKey,
                    foreignKey: $this->localKey,
                    foreignTable: $currentTable,
                    type: 'belongsTo'
                );
                
            default:
                return null;
        }
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'localKey' => $this->localKey,
            'foreignKey' => $this->foreignKey,
            'foreignTable' => $this->foreignTable,
            'type' => $this->type,
            'relationshipName' => $this->relationshipName,
            'relatedModel' => $this->relatedModel,
        ];
    }
}
