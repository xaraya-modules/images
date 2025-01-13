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

use Xaraya\Modules\UserApiClass;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use xarMod;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the images user API
 *
 * @method mixed decodeShorturl(array $args = []) extract function and arguments from short URLs for this module, and pass - them back to xarGetRequestInfo()
 * @method mixed dropdownlist(array $args) get an array of images (id => field) for use in dropdown lists - Note : for additional optional parameters, see the getuploads() and getimages() functions
 *  array{bid?: mixed, field: mixed}
 * @method mixed encodeShorturl(array $args = []) return the path for a short URL to xarController::URL for this module
 * @method mixed gdInfo(array $args = []) Images Module
 * @method mixed getbasedirs(array $args = []) Get the configured base directories for server images
 * @method mixed getimageinfo(array $args) Get information about an image (from file or database)
 *  array{fileId: int, fileLocation: string, basedir?: string, baseurl?: string}
 * @method mixed getimagesize(array $args) Get the size of an image (from file or database)
 *  array{fileLocation: string, fileId: int, fileType: int, storeType: int}
 * @method mixed getsettings(array $args = []) Get the predefined settings for image processing
 * @method mixed handleImageTag(array $args = []) Handle <xar:image-resize ... /> tags - Format : <xar:image-resize src="fileId | URL" width="[0-9]+(px|%)" [height="[0-9]+(px|%)" constrain="(yes|true|1|no|false|0)"] label="text" /> - examples: -  <xar:image-resize src="32" width="50px" height="50px" label="resize an image using pixels" /> -  <xar:image-resize src="somedir/some_image.jpg" width="25%" constrain="yes" label="resize an image using percentages" /> -  <xar:image-resize src="32" setting="JPEG 800 x 600" label="process an image with predefined setting" /> -  <xar:image-resize src="32" params="$params" label="process an image with phpThumb parameters" />
 * @method mixed loadImage(array $args) Load an image object for further manipulation
 *  array{fileId: int, fileLocation: string, thumbsdir?: string}
 * @method mixed resize(array $args) Resizes an image to the given dimensions and returns an img tag for the image
 *  array{src: mixed, basedir?: string, height: string, width: string, constrain: bool, label: string, setting: string, params: string, static: bool, baseurl?: string, returnpath?: bool}
 * @method mixed transformhook(array $args) Primarily used by Articles as a transform hook to turn "upload tags" into various display formats
 *  array{extrainfo: mixed}
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
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
