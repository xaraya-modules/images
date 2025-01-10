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

use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi getimageinfo function
 */
class GetimageinfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get information about an image (from file or database)
     * @param int $fileId The (uploads) file id of the image, or
     * @param string $fileLocation The file location of the image
     * @param string $basedir (optional) The directory where images are stored
     * @param string $baseurl (optional) The corresponding base URL for the images
     * @return array An array containing the image information if available or false if not available
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($fileId) && empty($fileLocation)) {
            $mesg = xarML(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                '',
                'getimageinfo',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileId) && !is_numeric($fileId)) {
            $mesg = xarML(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileId',
                'getimageinfo',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        } elseif (!empty($fileLocation) && !is_string($fileLocation)) {
            $mesg = xarML(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'fileLocation',
                'getimageinfo',
                'images'
            );
            throw new BadParameterException(null, $mesg);
        }

        if (!empty($fileId) && is_numeric($fileId)) {
            // Get file information from the uploads module
            $imageInfoArray = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $fileId]);
            $imageInfo = end($imageInfoArray);
            if (!empty($imageInfo)) {
                // Check the modified and writable
                if (file_exists($imageInfo['fileLocation'])) {
                    $imageInfo['fileModified'] = @filemtime($imageInfo['fileLocation']);
                    $imageInfo['isWritable']   = @is_writable($imageInfo['fileLocation']);
                } else {
                    $imageInfo['fileModified'] = '';
                    $imageInfo['isWritable']   = false;
                }
                // Get image size and type information
                $sizeinfo = xarMod::apiFunc('images', 'user', 'getimagesize', $imageInfo);
                if (!empty($sizeinfo)) {
                    $imageInfo['imageWidth']  = $sizeinfo[0];
                    $imageInfo['imageHeight'] = $sizeinfo[1];
                    $imageInfo['imageType']   = $sizeinfo[2];
                    $imageInfo['imageAttr']   = $sizeinfo[3];
                }
            }
            return $imageInfo;
        } elseif (!empty($fileLocation)) {
            // Check if the file exists
            $fileName = $fileLocation;
            if (!empty($basedir) && file_exists($basedir . '/' . $fileName)) {
                $fileLocation = $basedir . '/' . $fileName;
            } elseif (file_exists($fileName)) {
                $fileLocation = $fileName;
            } else {
                return;
            }
            // Get file statistics
            $statinfo = @stat($fileLocation);
            // Get image size and type information
            $sizeinfo = @getimagesize($fileLocation);
            if (empty($statinfo) || empty($sizeinfo)) {
                return;
            }

            // Note: we're using base 64 encoded fileId's here
            $id = base64_encode($fileName);
            $imageInfo = ['fileLocation' => $fileLocation,
                'fileDownload' => (!empty($baseurl) ? $baseurl . '/' . $fileName : $fileName),
                'fileName'     => $fileName,
                'fileType'     => $sizeinfo['mime'],
                'fileSize'     => $statinfo['size'],
                'fileId'       => $id,
                'fileModified' => $statinfo['mtime'],
                'isWritable'   => @is_writable($fileLocation),
                'imageWidth'   => $sizeinfo[0],
                'imageHeight'  => $sizeinfo[1],
                'imageType'    => $sizeinfo[2],
                'imageAttr'    => $sizeinfo[3], ];

            return $imageInfo;
        }

        return false;
    }
}
