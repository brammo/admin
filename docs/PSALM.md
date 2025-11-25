# Psalm Configuration

This project uses [Psalm](https://psalm.dev/) for static code analysis at level 1 (the strictest level).

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

- **Error Level**: 1 (strictest)
- **Analyzed Paths**: `src/` directory
- **Baseline**: `psalm-baseline.xml` for existing issues
- **Find Unused Code**: Disabled (can be enabled for deeper analysis)
- **Target PHP Version**: 8.1 (inferred from composer.json)

### Suppressed Issues

The configuration suppresses some issues that are standard in CakePHP applications:

1. **PropertyNotSetInConstructor** - View Helpers use CakePHP's initialization pattern
2. **MissingConstructor** - Controllers and Helpers don't require explicit constructors
3. **PossiblyUnusedMethod** - Public methods may be called by CakePHP framework

These are standard patterns in CakePHP and don't represent actual errors.

## Baseline

This project uses a baseline file (`psalm-baseline.xml`) to track existing issues that are being gradually fixed. The baseline allows the project to:

- Add strict static analysis without breaking existing workflows
- Track which issues are legacy vs. new
- Gradually improve code quality over time

### Regenerating Baseline

If you fix issues in the baseline, regenerate it:

```bash
composer psalm-baseline
```

This updates `psalm-baseline.xml` with the current state of issues.

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

Psalm has 8 error levels (1-8). This project uses level 1:

- **Level 8**: Least strict, basic checks
- **Level 5**: Medium strictness
- **Level 3**: Stricter type checking
- **Level 1**: Strictest (used here) - catches almost all type issues

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
