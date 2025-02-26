# TYPO3 Recordlist

This package allows you to quickly create backend modules for advanced record listing.

Optional workspaces integration: More simple workflow for requesting and approving changes.

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
You're free to adjust the settings as you like, the only important setting is the `controllerActions`, which needs to point to your newly created controller:

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

To use the template and partials, you need to add the template path to your sitepackge [with TSconfig](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-96812-OverrideBackendTemplatesWithTSconfig.html#feature-96812):

```
# EXT:my_extension/Configuration/page.tsconfig
templates.vendor/my-extension.1740563365 = xima/xima-typo3-recordlist:Resources/Private/
```

That's it. You can find working examples in the [examples_sitepackage](Tests/examples_sitepackage) directorys.

## Customization

### Template

To a customize the template, partials or sections, you need to configure an additional template path in your TSconfig:

```
# EXT:my_extension/Configuration/page.tsconfig
templates.vendor/my-extension.1740570140 = my-vendor/my-extension:Resources/Private/TemplateOverrides
```

Inside your TemplateOverrides folder, create a `templates` directory, copy the [Default.html](Resources/Private/Templates/Default.html) file into it and adjust it to your needs.

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
    public function addAdditionalConstraints(): array
    {
        $constraints = [];
        $body = $this->request->getParsedBody();
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');

        // Set default value + check POST override
        $registerDate = new DateTime();
        if (isset($body['register_date']) && $body['register_date']) {
            $registerDate = new DateTime($body['register_date']);
        }

        // Add value for form element to template
        $this->view->assign('register_date', $registerDate);

        // Add default condition
        $constraints[] = $qb->expr()->gte('register_date', $registerDate->getTimestamp());

        return $constraints;
    }
}
```
