# PHPStan Configuration

This project uses [PHPStan](https://phpstan.org/) for static code analysis at level 8 (the strictest level).

## Running PHPStan

Run static analysis:

```bash
composer stan
```

Or use PHPStan directly:

```bash
vendor/bin/phpstan analyse
```

## Configuration

PHPStan is configured via `phpstan.neon` with the following settings:

- **Analysis Level**: 8 (strictest)
- **Analyzed Paths**: `src/` directory
- **Excluded Paths**: `tests/` directory
- **Bootstrap Files**: CakePHP Core and I18n functions for proper analysis

### Ignored Errors

The configuration ignores some errors that are expected in CakePHP applications:

1. **I18n Functions** - CakePHP translation functions like `__d()` are loaded via bootstrap files

These are standard patterns in CakePHP and don't represent actual errors.

## Generating Baseline

If you need to add PHPStan to an existing project with many errors, you can generate a baseline:

```bash
vendor/bin/phpstan analyse --generate-baseline
```

This creates `phpstan-baseline.neon` which allows you to gradually fix issues without blocking CI.

## CI Integration

Add PHPStan to your continuous integration:

```yaml
# GitHub Actions example
- name: PHPStan Analysis
  run: composer stan
```

```yaml
# GitLab CI example
phpstan:
  script:
    - composer install
    - composer stan
```

## IDE Integration

### PHPStorm

1. Go to Settings → PHP → Quality Tools → PHPStan
2. Point to `vendor/bin/phpstan`
3. Enable automatic inspection

### VS Code

Install the PHPStan extension:

```bash
code --install-extension calsmurf2904.vscode-phpstan
```

## Level Guidelines

PHPStan has 10 levels (0-9). This project uses level 8:

- **Level 0**: Basic checks
- **Level 4**: Dead code detection
- **Level 6**: Type inference
- **Level 8**: Strict type checking (used here)
- **Level 9**: Mixed type checks (very strict)

## Common Issues

### Out of Memory

If PHPStan runs out of memory:

```bash
# Increase memory limit
composer stan -- --memory-limit=1G
```

### False Positives

If you encounter false positives, you can add them to `ignoreErrors` in `phpstan.neon`:

```neon
parameters:
    ignoreErrors:
        - '#Your error pattern here#'
```

### Excluding Files

To exclude specific files or directories:

```neon
parameters:
    excludePaths:
        - src/Legacy/*
        - src/Generated/*
```

## Benefits

Running PHPStan helps catch:

- Type errors before runtime
- Undefined methods and properties
- Dead code
- Incorrect PHPDoc types
- Logic errors
- Parameter type mismatches

## More Information

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPStan Rule Levels](https://phpstan.org/user-guide/rule-levels)
- [PHPStan for CakePHP](https://github.com/CakeDC/cakephp-phpstan)
