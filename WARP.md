# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Overview

GF Form Notices is a Gravity Forms add-on that displays scheduled messages above forms based on date ranges. It's built using Gravity Forms' Feed Add-On Framework, which means notices are managed as individual "feeds" rather than simple settings.

## Architecture

### Core Components

**Bootstrap (`gf-form-notices.php`)**
- Entry point that hooks into `gform_loaded` action
- Checks for Gravity Forms Feed Add-On Framework availability
- Registers the main add-on class

**Main Add-On Class (`class-gf-form-notices.php`)**
- Extends `GFFeedAddOn` (Gravity Forms Feed Add-On Framework)
- Each notice is stored as a feed with metadata (start_date, end_date, message, advance_days)
- Feeds support ordering via `$_supports_feed_ordering = true`

**Frontend Display (`handle_notice_messages` method)**
- Hooks into `gform_get_form_filter` to inject notices before form HTML
- Retrieves all active feeds for the form
- Evaluates each feed's date range against current timestamp
- Supports advance notice display (shows N days before start date)
- Multiple notices can display simultaneously if date ranges overlap

**Date Handling (`get_date_timestamp` method)**
- Supports three date formats:
  1. Specific dates: `2024-12-25`
  2. Recurring dates with wildcards: `*-12-25` (matches any year)
  3. Natural language: `Last Thursday of November`
- Wildcards (`*`) are replaced with current year/month/day values
- Can return midnight (start of day) or 11:59pm (end of day) timestamps

**Merge Tag System (`replace_date_merge_tags` method)**
- Custom merge tags for dynamic date display in messages:
  - `{start_date:FORMAT}` - Uses start_date with PHP date format
  - `{end_date:FORMAT}` - Uses end_date with PHP date format  
  - `{next_weekday:FORMAT}` - Calculates next business day after end_date (skips weekends)
- Example: `{start_date:l, F jS}` outputs "Monday, December 25th"

### Admin Interface

**Feed Settings UI**
- Uses Gravity Forms settings field types (`text`, `textarea` with editor)
- Custom field type `gffn_date` renders as text input with datepicker
- JavaScript (`js/feed-settings.js`) initializes jQuery UI datepickers
- AJAX preview shows how dates will be interpreted (`ajax_preview_date` method)

**Feed List Display**
- Custom columns: Name, Dates
- Feed ordering UI automatically available via framework
- Feeds can be duplicated (`can_duplicate_feed` returns true)

### Plugin Settings

Global settings (not per-form) allow customizing notice HTML output:
- `container_markup` setting defines wrapper HTML for each notice
- Uses `{content}` placeholder that gets replaced with actual message
- Default: `<div class="gffn-notice">{content}</div>`
- All notices wrapped in outer `<div class="gffn-notices">` container

## Development Notes

### WordPress/Gravity Forms Context

This is a WordPress plugin running in the Local by Flywheel environment at:
`/Users/david/Local Sites/wand/app/public/wp-content/plugins/gf-form-notices`

When testing, you need access to:
- WordPress admin at the Local site
- A Gravity Forms installation (required dependency)
- Forms to test the notice display

### Code Patterns

**Feed Add-On Framework Conventions**
- Settings defined in `feed_settings_fields()` and `plugin_settings_fields()` arrays
- Each field has: name, label, type, tooltip, class, required, etc.
- Feed metadata accessed via `rgar($feed['meta'], 'field_name')`
- Active feeds retrieved via `$this->get_active_feeds($form_id)`

**Gravity Forms Hooks**
- `gform_get_form_filter` - Modify form HTML before display
- `gform_custom_merge_tags` - Add custom merge tags to dropdown
- Uses `current_time('timestamp')` for WordPress timezone-aware dates

**Date Comparison Logic**
- Start date uses midnight timestamp for beginning of range
- End date uses 11:59pm timestamp for end of range
- Advance days subtracted from display start: `strtotime('-' . $advance_days . ' days', $start_timestamp)`
- Current timestamp must be >= display_start AND <= end to show notice

### File Structure

```
gf-form-notices/
├── gf-form-notices.php          # Bootstrap/entry point
├── class-gf-form-notices.php    # Main add-on class
├── css/
│   └── feed-settings.css        # Admin UI styles
├── js/
│   └── feed-settings.js         # Datepicker initialization and preview
├── readme.txt                   # WordPress.org plugin readme
└── changelog.txt                # Version history
```

### No Build Process

This plugin has no build system, package.json, or composer.json. All PHP, CSS, and JavaScript files are loaded directly. Changes take effect immediately after file save (may need to clear WordPress cache).

### WordPress Text Domain

All translatable strings use text domain `gf-form-notices` with `__()` or `esc_html()` functions.
