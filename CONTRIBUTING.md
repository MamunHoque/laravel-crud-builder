# Contributing to Laravel CRUD Builder

Thank you for considering contributing to the Laravel CRUD Builder! This document provides guidelines and information for contributors.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to [mamun@example.com](mailto:mamun@example.com).

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected vs actual behavior**
- **Environment details** (PHP version, Laravel version, OS)
- **Migration file content** (if relevant)
- **Generated code** (if applicable)
- **Error messages** and stack traces

#### Bug Report Template

```markdown
**Bug Description**
A clear description of the bug.

**To Reproduce**
Steps to reproduce the behavior:
1. Create migration with '...'
2. Run command '...'
3. See error

**Expected Behavior**
What you expected to happen.

**Actual Behavior**
What actually happened.

**Environment**
- PHP Version: 
- Laravel Version: 
- Package Version: 
- OS: 

**Migration Content**
```php
// Your migration content here
```

**Generated Code**
```php
// Generated code that has issues
```

**Error Output**
```
Error messages here
```
```

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- **Clear title and description**
- **Use case** and motivation
- **Detailed explanation** of the proposed feature
- **Examples** of how it would work
- **Alternatives considered**

### Pull Requests

1. **Fork** the repository
2. **Create a feature branch** (`git checkout -b feature/amazing-feature`)
3. **Make your changes**
4. **Add tests** for new functionality
5. **Ensure tests pass** (`composer test`)
6. **Update documentation** if needed
7. **Commit your changes** (`git commit -m 'Add amazing feature'`)
8. **Push to the branch** (`git push origin feature/amazing-feature`)
9. **Open a Pull Request**

#### Pull Request Guidelines

- **One feature per PR** - Keep changes focused
- **Write tests** - All new functionality must have tests
- **Update documentation** - Include README updates if needed
- **Follow coding standards** - Use PSR-12 coding style
- **Write clear commit messages** - Use conventional commit format
- **Keep history clean** - Squash commits if necessary

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 10.0 or higher (for testing)

### Installation

1. Clone your fork:
```bash
git clone https://github.com/your-username/laravel-crud-builder.git
cd laravel-crud-builder
```

2. Install dependencies:
```bash
composer install
```

3. Run tests to ensure everything works:
```bash
composer test
```

### Project Structure

```
src/
├── Console/Commands/     # Artisan commands
├── Data/                # Data transfer objects
├── Generators/          # Code generators
├── Parsers/            # Migration parsers
├── CrudBuilder.php     # Main service class
└── CrudBuilderServiceProvider.php

tests/
├── Feature/            # Integration tests
├── Unit/              # Unit tests
└── TestCase.php       # Base test class

stubs/                 # Code generation templates
config/               # Package configuration
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/MigrationParserTest.php

# Run specific test method
vendor/bin/phpunit --filter test_method_name
```

### Code Style

This project follows PSR-12 coding standards. Run the style fixer:

```bash
# Check code style
composer check-style

# Fix code style
composer fix-style
```

### Static Analysis

We use PHPStan for static analysis:

```bash
composer analyse
```

## Coding Guidelines

### PHP Standards

- Follow **PSR-12** coding style
- Use **strict types** (`declare(strict_types=1);`)
- Add **return type hints** for all methods
- Use **property type hints** where possible
- Write **comprehensive docblocks**

### Testing Standards

- **100% test coverage** for new features
- **Unit tests** for individual components
- **Feature tests** for command functionality
- **Integration tests** for complete workflows
- Use **descriptive test names** (`it_can_parse_complex_migration_with_relationships`)
- **Mock external dependencies**
- **Clean up** generated files in tests

### Documentation Standards

- Update **README.md** for new features
- Add **inline comments** for complex logic
- Include **usage examples** in docblocks
- Update **CHANGELOG.md** for all changes
- Write **clear commit messages**

### Git Workflow

#### Commit Messages

Use conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(parser): add support for polymorphic relationships
fix(generator): handle nullable foreign keys correctly
docs(readme): add configuration examples
test(parser): add tests for enum value parsing
```

#### Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring

## Adding New Features

### Migration Parser Enhancements

When adding new migration parsing capabilities:

1. **Add test migration** in `tests/TestCase.php`
2. **Update parser logic** in `src/Parsers/MigrationParser.php`
3. **Add data structures** in `src/Data/` if needed
4. **Write comprehensive tests**
5. **Update documentation**

### New Code Generators

When adding new generators:

1. **Extend BaseGenerator** class
2. **Create stub template** in `stubs/`
3. **Add generator to CrudBuilder** class
4. **Create command** in `src/Console/Commands/`
5. **Write tests** for the generator
6. **Update configuration** if needed

### New Commands

When adding new Artisan commands:

1. **Extend base command** structure
2. **Add to service provider**
3. **Write feature tests**
4. **Update documentation**

## Release Process

1. **Update version** in `composer.json`
2. **Update CHANGELOG.md** with new version
3. **Create release tag** (`git tag v1.x.x`)
4. **Push tag** (`git push origin v1.x.x`)
5. **Create GitHub release** with changelog
6. **Announce** on relevant channels

## Getting Help

- **GitHub Issues** - For bugs and feature requests
- **GitHub Discussions** - For questions and general discussion
- **Email** - [mamun@example.com](mailto:mamun@example.com) for private matters

## Recognition

Contributors will be recognized in:
- **README.md** contributors section
- **CHANGELOG.md** for significant contributions
- **GitHub releases** notes

Thank you for contributing to make this package better! 🚀
