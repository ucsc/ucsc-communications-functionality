# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### PHP
```bash
composer run lint        # PHP CodeSniffer (WordPress-Extra + WordPress-Docs standards)
composer run lint-fix    # Auto-fix PHPCS violations
```

### Release & Packaging
```bash
npm run dryrun     # Preview version bump without committing
npm run release    # Bump version via standard-version (updates plugin.php header + CHANGELOG.md)
npm run zip        # Package plugin as .zip for distribution
```

`npm run release` uses `wp-plugin-version-updater.js` to keep the version string in the `plugin.php` header in sync alongside the standard `package.json` bump.

## Architecture

This is a single-purpose WordPress plugin that provides an **A-Z Editorial Style Guide** CPT backed by ACF Pro.

### Core dependency
**Advanced Custom Fields Pro** must be active. The plugin does not function without it.

### Custom Post Type
`a_z_style_guide` ("Editorial Style Guide") — publicly queryable, searchable, non-hierarchical. Registered in `plugin.php`.

### ACF JSON
Field group definitions live in `acf-json/`. The plugin filters `acf/settings/save_json` and `acf/settings/load_json` so ACF reads/writes field definitions from this directory rather than the database — keeping them version-controlled.

- `group_5a1f2f21b2d3c.json` — field group with a `style_definitions` repeater (sub-fields: `editorial_style_item` text + `editorial_style_definition` WYSIWYG)
- `post_type_685d7e97c87c6.json` — ACF-managed CPT definition

### Shortcodes (`lib/functions/shortcodes.php`)
- `[style-definition]` — renders the `style_definitions` repeater for the current post
- `[style-archive]` — queries all `a_z_style_guide` posts and renders their definitions

### Admin settings page (`lib/functions/settings.php`)
Simple informational page under **Settings** showing plugin version (linked to GitHub releases) and feature list. Requires `manage_options` capability.

### Namespace / autoloading
`composer.json` defines PSR-4 autoloading: `UCSC\UcscCommunicationsFunctionality\` → `src/`. No `src/` directory exists yet; this is placeholder infrastructure. Current code uses procedural `require_once` includes.

## Known quirks

- No unit tests, no PHPStan config, no JS build pipeline (no blocks or interactive JS).
