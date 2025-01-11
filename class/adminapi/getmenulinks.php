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
     */
    public function __invoke(array $args = [])
    {
        if (xarSecurity::check('AdminImages')) {
            if (xarMod::isAvailable('uploads') && xarSecurity::check('AdminUploads', 0)) {
                $menulinks[] = ['url'   => xarController::URL(
                    'images',
                    'admin',
                    'uploads'
                ),
                    'title' => xarML('View Uploaded Images'),
                    'label' => xarML('View Uploaded Images'), ];
            }
            $menulinks[] = ['url'   => xarController::URL(
                'images',
                'admin',
                'derivatives'
            ),
                'title' => xarML('View Derivative Images'),
                'label' => xarML('View Derivative Images'), ];
            $menulinks[] = ['url'   => xarController::URL(
                'images',
                'admin',
                'browse'
            ),
                'title' => xarML('Browse Server Images'),
                'label' => xarML('Browse Server Images'), ];
            /**
            $menulinks[] = ['url'   => xarController::URL(
                'images',
                'admin',
                'phpthumb'
            ),
            'title' => xarML('Define Settings for Image Processing'),
            'label' => xarML('Image Processing'), ];
             */
            $menulinks[] = ['url'   => xarController::URL(
                'images',
                'admin',
                'modifyconfig'
            ),
                'title' => xarML('Edit the Images Configuration'),
                'label' => xarML('Modify Config'), ];
        }
        if (empty($menulinks)) {
            $menulinks = '';
        }
        return $menulinks;
    }
}
