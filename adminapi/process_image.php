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
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi process_image function
 * @extends MethodClass<AdminApi>
 */
class ProcessImageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Process an image using phpThumb
     * @author mikespub
     * @param array<mixed> $args
     * @var array $image The image info array (e.g. coming from getimageinfo or getimages/getuploads/getderivatives)
     * @var int $saveas How to save the processed image (0 = derivative, 1 = [image]_new.[ext], 2 = replace, 3 = output)
     * @var string $setting The predefined setting to use, or
     * @var array $params The phpThumb parameters to use
     * @var bool $iscached Check if the processed file already exists (default FALSE)
     * @return string the location of the newly processed image
     * @deprecated 2.0.0 phpThumb() is seriously dated and doesn't play nice as a library
     * @see AdminApi::processImage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $adminapi->getUploadsAPI();

        $settings = $userapi->getsettings();
        if (!empty($setting) && !empty($settings[$setting])) {
            $params = $settings[$setting];
        } elseif (!empty($params)) {
            $setting = md5(serialize($params));
        } else {
            $setting = '';
            $params = '';
        }

        if (empty($saveas)) {
            $saveas = 0;
        }

        if (empty($image) || empty($params)) {
            $msg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                '',
                'process_image',
                'images'
            );
            if ($saveas == 3) {
                $phpThumb = $adminapi->getPhpThumb();
                // Generate an error image
                $phpThumb->ErrorImage($msg);
                // The calling GUI needs to stop processing here
                return true;
            } else {
                throw new BadParameterException(null, $msg);
            }
        }

        // Default to JPEG format (like phpThumb itself)
        if (empty($params['f'])) {
            $params['f'] = 'jpeg';
        }
        // Determine new file extension based on format
        switch ($params['f']) {
            case 'jpeg':
                $ext = 'jpg';
                break;
            case 'png':
            case 'gif':
            default:
                $ext = $params['f'];
                break;
        }

        // If the image is stored in a real file
        if (file_exists($image['fileLocation'])) {
            switch ($saveas) {
                case 1: // [image]_new.[ext]
                    $save = realpath($image['fileLocation']);
                    if ($save) {
                        $save = preg_replace('/\.\w+$/', "_new.$ext", $save);
                    }
                    break;

                case 2: // replace
                    // Note: the file extension might not match the selected format here
                    $save = realpath($image['fileLocation']);
                    break;

                case 3: // output the image to the browser
                    $save = '';
                    break;

                case 0: // derivative
                default:
                    $thumbsdir = $this->mod()->getVar('path.derivative-store');
                    // Use MD5 hash of file location here
                    $save = realpath($thumbsdir) . '/' . md5($image['fileLocation']);
                    // Add the setting to the filename
                    $add = \xarVarPrep::forOS($setting);
                    $add = strtr($add, [' ' => '']);
                    $save .= "-$add.$ext";
                    break;
            }
            // Check if we can use a cached file
            if (!empty($iscached) && !empty($save) && file_exists($save)) {
                return $save;
            }

            $file = realpath($image['fileLocation']);
            $phpThumb = $adminapi->getPhpThumb();
            $phpThumb->setSourceFilename($file);

            // If the image is stored in the database (uploads module)
            // NOTE: the next line is the *only* place i could find which suppresses exceptions through the 0 parameter at the end
            // NOTE: in the 2.x branch that parameter does not exist anymore, so the next code needs to be changed.
        } elseif (is_numeric($image['fileId']) && $this->mod()->isAvailable('uploads') && $this->mod()->apiLoad('uploads', 'user') &&
                  defined('\Xaraya\Modules\Uploads\Defines::STORE_DB_DATA') && ($image['storeType'] & \Xaraya\Modules\Uploads\Defines::STORE_DB_DATA)) {
            $uploadsdir = $this->mod('uploads')->getVar('path.uploads-directory');
            switch ($saveas) {
                case 1: // [image]_new.[ext] // CHECKME: not in the database ?
                    $save = realpath($uploadsdir) . '/' . $image['fileName'];
                    $save = preg_replace('/\.\w+$/', "_new.$ext", $save);
                    break;

                case 2: // replace in the database here
                    if (is_dir($uploadsdir) && is_writable($uploadsdir)) {
                        $save = tempnam($uploadsdir, 'xarimage-');
                    } else {
                        $save = tempnam(sys_get_temp_dir(), 'xarimage-');
                    }
                    $dbfile = 1;
                    break;

                case 3: // output the image to the browser
                    $save = '';
                    break;

                case 0: // derivative
                default:
                    $thumbsdir = $this->mod()->getVar('path.derivative-store');
                    // Use file id here
                    $save = realpath($thumbsdir) . '/' . $image['fileId'];
                    // Add the setting to the filename
                    $add = \xarVarPrep::forOS($setting);
                    $add = strtr($add, [' ' => '']);
                    $save .= "-$add.$ext";
                    break;
            }
            // Check if we can use a cached file
            if (!empty($iscached) && !empty($save) && empty($dbfile) && file_exists($save)) {
                return $save;
            }

            // get the image data from the database
            $data = $uploadsapi->dbGetFileData(['fileId' => $image['fileId']]);
            if (empty($data)) {
                $msg = $this->ml(
                    "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                    'image',
                    'process_image',
                    'images'
                );
                if ($saveas == 3) {
                    $phpThumb = $adminapi->getPhpThumb();
                    // Generate an error image
                    $phpThumb->ErrorImage($msg);
                    // The calling GUI needs to stop processing here
                    return true;
                } else {
                    throw new BadParameterException(null, $msg);
                }
            }

            $src = implode('', $data);
            unset($data);
            $phpThumb = $adminapi->getPhpThumb();
            $phpThumb->setSourceData($src);
        } else {
            $msg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'image',
                'process_image',
                'images'
            );
            if ($saveas == 3) {
                $phpThumb = $adminapi->getPhpThumb();
                // Generate an error image
                $phpThumb->ErrorImage($msg);
                // The calling GUI needs to stop processing here
                return true;
            } else {
                throw new BadParameterException(null, $msg);
            }
        }

        // or $phpThumb->setSourceImageResource($gd_image_resource);

        foreach ($params as $name => $value) {
            if (isset($value) && $value !== false) {
                $phpThumb->$name = $value;
            }
        }

        // Process the image
        $result = $phpThumb->GenerateThumbnail();

        if (empty($result)) {
            $msg = implode("\n\n", $phpThumb->debugmessages);
            if ($saveas == 3) {
                // Generate an error image
                $phpThumb->ErrorImage($msg);
                // The calling GUI needs to stop processing here
                return true;
            } else {
                throw new BadParameterException(null, $msg);
            }
        }

        // Output the image to the browser
        if ($saveas == 3) {
            $phpThumb->OutputThumbnail();
            // The calling GUI needs to stop processing here
            return true;
        }

        // Save it to file
        if (empty($save)) {
            $msg = $this->ml(
                "Invalid parameter '#(1)' to API function '#(2)' in module '#(3)'",
                'save',
                'process_image',
                'images'
            );
            throw new BadParameterException(null, $msg);
        }

        $result = $phpThumb->RenderToFile($save);

        if (empty($result)) {
            $msg = implode("\n\n", $phpThumb->debugmessages);
            throw new BadParameterException(null, $msg);
        }

        // TODO: add file entry to uploads when saveas == 1 ?

        // update the uploads file entry if we overwrite a file !
        if (is_numeric($image['fileId']) && $saveas == 2) {
            if (!$uploadsapi->dbModifyFile([
                'fileId'    => $image['fileId'],
                'fileType'  => 'image/' . $params['f'],
                'fileSize'  => filesize($save),
                // reset the extrainfo
                'extrainfo' => '',
            ])) {
                return '';
            }
            if (!empty($dbfile)) {
                // store the image in the database
                if (!$uploadsapi->fileDump([
                    'fileSrc' => $save,
                    'fileId'  => $image['fileId'],
                ])) {
                    return '';
                }
            }
        }

        return $save;
    }
}
