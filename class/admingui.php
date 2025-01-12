<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Images;

use Xaraya\Modules\AdminGuiClass;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use xarMod;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.images.class.adminapi');

/**
 * Handle the images admin GUI
 *
 * @method mixed browse(array $args)
 * @method mixed derivatives(array $args)
 * @method mixed importpictures(array $args)
 * @method mixed main(array $args)
 * @method mixed modifyconfig(array $args)
 * @method mixed overview(array $args)
 * @method mixed phpthumb(array $args)
 * @method mixed updateconfig(array $args)
 * @method mixed uploads(array $args)
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    /**
     * Get Uploads UserApi class
     * @return UploadsApi
     */
    public function getUploadsAPI()
    {
        /** @var UploadsApi $uploadsapi */
        $uploadsapi = xarMod::getAPI('uploads');
        return $uploadsapi;
    }
}
