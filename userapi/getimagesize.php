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

use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi getimagesize function
 * @extends MethodClass<UserApi>
 */
class GetimagesizeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get the size of an image (from file or database)
     * @param array<mixed> $args
     * @var string $fileLocation The file location of the image, or
     * @var int $fileId The (uploads) file id of the image
     * @var int $fileType The (uploads) mime type for the image
     * @var int $storeType The (uploads) store type for the image
     * @return array|void Array containing the width, height and gd_info if available
     * @see UserApi::getimagesize()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($fileId) && empty($fileLocation)) {
            $mesg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                '',
                'getimagesize',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileId) && !is_numeric($fileId)) {
            $mesg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileId',
                'getimagesize',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
            $mesg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileLocation',
                'getimagesize',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!empty($fileLocation) && file_exists($fileLocation)) {
            return @getimagesize($fileLocation);
        } elseif (!empty($extrainfo['width']) && !empty($extrainfo['height'])) {
            // Simulate the type returned by getimagesize()
            switch ($fileType) {
                case 'image/gif':
                    $type = 1;
                    break;
                case 'image/jpeg':
                    $type = 2;
                    break;
                case 'image/png':
                    $type = 3;
                    break;
                default:
                    $type = 0;
                    break;
            }
            $string = 'width="' . $extrainfo['width'] . '" height="' . $extrainfo['height'] . '"';
            return [$extrainfo['width'],$extrainfo['height'],$type,$string];
        } elseif (extension_loaded('gd') && $this->mod()->apiLoad('uploads', 'user') &&
                  defined('\Xaraya\Modules\Uploads\Defines::STORE_DB_DATA') && ($storeType & \Xaraya\Modules\Uploads\Defines::STORE_DB_DATA)) {

            /** @var UploadsApi $uploadsapi */
            $uploadsapi = $userapi->getUploadsAPI();

            // get the image data from the database
            $data = $uploadsapi->dbGetFileData(['fileId' => $fileId]);
            if (!empty($data)) {
                $src = implode('', $data);
                unset($data);
                $img = @imagecreatefromstring($src);
                if (!empty($img)) {
                    $width  = @imagesx($img);
                    $height = @imagesy($img);
                    @imagedestroy($img);
                    // update the file entry in the uploads module
                    if (empty($extrainfo)) {
                        $extrainfo = [];
                    }
                    $extrainfo['width'] = $width;
                    $extrainfo['height'] = $height;
                    $uploadsapi->dbModifyFile([
                        'fileId' => $fileId,
                        'extrainfo' => $extrainfo,
                    ]);
                    // Simulate the type returned by getimagesize()
                    switch ($fileType) {
                        case 'image/gif':
                            $type = 1;
                            break;
                        case 'image/jpeg':
                            $type = 2;
                            break;
                        case 'image/png':
                            $type = 3;
                            break;
                        default:
                            $type = 0;
                            break;
                    }
                    $string = 'width="' . $width . '" height="' . $height . '"';
                    return [$width,$height,$type,$string];
                }
            }
        }
    }
}
