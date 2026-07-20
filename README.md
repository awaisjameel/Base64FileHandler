# Base64 File Handler

[![Latest Version on Packagist](https://img.shields.io/packagist/v/awaisjameel/base64filehandler.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/base64filehandler)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/base64filehandler/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/awaisjameel/base64filehandler/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/awaisjameel/base64filehandler/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/awaisjameel/base64filehandler/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/awaisjameel/base64filehandler.svg?style=flat-square)](https://packagist.org/packages/awaisjameel/base64filehandler)

**Base64FileHandler** decodes `data:` URI / raw base64 payloads — the kind you get from a JS canvas export, a signature pad, or a mobile app's JSON upload — validates them, and stores them on any Laravel filesystem disk. It resolves MIME types and extensions using [`awaisjameel/mimetypes`](https://github.com/awaisjameel/mimetypes), so file-type detection stays accurate without a hardcoded, ever-stale lookup table baked into this package.

## Features

- **Store base64 data on any disk** — local, S3, or any other Laravel filesystem, with a configurable default disk/path and per-call overrides.
- **Extension allow-listing** — restrict which file types may be stored, globally via config or per call.
- **Image validation** — check that a base64 payload decodes to one of a configurable set of image extensions.
- **File info inspection** — pull the MIME type, extension, size, and raw decoded bytes out of a base64 string without storing it.
- **Collision-safe filenames** — generated paths are unique per call, even for concurrent requests uploading the same original filename.
- **Typed exceptions** — a distinct exception class per failure mode, so you can catch precisely what you expect instead of a generic `Exception`.
- **Container-aware configuration** — resolve the handler via the facade or dependency injection and it honours your published config, environment variables, and any runtime `config()` overrides.

## Requirements

| Base64FileHandler | PHP  | Laravel                    |
|--------------------|------|-----------------------------|
| ^2.0               | ^8.2 | ^10.0, ^11.0, ^12.0, ^13.0  |

## Installation

Install the package via Composer:

```bash
composer require awaisjameel/base64filehandler
```

The service provider and `Base64FileHandler` facade are auto-discovered — there's nothing else to register.

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="base64filehandler-config"
```

This is the config that gets published:

```php
return [
    'disk' => env('BASE64_FILE_HANDLER_DISK', 'public'),
    'path' => env('BASE64_FILE_HANDLER_PATH', 'uploads/'),
    'allowed_extensions' => [
        // 'pdf', 'jpg', 'png', 'docx', etc.
    ],
    'valid_image_extensions' => [
        'jpeg', 'jpg', 'png', 'gif', 'webp',
    ],
];
```

## Usage

The recommended way to use the package is through its facade — resolving it this way always honours your published config and any runtime `config()` overrides:

```php
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler;

$path = Base64FileHandler::store($base64Data);
```

Prefer dependency injection? The underlying `AwaisJameel\Base64FileHandler\Base64FileHandler` class is bound as a singleton in the container, so you can type-hint it anywhere:

```php
use AwaisJameel\Base64FileHandler\Base64FileHandler;

class AvatarController
{
    public function __construct(private Base64FileHandler $handler)
    {
    }

    public function store(Request $request)
    {
        $path = $this->handler->store($request->input('avatar'));

        // ...
    }
}
```

### Storing a base64 file

```php
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler;

// Uses the configured default disk, path, and allowed extensions.
$path = Base64FileHandler::store($base64Data);

// Every argument after the base64 payload is optional and overrides config for this call only.
$path = Base64FileHandler::store(
    $base64Data,
    disk: 's3',
    path: 'avatars/',
    originalName: 'profile-picture.jpg',
    allowedExtensions: ['jpg', 'jpeg', 'png'],
);
```

`$base64Data` may be a full data URI (`data:image/png;base64,...`) or a raw base64 string with no prefix — the MIME type is detected either way. The returned `$path` is the file's path on the given disk (suitable for `Storage::disk($disk)->url($path)`, `->get($path)`, etc.).

If `originalName` is given, its slugified filename is used as a prefix so stored paths stay readable; a unique suffix is always appended so concurrent uploads — even of the same original filename — never collide or overwrite one another.

### Validating that base64 data is an image

```php
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler;
use AwaisJameel\Base64FileHandler\Exceptions\InvalidImageException;

try {
    Base64FileHandler::isValidImage($base64Data);
    // The data decodes to one of the configured valid image extensions.
} catch (InvalidImageException $e) {
    // Not a valid image.
}

// Or check against a custom set of extensions for this call only:
Base64FileHandler::isValidImage($base64Data, ['png', 'webp']);
```

### Inspecting base64 data without storing it

```php
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler;

$fileInfo = Base64FileHandler::getFileInfo($base64Data);

// [
//     'mime' => 'image/png',
//     'extension' => 'png',
//     'size' => 18342,
//     'data' => "\x89PNG\x0D\x0A...", // raw decoded binary
// ]
```

## Exceptions

Every method throws a specific exception for each failure mode instead of a generic `Exception`, so you can catch exactly what you expect:

| Exception | Thrown when |
|---|---|
| `AwaisJameel\Base64FileHandler\Exceptions\InvalidBase64DataException` | The given string isn't valid base64 data. |
| `AwaisJameel\Base64FileHandler\Exceptions\UnableToDetermineMimeTypeException` | No `data:` MIME prefix was given and the decoded content's MIME type couldn't be sniffed. |
| `AwaisJameel\Base64FileHandler\Exceptions\UnsupportedFileExtensionException` | The resolved file extension isn't in the allowed extensions list (`store()` only). |
| `AwaisJameel\Base64FileHandler\Exceptions\InvalidImageException` | The resolved file extension isn't in the valid image extensions list (`isValidImage()` only). |
| `AwaisJameel\Base64FileHandler\Exceptions\UnableToStoreFileException` | The filesystem disk rejected the write (`store()` only). |

All of the above extend the package's base `AwaisJameel\Base64FileHandler\Exceptions\Base64FileHandlerException`, so you can catch everything this package itself throws in one place:

```php
use AwaisJameel\Base64FileHandler\Exceptions\Base64FileHandlerException;
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler;

try {
    $path = Base64FileHandler::store($base64Data);
} catch (Base64FileHandlerException $e) {
    report($e);
    abort(422, $e->getMessage());
}
```

Resolving a MIME type or extension is delegated to [`awaisjameel/mimetypes`](https://github.com/awaisjameel/mimetypes), which can itself throw `AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException` (an unrecognized MIME type/extension) or `AwaisJameel\MimeTypes\Exceptions\UnableToFetchMimeTypesException` (its MIME type source is unreachable and its offline fallback has been disabled). Both extend `AwaisJameel\MimeTypes\Exceptions\MimeTypesException` — catch that too if you want a single `catch` block covering everything either package can throw:

```php
use AwaisJameel\Base64FileHandler\Exceptions\Base64FileHandlerException;
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler;
use AwaisJameel\MimeTypes\Exceptions\MimeTypesException;

try {
    $path = Base64FileHandler::store($base64Data);
} catch (Base64FileHandlerException | MimeTypesException $e) {
    report($e);
    abort(422, 'Unable to process the uploaded file.');
}
```

## Configuration reference

| Key | Env variable | Default | Description |
|---|---|---|---|
| `disk` | `BASE64_FILE_HANDLER_DISK` | `public` | The default filesystem disk files are stored on. |
| `path` | `BASE64_FILE_HANDLER_PATH` | `uploads/` | The default path within the disk files are stored under. |
| `allowed_extensions` | — | `[]` (all allowed) | Extensions `store()` will accept. Matched case-insensitively. |
| `valid_image_extensions` | — | `['jpeg', 'jpg', 'png', 'gif', 'webp']` | Extensions `isValidImage()` treats as images. Matched case-insensitively. |

Every option above can also be overridden per call via `store()`'s and `isValidImage()`'s optional arguments, or by constructing the class directly with a custom config array:

```php
use AwaisJameel\Base64FileHandler\Base64FileHandler;

$handler = new Base64FileHandler([
    'disk' => 'local',
    'path' => 'custom/path/',
    'allowed_extensions' => ['jpg', 'png', 'pdf'],
]);

$path = $handler->store($base64Data);
```

Note that a directly-instantiated `new Base64FileHandler(...)` does **not** read your published config file — it only uses the defaults above merged with whatever you pass in. Resolve it via the facade or the container (as shown under [Usage](#usage)) if you want your published config file honoured.

## Testing

The package's `MimeTypes` dependency resolves its data over HTTP; the test suite fakes that request (see `tests/TestCase.php`) so tests run fully offline and deterministically, using the bundled MIME type snapshot as a fallback. Keep that in mind if you write your own tests against this package — fake the same way, or `Http::fake()` a MIME type response yourself.

```bash
composer test
```

Or with coverage:

```bash
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Awais Jameel](https://github.com/awaisjameel)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
