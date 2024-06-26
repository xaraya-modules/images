<?php
/**
 * Images Module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Images Module
 * @link http://xaraya.com/index.php/release/152.html
 * @author Images Module Development Team
 */
/**
 * Get the size of an image (from file or database)
 *
 * @param   string  $fileLocation  The file location of the image, or
 * @param   integer $fileId        The (uploads) file id of the image
 * @param   integer $fileType      The (uploads) mime type for the image
 * @param   integer $storeType     The (uploads) store type for the image
 * @return  array Array containing the width, height and gd_info if available
 */
function images_userapi_getimagesize(array $args = [], $context = null)
{
    extract($args);

    if (empty($fileId) && empty($fileLocation)) {
        $mesg = xarML(
            "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
            '',
            'getimagesize',
            'images'
        );
        throw new BadParameterException(null, $mesg);
    } elseif (!empty($fileId) && !is_numeric($fileId)) {
        $mesg = xarML(
            "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
            'fileId',
            'getimagesize',
            'images'
        );
        throw new BadParameterException(null, $mesg);
    } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
        $mesg = xarML(
            "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
            'fileLocation',
            'getimagesize',
            'images'
        );
        throw new BadParameterException(null, $mesg);
    }

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
    } elseif (extension_loaded('gd') && xarMod::apiLoad('uploads', 'user') &&
              defined('_UPLOADS_STORE_DB_DATA') && ($storeType & _UPLOADS_STORE_DB_DATA)) {
        // get the image data from the database
        $data = xarMod::apiFunc('uploads', 'user', 'db_get_file_data', ['fileId' => $fileId]);
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
                xarMod::apiFunc(
                    'uploads',
                    'user',
                    'db_modify_file',
                    ['fileId' => $fileId,
                                    'extrainfo' => $extrainfo, ]
                );
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
