<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 23.02.15
 * Time: 20:12
 */
namespace Prime\NavigationBundle\Navigation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Builder
{

    protected $container;
    protected $kernel;

    public function __construct(ContainerInterface $container, KernelInterface $kernel)
    {
        $this->container = $container;
        $this->kernel = $kernel;
    }

    public function buildFromAlias($alias)
    {
        $data = explode(':', $alias);
        $class = null;
        if (sizeof($data) == 3) {
            list($classBundle, $classPath, $className) = $data;
            foreach ($this->kernel->getBundle($classBundle, false) as $bundle) {
                $try = $this->getNormalizedClassName($bundle->getNamespace(), $classPath, $className);
                if (class_exists($try)) {
                    $class = new $try;
                    break;
                }
            }

            if (!$class) {
                throw new \Exception('Navigation class not found');
            }

        } elseif (sizeof($data) == 1) {
            $class = $this->container->get($alias);
            if (!$class) {
                throw new \Exception('Service ' . $alias . ' not found');
            }
        }

        $tree = $class->build();

        $navigation = new Navigation();
        $navigation->addPages($tree);
        $navigation->setRouter($this->container->get('router'), true);
        $requestStack = $this->container->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $navigation->setRequest($request, true);

        return $navigation;
    }

    protected function getNormalizedClassName($bundleNamespace, $classPath, $className)
    {
        return $bundleNamespace . '\\' . $classPath . '\\' . ucfirst($className) . 'Navigation';
    }
}
