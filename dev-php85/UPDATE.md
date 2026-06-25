# PHP 8.5.7 Migration — `ctw/ctw-middleware-responsetime`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **PHPUnit:** 12 → **13.2.1**
- **Status:** ✅ done

PSR-15 middleware that adds an `X-Response-Time` header. Depends on
`ctw/ctw-middleware`; the PHP 8.5 fixes arrive transitively through that base
package's `dev-php85` branch. The test suite drives a real
`ServiceManager` / `Middlewares\Utils\Dispatcher`, so there are no test doubles
to modernize.

## Audit checklist

### Composer resolution (root blocker)

- [x] **(fatal) `composer update -W`** — under PHP 8.5 the update aborted:
  `ctw/ctw-middleware ^4.0` → `laminas/laminas-diactoros ^2.11`, and Diactoros
  2.x caps PHP at `~8.0 || ~8.1 || ~8.2 || ~8.3`, so it refused PHP 8.5.7. The
  cap was transitive — this package has no direct `laminas-diactoros` require.
  - **Fix:** require `ctw/ctw-middleware: dev-php85`, which bumps Diactoros to
    `^3` (3.8.0), `middlewares/utils` to `^4` (4.0.2) and
    `laminas/laminas-servicemanager` to 4.5.1. Resolution is green under 8.5.7.

### Vendor runtime deprecations (`middlewares/utils`)

These "implicitly nullable parameter" deprecations were emitted by
`middlewares/utils` 3.x under PHP 8.5, exercised through the real `Dispatcher`
and `Factory` the tests run. No first-party `src/` change is required — they are
cleared by the dependency bump.

- [x] **(deprecation) `vendor/middlewares/utils/src/Dispatcher.php:21`** —
  `Dispatcher::run()` parameter `$request` implicitly nullable.
  - **Fix:** resolved by `middlewares/utils ^4` (explicit
    `?ServerRequestInterface $request`), pulled in via
    `ctw/ctw-middleware: dev-php85`.
- [x] **(deprecation) `vendor/middlewares/utils/src/Factory.php:88`** —
  `Factory::createUploadedFile()` parameter `$size` implicitly nullable.
  - **Fix:** resolved by `middlewares/utils ^4` (explicit `?int $size`).
- [x] **(deprecation) `vendor/middlewares/utils/src/Factory.php:90`** —
  `Factory::createUploadedFile()` parameter `$filename` implicitly nullable.
  - **Fix:** resolved by `middlewares/utils ^4` (explicit `?string $filename`).
- [x] **(deprecation) `vendor/middlewares/utils/src/Factory.php:91`** —
  `Factory::createUploadedFile()` parameter `$mediaType` implicitly nullable.
  - **Fix:** resolved by `middlewares/utils ^4` (explicit `?string $mediaType`).
- [x] **(deprecation) `vendor/middlewares/utils/src/CallableHandler.php:25`** —
  `CallableHandler::__construct()` parameter `$responseFactory` implicitly
  nullable.
  - **Fix:** resolved by `middlewares/utils ^4` (explicit
    `?ResponseFactoryInterface $responseFactory`).

### Test suite — static analysis

- [x] **(tooling) PHPStan `staticMethod.alreadyNarrowedType`,
  `test/ResponseTimeMiddlewareTest.php:95`** — in
  `testResponseTimeHasThreeDecimalPlaces()` the regex capture narrowed
  `$matches` to a fixed two-element shape, so `assertCount(2, $matches)` (and,
  after a first rewrite, `assertNotEmpty($matches)`) was provably always true.
  - **Fix:** assert on what actually matters — `preg_match()` returns `1` and
    the captured group matches `^\d+\.\d{3}$` — instead of the tautological
    count/empty checks. PHPStan clean; assertion count unchanged.

### PHPUnit configuration

- [x] **(tooling) `phpunit.xml.dist`** — schema pinned to the 12.2 XSD.
  - **Fix:** bumped `xsi:noNamespaceSchemaLocation` to
    `https://schema.phpunit.de/13.2/phpunit.xsd`.

## composer.json & CI

- [x] **`require.php` `^8.3` → `^8.5`** — package now targets PHP 8.5 only.
- [x] **`ctw/ctw-middleware` `^4.0` → `dev-php85`** — pulls the Diactoros 3 /
  middlewares-utils 4 / servicemanager 4 stack (the resolution fix above).
- [x] **`ctw/ctw-qa` `^5.0` → `dev-php85`** — QA toolchain aligned for PHP 8.5.
- [x] **`phpunit/phpunit` `^12.0` → `^13.0`** — installs 13.2.1.
- [x] **`.github/workflows/tests.yml` → PHP 8.5 only** — matrix reduced from the
  commented `[ '8.3', '8.4', '8.5' ]` / active `[ '8.3' ]` to `[ '8.5' ]`.

## Final audit (PHP 8.5.7)

- [x] **`php -v`** — PHP **8.5.7** (cli).
- [x] **`composer update -W`** — clean; no security advisories. Installed:
  `laminas/laminas-diactoros 3.8.0`, `middlewares/utils 4.0.2`,
  `laminas/laminas-servicemanager 4.5.1`, `phpunit/phpunit 13.2.1`,
  `ctw/ctw-middleware dev-php85`, `ctw/ctw-qa dev-php85`.
- [x] **PHPUnit** — **29 tests, 35 assertions**; 0 deprecations, 0 warnings,
  0 notices, 0 errors, 0 skipped.
- [x] **PHPStan** — no issues found.
- [x] **Rector (dry-run)** — no changes proposed.

> **Before merge:** re-tag `ctw/ctw-middleware` (and `ctw/ctw-qa`) to a stable
> release and replace the `dev-php85` pins.
