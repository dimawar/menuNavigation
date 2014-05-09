<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 06/11/13
 * Time: 12:30
 */

namespace Prime\NavigationBundle\Twig\Extension;

use Rybakit\Bundle\NavigationBundle\Navigation\Iterator\RecursiveTreeIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NavigationExtension extends \Twig_Extension
{

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    private $builder;
    private $defaultTemplate;


    function __construct($builder, $template)
    {
        $this->builder = $builder;
        $this->defaultTemplate = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'prime_navigation';
    }


    public function getFunctions()
    {
        return array(
            'prime_navigation' => new \Twig_Function_Method($this, 'render', array('is_safe' => array('html'))),
        );
    }

    public function render($menu, $templatePath = null)
    {

        $navigation = $this->builder->build($menu);

        if (!$templatePath) {
            $templatePath = $this->defaultTemplate;
        }

        $template = $this->environment->loadTemplate($templatePath);

        $iterator = new RecursiveTreeIterator($navigation['root']);

//        if (null !== $depth) {
//            $iterator->setMaxLevel((int) $depth - 1);
//        }

        return $template->renderBlock('navlist', array(
            'items' => $iterator,
        ));
    }
}