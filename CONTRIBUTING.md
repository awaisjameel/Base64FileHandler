# Contributing

Use a maintained PHP version compatible with your target Laravel release: PHP
8.2–8.5 with Laravel 12, or PHP 8.3–8.5 with Laravel 13. Install dependencies with
`composer install` and run:

```bash
composer test
composer analyse
composer format
```

Include regression tests for behavioral changes. Test both raw base64 and data URI
inputs when changing decoding or MIME handling. Fake HTTP requests and filesystem
disks in tests; never use production storage or credentials.

Before submitting, run `vendor/bin/pint --test`, `composer validate --strict`, and
`git diff --check`. Document public API changes and new exceptions in the README
and changelog. Release versions come from Git tags rather than a Composer manifest
`version` field.
