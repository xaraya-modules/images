<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\AdminApi;

use Xaraya\Modules\Images\AdminApi;
use Xaraya\Modules\MethodClass;

/**
 * images adminapi getmenulinks function
 * @extends MethodClass<AdminApi>
 */
class GetmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function pass individual menu items to the main menu
     * @return array containing the menulinks for the main menu items.
     * @see AdminApi::getmenulinks()
     */
    public function __invoke(array $args = [])
    {
        if ($this->sec()->checkAccess('AdminImages')) {
            if ($this->mod()->isAvailable('uploads') && $this->sec()->checkAccess('AdminUploads', 0)) {
                $menulinks[] = ['url'   => $this->mod()->getURL('admin', 'uploads'),
                    'title' => $this->ml('View Uploaded Images'),
                    'label' => $this->ml('View Uploaded Images'), ];
            }
            $menulinks[] = ['url'   => $this->mod()->getURL('admin', 'derivatives'),
                'title' => $this->ml('View Derivative Images'),
                'label' => $this->ml('View Derivative Images'), ];
            $menulinks[] = ['url'   => $this->mod()->getURL('admin', 'browse'),
                'title' => $this->ml('Browse Server Images'),
                'label' => $this->ml('Browse Server Images'), ];
            /**
            $menulinks[] = ['url'   => $this->mod()->getURL('admin', 'phpthumb'),
            'title' => $this->ml('Define Settings for Image Processing'),
            'label' => $this->ml('Image Processing'), ];
             */
            $menulinks[] = ['url'   => $this->mod()->getURL('admin', 'modifyconfig'),
                'title' => $this->ml('Edit the Images Configuration'),
                'label' => $this->ml('Modify Config'), ];
        }
        if (empty($menulinks)) {
            $menulinks = '';
        }
        return $menulinks;
    }
}
