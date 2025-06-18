<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings for the CRUD builder. You can override
    | these settings by passing options to the command or by modifying
    | this configuration file.
    |
    */

    'defaults' => [
        'generate_model' => true,
        'generate_controller' => true,
        'generate_service' => true,
        'generate_requests' => true,
        'generate_resource' => true,
        'generate_tests' => true,
        'generate_routes' => true,
        'generate_factory' => true,
        'generate_seeder' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where the generated files should be placed.
    |
    */

    'paths' => [
        'models' => 'app/Models',
        'controllers' => 'app/Http/Controllers',
        'services' => 'app/Services',
        'requests' => 'app/Http/Requests',
        'resources' => 'app/Http/Resources',
        'tests' => 'tests/Feature',
        'unit_tests' => 'tests/Unit',
        'factories' => 'database/factories',
        'seeders' => 'database/seeders',
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the namespaces for generated classes.
    |
    */

    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers',
        'services' => 'App\\Services',
        'requests' => 'App\\Http\\Requests',
        'resources' => 'App\\Http\\Resources',
        'tests' => 'Tests\\Feature',
        'unit_tests' => 'Tests\\Unit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how routes should be generated and organized.
    |
    */

    'routes' => [
        'file' => 'routes/api.php',
        'middleware_groups' => [
            'admin' => ['auth:api', 'role:admin'],
            'public' => [],
            'auth' => ['auth:api'],
        ],
        'default_middleware' => 'admin',
        'prefix' => [
            'admin' => 'admin',
            'public' => '',
            'auth' => 'api',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure model generation settings.
    |
    */

    'model' => [
        'use_soft_deletes' => true,
        'use_timestamps' => true,
        'use_uuid' => false,
        'fillable_guarded' => 'fillable', // 'fillable', 'guarded', or 'both'
        'generate_relationships' => true,
        'generate_accessors' => true,
        'generate_mutators' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller Configuration
    |--------------------------------------------------------------------------
    |
    | Configure controller generation settings.
    |
    */

    'controller' => [
        'use_form_requests' => true,
        'use_resources' => true,
        'use_service_layer' => true,
        'include_authorization' => false,
        'api_only' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure service layer generation settings.
    |
    */

    'service' => [
        'use_repository_pattern' => false,
        'include_caching' => false,
        'include_events' => false,
        'include_logging' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how validation rules are generated.
    |
    */

    'validation' => [
        'generate_smart_rules' => true,
        'include_unique_rules' => true,
        'include_foreign_key_rules' => true,
        'nullable_fields_optional' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Configuration
    |--------------------------------------------------------------------------
    |
    | Configure test generation settings.
    |
    */

    'tests' => [
        'generate_feature_tests' => true,
        'generate_unit_tests' => true,
        'include_factory_usage' => true,
        'test_all_endpoints' => true,
        'include_validation_tests' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database-specific settings.
    |
    */

    'database' => [
        'support_postgresql' => true,
        'support_mysql' => true,
        'support_sqlite' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Style Configuration
    |--------------------------------------------------------------------------
    |
    | Configure code generation style preferences.
    |
    */

    'code_style' => [
        'use_strict_types' => true,
        'use_return_types' => true,
        'use_property_types' => true,
        'include_docblocks' => true,
        'psr12_compliant' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtering Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic filtering generation.
    |
    */

    'filtering' => [
        'generate_search_filters' => true,
        'generate_relationship_filters' => true,
        'exclude_sensitive_fields' => ['password', 'token', 'secret', 'key'],
        'searchable_field_types' => ['string', 'text', 'varchar'],
    ],
];
