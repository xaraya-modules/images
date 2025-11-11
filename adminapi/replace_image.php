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
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use BadParameterException;

/**
 * images adminapi replace_image function
 * @extends MethodClass<AdminApi>
 */
class ReplaceImageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Replaces an image with a resized image to the given dimensions
     * @author mikespub
     * @param array<mixed> $args
     * @var int $fileId The (uploads) file id of the image to load, or
     * @var string $fileLocation The file location of the image to load
     * @var string $height The new height (in pixels or percent) ([0-9]+)(px|%)
     * @var string $width The new width (in pixels or percent)  ([0-9]+)(px|%)
     * @var bool $constrain if height XOR width, then constrain the missing value to the given one
     * @return string|void the location of the newly resized image
     * @see AdminApi::replaceImage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $adminapi->getUploadsAPI();

        if (!empty($fileId) && empty($fileLocation)) {
            $fileInfos = $uploadsapi->dbGetFile(['fileId' => $fileId]);
            $fileInfo = end($fileInfos);
            if (empty($fileInfo)) {
                return;
            } else {
                $fileLocation = $fileInfo['fileLocation'];
            }
        }

        // make sure we can replace the file first
        if (file_exists($fileLocation)) {
            $checkwrite = $fileLocation;
        } else {
            $checkwrite = dirname($fileLocation);
        }
        if (!is_writable($checkwrite)) {
            $mesg = $this->ml(
                'Unable to replace #(1) - please check your file permissions',
                $fileLocation
            );
            throw new BadParameterException(null, $mesg);
        }
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        // TODO: replace files stored in xar_file_data too

        $location = $adminapi->resizeImage([
            'fileLocation' => $fileLocation,
            'width'  => (!empty($width) ? $width : null),
            'height' => (!empty($height) ? $height : null),
            'derivName'   => $fileLocation,
            'forceResize' => true,
        ]);
        if (!$location) {
            return;
        }

        if (empty($fileId)) {
            // We're done here
            return $location;
        }

        // Update the uploads database information
        if (!$uploadsapi->dbModifyFile([
            'fileId'   => $fileId,
            // FIXME: resize() always uses JPEG format for now
            'fileType' => 'image/jpeg',
            'fileSize' => filesize($fileLocation),
        ])) {
            return;
        }

        return $location;
    }
}
