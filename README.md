# Recordlist

## Install

```bash
composer require xima/xima-typo3-recordlist
```

## Usage

### 1. Extend new controller from `AbstractBackendController`

The abstract controller implements the `BackendControllerInterface` which requires you to add the
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

```php
ExtensionManagementUtility::addModule(
    'web',
    'events',
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

### 3. Add Template

The name of the template is determined by the controller name, e.g. `UserController` will load a `User.html` from the configured template paths.

The configuration of template paths is done via TypoScript constants:

```typo3_typoscript
module.tx_ximatypo3recordlist {
    view {
      partialRootPaths =  EXT:your_ext/Resources/Private/Backend/Partials
      templateRootPath =  EXT:your_ext/Resources/Private/Backend/Templates
      layoutRootPath =  EXT:your_ext/Resources/Private/Backend/Layouts
    }
}
```

Have a look into the `ExampleTemplate.html`

## Customization

### Controller methods

```editRecord(&array)```

```php
class UserController extends AbstractBackendController
{
    public function modifyRecord(array &$record): void
    {
        $record['fullName'] = $record['first_name'] . ' ' . $record['last_name'];
    }
}
```
