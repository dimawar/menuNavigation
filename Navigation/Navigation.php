<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 23.02.15
 * Time: 18:57
 */
namespace Prime\NavigationBundle\Navigation;

use Prime\NavigationBundle\Navigation\Page\Page;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

class Navigation implements \Countable, \RecursiveIterator
{
    /**
     * An index that contains the order in which to iterate pages
     *
     * @var array
     */
    protected $index = array();

    /**
     * Whether index is dirty and needs to be re-arranged
     *
     * @var bool
     */
    protected $dirtyIndex;

    /**
     * Contains sub pages
     *
     * @var Page[]
     */
    protected $pages = array();

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Request
     */
    protected $request;

    protected function sort()
    {
        if (!$this->dirtyIndex) {
            return;
        }

        $newIndex = array();
        $index = 0;

        foreach ($this->pages as $hash => $page) {
            $order = $page->getOrder();
            if ($order === null) {
                $newIndex[$hash] = $index;
                $index++;
            } else {
                $newIndex[$hash] = $order;
            }
        }

        asort($newIndex);
        $this->index = $newIndex;
        $this->dirtyIndex = false;
    }


    /**
     * Notifies container that the order of pages are updated
     *
     * @return void
     */
    public function notifyOrderUpdated()
    {
        $this->dirtyIndex = true;
    }

    /**
     * Returns number of pages in container
     *
     * Implements Countable interface.
     *
     * @return int number of pages in the container
     */
    public function count()
    {
        return count($this->index);
    }

    /**
     * Returns current page
     *
     * Implements RecursiveIterator interface.
     *
     * @return Page current page or null
     * @throws \Exception if the index is invalid
     */
    public function current()
    {
        $this->sort();

        current($this->index);
        $hash = key($this->index);
        if (!isset($this->pages[$hash])) {
            throw new \Exception(
                'Corruption detected in container; '
                . 'invalid key found in internal iterator'
            );
        }

        return $this->pages[$hash];
    }

    /**
     * Moves index pointer to next page in the container
     *
     * Implements RecursiveIterator interface.
     *
     * @return void
     */
    public function next()
    {
        $this->sort();
        next($this->index);
    }

    /**
     * Returns hash code of current page
     *
     * Implements RecursiveIterator interface.
     *
     * @return string  hash code of current page
     */
    public function key()
    {
        $this->sort();
        return key($this->index);
    }

    /**
     * Checks if container index is valid
     *
     * Implements RecursiveIterator interface.
     *
     * @return bool
     */
    public function valid()
    {
        $this->sort();
        return current($this->index) !== false;
    }

    /**
     * Sets index pointer to first page in the container
     *
     * Implements RecursiveIterator interface.
     *
     * @return void
     */
    public function rewind()
    {
        $this->sort();
        reset($this->index);
    }

    /**
     * Proxy to hasPages()
     *
     * Implements RecursiveIterator interface.
     *
     * @return bool whether container has any pages
     */
    public function hasChildren()
    {
        return $this->valid() && $this->current()->hasPages();
    }

    /**
     * Returns the child container.
     *
     * Implements RecursiveIterator interface.
     *
     * @return Page|null
     */
    public function getChildren()
    {
        $hash = key($this->index);

        if (isset($this->pages[$hash])) {
            return $this->pages[$hash];
        }

        return null;
    }


    /**
     * Adds a page to the container
     *
     * This method will inject the container as the given page's parent by
     * calling {@link Page::setParent()}.
     *
     * @param  Page|array|\Traversable $page page to add
     * @return self fluent interface, returns self
     * @throws \Exception if page is invalid
     */
    public function addPage($page)
    {
        if ($page === $this) {
            throw new \Exception(
                'A page cannot have itself as a parent'
            );
        }

        if (!$page instanceof Page) {
            if (!is_array($page) && !$page instanceof \Traversable) {
                throw new \Exception(
                    'Invalid argument: $page must be an instance of '
                    . 'Page or Traversable, or an array'
                );
            }

            $page = new Page($page);
        }

        $hash = $page->hashCode();

        if (array_key_exists($hash, $this->index)) {
            // page is already in container
            return $this;
        }

        // adds page to container and sets dirty flag
        $this->pages[$hash] = $page;
        $this->index[$hash] = $page->getOrder();
        $this->dirtyIndex = true;

        // inject self as page parent
        $page->setParent($this);

        return $this;
    }

    /**
     * Adds several pages at once
     *
     * @param  array|\Traversable|Navigation $pages pages to add
     * @return self fluent interface, returns self
     * @throws \Exception if $pages is not array, Traversable or Navigation
     */
    public function addPages($pages)
    {
        if (!is_array($pages) && !$pages instanceof \Traversable) {
            throw new \Exception(
                'Invalid argument: $pages must be an array, an '
                . 'instance of Traversable or an instance of '
                . 'Navigation'
            );
        }

        // Because adding a page to a container removes it from the original
        // (see {@link Page::setParent()}), iteration of the
        // original container will break. As such, we need to iterate the
        // container into an array first.
        if ($pages instanceof Navigation) {
            $pages = iterator_to_array($pages);
        }

        foreach ($pages as $page) {
            if (null === $page) {
                continue;
            }
            $this->addPage($page);
        }

        return $this;
    }

    /**
     * Sets pages this container should have, removing existing pages
     *
     * @param  array $pages pages to set
     * @return self fluent interface, returns self
     */
    public function setPages(array $pages)
    {
        $this->removePages();
        return $this->addPages($pages);
    }

    /**
     * Returns pages in the container
     *
     * @return Page[] array of Page instances
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Removes the given page from the container
     *
     * @param  Page|int $page page to remove, either a page instance or a specific page order
     * @param  bool $recursive [optional] whether to remove recursively
     * @return bool whether the removal was successful
     */
    public function removePage($page, $recursive = false)
    {
        if ($page instanceof Page) {
            $hash = $page->hashCode();
        } elseif (is_int($page)) {
            $this->sort();
            if (!$hash = array_search($page, $this->index)) {
                return false;
            }
        } else {
            return false;
        }

        if (isset($this->pages[$hash])) {
            unset($this->pages[$hash]);
            unset($this->index[$hash]);
            $this->dirtyIndex = true;
            return true;
        }

        if ($recursive) {
            /** @var Page $childPage */
            foreach ($this->pages as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    $childPage->removePage($page, true);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Removes all pages in container
     *
     * @return self fluent interface, returns self
     */
    public function removePages()
    {
        $this->pages = array();
        $this->index = array();
        return $this;
    }

    /**
     * Checks if the container has the given page
     *
     * @param  Page $page page to look for
     * @param  bool $recursive [optional] whether to search recursively. Default is false.
     * @return bool whether page is in container
     */
    public function hasPage(Page $page, $recursive = false)
    {
        if (array_key_exists($page->hashCode(), $this->index)) {
            return true;
        } elseif ($recursive) {
            foreach ($this->pages as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns true if container contains any pages
     *
     * @param  bool $onlyVisible whether to check only visible pages
     * @return bool  whether container has any pages
     */
    public function hasPages($onlyVisible = false)
    {
        if ($onlyVisible) {
            foreach ($this->pages as $page) {
                if ($page->isVisible()) {
                    return true;
                }
            }
            // no visible pages found
            return false;
        }
        return count($this->index) > 0;
    }

    /**
     * Returns a child page matching $property == $value, or null if not found
     *
     * @param  string $property name of property to match against
     * @param  mixed $value value to match property against
     * @return Page|null  matching page or null
     */
    public function findOneBy($property, $value)
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $page) {
            $getMethod = 'get' . ucfirst($property);

            if (method_exists($page, $getMethod) && $page->$getMethod() == $value) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Returns all child pages matching $property == $value, or an empty array
     * if no pages are found
     *
     * @param  string $property name of property to match against
     * @param  mixed $value value to match property against
     * @return array  array containing only Page instances
     */
    public function findAllBy($property, $value)
    {
        $found = array();

        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $page) {
            $getMethod = 'get' . ucfirst($property);

            if (method_exists($page, $getMethod) && $page->$getMethod() == $value) {
                $found[] = $page;
            }
        }

        return $found;
    }

    /**
     * Returns page(s) matching $property == $value
     *
     * @param  string $property name of property to match against
     * @param  mixed $value value to match property against
     * @param  bool $all [optional] whether an array of all matching
     *                           pages should be returned, or only the first.
     *                           If true, an array will be returned, even if not
     *                           matching pages are found. If false, null will
     *                           be returned if no matching page is found.
     *                           Default is false.
     * @return Page|null  matching page or null
     */
    public function findBy($property, $value, $all = false)
    {
        if ($all) {
            return $this->findAllBy($property, $value);
        }

        return $this->findOneBy($property, $value);
    }

    /**
     * Returns an array representation of all pages in container
     *
     * @return array
     */
    public function toArray()
    {
        $this->sort();
        $pages = array();
        $indexes = array_keys($this->index);
        foreach ($indexes as $hash) {
            $pages[] = $this->pages[$hash]->toArray();
        }
        return $pages;
    }

    /**
     * @return Router|null
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param Router $router
     */
    public function setRouter($router, $recursive = false)
    {
        $this->router = $router;
        if ($recursive) {
            foreach ($this->getPages() as $page) {
                $page->setRouter($router, true);
            }
        }
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @param bool $recursive
     */
    public function setRequest(Request $request, $recursive = false)
    {
        $this->request = $request;
        if ($recursive) {
            foreach ($this->getPages() as $page) {
                $page->setRequest($request, true);
            }
        }
    }
}
