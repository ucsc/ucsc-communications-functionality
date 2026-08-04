# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### PHP
```bash
composer run lint        # PHP CodeSniffer (WordPress-Extra + WordPress-Docs standards)
composer run lint-fix    # Auto-fix PHPCS violations
composer run test        # PHPUnit — runs against WP/ACF doubles, no WordPress install
composer run check       # lint + test
```

`.phpcs.xml.dist` scans `plugin.php` and `lib/` (PHP only), so no paths are needed.
`tests/` is deliberately **not** scanned — see the comment in the ruleset.

The ruleset also runs **PHPCompatibilityWP** at `testVersion` `7.4-`, which is what
actually proves the `Requires PHP: 7.4` header in `plugin.php`. Nothing else enforces
that floor: `composer.json` declares no `require` php constraint and no
`config.platform.php`. **The two values must be changed together** — raising or lowering
one without the other silently re-opens the contradiction that issue #25 fixed. Note the
floor is a *runtime* claim about where the plugin may be installed; it is unrelated to
the PHP the test suite needs (PHPUnit 12 wants 8.3+).

**PHPCS currently passes clean — `composer run lint` exits 0 with zero errors and zero
warnings.** Keep it that way: any non-zero exit is a genuine regression.

`.github/workflows/ci.yml` runs both on every PR, so this is now machine-enforced
rather than a matter of discipline.

**Local requirement:** PHPUnit needs `ext-mbstring`, which the system `php8.5` here
does not have. Either `sudo apt install php8.5-mbstring`, or run the suite in a
container:
`docker run --rm -v "$PWD":/app -w /app --user "$(id -u):$(id -g)" php:8.3-cli ./vendor/bin/phpunit`

### Release & Packaging
```bash
npm run dryrun     # Preview version bump without committing
npm run release    # Bump version via commit-and-tag-version (updates plugin.php header + CHANGELOG.md)
npm run zip        # Package plugin as .zip for distribution
```

`npm run release` uses `wp-plugin-version-updater.js` to keep the version string in the `plugin.php` header in sync alongside the standard `package.json` bump.

## Architecture

This is a single-purpose WordPress plugin that provides an **A-Z Editorial Style Guide** CPT backed by ACF Pro.

### Core dependency
**Advanced Custom Fields Pro** must be active. The plugin does not function without it.

### Constants (defined in `plugin.php`)
`UCSCCOMMS_PLUGIN_DIR` (filesystem path), `UCSCCOMMS_PLUGIN_URL` (URL), `UCSCCOMMS_PLUGIN_BASE`.
Always build asset paths/URLs from these rather than from `__FILE__` in an included file —
files under `lib/functions/` resolve relative to *that* directory, which is what broke the
admin stylesheet enqueue previously.

### Custom Post Type
`a_z_style_guide` ("Editorial Style Guide") — publicly queryable, searchable, non-hierarchical. Registered by ACF from `acf-json/post_type_685d7e97c87c6.json`, **not** in PHP — so it does not exist without ACF Pro active.

### ACF JSON
Field group definitions live in `acf-json/`. The plugin filters `acf/settings/save_json` and `acf/settings/load_json` so ACF reads/writes field definitions from this directory rather than the database — keeping them version-controlled.

- `group_5a1f2f21b2d3c.json` — field group with a `style_definitions` repeater (sub-fields: `editorial_style_item` text + `editorial_style_definition` WYSIWYG)
- `post_type_685d7e97c87c6.json` — ACF-managed CPT definition

### Shortcodes (`lib/functions/shortcodes.php`)
- `[style-definition]` — renders the `style_definitions` repeater for the current post
- `[style-archive]` — queries all `a_z_style_guide` posts and renders their definitions

Both build and *return* an HTML string rather than echoing it. Escape at the point of
concatenation — `esc_html()` for the text sub-field and post title, `wp_kses_post()` for
the WYSIWYG sub-field. PHPCS cannot check this: `WordPress.Security.EscapeOutput` only
inspects `echo`/`print`, so unescaped values in a returned string lint clean.

### Admin settings page (`lib/functions/settings.php`)
Simple informational page under **Settings** showing plugin version (linked to GitHub releases) and feature list. Requires `manage_options` capability.

### Namespace / autoloading
There is none at runtime. All code is procedural, loaded by `require_once` from
`plugin.php`, and `vendor/autoload.php` is never required — `vendor/` is gitignored and
`npm run zip` does not ship it, so nothing autoloaded could survive packaging anyway.

`composer.json` keeps only an `autoload-dev` PSR-4 mapping,
`UCSC\UcscCommunicationsFunctionality\Tests\` → `tests/`, which the test suite does use.
The matching production mapping to `src/` was removed in #24 as dead configuration. If
classes are ever introduced, re-add the `autoload` block in the same commit as the first
class, and remember that shipping them also means requiring the autoloader in `plugin.php`
and including `vendor/` (or a production-only autoloader) in the release zip.

### Tests (`tests/`)
PHPUnit against hand-written WordPress/ACF doubles — no WordPress install, no database,
no ACF Pro. Things to know before touching them:

- `tests/bootstrap.php` **must** `define( 'ABSPATH' )` before including anything. All
  four PHP files open with `defined( 'ABSPATH' ) || exit;`, so an include without it
  silently exits and takes the test runner down with it.
- The bootstrap includes `plugin.php` exactly once (no `function_exists()` guards there),
  which fires every `add_action`/`add_filter`/`add_shortcode` into a recording registry.
  `ucsccomms_test_reset( true )` preserves that registry between tests while clearing
  everything else.
- `have_rows()`/`the_row()` in `tests/wp-stubs.php` are a **stateful iterator pair** over
  a cursor in `$GLOBALS['ucsccomms_test']`. `have_rows()` peeks and never advances; only
  `the_row()` moves the cursor. A double that returns a constant `true` infinite-loops —
  there is a 1000-call tripwire that throws rather than hanging CI.
  `WP_Query::the_post()` rewinds that cursor per post, mirroring real ACF.
- **`esc_html()` is faithful; `wp_kses_post()` is a pass-through spy.** The escaping tests
  assert the *escape-before-concatenate contract*, not real KSES filtering. A green suite
  is not proof of XSS safety. This asymmetry is deliberate — it makes the two regressions
  fail different assertions.

## Known issues

None outstanding. All nine items from the original audit are fixed — see
[ROADMAP.md](ROADMAP.md), which keeps each one with its cause and fix for context.

## Known quirks

- No PHPStan config, no JS build pipeline (no blocks or interactive JS).
- The `Requires PHP` header and the PHPCS `testVersion` are a second pair that must stay
  in sync by hand — see the note under **Commands → PHP**.
- `settings.php` and `general.php` each hard-code the literal
  `ucsc-communications-functionality-settings` independently — one as the menu slug,
  one in a `strpos()` screen check — with no shared constant. They must stay in sync
  or the admin stylesheet silently stops loading. Pinned by `RegistrationTest`.
- `plugin.php` and `lib/functions/shortcodes.php` have **no `function_exists()` guards**
  (unlike `general.php` and `settings.php`), so double-inclusion is fatal.
