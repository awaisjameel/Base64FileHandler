# Changelog

All notable changes to `Base64FileHandler` will be documented in this file.

## 3.0.0 - 2026-10-26

- Enforce Pint, PHPStan, strict manifest validation and dependency audits on pull requests and pushes; consolidate duplicate/automatic-formatting workflows.

### Changed

- **Breaking:** support maintained Laravel 12–13 and PHP 8.2–8.5 only.
- **Breaking:** detect MIME types from decoded content and reject conflicting data
  URI labels with `MimeTypeMismatchException`.
- **Breaking:** image checks require image content with recognizable dimensions.
- Support MimeTypes 3 alongside 2 and update test tooling through Pest 5,
  Testbench 11, and Larastan 3.
- Declare fileinfo and filesystem dependencies; remove redundant test dependencies
  and unused factory/workbench autoload entries.
- Decode payloads once per public operation and detect content types in memory.
- Upgrade CI actions and cover maintained PHP/framework combinations.

### Fixed

- Normalize failed and thrown filesystem writes to `UnableToStoreFileException`
  while preserving the underlying cause.
- Correct fixture path casing for Linux and remove a nonexistent analysis path.
- Exclude installed dependencies and local test artifacts from release archives.
- Add regression coverage for raw base64, MIME spoofing, URI metadata, storage
  failures, and byte-for-byte storage integrity.
