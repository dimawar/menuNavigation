<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 23.02.15
 * Time: 18:58
 */
namespace Prime\NavigationBundle\Navigation\Page;

use Prime\NavigationBundle\Navigation\Navigation;

class Page extends Navigation
{
    /**
     * Page order used by parent container
     *
     * @var int|null
     */
    protected $order;

    /**
     * Parent container
     *
     * @var Navigation|null
     */
    protected $parent;

    /**
     * Whether this page should be considered visible
     *
     * @var bool
     */
    protected $visible = true;

    /**
     * Page label
     *
     * @var string|null
     */
    protected $label;

    /**
     * Whether this page should be considered active
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Custom properties
     *
     * @var array
     */
    protected $extras = array();


    /**
     * HTML attributes
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Fragment identifier (anchor identifier)
     *
     * The fragment identifier (anchor identifier) pointing to an anchor within
     * a resource that is subordinate to another, primary resource.
     * The fragment identifier introduced by a hash mark "#".
     * Example: http://www.example.org/foo.html#bar ("bar" is the fragment identifier)
     *
     * @link http://www.w3.org/TR/html401/intro/intro.html#fragment-uri
     *
     * @var string|null
     */
    protected $fragment;
    protected $route;
    protected $routeParameters = array();
    protected $strictMatch = true;

    public function __construct(array $data = array())
    {
        if (!empty($data)) {
            $this->createFromArray($data);
        }
    }

    public function createFromArray(array $data)
    {
        foreach ($data as $key => $value) {
            $setterMethod = 'set'.ucfirst($key);
            if (method_exists($this, $setterMethod)) {
                $this->$setterMethod($value);
            }
        }
    }

    /**
     * Returns a hash code value for the page
     *
     * @return string  a hash code value for this page
     */
    final public function hashCode()
    {
        return spl_object_hash($this);
    }

    /**
     * @return Navigation|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets parent container
     *
     * @param  Navigation $parent [optional] new parent to set.
     *                           Default is null which will set no parent.
     * @throws \Exception
     * @return Page fluent interface, returns self
     */
    public function setParent(Navigation $parent = null)
    {
        if ($parent === $this) {
            throw new \Exception(
                'A page cannot have itself as a parent'
            );
        }

        // return if the given parent already is parent
        if ($parent === $this->parent) {
            return $this;
        }

        // remove from old parent
        if (null !== $this->parent) {
            $this->parent->removePage($this);
        }

        // set new parent
        $this->parent = $parent;

        // add to parent if page and not already a child
        if (null !== $this->parent && !$this->parent->hasPage($this, false)) {
            $this->parent->addPage($this);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Returns page order used in parent container
     *
     * @return int|null  page order or null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets page order to use in parent container
     *
     * @param  int $order [optional] page order in container.
     *                    Default is null, which sets no
     *                    specific order.
     * @return Page fluent interface, returns self
     * @throws \Exception if order is not integer or null
     */
    public function setOrder($order = null)
    {
        if (is_string($order)) {
            $temp = (int)$order;
            if ($temp < 0 || $temp > 0 || $order == '0') {
                $order = $temp;
            }
        }

        if (null !== $order && !is_int($order)) {
            throw new \Exception(
                'Invalid argument: $order must be an integer or null, ' .
                'or a string that casts to an integer'
            );
        }

        $this->order = $order;

        // notify parent, if any
        if (isset($this->parent)) {
            $this->parent->notifyOrderUpdated();
        }

        return $this;
    }

    /**
     * Returns a boolean value indicating whether the page is visible
     *
     * @param  bool $recursive [optional] whether page should be considered
     *                          invisible if parent is invisible. Default is
     *                          false.
     *
     * @return bool             whether page should be considered visible
     */
    public function isVisible($recursive = false)
    {
        if ($recursive && isset($this->parent) && $this->parent instanceof self) {
            if (!$this->parent->isVisible(true)) {
                return false;
            }
        }

        return $this->visible;
    }

    /**
     * @return null|string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param null|string $fragment
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
    }

    /**
     * @return array
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param array $extras
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Returns whether page should be considered active or not
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default is
     *                          false.
     * @return bool             whether page should be considered active
     */
    public function isActive($recursive = true)
    {
        if (!$this->active && $recursive) {
            foreach ($this->pages as $page) {
                if ($page->isActive(true)) {
                    return true;
                }
            }
        }

        $currentRoute = $this->request->get('_route');
        $currentRouteParameters = $this->request->get('_route_params');

        if ($currentRoute == $this->getRoute()) {
            if (!$this->isStrictMatch()) {
                $this->setActive(true);
                return true;
            }

            $routeParameters = $this->getRouteParameters();

            if (array_intersect($routeParameters, $currentRouteParameters) == $routeParameters) {
                $this->setActive(true);
                return true;
            }
        }

        return false;
    }

    /**
     * Proxy to isActive()
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default
     *                          is false.
     *
     * @return bool             whether page should be considered active
     */
    public function getActive($recursive = true)
    {
        return $this->isActive($recursive);
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     * @throws \Exception if route is invalid
     */
    public function setRoute($route)
    {
        if (!is_string($route)) {
            throw new \Exception(
                'Invalid argument: $route must be a string'
            );
        }
        $this->route = $route;
    }

    /**
     * @return boolean
     */
    public function isStrictMatch()
    {
        return $this->strictMatch;
    }

    /**
     * @param boolean $strictMatch
     */
    public function setStrictMatch($strictMatch)
    {
        $this->strictMatch = $strictMatch;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array $routeParameters
     */
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @param string $name
     * @return null
     */
    public function getExtra($name)
    {
        if (isset($this->extras[$name])) {
            return $this->extras[$name];
        }

        return null;
    }

    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return null;
    }

    public function toArray()
    {
        return array(
            'active' => $this->getActive(),
            'label' => $this->getLabel(),
            'order' => $this->getOrder(),
            'visible' => $this->isVisible(),
            'fragment' => $this->getFragment(),
            'extra' => $this->getExtras(),
            'attributes' => $this->getAttributes(),
            'route' => $this->getRoute(),
            'routeParameters' => $this->getRouteParameters(),
            'href' => $this->getHref(),
            'pages' => parent::toArray(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getHref()
    {
        if ($this->getRouter() && $this->strictMatch) {
            $routeParameters = $this->getRouteParameters();
            $href = $this->getRouter()->generate($this->getRoute(), $routeParameters);
            return $href;
        }

        return null;
    }
}
