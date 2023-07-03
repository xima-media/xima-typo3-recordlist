# TYPO3 Recordlist

This package allows you to quickly create backend modules for advanced record listing.

Workspaces integration: More simple workflow for requesting and approving changes.

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
the [Backend module API](https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/ExtensionArchitecture/HowTo/BackendModule/BackendModulesWithoutExtbase/BackendModuleApiWithoutExtbase.html).
You're free to adjust the settings as you like, the only import setting is the `routeTarget`: Make sure you always use
the `::mainAction`.

```php
ExtensionManagementUtility::addModule(
    'web',
    'users',
    '',
    '',
    [
        'routeTarget' => UserController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_events',
        'iconIdentifier' => 'your-module-icon',
        'labels' => 'LLL:EXT:your_ext/Resources/Private/Language/locallang_mod.xlf',
        'inheritNavigationComponentFromMainModule' => false,
    ]
);
```

That's it.

## Customization

### Template

To use a custom template, override the `TEMPLATE_NAME` constant in your controller and configure the template paths via
TypoScript constants:

```typo3_typoscript
module.tx_ximatypo3recordlist {
    view {
        partialRootPaths = EXT:your_ext/Resources/Private/Backend/Partials
        templateRootPath = EXT:your_ext/Resources/Private/Backend/Templates
        layoutRootPath = EXT:your_ext/Resources/Private/Backend/Layouts
    }
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
