# Laravel Sail Development Environment

## Command Execution

Always run PHP/Composer commands in the Sail container using:

```bash
./vendor/bin/sail command
```

Examples:
- `./vendor/bin/sail composer install`
- `./vendor/bin/sail php artisan migrate`
- `./vendor/bin/sail phpunit`
- `./vendor/bin/sail composer require package-name`

This ensures commands run in the proper containerized environment with all dependencies available.

## PHPUnit Testing

When running PHPUnit tests, note that the `--verbose` option is not available in this version of PHPUnit. Use alternative options for detailed output:

- Use `./vendor/bin/sail php artisan test` for Laravel's test runner with better output
- Use `./vendor/bin/sail phpunit --testdox` for readable test descriptions
- Use `./vendor/bin/sail phpunit --debug` for debugging information

Examples:
- `./vendor/bin/sail php artisan test --filter TestName`
- `./vendor/bin/sail phpunit tests/Unit/ExampleTest.php --testdox`