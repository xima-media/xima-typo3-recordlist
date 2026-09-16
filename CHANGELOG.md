# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Bug Fixes

- **Directory Selection**: The directory selected in the doc header lived in the query parameters of the menu links only, so re-entering the module reset the list to "All directories". The selected page and scope are now stored in the backend user settings and restored on the next visit.

## [15.0.0] - 2026-09-15

### Breaking Changes

- **Preview URI Key**: The preview URI moved from the record key `url` to `_previewUrl`. Controllers setting their own view links in `modifyPaginatedRecords()` and templates overriding `Actions/View.html` must use the new key.
- **Meta Field Keys**: The computed record keys `state`, `editable`, `referencesToPublish`, `possible_translations` and `possible_translations_deepl` are prefixed and camel-cased as `_state`, `_editable`, `_referencesToPublish`, `_possibleTranslations` and `_possibleTranslationsDeepl`, so they no longer collide with database columns of the same name. Custom templates reading them must use the new keys. The rendered `data-state` attribute and the `workspace-state-*` CSS classes are unchanged.

### Bug Fixes

- **Columns Outside a Record Type**: For tables with record types (`ctrl.type`), the list rendered every active column for every record, so a column that is not part of a record's type showed its database default — a value the editor can neither see nor change in FormEngine. Such cells now stay empty. The type of each record is resolved via `BackendUtility::getTCAtypeValue()`, so pointer fields in the `field:relationField` form are covered, and columns a type adds only through `columnsOverrides` count as configured. Columns that no type lists at all — system fields such as `crdate`, `sorting` or `sys_language_uid` — belong to every record and keep their value.
- **Preview Action for `url` Columns**: The preview URI was stored in the record key `url` and collided with a database column of the same name, so the view button linked to the column value, or the column showed the preview URI. The URI now lives in `_previewUrl`.
- **Multiple New Buttons**: Overriding `addNewButtonToModuleTemplate()` to register more than one "New" button rendered each of them with fully rounded corners instead of one joined button group. The v14 border fix is now scoped to the trailing button of the group.

## [14.8.0] - 2026-08-24

### Features

- **Optional Action Grouping**: `enableActionGroups` in the table configuration switches the translation/workspace action dropdowns off per table, rendering every row action as a plain button again.

### Bug Fixes

- **Workspace Draft Preview**: The preview button of a workspace-aware controller opened the live version instead of the draft. The preview URI listener of `EXT:workspaces` evaluates the workspace aspect of the `Context` rather than the backend user, so the module workspace is now passed to `PreviewUriBuilder::buildUri()` via a dedicated `Context` per record. `CurrentFrontendWorkspaceManipulation` now runs before page resolution and `PreviewSimulator` so that preview mode and the cache bypass are actually activated — previously workspace content could be written into the live page cache. The middleware additionally verifies that the backend user may access the requested workspace.
- **Workspace Preview from the Editing Form**: The view button of the record editing form (route `record_edit`) linked to the native workspace split preview module, which expects the workspace to be actively selected, and resulted in an error. The new `WorkspacePreviewUriRewriter` event listener keeps `EXT:workspaces` from redirecting there, so every preview URI TYPO3 builds for a manipulated workspace is a direct frontend URI and behaves like the record list view button. Scoped to requests of this extension via `WorkspacePreviewState`, regular workspace usage of the installation is unaffected.
- **Filter Panel After Search Reset**: Resetting the search form no longer collapses the filter panel.
- **Category Filter Defaults**: The category filter ignores `TCAdefaults` configured for the category field, which otherwise preselected categories nobody asked for.

## [14.7.1] - 2026-07-20

### Bug Fixes

- **Category Filter Storage**: The category filter form element uses the current pid, so the category tree is read from the page the module actually lists.

## [14.7.0] - 2026-07-05

### Features

- **Filter Items via FormDataCompiler**: Select and category filter items are resolved through the `FormDataCompiler`, so filters see the same items FormEngine does, including items added by TCA item procs.

### Bug Fixes

- **Preview Link Fallback**: Preview link generation falls back to the record pid when no `previewPageId` is configured.

## [14.6.0] - 2026-06-22

### Features

- **Multiple Record Sources**: New `getRecordSources()` override exposes records from several pages/folders at once via `RecordSource` objects, each with an optional recursive `includeSubpages` flag and `depth`. Replaces the implicit "single pid + direct children" entry point.
- **Multi-Site Directories**: Accessible pages spanning more than one site get site-prefixed labels (e.g. `Site A › News`) in the directory dropdown and the new-record modal, disambiguating identically named folders across mandants.
- **Missing Storage Message**: Modules without an accessible record storage show an explicit error message instead of an empty list.

### Bug Fixes

- **Root/First Page Selectable on Create**: The new-record modal now lists every accessible page (including the configured root/first page), which was previously excluded so only subpages could be chosen.
- **First Directory Filterable**: The directory dropdown now has an explicit "All directories" entry and uses a `scope` query parameter, so selecting the first directory filters to it instead of implicitly showing all pages.

### Deprecations

- **`getRecordPid()`** is deprecated since 14.6.0 and will be removed in 15.0.0. Implement `getRecordSources()` instead. Controllers still implementing `getRecordPid()` continue to work (the default `getRecordSources()` falls back to it) but emit an `E_USER_DEPRECATED` notice.

### Backwards Compatibility

- Controllers implementing only the deprecated `getRecordPid()` are unaffected at runtime — the default `getRecordSources()` reproduces the previous "configured page + direct children" behaviour.

## [14.5.0] - 2026-06-21

### Features

- **Sorting Actions**: Row actions to move a record up or down in manual sort order.
- **Grouped Workspace and Language Actions**: Workspace and translation actions are collapsed into dropdowns to keep the action column narrow.

### Bug Fixes

- **Start- and Endtime Restriction**: The list no longer hides records whose start/endtime lies outside the current time.
- **Composer Package Type**: The package declares `typo3-cms-extension` instead of `library`, so strict extension discovery (the TYPO3 testing framework in particular) recognizes it and registers its `Services.yaml`.

## [14.4.0] - 2026-06-10

### Features

- **Default Filters**: Filters can be preset per table, applied on first visit of the module.
- **Workspace Status Filter**: The workspace status filter uses select inputs.
- **crdate and tstamp Columns**: Both timestamps are available as columns.
- **Reset View Button**: Restores the default columns, filters and sorting of a table.
- **Empty State Message**: Lists without records show an explicit message.

## [14.0.0 - 14.3.0] - Multiple Tables Support, Template Configuration, View Dropdown & Row Selection

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

## [13.0.0] - Initial Release

### Features

- List records from any table
- Filter records by any field
- Sort records by any field
- Configurable + sortable columns
- Inline editing support
- Optional workspaces integration
