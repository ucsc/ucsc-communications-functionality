# Roadmap

Known issues and planned work for the UCSC Communications Custom Functionality plugin.

Findings below came from an audit against the WordPress Plugin Handbook baseline
(structure, lifecycle, Settings API, security). Every item is tracked by a GitHub
issue; none have been fixed yet.

| # | Issue | Item |
|---|-------|------|
| 1 | [#9](https://github.com/ucsc/ucsc-communications-functionality/issues/9) | Admin settings CSS never loads |
| 2 | [#10](https://github.com/ucsc/ucsc-communications-functionality/issues/10) | `wp_reset_postdata()` unreachable |
| 3 | [#11](https://github.com/ucsc/ucsc-communications-functionality/issues/11) | `composer run lint` fails |
| 4 | [#12](https://github.com/ucsc/ucsc-communications-functionality/issues/12) | Shortcode output not escaped |
| 5 | [#13](https://github.com/ucsc/ucsc-communications-functionality/issues/13) | No direct-access guards |
| 6 | [#14](https://github.com/ucsc/ucsc-communications-functionality/issues/14) | Stale duplicate plugin header |
| 7 | [#15](https://github.com/ucsc/ucsc-communications-functionality/issues/15) | `get_plugin_data()` on every admin page |
| 8 | [#16](https://github.com/ucsc/ucsc-communications-functionality/issues/16) | `Update URI` points at wrong repo |
| 9 | [#17](https://github.com/ucsc/ucsc-communications-functionality/issues/17) | PHPCS formatting backlog |

---

## P1 — Bugs (silently broken today)

### 1. Admin settings CSS never loads
**Status:** Open — [#9](https://github.com/ucsc/ucsc-communications-functionality/issues/9)
**File:** `lib/functions/general.php:24`

`plugin_dir_url( __FILE__ )` resolves to `…/lib/functions/`, and the code then
appends `lib/css/admin-settings.css`, producing:

```
…/lib/functions/lib/css/admin-settings.css
```

The stylesheet actually lives at `lib/css/admin-settings.css`, so the enqueue
resolves to a 404 and the settings page renders unstyled.

**Fix:** Build the URL from the plugin root, e.g. define a `UCSCCOMMS_PLUGIN_URL`
constant in `plugin.php` alongside `UCSCCOMMS_PLUGIN_DIR` and use it here.

---

### 2. `wp_reset_postdata()` is unreachable in `[style-archive]`
**Status:** Open — [#10](https://github.com/ucsc/ucsc-communications-functionality/issues/10)
**File:** `lib/functions/shortcodes.php:73-75`

```php
return $finalloop;

wp_reset_postdata();   // never executes
```

The custom `WP_Query` loop calls `the_post()`, which overwrites the global
`$post`. Because the reset never runs, any template content rendered after the
shortcode on the same page sees the wrong post context (wrong title, permalink,
fields).

**Fix:** Move `wp_reset_postdata()` above the `return`, immediately after the
loop closes.

---

### 3. `composer run lint` fails outright
**Status:** Open — [#11](https://github.com/ucsc/ucsc-communications-functionality/issues/11)
**File:** `.phpcs.xml.dist`

The ruleset declares no `<file>` element, so PHPCS exits with:

```
ERROR: You must supply at least one file or directory to process.
```

The config is still upstream boilerplate — it is named `"Example Project"` and
excludes `/docroot/…` paths that do not exist in this repo. Both `lint` and
`lint-fix` are unusable as documented.

**Fix:** Add `<file>plugin.php</file>` and `<file>lib/</file>`, drop the
`/docroot/` excludes, and rename the ruleset. Consider also raising
`minimum_supported_wp_version` (currently `4.9`) to match the `Requires at
least: 6.1` plugin header.

---

## P2 — Security baseline

### 4. Shortcode output is not escaped
**Status:** Open — [#12](https://github.com/ucsc/ucsc-communications-functionality/issues/12)
**File:** `lib/functions/shortcodes.php:27, 60, 67`

ACF field values and the post title are concatenated raw into returned HTML:

```php
$finaldefs .= '<p><b>'.$azItem.':</b></p>'.$azDef.'<hr>';
```

Editors with `unfiltered_html` (or any future change to who can edit these
posts) can inject arbitrary markup into every page using the shortcodes.

**Fix:** `esc_html()` for `$azItem` and the post title; `wp_kses_post()` for
`$azDef`, which is a WYSIWYG field and legitimately contains markup.

**Note:** PHPCS does not catch these. The `WordPress.Security.EscapeOutput`
sniff only inspects `echo`/`print`, not values accumulated into a returned
string — so this class of bug needs review, not just linting.

---

### 5. No direct-access guards
**Status:** Open — [#13](https://github.com/ucsc/ucsc-communications-functionality/issues/13)
**Files:** `plugin.php`, `lib/functions/*.php`

None of the PHP files guard against direct HTTP access.

**Fix:** Add `defined( 'ABSPATH' ) || exit;` near the top of each file.

---

## P3 — Structure and hygiene

### 6. Stale duplicate plugin header in `shortcodes.php`
**Status:** Open — [#14](https://github.com/ucsc/ucsc-communications-functionality/issues/14)
**File:** `lib/functions/shortcodes.php:2-8`

The file opens with a second `Plugin Name:` header block declaring
`Version: 0.1.0`. Only the main bootstrap should carry a plugin header; this one
is stale and misleading (and confuses tooling that scans for plugin entry
points).

**Fix:** Replace with a normal file docblock matching `general.php` and
`settings.php`, including the `@package` tag PHPCS wants.

---

### 7. `get_plugin_data()` runs on every admin page load
**Status:** Open — [#15](https://github.com/ucsc/ucsc-communications-functionality/issues/15)
**File:** `lib/functions/general.php:26-30`

`get_plugin_data()` reads and parses `plugin.php` from disk before the
`$current_screen` check bails out, so the cost is paid on every single admin
screen rather than only on the plugin's settings page.

**Fix:** Move the early return above the `get_plugin_data()` call. While there,
guard against `get_current_screen()` returning `null`, and pass `array()`
rather than `''` as the `$deps` argument to `wp_register_style()`.

---

### 8. `Update URI` header points at a non-existent repo
**Status:** Open — [#16](https://github.com/ucsc/ucsc-communications-functionality/issues/16)
**File:** `plugin.php:13`

```
Update URI: https://github.com/ucsc/ucsc-communications-functionality-plugin/releases
```

The actual repository is `ucsc/ucsc-communications-functionality` (no `-plugin`
suffix).

**Fix:** Correct the URL. Minor related nit: `Author URI` on line 10 has a
trailing space.

---

### 9. PHPCS formatting backlog
**Status:** Open — [#17](https://github.com/ucsc/ucsc-communications-functionality/issues/17)

Once `.phpcs.xml.dist` is usable (item 3), a full run reports **113 errors**
across the plugin, **98 of them auto-fixable** by `phpcbf` — concentrated in
`shortcodes.php` (missing docblocks, spacing, camelCase locals such as
`$azItem` / `$azDef` / `$azDir` against WordPress naming conventions).

**Fix:** Run `composer run lint-fix`, then hand-resolve the remainder. Best done
as its own commit so the mechanical reformatting does not obscure the
behavioural fixes above.

---

## Deferred / not planned

- **No unit tests.** No PHPUnit or WP test scaffolding exists.
- **No PHPStan config.** See the `wp-phpstan` workflow if this is picked up.
- **Empty `src/` PSR-4 mapping.** `composer.json` maps
  `UCSC\UcscCommunicationsFunctionality\` → `src/`, but no `src/` directory
  exists and all current code is procedural. Either build into it or drop the
  mapping.
- **No `uninstall.php`.** Currently correct: the plugin registers no options and
  creates no tables, so there is nothing to clean up. Revisit if that changes.
