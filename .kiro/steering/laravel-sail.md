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