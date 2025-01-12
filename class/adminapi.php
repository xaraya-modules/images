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

use Xaraya\Modules\AdminApiClass;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use xarMod;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the images admin API
 *
 * @method mixed countderivatives(array $args)
 * @method mixed countimages(array $args)
 * @method mixed countuploads(array $args)
 * @method mixed getderivatives(array $args)
 * @method mixed getimages(array $args)
 * @method mixed getmenulinks(array $args)
 * @method mixed getuploads(array $args)
 * @method mixed processImage(array $args)
 * @method mixed replaceImage(array $args)
 * @method mixed resizeImage(array $args)
 * @method mixed setsettings(array $args)
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    /**
     * Get Mime UserApi class
     * @return MimeApi
     */
    public function getMimeAPI()
    {
        /** @var MimeApi $mimeapi */
        $mimeapi = xarMod::getAPI('mime');
        return $mimeapi;
    }

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
