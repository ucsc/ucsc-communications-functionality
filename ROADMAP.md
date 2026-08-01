# Roadmap

Known issues and planned work for the UCSC Communications Custom Functionality plugin.

Findings below came from an audit against the WordPress Plugin Handbook baseline
(structure, lifecycle, Settings API, security). Every item is tracked by a GitHub
issue. Fixed items are struck through below and kept for context.

| # | Issue | Item |
|---|-------|------|
| 1 | [#9](https://github.com/ucsc/ucsc-communications-functionality/issues/9) | ~~Admin settings CSS never loads~~ ✅ |
| 2 | [#10](https://github.com/ucsc/ucsc-communications-functionality/issues/10) | `wp_reset_postdata()` unreachable |
| 3 | [#11](https://github.com/ucsc/ucsc-communications-functionality/issues/11) | ~~`composer run lint` fails~~ ✅ |
| 4 | [#12](https://github.com/ucsc/ucsc-communications-functionality/issues/12) | Shortcode output not escaped |
| 5 | [#13](https://github.com/ucsc/ucsc-communications-functionality/issues/13) | No direct-access guards |
| 6 | [#14](https://github.com/ucsc/ucsc-communications-functionality/issues/14) | Stale duplicate plugin header |
| 7 | [#15](https://github.com/ucsc/ucsc-communications-functionality/issues/15) | ~~`get_plugin_data()` on every admin page~~ ✅ |
| 8 | [#16](https://github.com/ucsc/ucsc-communications-functionality/issues/16) | `Update URI` points at wrong repo |
| 9 | [#17](https://github.com/ucsc/ucsc-communications-functionality/issues/17) | PHPCS formatting backlog |

---

## P1 — Bugs (silently broken today)

### 1. ~~Admin settings CSS never loads~~
**Status:** ✅ Fixed — [#9](https://github.com/ucsc/ucsc-communications-functionality/issues/9)
**File:** `lib/functions/general.php`

`plugin_dir_url( __FILE__ )` resolved to `…/lib/functions/`, and the code then
appended `lib/css/admin-settings.css`, producing
`…/lib/functions/lib/css/admin-settings.css`. The stylesheet lives at
`lib/css/admin-settings.css`, so the enqueue 404'd and the settings page
rendered unstyled.

**Fixed by:** adding a `UCSCCOMMS_PLUGIN_URL` constant in `plugin.php` alongside
`UCSCCOMMS_PLUGIN_DIR`, and building the asset URL from the plugin root rather
than from the including file.

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

### 3. ~~`composer run lint` fails outright~~
**Status:** ✅ Fixed — [#11](https://github.com/ucsc/ucsc-communications-functionality/issues/11)
**File:** `.phpcs.xml.dist`

The ruleset declared no `<file>` element, so PHPCS exited with "You must supply
at least one file or directory to process." The config was upstream boilerplate,
named `"Example Project"` and excluding `/docroot/…` paths that do not exist in
this repo.

**Fixed by:** adding `<file>plugin.php</file>` and `<file>lib/</file>`, dropping
the `/docroot/` excludes, renaming the ruleset, restricting scanning to
`extensions="php"`, adding `basepath`/`parallel`/`colors`/`sp` args, and raising
`minimum_supported_wp_version` from `4.9` to `6.1` to match the plugin header.
`composer lint-fix` was also simplified from `phpcbf --extensions=php .` to plain
`phpcbf`, so both scripts now honour the same ruleset.

**Note:** `composer run lint` still exits non-zero — that is the violation
backlog in item 9, not a config fault.

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

### 7. ~~`get_plugin_data()` runs on every admin page load~~
**Status:** ✅ Fixed — [#15](https://github.com/ucsc/ucsc-communications-functionality/issues/15)
**File:** `lib/functions/general.php`

`get_plugin_data()` read and parsed `plugin.php` from disk before the
`$current_screen` check bailed out, so the cost was paid on every admin screen
rather than only on the plugin's settings page.

**Fixed by:** moving both early returns above the `get_plugin_data()` call,
guarding against `get_current_screen()` returning `null` (via an
`instanceof WP_Screen` check), passing `array()` rather than `''` as the
`$deps` argument to `wp_register_style()`, and dropping a stray double slash in
the `UCSCCOMMS_PLUGIN_DIR . '/plugin.php'` path.

Fixed together with item 1, which touches the same function.

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

Now that `.phpcs.xml.dist` is usable (item 3), `composer run lint` reports **102
errors and 11 warnings**, **87 of them auto-fixable** by `phpcbf` — concentrated
in `shortcodes.php` (missing docblocks, spacing, camelCase locals such as
`$azItem` / `$azDef` / `$azDir` against WordPress naming conventions). This is
why `composer run lint` exits non-zero today.

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
