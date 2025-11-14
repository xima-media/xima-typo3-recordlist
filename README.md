<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 Recordlist

</div>

This package allows you to quickly create backend modules for advanced record listing.

Optional workspaces integration: More simple workflow for requesting and approving changes.

![Screenshot](Documentation/Images/Preview.png)

## Features

* List records from any table
* Filter records by any field
* Sort records by any field
* Configurable + sortable columns
* Inline editing support

## Install

```bash
composer require xima/xima-typo3-recordlist
```

## Usage

Start by creating a new backend controller in your TYPO3 extension.

### 1. Extend new controller from `AbstractBackendController`

The controller implements the `BackendControllerInterface` which requires you to add the
methods `getTableName()` and `getRecordPid()`:

```php
<?php
// EXT:my_extension/Classes/Controller/UserController.php

namespace Vendor\MyExtension\Controller\Backend;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class UserController extends AbstractBackendController
{
    public function getTableName(): string
    {
        return 'fe_users';
    }

    public function getRecordPid(): int
    {
        return $this->site->getConfiguration()['userPid'] ?? 0;
    }
}
```

### 2. Register Backend module

Add a new backend module via
the [Backend module API](https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html).
You're free to adjust the settings as you like, the only important setting is the `controllerActions`, which needs to point to your newly
created controller:

```php
<?php
// EXT:my_extension/Configuration/Backend/Modules.php

use Xima\XimaTypo3Recordlist\Controller\ExamplePagesController;

return [
    'example_pages' => [
        'parent' => 'web',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-cshmanual',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang_pages_module.xlf',
        'extensionName' => 'MyExtension',
        'controllerActions' => [
            ExamplePagesController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
];

```

### 3. Configure template path

To use the template and partials, you need to add the template path to your
sitepackge [with TSconfig](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-96812-OverrideBackendTemplatesWithTSconfig.html#feature-96812):

```
# EXT:my_extension/Configuration/page.tsconfig
templates.vendor/my-extension.1740563365 = xima/xima-typo3-recordlist:Resources/Private/
```

That's it. You can find working examples in the [Example directory](Classes/Controller/Example).

## Customization

### Template

To a customize the template, partials or sections, you need to configure an additional template path in your TSconfig:

```
# EXT:my_extension/Configuration/page.tsconfig
templates.vendor/my-extension.1740570140 = my-vendor/my-extension:Resources/Private/TemplateOverrides
```

Inside your TemplateOverrides folder, create a `templates` directory, copy the [Default.html](Resources/Private/Templates/Default.html) file
into it and adjust it to your needs.

In case you have multiple backend modules, you can adjust the template name by overriding the `TEMPLATE_NAME` constant in your controller:

```php
<?php

class UserController extends AbstractBackendController
{
    protected const TEMPLATE_NAME = 'Custom';
}
```

### Data

Each record item can be modified using the `modifyRecord` method:

```php
class UserController extends AbstractBackendController
{
    public function modifyRecord(array &$record): void
    {
        $record['fullName'] = $record['first_name'] . ' ' . $record['last_name'];
    }
}
```

Add new filter options

```php
class UserController extends AbstractBackendController
{
    public function modifyQueryBuilder(): void
    {
        if (isset($body['register_date']) && $body['register_date']) {
            $registerDate = new DateTime($body['register_date']);
            $this->additionalConstraints[] = $this->queryBuilder->expr()->gte('register_date', $registerDate->getTimestamp());
        }
    }
}
```

### Default columns

To change the default columns, you can override the `modifyTableConfiguration` method:

```php
<?php

class NewsController extends AbstractBackendController
{
    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['columns']['fal_media']['defaultPosition'] = 2;
        $this->tableConfiguration['columns']['author']['defaultPosition'] = 3;
        $this->tableConfiguration['columns']['sitemap_changefreq']['defaultPosition'] = 4;
        $this->tableConfiguration['columns']['sys_language_uid']['defaultPosition'] = 5;
        $this->tableConfiguration['columns']['workspace-status']['defaultPosition'] = 6;
    }
}
```

### Custom Columns

To add custom columns, you can override the `modifyTableConfiguration` method:

```php
<?php

class UserController extends AbstractBackendController
{
    public function modifyTableConfiguration(): void
    {
        // make title field inline editiable
        $this->tableConfiguration['columns']['title']['partial'] = 'TextInlineEdit';
    }
}
```

### View Action

The view button is automatically displayed if [TCEMAIN.preview](https://docs.typo3.org/permalink/t3tsref:pagetcemain-preview) is configured for this table.

To manually add a view action, you can override the `url` property of records:

```php

class UserController extends AbstractBackendController
{
    protected function modifyPaginatedRecords(): void
    {
        parent::modifyPaginatedRecords();

        foreach ($this->records as &$record) {
            $record['url'] = 'https://example.com/view/' . $record['uid'];
        }
    }
}
```

## Development and Contribution

For easy development, you can use the provided ddev setup. Simply run `ddev start` and open the URL in your browser.

After a `composer install`, you can run `ddev init-typo3` to setup a TYPO3 installation with example data. Login with `admin` / `Passw0rd!` and `editor` / `Passw0rd!`.
