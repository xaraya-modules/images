<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\UserApi;

use Xaraya\Modules\Images\Defines;
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Images\Image_GD;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi load_image function
 * @extends MethodClass<UserApi>
 */
class LoadImageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Load an image object for further manipulation
     * @param array<mixed> $args
     * @var int $fileId The (uploads) file id of the image to load, or
     * @var string $fileLocation The file location of the image to load
     * @var string $thumbsdir (optional) The directory where derivative images are stored
     * @return object|null Image_GD (or other) object
     * @see UserApi::loadImage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($fileId) && empty($fileLocation)) {
            $mesg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                '',
                'load_image',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileId) && !is_string($fileId)) {
            $mesg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileId',
                'load_image',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
            $mesg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileLocation',
                'load_image',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // if both arguments are specified, give priority to fileId
        if (!empty($fileId) && is_numeric($fileId)) {
            // if we only get the fileId
            if (empty($fileLocation) || !isset($storeType)) {
                /** @var UploadsApi $uploadsapi */
                $uploadsapi = $userapi->getUploadsAPI();

                $fileInfoArray = $uploadsapi->dbGetFile(['fileId' => $fileId]);
                $fileInfo = end($fileInfoArray);
                if (empty($fileInfo)) {
                    return null;
                }
                if (!empty($fileInfo['fileLocation']) && file_exists($fileInfo['fileLocation'])) {
                    // pass the file location to Image_Properties
                    $location = $fileInfo['fileLocation'];
                } elseif (defined('\Xaraya\Modules\Uploads\Defines::STORE_DB_DATA') && ($fileInfo['storeType'] & \Xaraya\Modules\Uploads\Defines::STORE_DB_DATA)) {
                    // pass the file info array to Image_Properties
                    $location = $fileInfo;
                }

                // if we get the whole file info
            } elseif (file_exists($fileLocation)) {
                $location = $fileLocation;
            } elseif (defined('\Xaraya\Modules\Uploads\Defines::STORE_DB_DATA') && ($storeType & \Xaraya\Modules\Uploads\Defines::STORE_DB_DATA)) {
                // pass the whole array to Image_Properties
                $location = $args;
            } else {
                $mesg = $this->ml(
                    "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                    'fileLocation',
                    'load_image',
                    'images'
                );
                throw new BadParameterException(null, $mesg);
            }
        } else {
            $location = $fileLocation;
        }

        if (empty($thumbsdir)) {
            $thumbsdir = $this->mod()->getVar('path.derivative-store');
        }

        sys::import('modules.images.class.image_properties');

        switch ($this->mod()->getVar('type.graphics-library')) {
            /**
            case Defines::LIBRARY_IMAGEMAGICK:
                sys::import('modules.images.class.image_ImageMagick');
                $newImage = new Image_ImageMagick($location, $thumbsdir);
                return $newImage;
                break;
            case Defines::LIBRARY_NETPBM:
                sys::import('modules.images.class.image_NetPBM');
                $newImage = new Image_NetPBM($location, $thumbsdir);
                return $newImage;
                break;
             */
            default:
            case Defines::LIBRARY_GD:
                sys::import('modules.images.class.image_gd');
                $newImage = new Image_GD($location, $thumbsdir);
                return $newImage;
        }
    }
}
