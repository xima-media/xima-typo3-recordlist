# Changelog

All notable changes to this project will be documented in this file.

## [14.x] - Multiple Tables Support & Row Selection

### Features

- **Multiple Tables Support**: Backend modules can now manage multiple tables with a table selection menu
- **Table-Prefixed Settings**: Module data uses dot notation (e.g., `tableName.settingKey`) for proper table isolation
- **New Example Controllers**: Added `BeUsersController` demonstrating multiple related tables (be_users, be_groups, sys_filemounts)
- **Root Page Support**: Added handling for root-level pages (pageUid = 0)
- **Fluid Template Helper**: Settings are automatically transformed into nested arrays for easier Fluid template access
- **Row Selection**: Checkbox, row click, and keyboard shortcuts (Shift+Click, Ctrl/Alt+Click) for selecting records
- **Batch Field Editing**: Edit specific fields across multiple records from column header dropdown
- **Multi-Selection Controls**: Check All, Uncheck All, and Toggle Selection buttons in checkbox column header

### Breaking Changes

- `getTableName(): string` method replaced with `getTableNames(): array`
- Module data settings now use dot notation: `tableName.settingKey` instead of `settingKey`
- Fluid templates require scoped access: `{settings.{table}.settingKey}` instead of `{settings.settingKey}`

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
