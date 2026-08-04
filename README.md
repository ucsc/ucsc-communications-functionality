# UCSC Communications Custom Functionality

[![CI](https://github.com/ucsc/ucsc-communications-functionality/actions/workflows/ci.yml/badge.svg)](https://github.com/ucsc/ucsc-communications-functionality/actions/workflows/ci.yml)

Custom functionality plugin for the UC Santa Cruz Communications and Marketing website
on the CampusPress network. It provides the **A-Z Editorial Style Guide** — a custom
post type, its ACF field group, and two shortcodes for rendering entries — plus an
informational settings page in the admin.

## Requirements

| Requirement                    | Version              |
| ------------------------------ | -------------------- |
| WordPress                      | 6.1 or later         |
| PHP                            | 7.4 or later         |
| **Advanced Custom Fields Pro** | Required — see below |

ACF Pro is a hard dependency, not an enhancement. The `a_z_style_guide` post type is
registered **by ACF** from `acf-json/post_type_685d7e97c87c6.json`, not in PHP, so
without ACF Pro active the post type does not exist and both shortcodes render nothing.
The plugin declares this via `Requires Plugins: advanced-custom-fields-pro` in the
plugin header; it does not bundle or install ACF Pro for you.

## Installation

1. Download the latest `ucsc-communications-functionality.zip` from the
   [releases page](https://github.com/ucsc/ucsc-communications-functionality/releases).
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate the plugin. Make sure ACF Pro is active too.

Once active, a read-only info page listing the version and available features lives at
**Settings → UCSC Communications Functionality** (requires the `manage_options`
capability).

## Usage

Style guide entries are managed under the **A-Z Ed Style Guide** menu in the admin.
Each entry has a **Style Definitions** repeater field, where every row holds:

- **Editorial Style Item** — the term, plain text
- **Editorial Style Definition** — the guidance, a WYSIWYG field

Two shortcodes render those rows:

| Shortcode            | Output                                                                        |
| -------------------- | ----------------------------------------------------------------------------- |
| `[style-definition]` | The Style Definitions of the post it is placed on.                            |
| `[style-archive]`    | Every published style guide entry, ordered by title ascending, with headings.  |

Neither shortcode takes attributes. Both are defined in
[lib/functions/shortcodes.php](lib/functions/shortcodes.php).

## Development

```bash
git clone https://github.com/ucsc/ucsc-communications-functionality.git
cd ucsc-communications-functionality
composer install
npm install
```

### Commands

| Command                 | What it does                                                        |
| ----------------------- | ------------------------------------------------------------------- |
| `composer run lint`     | PHP CodeSniffer — WordPress-Extra, WordPress-Docs, PHPCompatibility  |
| `composer run lint-fix` | Auto-fix the PHPCS violations that are fixable                      |
| `composer run test`     | PHPUnit                                                             |
| `composer run check`    | `lint` then `test` — run this before opening a PR                   |
| `npm run dryrun`        | Preview the next version bump without committing                    |
| `npm run release`       | Bump the version and update the changelog                           |
| `npm run zip`           | Package the plugin as a distributable zip                           |

There is no build step — the plugin ships no compiled assets.

### Tests

The suite in [tests/](tests/) runs against hand-written WordPress and ACF doubles, so it
needs no WordPress install, no database, and no ACF Pro. It does need PHP's `mbstring`
extension. If your local PHP lacks it, either install it
(`sudo apt install php8.5-mbstring`) or run the suite in a container:

```bash
docker run --rm -v "$PWD":/app -w /app --user "$(id -u):$(id -g)" \
  php:8.3-cli ./vendor/bin/phpunit
```

### Project layout

```
plugin.php                    Plugin header, constants, ACF JSON save/load filters
lib/functions/general.php     Admin stylesheet enqueue
lib/functions/settings.php    Settings → info page
lib/functions/shortcodes.php  [style-definition] and [style-archive]
lib/css/                      Admin settings page styles
acf-json/                     Version-controlled ACF field group and CPT definitions
tests/                        PHPUnit suite plus the WP/ACF doubles it runs against
```

Field group definitions are kept in `acf-json/` rather than the database: `plugin.php`
filters `acf/settings/save_json` and `acf/settings/load_json` so ACF reads and writes
them there, keeping them under version control.

## Releasing

`npm run release` uses [commit-and-tag-version](https://github.com/absolute-version/commit-and-tag-version)
to bump `package.json`, `package-lock.json`, and the `Version:` header in `plugin.php`
(through `wp-plugin-version-updater.js`), and to update `CHANGELOG.md` from the commit
history. Push the resulting `v*.*.*` tag and
[.github/workflows/release.yml](.github/workflows/release.yml) builds the zip and
publishes the GitHub release via the shared `ucsc/actions` workflow.

## Contributing

[.github/workflows/ci.yml](.github/workflows/ci.yml) runs PHPCS on PHP 8.3 and PHPUnit
on PHP 8.3 and 8.4 for every pull request, so `composer run check` should pass locally
first. Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/) —
the changelog is generated from them.

For architecture notes, known quirks, and the gotchas worth reading before changing
anything, see [CLAUDE.md](CLAUDE.md). [ROADMAP.md](ROADMAP.md) records the original
audit items with their causes and fixes.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
