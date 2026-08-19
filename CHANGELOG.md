# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Features

- **Multiple Record Sources**: New `getRecordSources()` override exposes records from several pages/folders at once via `RecordSource` objects, each with an optional recursive `includeSubpages` flag and `depth`. Replaces the implicit "single pid + direct children" entry point.
- **Multi-Site Directories**: Accessible pages spanning more than one site get site-prefixed labels (e.g. `Site A › News`) in the directory dropdown and the new-record modal, disambiguating identically named folders across mandants.

### Bug Fixes

- **Workspace Draft Preview**: The preview button of a workspace-aware controller opened the live version instead of the draft. Since TYPO3 v13 the preview URI listener of `EXT:workspaces` evaluates the workspace aspect of the `Context` rather than the backend user, so the module workspace is now passed to `PreviewUriBuilder::buildUri()` via a dedicated `Context`. The `workspace_previewcontrols` route is handled by `CurrentBackendWorkspaceManipulation`, and `CurrentFrontendWorkspaceManipulation` now runs before page resolution and `PreviewSimulator` so that preview mode and the cache bypass are actually activated — previously workspace content could be written into the live page cache. The frontend middleware additionally verifies that the backend user may access the requested workspace.
- **Root/First Page Selectable on Create**: The new-record modal now lists every accessible page (including the configured root/first page), which was previously excluded so only subpages could be chosen.
- **First Directory Filterable**: The directory dropdown now has an explicit "All directories" entry and uses a `scope` query parameter, so selecting the first directory filters to it instead of implicitly showing all pages.

### Deprecations

- **`getRecordPid()`** is deprecated since 14.6.0 and will be removed in 15.0.0. Implement `getRecordSources()` instead. Controllers still implementing `getRecordPid()` continue to work (the default `getRecordSources()` falls back to it) but emit an `E_USER_DEPRECATED` notice.

### Backwards Compatibility

- Controllers implementing only the deprecated `getRecordPid()` are unaffected at runtime — the default `getRecordSources()` reproduces the previous "configured page + direct children" behaviour.

## [14.x] - Multiple Tables Support, Template Configuration, View Dropdown & Row Selection

### Features

- **Multiple Tables Support**: Backend modules can now manage multiple tables with a table selection menu
- **Table-Prefixed Settings**: Module data uses dot notation (e.g., `tableName.settingKey`) for proper table isolation
- **View Button Dropdown**: New unified dropdown in the doc header containing view-related actions
- **Template Configurations**: Define multiple templates per controller with custom layouts and actions
- **Per-Template Actions**: Control which buttons/features are available for each template via the `actions` configuration
- **Template Selection**: Users can switch between templates from the View dropdown (saved per table)
- **New Example Controllers**: Added `BeUsersController` demonstrating multiple related tables (be_users, be_groups, sys_filemounts)
- **Root Page Support**: Added handling for root-level pages (pageUid = 0)
- **Fluid Template Helper**: Settings are automatically transformed into nested arrays for easier Fluid template access
- **Row Selection**: Checkbox, row click, and keyboard shortcuts (Shift+Click, Ctrl/Alt+Click) for selecting records
- **Improved Sorting UI**: Dropdown menu for sorting in table headers with improved positioning
- **Batch Field Editing**: Edit specific fields across multiple records from column header dropdown
- **Multi-Selection Controls**: Check All, Uncheck All, and Toggle Selection buttons in checkbox column header
- **Export Functionality**: Download selected records and fields in new formats (XLSX, JSON)
- **Independent Icon Column**: Icon column is now a dedicated table column (always visible by default, positioned after checkbox)
- **Configurable Fixed Columns**: Both checkbox and icon columns can be hidden per table using `showCheckboxColumn` and `showIconColumn` flags
- **Special Columns**: New automatically-generated UID and PID columns available in column selector (disabled by default)
- **Language Indentation**: Moved to the icon column with improved CSS targeting

### Breaking Changes

- `getTableName(): string` method replaced with `getTableNames(): array`
- Module data settings now use dot notation: `tableName.settingKey` instead of `settingKey`
- Fluid templates require scoped access: `{settings.{table}.settingKey}` instead of `{settings.settingKey}`
- **Icon Column Architecture**: Icons are no longer part of column configuration, now appear as independent column after checkbox
- **Removed Properties**: `icon` and `languageIndent` properties removed from column configuration
- **Template Structure**: Custom templates overriding `Default.html` must be updated to include new icon column structure
- **CSS Selectors**: Language indent selector changed from `td[data-language-indent="1"] > *:first-child` to `td.col-icon[data-language-indent="1"]`

### Migration Guide

See [MIGRATION.md](MIGRATION.md) for detailed migration instructions from 13.x to 14.x.

## [13.x] - Initial Release

### Features

- List records from any table
- Filter records by any field
- Sort records by any field
- Configurable + sortable columns
- Inline editing support
- Optional workspaces integration
