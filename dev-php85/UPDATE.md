# PHP 8.5.7 Upgrade — `ctw/ctw-middleware-responsetime`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **Date:** 2026-06-25

This is a **TODO list** of the changes required for this package to run cleanly
under PHP 8.5.7. Nothing here has been fixed yet — the fixes happen in a second
step. Boxes are intentionally left unchecked.

Detection commands used:

```bash
composer update -W
php vendor/bin/phpunit --no-coverage --display-deprecations --display-warnings --display-notices --display-errors
composer rector      # rector --dry-run
composer phpstan
```

---

## 1. `composer update -W` — ❌ FAILS (inherited blocker)

```
Problem 1
  - Root composer.json requires ctw/ctw-middleware ^4.0
  - ctw/ctw-middleware[4.0.0 ... 4.0.6] require laminas/laminas-diactoros ^2.11
  - laminas/laminas-diactoros[2.11 ... 2.26] require php ~8.0 || ~8.1 || ~8.2 || ~8.3
    -> your php version (8.5.7) does not satisfy that requirement.
```

No direct `laminas-diactoros` dependency; blocked transitively through
`ctw/ctw-middleware ^4.0` (its 4.0.x releases pin Diactoros 2.x, PHP ≤ 8.3).

- [ ] **Blocked on `ctw/ctw-middleware`.** Fix & publish the Diactoros 3 bump
  there first (`ctw-middleware/dev-php85/UPDATE.md` §1), then bump this package's
  `ctw/ctw-middleware` constraint and re-run `composer update -W`.

> §2 was captured against the existing (master) lockfile because the update
> aborts.

---

## 2. PHP 8.5 runtime deprecations

All originate in the **third-party** `middlewares/utils` dependency — the
"implicitly nullable parameter" deprecation. **No first-party `src/` change is
required.**

| Location | Method / parameter |
| --- | --- |
| `vendor/middlewares/utils/src/Dispatcher.php:21` | `Dispatcher::run()` `$request` |
| `vendor/middlewares/utils/src/Factory.php:88` | `Factory::createUploadedFile()` `$size` |
| `vendor/middlewares/utils/src/Factory.php:90` | `Factory::createUploadedFile()` `$filename` |
| `vendor/middlewares/utils/src/Factory.php:91` | `Factory::createUploadedFile()` `$mediaType` |
| `vendor/middlewares/utils/src/CallableHandler.php:25` | `CallableHandler::__construct()` `$responseFactory` |

- [ ] Resolved by updating `middlewares/utils` once §1 is cleared; escalate
  upstream if the latest release still emits them.

---

## 3. QA tooling issues

- [ ] **PHPStan unmatched ignore pattern** (`missingType.generics`) — fix
  centrally in **`ctw/ctw-qa`** (`ctw-qa/dev-php85/UPDATE.md` §3). PHPStan
  currently reports **1 error**, this spurious one only.

---

## 4. Notes (non-blocking)

- Run locally with `--no-coverage` (no Xdebug/PCOV here). Not a PHP 8.5 issue.

---

## 5. Verification snapshot (current state on `php85`)

| Check | Result |
| --- | --- |
| `composer update -W` | ❌ fails — transitive `laminas-diactoros` 2.x (§1) |
| PHPUnit (`--no-coverage`, stale deps) | 29 tests, 35 assertions, **5 deprecations** (`middlewares/utils`, §2) |
| Rector (dry-run) | ✅ no changes proposed |
| PHPStan | ❌ 1 error (shared unmatched-ignore, §3) |

No first-party PHP 8.5 source issues; gated on upstream `ctw/ctw-middleware` +
`ctw/ctw-qa` fixes.
