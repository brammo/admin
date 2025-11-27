# Psalm Configuration

This project uses [Psalm](https://psalm.dev/) for static code analysis at error level 8.

## Running Psalm

Run static analysis:

```bash
composer psalm
```

Or use Psalm directly:

```bash
vendor/bin/psalm --show-info=false
```

To see all issues including informational ones:

```bash
vendor/bin/psalm
```

## Configuration

Psalm is configured via `psalm.xml` with the following settings:

- **Error Level**: 8 (least strict)
- **Analyzed Paths**: `src/` directory
- **Ignored Paths**: `vendor/` directory
- **Stubs**: CakePHP functions file for proper analysis
- **Target PHP Version**: 8.1 (inferred from composer.json)

### Generating Baseline

If you need to add Psalm to an existing project with many errors, you can generate a baseline:

```bash
composer psalm-baseline
```

This creates `psalm-baseline.xml` which allows you to gradually fix issues without blocking CI.

## CI Integration

Add Psalm to your continuous integration:

```yaml
# GitHub Actions example
- name: Psalm Analysis
  run: composer psalm
```

```yaml
# GitLab CI example
psalm:
  script:
    - composer install
    - composer psalm
```

## IDE Integration

### PHPStorm

1. Go to Settings → PHP → Quality Tools → Psalm
2. Point to `vendor/bin/psalm`
3. Enable automatic inspection

### VS Code

Install the Psalm extension:

```bash
code --install-extension getpsalm.psalm-vscode-plugin
```

## Error Levels

Psalm has 8 error levels (1-8). This project uses level 8:

- **Level 1**: Strictest - catches almost all type issues
- **Level 3**: Stricter type checking
- **Level 5**: Medium strictness
- **Level 8**: Least strict, basic checks (used here)

## Common Issues

### Memory Issues

If Psalm runs out of memory, increase the limit:

```bash
vendor/bin/psalm --memory-limit=1G
```

### Too Many Errors

If you're adding Psalm to an existing project:

1. Start with a higher error level (e.g., level 5)
2. Generate a baseline: `composer psalm-baseline`
3. Gradually fix issues and update baseline
4. Lower error level as code quality improves

### False Positives

If you encounter false positives, you can:

1. Add to baseline (temporary solution)
2. Add `@psalm-suppress` annotation (specific suppression)
3. Update `psalm.xml` to suppress issue type globally

Example of specific suppression:

```php
/** @psalm-suppress MixedAssignment */
$data = $this->request->getData();
```

## Differences from PHPStan

While both PHPStan and Psalm perform static analysis, they have different strengths:

- **Psalm**: Better at understanding complex template types, more aggressive inference
- **PHPStan**: More conservative, better IDE integration

Running both tools provides complementary analysis and catches more issues.

## Benefits

Running Psalm helps catch:

- Type errors before runtime
- Unused variables and code
- Invalid array access
- Null reference errors
- Mixed type usage
- Invalid function/method calls
- Incorrect return types

## More Information

- [Psalm Documentation](https://psalm.dev/docs/)
- [Psalm Error Levels](https://psalm.dev/docs/running_psalm/error_levels/)
- [Psalm Annotations](https://psalm.dev/docs/annotating_code/supported_annotations/)
- [Fixing Psalm Errors](https://psalm.dev/docs/fixing_code/)
