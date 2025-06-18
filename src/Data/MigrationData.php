<?php

namespace MamunHoque\CrudBuilder\Data;

class MigrationData
{
    protected array $columns = [];

    protected array $relationships = [];

    protected array $indexes = [];

    protected bool $hasSoftDeletes = false;

    protected bool $hasTimestamps = false;

    public function __construct(
        public string $tableName,
        public ?string $migrationFile = null
    ) {}

    /**
     * Set columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * Get all columns
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get fillable columns
     */
    public function getFillableColumns(): array
    {
        return array_filter($this->columns, fn (ColumnData $column) => $column->isFillable());
    }

    /**
     * Get searchable columns
     */
    public function getSearchableColumns(): array
    {
        return array_filter($this->columns, fn (ColumnData $column) => $column->isSearchable());
    }

    /**
     * Get foreign key columns
     */
    public function getForeignKeyColumns(): array
    {
        return array_filter($this->columns, fn (ColumnData $column) => $column->isForeignKey());
    }

    /**
     * Get columns that should have accessors
     */
    public function getAccessorColumns(): array
    {
        return array_filter($this->columns, fn (ColumnData $column) => $column->shouldHaveAccessor());
    }

    /**
     * Get columns with cast types
     */
    public function getCastableColumns(): array
    {
        return array_filter($this->columns, fn (ColumnData $column) => $column->getCastType() !== null);
    }

    /**
     * Set relationships
     */
    public function setRelationships(array $relationships): void
    {
        $this->relationships = $relationships;
    }

    /**
     * Get all relationships
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Get belongsTo relationships
     */
    public function getBelongsToRelationships(): array
    {
        return array_filter($this->relationships, fn (RelationshipData $rel) => $rel->type === 'belongsTo');
    }

    /**
     * Get relationships that should be eager loaded
     */
    public function getEagerLoadRelationships(): array
    {
        return array_filter($this->relationships, fn (RelationshipData $rel) => $rel->shouldEagerLoad());
    }

    /**
     * Set indexes
     */
    public function setIndexes(array $indexes): void
    {
        $this->indexes = $indexes;
    }

    /**
     * Get indexes
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Set soft deletes flag
     */
    public function setHasSoftDeletes(bool $hasSoftDeletes): void
    {
        $this->hasSoftDeletes = $hasSoftDeletes;
    }

    /**
     * Check if table has soft deletes
     */
    public function hasSoftDeletes(): bool
    {
        return $this->hasSoftDeletes;
    }

    /**
     * Set timestamps flag
     */
    public function setHasTimestamps(bool $hasTimestamps): void
    {
        $this->hasTimestamps = $hasTimestamps;
    }

    /**
     * Check if table has timestamps
     */
    public function hasTimestamps(): bool
    {
        return $this->hasTimestamps;
    }

    /**
     * Get model name from table name
     */
    public function getModelName(): string
    {
        return str()->studly(str()->singular($this->tableName));
    }

    /**
     * Get controller name
     */
    public function getControllerName(): string
    {
        return $this->getModelName().'Controller';
    }

    /**
     * Get service name
     */
    public function getServiceName(): string
    {
        return $this->getModelName().'Service';
    }

    /**
     * Get resource name
     */
    public function getResourceName(): string
    {
        return $this->getModelName().'Resource';
    }

    /**
     * Get request names
     */
    public function getRequestNames(): array
    {
        $modelName = $this->getModelName();

        return [
            'store' => "Store{$modelName}Request",
            'update' => "Update{$modelName}Request",
        ];
    }

    /**
     * Get validation rules for store request
     */
    public function getStoreValidationRules(): array
    {
        $rules = [];

        foreach ($this->getFillableColumns() as $column) {
            $rules[$column->name] = $column->getValidationRules(false);
        }

        return $rules;
    }

    /**
     * Get validation rules for update request
     */
    public function getUpdateValidationRules(): array
    {
        $rules = [];

        foreach ($this->getFillableColumns() as $column) {
            $rules[$column->name] = $column->getValidationRules(true);
        }

        return $rules;
    }

    /**
     * Get all use statements needed for the model
     */
    public function getModelUseStatements(): array
    {
        $uses = [
            'Illuminate\Database\Eloquent\Model',
            'Illuminate\Database\Eloquent\Factories\HasFactory',
        ];

        if ($this->hasSoftDeletes()) {
            $uses[] = 'Illuminate\Database\Eloquent\SoftDeletes';
        }

        // Add relationship use statements
        foreach ($this->relationships as $relationship) {
            $uses = array_merge($uses, $relationship->getUseStatements());
        }

        return array_unique($uses);
    }

    /**
     * Get model traits
     */
    public function getModelTraits(): array
    {
        $traits = ['HasFactory'];

        if ($this->hasSoftDeletes()) {
            $traits[] = 'SoftDeletes';
        }

        return $traits;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'tableName' => $this->tableName,
            'migrationFile' => $this->migrationFile,
            'columns' => array_map(fn (ColumnData $col) => $col->toArray(), $this->columns),
            'relationships' => array_map(fn (RelationshipData $rel) => $rel->toArray(), $this->relationships),
            'indexes' => $this->indexes,
            'hasSoftDeletes' => $this->hasSoftDeletes,
            'hasTimestamps' => $this->hasTimestamps,
        ];
    }
}
