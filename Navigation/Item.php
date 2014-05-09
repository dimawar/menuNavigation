<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 09/05/14
 * Time: 17:35
 */

namespace Prime\NavigationBundle\Navigation;

use Rybakit\Bundle\NavigationBundle\Navigation\Item as BaseItem;

class Item extends BaseItem
{

    protected $icon;

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }


} 