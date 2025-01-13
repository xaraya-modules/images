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

use Xaraya\Modules\Images\Defines;
use Xaraya\Modules\Images\AdminApi;
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarMod;
use xarModVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi resize_image function
 * @extends MethodClass<AdminApi>
 */
class ResizeImageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Resizes an image to the given dimensions
     * @author mikespub
     * @param array<mixed> $args
     * @var int $fileId The (uploads) file id of the image to load, or
     * @var string $fileLocation The file location of the image to load
     * @var string $height The new height (in pixels or percent) ([0-9]+)(px|%)
     * @var string $width The new width (in pixels or percent)  ([0-9]+)(px|%)
     * @var bool $constrain if height XOR width, then constrain the missing value to the given one
     * @var string $thumbsdir (optional) The directory where derivative images are stored
     * @var string $derivName (optional) The name of the derivative image to be saved
     * @var bool $forceResize (optional) Force resizing the image even if it already exists
     * @return string|void the location of the newly resized image
     * @see AdminApi::resizeImage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        // Check the conditions
        if (empty($fileId) && empty($fileLocation)) {
            $mesg = $this->translate(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                '',
                'resize_image',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileId) && !is_numeric($fileId)) {
            $mesg = $this->translate(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileId',
                'resize_image',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
            $mesg = $this->translate(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileLocation',
                'resize_image',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        }

        if (!isset($width) && !isset($height)) {
            $msg = $this->translate("Required parameters '#(1)' and '#(2)' are missing.", 'width', 'height');
            throw new BadParameterException(null, $msg);
        } elseif (!isset($width) && !xarVar::validate('regexp:/[0-9]+(px|%)/:', $height)) {
            $msg = $this->translate("'#(1)' parameter is incorrectly formatted.", 'height');
            throw new BadParameterException(null, $msg);
        } elseif (!isset($height) && !xarVar::validate('regexp:/[0-9]+(px|%)/:', $width)) {
            $msg = $this->translate("'#(1)' parameter is incorrectly formatted.", 'width');
            throw new BadParameterException(null, $msg);
        }
        $adminapi = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $adminapi->getAPI();

        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $adminapi->getUploadsAPI();

        // just a flag for later
        $constrain_both = false;

        if (!isset($constrain)) {
            if (isset($width) xor isset($height)) {
                $constrain = true;
            } elseif (isset($width) && isset($height)) {
                $constrain = false;
            }
        } else {
            // we still want to constrain here, but we might need to be a little bit smarter about it
            // if we have both a height and a width, we don't want the image to be any larger than
            // any pf the supplied values, so we have to provide some logic to handle this
            if (isset($width) && isset($height)) {
                //$constrain = FALSE;
                $constrain_both = true;
            } //else {
            $constrain = (bool) $constrain;
            //}
        }

        $notSupported = false;

        // if both arguments are specified, give priority to fileId
        if (!empty($fileId)) {
            $fileInfos = $uploadsapi->dbGetFile(['fileId' => $fileId]);
            $fileInfo = end($fileInfos);
            if (empty($fileInfo)) {
                return;
            } else {
                $location = $fileInfo['fileLocation'];
            }
        } else {
            $location = $fileLocation;
            $fileId = null;
        }

        // TODO: refactor to support other libraries (ImageMagick/NetPBM)
        if (!empty($fileInfo['fileLocation'])) {
            $imageInfo = $userapi->getimagesize($fileInfo);
            $gd_info = $userapi->gdInfo();
            if (empty($imageInfo) || (!$imageInfo[2] & $gd_info['typesBitmask'])) {
                $notSupported = true;
            }
        } elseif (!empty($fileLocation) && file_exists($fileLocation)) {
            $imageInfo = @getimagesize($fileLocation);
            $gd_info = $userapi->gdInfo();
            if (empty($imageInfo) || (!$imageInfo[2] & $gd_info['typesBitmask'])) {
                $notSupported = true;
            }
        } else {
            $notSupported = true;
        }
        // Raise a user error when the format is not supported
        if ($notSupported) {
            $msg = $this->translate('Image type for file: #(1) is not supported for resizing', $location);
            throw new BadParameterException(null, $msg);
        }

        if (empty($thumbsdir)) {
            $thumbsdir = xarModVars::get('images', 'path.derivative-store');
        }

        $image = $userapi->loadImage([
            'fileId' => $fileId,
            'fileLocation' => $location,
            'thumbsdir' => $thumbsdir,
        ]);

        if (!is_object($image)) {
            $msg = $this->translate('File not found.');
            throw new BadParameterException(null, $msg);
        }

        if (isset($width)) {
            preg_match('/([0-9]+)(px|%)/i', $width, $parts);
            $type = ($parts[2] == '%') ? Defines::UNIT_TYPE_PERCENT : Defines::UNIT_TYPE_PIXELS;
            switch ($type) {
                case Defines::UNIT_TYPE_PERCENT:
                    $image->setPercent(['wpercent' => $width]);
                    break;
                default:
                case Defines::UNIT_TYPE_PIXELS:
                    $image->setWidth($parts[1]);
            }

            if ($constrain) {
                $constrain_both ? $image->Constrain('both') : $image->Constrain('width');
            }
        }

        if (isset($height)) {
            preg_match('/([0-9]+)(px|%)/i', $height, $parts);
            $type = ($parts[2] == '%') ? Defines::UNIT_TYPE_PERCENT : Defines::UNIT_TYPE_PIXELS;
            switch ($type) {
                case Defines::UNIT_TYPE_PERCENT:
                    $image->setPercent(['hpercent' => $height]);
                    break;
                default:
                case Defines::UNIT_TYPE_PIXELS:
                    $image->setHeight($parts[1]);
            }

            if ($constrain) {
                $constrain_both ? $image->Constrain('both') : $image->Constrain('height');
            }
        }

        if (empty($derivName)) {
            $derivName = '';
        }

        if (empty($forceResize)) {
            $location = $image->getDerivative($derivName);
            $forceResize = false;
        } else {
            $location = '';
            $forceResize = true;
        }
        if (!$location) {
            if ($image->resize($forceResize)) {
                $location = $image->saveDerivative($derivName);
                if (!$location) {
                    $msg = $this->translate('Unable to save resized image !');
                    throw new BadParameterException(null, $msg);
                }
            } else {
                $msg = $this->translate("Unable to resize image '#(1)'!", $image->fileLocation);
                throw new BadParameterException(null, $msg);
            }
        }

        return $location;
    }
}
