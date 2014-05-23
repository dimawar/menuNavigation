<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 09/05/14
 * Time: 16:20
 */

namespace Prime\NavigationBundle\Navigation;

use Rybakit\Bundle\NavigationBundle\Navigation\Filter\BindFilter;
use Rybakit\Bundle\NavigationBundle\Navigation\Filter\FilterChain;
use Rybakit\Bundle\NavigationBundle\Navigation\Filter\Matcher\RoutesMatcher;
use Rybakit\Bundle\NavigationBundle\Navigation\Filter\MatchFilter;
use Rybakit\Bundle\NavigationBundle\Navigation\Filter\UrlFilter;
use Rybakit\Bundle\NavigationBundle\Navigation\ItemFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Builder 
{
    private $container;
    private $kernel;


    function __construct(ContainerInterface $container, KernelInterface $kernel)
    {
        $this->container = $container;
        $this->kernel = $kernel;
    }


    public function build($navigation)
    {
        $route = $this->container->get('request')->attributes->get('_route');
        $routeParams = $this->container->get('request')->attributes->get('_route_params', array());

        $filter = new FilterChain(array(
            new UrlFilter($this->container->get('router')),
            $matchFilter = new MatchFilter(new RoutesMatcher($route, $routeParams)),
            new BindFilter(),
        ));

        $item = new Item();
        $item->transDomain = 'navigation';
        $factory = new ItemFactory($filter, $item);

        $classData = explode(':', $navigation);

        if (sizeof($classData) != 2) {
            list($bundleName, $className, $methodName) = $classData;

            $class = null;

            foreach ($this->kernel->getBundle($bundleName, false) as $bundle) {
                $try = $bundle->getNamespace().'\\Navigation\\'.$className;
                if (class_exists($try)) {
                    $class = $try;
                    break;
                }
            }

            $builder = new $class;            
        } else {
            list($service, $methodName) = $classData;

            $builder = $this->container->get($service);
            if (!$builder) {
                throw new \Exception('Service not found');
            }
        }

        if (!method_exists($builder, $methodName)) {
            throw new \Exception('Class not found');
        }

        $navigationTree = $builder->$methodName();

        $root = $factory->create($navigationTree);

        if (!$current = $matchFilter->getMatched()) {
            $current = $root;
        }
        $current->setActive();

        return array('root' => $root, 'current' => $current);
    }
} 