<?php

namespace MamunHoque\CrudBuilder\Tests\Unit;

use MamunHoque\CrudBuilder\Parsers\MigrationParser;
use MamunHoque\CrudBuilder\Tests\TestCase;

class MigrationParserTest extends TestCase
{
    protected MigrationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MigrationParser();
    }

    /** @test */
    public function it_can_parse_migration_file()
    {
        $migrationData = $this->parser->parse('test_models');

        $this->assertNotNull($migrationData);
        $this->assertEquals('test_models', $migrationData->tableName);
    }

    /** @test */
    public function it_returns_null_for_non_existent_table()
    {
        $migrationData = $this->parser->parse('non_existent_table');

        $this->assertNull($migrationData);
    }

    /** @test */
    public function it_parses_columns_correctly()
    {
        $migrationData = $this->parser->parse('test_models');

        $this->assertNotNull($migrationData);
        
        $columns = $migrationData->getColumns();
        $this->assertNotEmpty($columns);

        // Check for specific columns
        $columnNames = array_map(fn($col) => $col->name, $columns);
        
        $this->assertContains('name', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertContains('description', $columnNames);
        $this->assertContains('is_active', $columnNames);
        $this->assertContains('price', $columnNames);
        $this->assertContains('status', $columnNames);
        $this->assertContains('category_id', $columnNames);
        $this->assertContains('metadata', $columnNames);
    }

    /** @test */
    public function it_identifies_fillable_columns()
    {
        $migrationData = $this->parser->parse('test_models');
        $fillableColumns = $migrationData->getFillableColumns();

        $fillableNames = array_map(fn($col) => $col->name, $fillableColumns);

        // Should include regular columns
        $this->assertContains('name', $fillableNames);
        $this->assertContains('email', $fillableNames);
        
        // Should exclude system columns
        $this->assertNotContains('id', $fillableNames);
        $this->assertNotContains('created_at', $fillableNames);
        $this->assertNotContains('updated_at', $fillableNames);
        $this->assertNotContains('deleted_at', $fillableNames);
    }

    /** @test */
    public function it_identifies_searchable_columns()
    {
        $migrationData = $this->parser->parse('test_models');
        $searchableColumns = $migrationData->getSearchableColumns();

        $searchableNames = array_map(fn($col) => $col->name, $searchableColumns);

        // Should include text/string columns
        $this->assertContains('name', $searchableNames);
        $this->assertContains('email', $searchableNames);
        $this->assertContains('description', $searchableNames);
        
        // Should exclude non-text columns
        $this->assertNotContains('is_active', $searchableNames);
        $this->assertNotContains('price', $searchableNames);
    }

    /** @test */
    public function it_identifies_foreign_key_columns()
    {
        $migrationData = $this->parser->parse('test_models');
        $foreignKeyColumns = $migrationData->getForeignKeyColumns();

        $foreignKeyNames = array_map(fn($col) => $col->name, $foreignKeyColumns);

        $this->assertContains('category_id', $foreignKeyNames);
    }

    /** @test */
    public function it_detects_soft_deletes()
    {
        $migrationData = $this->parser->parse('test_models');

        $this->assertTrue($migrationData->hasSoftDeletes());
    }

    /** @test */
    public function it_detects_timestamps()
    {
        $migrationData = $this->parser->parse('test_models');

        $this->assertTrue($migrationData->hasTimestamps());
    }

    /** @test */
    public function it_parses_relationships()
    {
        $migrationData = $this->parser->parse('test_models');
        $relationships = $migrationData->getRelationships();

        $this->assertNotEmpty($relationships);
        
        // Should have relationship for category_id
        $relationshipTables = array_map(fn($rel) => $rel->foreignTable, $relationships);
        $this->assertContains('categories', $relationshipTables);
    }

    /** @test */
    public function it_generates_validation_rules()
    {
        $migrationData = $this->parser->parse('test_models');
        
        $storeRules = $migrationData->getStoreValidationRules();
        $updateRules = $migrationData->getUpdateValidationRules();

        $this->assertNotEmpty($storeRules);
        $this->assertNotEmpty($updateRules);

        // Check specific rules
        $this->assertArrayHasKey('name', $storeRules);
        $this->assertArrayHasKey('email', $storeRules);
        
        // Email should have email validation
        $this->assertContains('email', $storeRules['email']);
        
        // Enum should have in validation
        $this->assertContains('in:draft,published,archived', $storeRules['status']);
    }
}
