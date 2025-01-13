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
 * @method mixed countderivatives(array $args = []) count the number of derivative images
 *  array{fileId?: mixed, fileName?: string, thumbsdir?: string, filematch?: string}
 * @method mixed countimages(array $args) count the number of server images
 *  array{basedir: string, baseurl?: string, filetypes?: string, recursive?: bool, fileId?: mixed, fileName?: string, filematch?: string}
 * @method mixed countuploads(array $args = []) count the number of uploaded images (managed by the uploads module)
 * @method mixed getderivatives(array $args = []) get the list of derivative images (thumbnails and resized)
 *  array{fileId?: mixed, fileName?: string, fileLocation?: string, thumbsdir?: string, filematch?: string, cacheExpire?: int, cacheRefresh?: bool}
 * @method mixed getimages(array $args) get the list of server images
 *  array{basedir: string, baseurl?: string, filetypes?: string, recursive?: bool, fileId?: mixed, fileName?: string, filematch?: string, cacheExpire?: int, cacheRefresh?: bool}
 * @method mixed getmenulinks(array $args = []) utility function pass individual menu items to the main menu
 * @method mixed getuploads(array $args = []) get the list of uploaded images (managed by the uploads module)
 * @method mixed processImage(array $args) Process an image using phpThumb
 *  array{image: array, saveas: int, setting: string, params: array, iscached: bool}
 * @method mixed replaceImage(array $args) Replaces an image with a resized image to the given dimensions
 *  array{fileId: int, fileLocation: string, height: string, width: string, constrain: bool}
 * @method mixed resizeImage(array $args) Resizes an image to the given dimensions
 *  array{fileId: int, fileLocation: string, height: string, width: string, constrain: bool, thumbsdir?: string, derivName?: string, forceResize?: bool}
 * @method mixed setsettings(array $args = []) Set the predefined settings for image processing
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
