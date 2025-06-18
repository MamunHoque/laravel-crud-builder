<?php

namespace MamunHoque\CrudBuilder\Data;

class ColumnData
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public ?string $default = null,
        public ?int $length = null,
        public bool $unsigned = false,
        public bool $unique = false,
        public bool $index = false,
        public ?string $comment = null,
        public ?array $enumValues = null,
        public bool $autoIncrement = false,
        public bool $primary = false
    ) {}

    /**
     * Check if column is a foreign key
     */
    public function isForeignKey(): bool
    {
        return str_ends_with($this->name, '_id') || $this->type === 'foreignId';
    }

    /**
     * Check if column is fillable (should be included in model's fillable array)
     */
    public function isFillable(): bool
    {
        // Exclude system columns
        $excludedColumns = [
            'id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at',
            'remember_token', 'password',
        ];

        return ! in_array($this->name, $excludedColumns) && ! $this->autoIncrement;
    }

    /**
     * Check if column is searchable
     */
    public function isSearchable(): bool
    {
        $searchableTypes = ['string', 'text', 'longText', 'mediumText', 'tinyText'];
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'hash'];

        return in_array($this->type, $searchableTypes) &&
               ! $this->containsSensitiveKeyword($sensitiveFields);
    }

    /**
     * Check if column contains sensitive keywords
     */
    protected function containsSensitiveKeyword(array $keywords): bool
    {
        $lowercaseName = strtolower($this->name);

        foreach ($keywords as $keyword) {
            if (str_contains($lowercaseName, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get validation rules for this column
     */
    public function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [];

        // Required rule
        if (! $this->nullable && ! $isUpdate) {
            $rules[] = 'required';
        } elseif ($this->nullable || $isUpdate) {
            $rules[] = 'nullable';
        }

        // Type-specific rules
        switch ($this->type) {
            case 'string':
            case 'char':
                $rules[] = 'string';
                if ($this->length) {
                    $rules[] = "max:{$this->length}";
                }
                break;

            case 'text':
            case 'longText':
            case 'mediumText':
            case 'tinyText':
                $rules[] = 'string';
                break;

            case 'integer':
            case 'bigInteger':
            case 'mediumInteger':
            case 'smallInteger':
            case 'tinyInteger':
                $rules[] = 'integer';
                if ($this->unsigned) {
                    $rules[] = 'min:0';
                }
                break;

            case 'decimal':
            case 'double':
            case 'float':
                $rules[] = 'numeric';
                if ($this->unsigned) {
                    $rules[] = 'min:0';
                }
                break;

            case 'boolean':
                $rules[] = 'boolean';
                break;

            case 'date':
                $rules[] = 'date';
                break;

            case 'dateTime':
            case 'timestamp':
                $rules[] = 'date';
                break;

            case 'email':
                $rules[] = 'email';
                break;

            case 'json':
            case 'jsonb':
                $rules[] = 'array';
                break;

            case 'enum':
                if ($this->enumValues) {
                    $values = implode(',', $this->enumValues);
                    $rules[] = "in:{$values}";
                }
                break;

            case 'foreignId':
                if ($this->isForeignKey()) {
                    $tableName = $this->getForeignTableName();
                    $rules[] = "exists:{$tableName},id";
                }
                break;
        }

        // Unique rule
        if ($this->unique && ! $isUpdate) {
            $rules[] = 'unique:'.$this->getTableName().','.$this->name;
        }

        return $rules;
    }

    /**
     * Get the foreign table name for foreign key columns
     */
    public function getForeignTableName(): ?string
    {
        if (! $this->isForeignKey()) {
            return null;
        }

        // Remove '_id' suffix and pluralize
        $baseName = str_replace('_id', '', $this->name);

        return str()->plural($baseName);
    }

    /**
     * Get the table name (placeholder - should be injected)
     */
    protected function getTableName(): string
    {
        // This would need to be injected or determined from context
        return 'table_name';
    }

    /**
     * Get PHP type hint for this column
     */
    public function getPhpType(): string
    {
        return match ($this->type) {
            'boolean' => 'bool',
            'integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger' => 'int',
            'decimal', 'double', 'float' => 'float',
            'json', 'jsonb' => 'array',
            'date', 'dateTime', 'timestamp' => '\\Carbon\\Carbon',
            default => 'string'
        };
    }

    /**
     * Get cast type for Eloquent model
     */
    public function getCastType(): ?string
    {
        return match ($this->type) {
            'boolean' => 'boolean',
            'integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger' => 'integer',
            'decimal', 'double', 'float' => 'decimal:2',
            'json', 'jsonb' => 'array',
            'date' => 'date',
            'dateTime', 'timestamp' => 'datetime',
            default => null
        };
    }

    /**
     * Check if column should have an accessor
     */
    public function shouldHaveAccessor(): bool
    {
        // Generate accessors for file/image columns
        $fileColumns = ['image', 'photo', 'avatar', 'file', 'document', 'attachment'];

        foreach ($fileColumns as $fileColumn) {
            if (str_contains(strtolower($this->name), $fileColumn)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'default' => $this->default,
            'length' => $this->length,
            'unsigned' => $this->unsigned,
            'unique' => $this->unique,
            'index' => $this->index,
            'comment' => $this->comment,
            'enumValues' => $this->enumValues,
            'autoIncrement' => $this->autoIncrement,
            'primary' => $this->primary,
        ];
    }
}
