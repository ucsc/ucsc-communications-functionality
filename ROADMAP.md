# Roadmap

Known issues and planned work for the UCSC Communications Custom Functionality plugin.

Findings below came from an audit against the WordPress Plugin Handbook baseline
(structure, lifecycle, Settings API, security). Every item is tracked by a GitHub
issue. **All nine are now fixed** — they are struck through below and kept for
context.

| # | Issue | Item |
|---|-------|------|
| 1 | [#9](https://github.com/ucsc/ucsc-communications-functionality/issues/9) | ~~Admin settings CSS never loads~~ ✅ |
| 2 | [#10](https://github.com/ucsc/ucsc-communications-functionality/issues/10) | ~~`wp_reset_postdata()` unreachable~~ ✅ |
| 3 | [#11](https://github.com/ucsc/ucsc-communications-functionality/issues/11) | ~~`composer run lint` fails~~ ✅ |
| 4 | [#12](https://github.com/ucsc/ucsc-communications-functionality/issues/12) | ~~Shortcode output not escaped~~ ✅ |
| 5 | [#13](https://github.com/ucsc/ucsc-communications-functionality/issues/13) | ~~No direct-access guards~~ ✅ |
| 6 | [#14](https://github.com/ucsc/ucsc-communications-functionality/issues/14) | ~~Stale duplicate plugin header~~ ✅ |
| 7 | [#15](https://github.com/ucsc/ucsc-communications-functionality/issues/15) | ~~`get_plugin_data()` on every admin page~~ ✅ |
| 8 | [#16](https://github.com/ucsc/ucsc-communications-functionality/issues/16) | ~~`Update URI` points at wrong repo~~ ✅ |
| 9 | [#17](https://github.com/ucsc/ucsc-communications-functionality/issues/17) | ~~PHPCS formatting backlog~~ ✅ |

---

## P1 — Bugs

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

### 2. ~~`wp_reset_postdata()` is unreachable in `[style-archive]`~~
**Status:** ✅ Fixed — [#10](https://github.com/ucsc/ucsc-communications-functionality/issues/10)
**File:** `lib/functions/shortcodes.php`

`wp_reset_postdata()` sat *after* `return $finalloop;`, so it never executed. The
custom `WP_Query` loop calls `the_post()`, which overwrites the global `$post`,
and without the reset any template content rendered after the shortcode on the
same page saw the wrong post context (wrong title, permalink, fields).

**Fixed by:** moving the call inside the `if` block, immediately after the loop
closes and before the `return`.

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

At the time of this fix `composer run lint` still exited non-zero due to the
violation backlog in item 9; that has since been cleared and it now exits 0.

---

## P2 — Security baseline

### 4. ~~Shortcode output is not escaped~~
**Status:** ✅ Fixed — [#12](https://github.com/ucsc/ucsc-communications-functionality/issues/12)
**File:** `lib/functions/shortcodes.php`

ACF field values and the post title were concatenated raw into returned HTML:

```php
$finaldefs .= '<p><b>'.$azItem.':</b></p>'.$azDef.'<hr>';
```

Editors with `unfiltered_html` — or any future change to who can edit these
posts — could inject arbitrary markup into every page using the shortcodes.

**Fixed by:** `esc_html()` on `$azItem` and the post title; `wp_kses_post()` on
`$azDef`, which is a WYSIWYG field and legitimately contains markup, so it gets
the same allowlist as post content rather than being flattened.

**Note:** PHPCS did not catch these and will not catch a regression. The
`WordPress.Security.EscapeOutput` sniff only inspects `echo`/`print`, not values
accumulated into a returned string — this class of bug needs review, not just
linting.

**Now covered by tests.** `tests/ShortcodeEscapingTest.php` pins both paths:
`test_single_loop_escapes_item_and_kses_filters_definition` and
`test_archive_loop_escapes_title_item_and_definition`. Both were mutation-checked
— deleting `esc_html()` or `wp_kses_post()` from either concatenation fails the
suite. See the new "Unit tests" section below for what those tests do and do not
prove.

---

### 5. ~~No direct-access guards~~
**Status:** ✅ Fixed — [#13](https://github.com/ucsc/ucsc-communications-functionality/issues/13)
**Files:** `plugin.php`, `lib/functions/*.php`

None of the PHP files guarded against direct HTTP access.

**Fixed by:** adding `defined( 'ABSPATH' ) || exit;` immediately after the file
docblock in all four PHP files. Placement is below the docblock so the plugin
header in `plugin.php` is still the first thing WordPress parses.

---

## P3 — Structure and hygiene

### 6. ~~Stale duplicate plugin header in `shortcodes.php`~~
**Status:** ✅ Fixed — [#14](https://github.com/ucsc/ucsc-communications-functionality/issues/14)
**File:** `lib/functions/shortcodes.php`

The file opened with a second `Plugin Name:` header block declaring
`Version: 0.1.0`. Only the main bootstrap should carry a plugin header; this one
was stale, misleading, and confused tooling that scans for plugin entry points.

**Fixed by:** replacing it with a normal file docblock matching `general.php` and
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

### 8. ~~`Update URI` header points at a non-existent repo~~
**Status:** ✅ Fixed — [#16](https://github.com/ucsc/ucsc-communications-functionality/issues/16)
**File:** `plugin.php`

The header pointed at `ucsc-communications-functionality-plugin`; the actual
repository has no `-plugin` suffix.

**Fixed by:** correcting the URL, aligning the header value with its neighbours,
and stripping a trailing space from the `Author URI` line.

---

### 9. ~~PHPCS formatting backlog~~
**Status:** ✅ Fixed — [#17](https://github.com/ucsc/ucsc-communications-functionality/issues/17)

Once `.phpcs.xml.dist` became usable (item 3), `composer run lint` reported 87
errors and 8 warnings, concentrated in `shortcodes.php`.

**Fixed in two commits** so the mechanical churn stayed separable from the
deliberate edits:

1. `phpcbf` auto-fixes — 72 violations of spacing, alignment and indentation.
   Whitespace only.
2. The remaining 23 by hand — docblocks attached to the functions they describe,
   camelCase locals renamed to snake_case, `@param [type]` placeholders given
   real types, and a stray `@package ucsc-giving-functionality` (copied from
   another plugin) removed.

**`composer run lint` now exits 0** with zero errors and zero warnings. A
non-zero exit from here on is a genuine regression.

---

## Unit tests

A PHPUnit suite now covers the shortcode escaping contract and the plugin's
structural contracts. `composer run test` runs it; `composer run check` runs lint
and tests together. It needs no WordPress install, no database and no ACF Pro,
and finishes in about 10ms.

**How it works.** `tests/wp-stubs.php` hand-writes doubles for the ~25 WordPress
and ACF functions the plugin actually calls. `tests/bootstrap.php` defines
`ABSPATH`, loads those doubles, then includes `plugin.php` exactly once — which
fires every `add_action`/`add_filter`/`add_shortcode` into a recording registry
that `RegistrationTest` asserts against.

**What the escaping tests prove, and what they don't.** `esc_html()` is a
faithful double, but `wp_kses_post()` is a deliberate pass-through spy — the real
KSES allowlist is not reproducible outside WordPress. So the suite asserts the
*escape-before-concatenate contract* — that every dynamic value goes through an
escaper before it lands in the returned string — and **not** that KSES filters
correctly. That contract is exactly the thing that broke in item 4, so it is the
right assertion, but do not read a green suite as proof of XSS safety.

**Why not integration tests.** The `a_z_style_guide` CPT and the
`style_definitions` repeater are both registered by ACF from `acf-json/`, and ACF
Pro is a paid, non-public dependency. Real integration tests would need a license
key in CI and a download from `connect.advancedcustomfields.com`. The escape
hatch — calling `register_post_type()` manually and hand-writing repeater meta —
means faking ACF anyway, with a WordPress install and a MySQL service around it.
Not a defensible trade for 272 lines.

**Why not Brain Monkey**, the usual choice here: `have_rows()`/`the_row()` are a
stateful iterator pair, and the cursor double has to be hand-written either way
(a `justReturn(true)` stub infinite-loops), so Brain Monkey adds nothing where the
difficulty actually is. It would also handle this plugin's include-time hook
registration badly, since it resets its registry per test. It remains the natural
upgrade path if a `src/` with classes ever appears — the test cases barely change.

**Every test was mutation-checked.** Deleting `esc_html()` or `wp_kses_post()`
from either concatenation, making `wp_reset_postdata()` unreachable again,
regressing the stylesheet path to `lib/functions/`, typoing the CPT slug, or
dropping `the_row()` from a loop each fail the suite. The last one trips a
deliberate 1000-iteration guard in `have_rows()` that throws instead of hanging CI.

---

## Deferred / not planned

- **No PHPStan config.** Worth knowing what it would and would not buy here:
  PHPStan at level 4 **would** have caught item 2 (`wp_reset_postdata()` sitting
  unreachable after a `return`), but it has **no taint analysis**, so it would
  **not** have caught item 4 — tracking an unescaped value into a returned string
  needs Psalm's `--taint-analysis`, which in turn needs a WP stub set annotated
  with taint sinks and escapers. That is more setup than this entire test suite,
  for one plugin. Item 4 is covered by tests instead. Still deferred, but for
  that reason rather than "not got to it yet". See the `wp-phpstan` workflow if
  it is picked up.
- **Empty `src/` PSR-4 mapping.** `composer.json` maps
  `UCSC\UcscCommunicationsFunctionality\` → `src/`, but no `src/` directory
  exists and all current code is procedural. Either build into it or drop the
  mapping.
- **No `uninstall.php`.** Currently correct: the plugin registers no options and
  creates no tables, so there is nothing to clean up. Revisit if that changes.
