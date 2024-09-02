# PrimeNavigationBundle

### Install

Add PrimeNavigationBundle to your composer.json

```
composer require dimawar/menu-navigation-bundle
```

### Create your first navigation

``` php

<?php
// src/App/Navigation/MainNavigation.php

namespace App\Navigation;


class AdminNavigation
{

    public function build()
    {
        return [
            [
                'label' => 'Page #1',
                'route' => 'route_homepage'
            ],
            [
                'label' => 'Page #2',
                'route' => 'route_page',
                'routeParameters => ['slug' => 'page_2']
            ],
        ];
    }
}

```

### Render navigation

```
{{ navigation('app.admin_navigation', {'template': 'Navigation/admin.html.twig'}) }}
```

### Configuration

``` yaml
// config/services.yaml

    app.admin_navigation:
        class: App\Navigation\AdminNavigation
        arguments: [ "@doctrine.orm.entity_manager" ]
        public: true
```
