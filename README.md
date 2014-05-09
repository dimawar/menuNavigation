# PrimeNavigationBundle

## Install

Add PrimeNavigationBundle to your composer.json

```
"require": {
    "rybakit/navigation-bundle": "dev-master",
    "prime/navigation-bundle": "dev-master"
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
// src/Acme/SampleBundle/Navigation/Builder.php

namespace Acme\SampleBundle\Navigation;


class Builder 
{

    public function mainNav()
    {
        return array(
            'label' => 'Root',
            'children' => array(
                array(
                    'label' => 'Homepage',
                    'route' => 'route_homepage'
                ),
            )
        );
    }
} 

```

### Render navigation

```
{{ prime_navigation('AcmeSampleBundle:Builder:mainNav') }}
```

### Configuration

``` yaml
// app/config.yml

prime_navigation:
    template: 'PrimeNavigationBundle::bs3.html.twig'
```