# Laravel CRUD Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mamunhoque/laravel-crud-builder.svg?style=flat-square)](https://packagist.org/packages/mamunhoque/laravel-crud-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/mamunhoque/laravel-crud-builder/run-tests?label=tests)](https://github.com/mamunhoque/laravel-crud-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/mamunhoque/laravel-crud-builder/Check%20&%20fix%20styling?label=code%20style)](https://github.com/mamunhoque/laravel-crud-builder/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mamunhoque/laravel-crud-builder.svg?style=flat-square)](https://packagist.org/packages/mamunhoque/laravel-crud-builder)

An advanced Laravel package that automatically generates complete CRUD operations with intelligent migration parsing and sophisticated code generation. This package goes beyond simple scaffolding by analyzing your existing migration files and generating production-ready code that follows Laravel best practices.

## Features

🚀 **Intelligent Migration Parsing**
- Sophisticated analysis of migration files beyond simple regex
- Handles complex column definitions, relationships, and constraints
- Supports both PostgreSQL and MySQL/MariaDB syntax
- Parses foreign keys, indexes, and enum values

🧠 **Smart Code Generation**
- Generates appropriate validation rules based on column types and constraints
- Creates dynamic search keys from text/varchar columns
- Auto-detects and handles file upload fields
- Generates API Resource classes for consistent response formatting
- Implements relationship-aware filtering

🎯 **Complete CRUD Stack**
- **Models** with fillable attributes, relationships, and accessors
- **Controllers** with proper error handling and resource responses
- **Services** with business logic and filtering capabilities
- **Form Requests** with intelligent validation rules
- **API Resources** for consistent data transformation
- **Factories** with realistic fake data generation
- **Tests** (Feature and Unit) with comprehensive coverage

⚙️ **Advanced Configuration**
- Support for multiple middleware groups (admin, public, auth, custom)
- Configurable route prefixes and naming conventions
- Flexible file paths and namespaces
- Soft deletes and timestamps support
- Database compatibility for PostgreSQL, MySQL, and SQLite

## Installation

You can install the package via Composer:

```bash
composer require mamunhoque/laravel-crud-builder
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="crud-builder-config"
```

Optionally, publish the stub files for customization:

```bash
php artisan vendor:publish --tag="crud-builder-stubs"
```

The package automatically publishes the `HelperTrait` to `app/Traits/HelperTrait.php` when installed. If you need to republish it:

```bash
php artisan crud-builder:publish-helper-trait
```

## Quick Start

### Basic Usage

Generate a complete CRUD for a model based on an existing migration:

```bash
php artisan make:advanced-crud Post
```

This will generate:
- `app/Models/Post.php`
- `app/Http/Controllers/PostController.php`
- `app/Services/PostService.php`
- `app/Http/Requests/StorePostRequest.php`
- `app/Http/Requests/UpdatePostRequest.php`
- `app/Http/Resources/PostResource.php`
- `database/factories/PostFactory.php`
- `tests/Feature/PostControllerTest.php`
- `tests/Unit/PostTest.php`
- Routes added to `routes/api.php`

### Selective Generation

Generate only specific components:

```bash
# Generate only model and service
php artisan make:advanced-crud Post --model --service

# Generate everything except tests
php artisan make:advanced-crud Post --all --no-tests
```

### Middleware and Route Configuration

```bash
# Generate for public API (no authentication)
php artisan make:advanced-crud Post --middleware=public

# Generate for authenticated users
php artisan make:advanced-crud Post --middleware=auth

# Custom route prefix
php artisan make:advanced-crud Post --prefix=v1/api
```

## Individual Commands

Generate components individually:

```bash
# Generate model only
php artisan make:crud-model Post

# Generate service only
php artisan make:crud-service Post

# Generate controller only
php artisan make:crud-controller Post

# Generate request classes only
php artisan make:crud-request Post

# Generate API resource only
php artisan make:crud-resource Post

# Generate tests only
php artisan make:crud-test Post
```

## Migration Requirements

The package analyzes your existing migration files. Ensure your migration follows Laravel conventions:

```php
// Example migration: create_posts_table.php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content')->nullable();
    $table->string('featured_image')->nullable();
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
    $table->boolean('is_featured')->default(false);
    $table->decimal('price', 8, 2)->nullable();
    $table->foreignId('category_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

## Generated Code Examples

### Model with Relationships

```php
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'featured_image', 'status', 
        'is_featured', 'price', 'category_id', 'user_id', 'metadata'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected $appends = ['featured_image_url'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featured_image) {
            return config('filesystems.disks.s3.url') . '/' . $this->featured_image;
        }
        return null;
    }
}
```

### Service with Intelligent Filtering

```php
class PostService
{
    use HelperTrait;

    public function index($request): Collection|LengthAwarePaginator|array
    {
        $query = Post::query();
        
        $query->with(['category', 'user']);

        // Sorting
        $this->applySorting($query, $request);

        // Searching
        $searchKeys = ['title', 'content'];
        $this->applySearch($query, $request->input('search'), $searchKeys);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginateOrGet($query, $request);
    }
}
```

### Smart Validation Rules

```php
class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts,slug',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'nullable|exists:users,id',
            'metadata' => 'nullable|array',
        ];
    }
}
```

## Configuration

The package is highly configurable. Here are some key configuration options:

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

    'model' => [
        'use_soft_deletes' => true,
        'use_timestamps' => true,
        'generate_relationships' => true,
        'generate_accessors' => true,
    ],

    'validation' => [
        'generate_smart_rules' => true,
        'include_unique_rules' => true,
        'include_foreign_key_rules' => true,
    ],
];
```

## Advanced Features

### Relationship-Aware Filtering

The package automatically generates filters for relationships:

```php
// Automatically generated in service
if ($request->filled('category_id')) {
    $query->where('category_id', $request->input('category_id'));
}
```

### File Upload Detection

Automatically detects file upload fields and generates appropriate handling:

```php
// In service
$data['featured_image'] = $this->s3FileUpload($request, 'featured_image', 'posts')['path'] ?? null;

// In model
public function getFeaturedImageUrlAttribute(): ?string
{
    if ($this->featured_image) {
        return config('filesystems.disks.s3.url') . '/' . $this->featured_image;
    }
    return null;
}
```

### HelperTrait Features

The package includes a comprehensive `HelperTrait` with useful methods:

```php
// Pagination and filtering
$this->applySorting($query, $request);
$this->applySearch($query, $searchValue, $searchKeys);
$this->paginateOrGet($query, $request);

// File uploads
$this->s3FileUpload($request, 'image', 'uploads');
$this->localFileUpload($request, 'file', 'documents');

// API responses
$this->successResponse($data, 'Success message');
$this->errorResponse($errors, 'Error message', 422);

// E-commerce specific filters
$this->applyEComFilters($query, $request);
$this->applyEComSorting($query, $request);
```

### Comprehensive Testing

Generates both feature and unit tests:

```php
// Feature test
public function it_can_create_post()
{
    $data = [
        'title' => fake()->sentence(),
        'content' => fake()->paragraph(),
        'status' => fake()->randomElement(['draft', 'published', 'archived']),
        'category_id' => Category::factory()->create()->id,
    ];

    $response = $this->postJson(route('admin.posts.store'), $data);

    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', $data);
}
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Existing migration files for the models you want to generate

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mamun Hoque](https://github.com/mamunhoque)
- [All Contributors](../../contributors)

## Roadmap

- [ ] Support for API versioning
- [ ] GraphQL schema generation
- [ ] Custom stub templates
- [ ] Integration with Laravel Sanctum
- [ ] Automatic API documentation generation
- [ ] Support for polymorphic relationships
- [ ] Database seeder generation
- [ ] Integration with Laravel Horizon for queued operations

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
