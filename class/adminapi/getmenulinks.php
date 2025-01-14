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
use xarSecurity;
use xarMod;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

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
        if ($this->checkAccess('AdminImages')) {
            if (xarMod::isAvailable('uploads') && $this->checkAccess('AdminUploads', 0)) {
                $menulinks[] = ['url'   => $this->getUrl('admin', 'uploads'),
                    'title' => $this->translate('View Uploaded Images'),
                    'label' => $this->translate('View Uploaded Images'), ];
            }
            $menulinks[] = ['url'   => $this->getUrl('admin', 'derivatives'),
                'title' => $this->translate('View Derivative Images'),
                'label' => $this->translate('View Derivative Images'), ];
            $menulinks[] = ['url'   => $this->getUrl('admin', 'browse'),
                'title' => $this->translate('Browse Server Images'),
                'label' => $this->translate('Browse Server Images'), ];
            /**
            $menulinks[] = ['url'   => $this->getUrl('admin', 'phpthumb'),
            'title' => $this->translate('Define Settings for Image Processing'),
            'label' => $this->translate('Image Processing'), ];
             */
            $menulinks[] = ['url'   => $this->getUrl('admin', 'modifyconfig'),
                'title' => $this->translate('Edit the Images Configuration'),
                'label' => $this->translate('Modify Config'), ];
        }
        if (empty($menulinks)) {
            $menulinks = '';
        }
        return $menulinks;
    }
}
