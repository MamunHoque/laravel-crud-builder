# Installation Guide

This guide will walk you through installing and setting up the Advanced Laravel CRUD Builder package.

## Requirements

Before installing, ensure your system meets these requirements:

- **PHP**: 8.1 or higher
- **Laravel**: 10.0 or higher
- **Composer**: Latest version recommended
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

## Installation Steps

### 1. Install via Composer

```bash
composer require mamunhoque/laravel-crud-builder
```

### 2. Publish Configuration (Optional)

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --tag="crud-builder-config"
```

This creates `config/crud-builder.php` where you can customize:
- Default generation settings
- File paths and namespaces
- Route middleware configurations
- Validation preferences
- And much more

### 3. Publish Stubs (Optional)

If you want to customize the code generation templates:

```bash
php artisan vendor:publish --tag="crud-builder-stubs"
```

This publishes stub files to `resources/stubs/crud-builder/` for customization.

### 4. Verify Installation

Check that the commands are available:

```bash
php artisan list make
```

You should see the new CRUD builder commands:
- `make:advanced-crud`
- `make:crud-controller`
- `make:crud-model`
- `make:crud-request`
- `make:crud-resource`
- `make:crud-service`
- `make:crud-test`

Also check for the HelperTrait command:
```bash
php artisan list crud-builder
```

You should see:
- `crud-builder:publish-helper-trait`

### 5. HelperTrait Auto-Publishing

The package automatically publishes the `HelperTrait` to `app/Traits/HelperTrait.php` when the service provider boots. This trait contains useful methods for:

- Pagination and sorting
- Search functionality
- File uploads (S3 and local)
- API response formatting
- E-commerce specific filters

If you need to republish the trait:

```bash
php artisan crud-builder:publish-helper-trait --force
```

## Configuration

### Basic Configuration

The default configuration works for most Laravel projects. Key settings include:

```php
// config/crud-builder.php
return [
    'defaults' => [
        'generate_model' => true,
        'generate_controller' => true,
        'generate_service' => true,
        'generate_requests' => true,
        'generate_resource' => true,
        'generate_tests' => true,
        'generate_routes' => true,
        'generate_factory' => true,
    ],
    
    'routes' => [
        'middleware_groups' => [
            'admin' => ['auth:api', 'role:admin'],
            'public' => [],
            'auth' => ['auth:api'],
        ],
        'default_middleware' => 'admin',
    ],
];
```

### Custom Paths

If your project uses non-standard paths:

```php
'paths' => [
    'models' => 'app/Models',
    'controllers' => 'app/Http/Controllers/Api',  // Custom API path
    'services' => 'app/Services',
    'requests' => 'app/Http/Requests',
    'resources' => 'app/Http/Resources',
    'tests' => 'tests/Feature',
    'unit_tests' => 'tests/Unit',
    'factories' => 'database/factories',
],
```

### Custom Namespaces

Adjust namespaces to match your project structure:

```php
'namespaces' => [
    'models' => 'App\\Models',
    'controllers' => 'App\\Http\\Controllers\\Api',
    'services' => 'App\\Services',
    'requests' => 'App\\Http\\Requests',
    'resources' => 'App\\Http\\Resources',
    'tests' => 'Tests\\Feature',
    'unit_tests' => 'Tests\\Unit',
],
```

## First Usage

### 1. Create a Migration

First, create a migration for your model:

```bash
php artisan make:migration create_posts_table
```

Define your table structure:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content')->nullable();
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
    $table->softDeletes();
});
```

### 2. Run the Migration

```bash
php artisan migrate
```

### 3. Generate CRUD

```bash
php artisan make:advanced-crud Post
```

This generates all CRUD components based on your migration!

## Troubleshooting

### Command Not Found

If commands aren't available:

1. Clear Laravel's cache:
```bash
php artisan config:clear
php artisan cache:clear
```

2. Ensure the service provider is registered (auto-discovery should handle this).

### Migration Not Found

If you get "Migration file not found":

1. Ensure your migration file exists in `database/migrations/`
2. Check the migration file name follows Laravel conventions: `create_{table_name}_table`
3. Verify the table name matches your model name (pluralized)

### Permission Issues

If you encounter file permission errors:

1. Ensure Laravel has write permissions to the target directories
2. Check your `app/`, `database/`, and `tests/` directories are writable

### Namespace Issues

If generated code has incorrect namespaces:

1. Check your `config/crud-builder.php` namespace settings
2. Ensure your `composer.json` PSR-4 autoloading is correct
3. Run `composer dump-autoload`

## Advanced Setup

### Custom Middleware

To use custom middleware groups:

```php
'routes' => [
    'middleware_groups' => [
        'api-v1' => ['auth:sanctum', 'throttle:60,1'],
        'admin' => ['auth:sanctum', 'role:admin'],
        'public' => ['throttle:100,1'],
    ],
],
```

### Custom Validation Rules

Override default validation generation:

```php
'validation' => [
    'generate_smart_rules' => true,
    'include_unique_rules' => true,
    'include_foreign_key_rules' => true,
    'nullable_fields_optional' => true,
],
```

### Database-Specific Settings

For PostgreSQL or other databases:

```php
'database' => [
    'support_postgresql' => true,
    'support_mysql' => true,
    'support_sqlite' => true,
],
```

## Next Steps

1. **Read the Documentation**: Check the [README.md](README.md) for detailed usage examples
2. **Explore Configuration**: Review all available options in `config/crud-builder.php`
3. **Customize Stubs**: Modify the stub files to match your coding style
4. **Run Tests**: Ensure everything works with `composer test`

## Getting Help

- **Documentation**: [README.md](README.md)
- **Issues**: [GitHub Issues](https://github.com/mamunhoque/laravel-crud-builder/issues)
- **Discussions**: [GitHub Discussions](https://github.com/mamunhoque/laravel-crud-builder/discussions)

Happy coding! 🚀
