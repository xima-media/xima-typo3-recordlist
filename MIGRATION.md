# Migration Guide: 13.x → 14.x

Version 14.x adds support for managing multiple tables in a single backend module.

## Breaking Changes

### Controller Interface Method

The `BackendControllerInterface` method signature changed:

```diff
class UserController extends AbstractBackendController
{
-   public function getTableName(): string
+   public function getTableNames(): array
    {
-       return 'fe_users';
+       return ['fe_users'];
    }
}
```

### Module Data Access (PHP)

Settings are now prefixed with table name using dot notation:

```diff
  $settings = $this->getModuleData()['settings'] ?? [];
- $offlineRecords = $settings['onlyOfflineRecords'] ?? false;
+ $offlineRecords = $settings[$this->getTableName() . '.onlyOfflineRecords'] ?? false;
```

### Fluid Templates

Settings are transformed into nested arrays via `getModuleDataSettingsForView()`:

```diff
- <f:if condition="{settings.onlyOfflineRecords}">
+ <f:if condition="{settings.{table}.onlyOfflineRecords}">
```

The `{table}` variable contains the current table name and is automatically available.

### Storing Custom Settings

When storing settings in `modifyQueryBuilder()`, prefix with table name:

```diff
  protected function modifyQueryBuilder(): void
  {
      $body = $this->request->getParsedBody();
      if (isset($body['custom_filter'])) {
-         $this->addToModuleDataSettings(['customFilter' => $body['custom_filter']]);
+         $this->addToModuleDataSettings([
+             $this->getTableName() . '.customFilter' => $body['custom_filter']
+         ]);
      }
  }
```

### TableConfiguration

No changes needed - `$this->tableConfiguration` is automatically scoped to the current table.

## Migration Steps

1. Update `getTableName()` → `getTableNames()` in your controller
2. Return array instead of string: `['table_name']`
3. Clear caches: `ddev typo3 cache:flush`
4. Test your module

## Troubleshooting

| Issue | Solution |
|-------|----------|
| `Class must implement method getTableNames()` | Change `getTableName(): string` to `getTableNames(): array` |
| Settings not in templates | Use `{settings.{table}.settingName}` instead of `{settings.settingName}` |
| Settings not persisting | Prefix keys: `$this->getTableName() . '.setting'` |

## Resources

- [Example Controllers](Classes/Controller/Example/) - Reference implementations
- [CHANGELOG.md](CHANGELOG.md) - Complete list of changes

## Rollback to 13.x

```bash
composer require "xima/xima-typo3-recordlist:^13.0"
```

Then revert `getTableNames()` back to `getTableName(): string`.
