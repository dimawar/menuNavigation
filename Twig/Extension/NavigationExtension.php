<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 23.02.15
 * Time: 17:26
 */
namespace Prime\NavigationBundle\Twig\Extension;

use Prime\NavigationBundle\Navigation\Builder;
use Prime\NavigationBundle\Navigation\Page\Page;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationExtension extends AbstractExtension
{
    /** @var Builder */
    protected $builder;

    protected $config;

    public function __construct(Builder $builder, $config)
    {
        $this->builder = $builder;
        $this->config = $config;
    }

    public function getName()
    {
        return 'prime_navigation';
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('navigation', [$this, 'navigation'], [
                'is_safe' => ['html'],
                'needs_environment' => true
            ]),
            new TwigFunction('navigation_breadcrumbs', [$this, 'breadcrumbs'], [
                'is_safe' => ['html'],
                'needs_environment' => true
            ]),
        );
    }

    public function navigation(Environment $env, $alias, $options = null)
    {
        $navigation = $this->builder->buildFromAlias($alias);

        $template = $this->config['template'];
        if (!empty($options['template'])) {
            $template = $options['template'];
        }

        return $env->render($template, array(
            'navigation' => $navigation
        ));
    }

    public function breadcrumbs(Environment $env, $alias, $options = null)
    {
        $navigation = $this->builder->buildFromAlias($alias);
        $template = $this->config['breadcrumbs_template'];
        if (!empty($options['template'])) {
            $template = $options['template'];
        }

        $found  = null;
        $foundDepth = -1;

        $minDepth = 0;
        $maxDepth = null;

        $iterator = new \RecursiveIteratorIterator($navigation, \RecursiveIteratorIterator::CHILD_FIRST);
        $breadcrumbs = array();

        foreach ($iterator as $page) {
            $currDepth = $iterator->getDepth();
            if ($currDepth < $minDepth) {
                // page is not accepted
                continue;
            }
            if ($page->isActive(false) && $currDepth > $foundDepth) {
                // found an active page at a deeper level than before
                $found = $page;
                $foundDepth = $currDepth;
            }
        }

        if (is_int($maxDepth) && $foundDepth > $maxDepth) {
            while ($foundDepth > $maxDepth) {
                if (--$foundDepth < $minDepth) {
                    $found = null;
                    break;
                }
                $found = $found->getParent();
                if (!$found instanceof Page) {
                    $found = null;
                    break;
                }
            }
        }

        if ($found) {
            $breadcrumbs[] = $found;

            $active = $found;

            while ($parent = $active->getParent()) {
                if ($parent == $navigation) {
                    break;
                }

                $breadcrumbs[] = $parent;
                $active = $parent;
            }
        }

        return $env->render($template, array(
            'breadcrumbs' => array_reverse($breadcrumbs)
        ));
    }
}
