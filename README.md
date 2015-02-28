# PrimeNavigationBundle

### Install

Add PrimeNavigationBundle to your composer.json

```
"require": {
    "prime/navigation-bundle": "v2.0-beta"
}
```

### Enable bundle

Add bundle to AppKernel

``` php
<?php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Prime\NavigationBundle\PrimeNavigationBundle(),
    );
}

```

### Create your first navigation

``` php

<?php
// src/AppBundle/Navigation/MainNavigation.php

namespace AppBundleBundle\Navigation;


class MainNavigation
{

    public function build()
    {
        return array(
            array(
                'label' => 'Page #1',
                'route' => 'route_homepage'
            ),
            array(
                'label' => 'Page #2',
                'route' => 'route_page',
                'routeParameters => array('slug' => 'page_2')
            ),
        );
    }
}

```

### Render navigation

```
{{ navigation('AppBundle:Navigation:Main') }}
```

### Configuration

``` yaml
// app/config.yml

prime_navigation:
    template: 'PrimeNavigationBundle:Navigation:simple.html.twig'
    breadcrumbs_template: 'PrimeNavigationBundle:Navigation:breadcrumbs.html.twig'
```