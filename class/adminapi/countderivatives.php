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
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarVar;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi countderivatives function
 * @extends MethodClass<AdminApi>
 */
class CountderivativesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count the number of derivative images
     * @author mikespub
     * @param mixed $fileId (optional) The file id(s) of the image(s) we're looking for
     * @param string $fileName (optional) The name of the image we're getting derivatives for
     * @param string $thumbsdir (optional) The directory where derivative images are stored
     * @param string $filematch (optional) Specific file match for derivative images
     * @return int the number of images
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($fileId)) {
            if (!is_array($fileId)) {
                $fileId = [$fileId];
            }
            return count($fileId);
        }

        if (empty($thumbsdir)) {
            $thumbsdir = xarModVars::get('images', 'path.derivative-store');
        }
        if (empty($thumbsdir)) {
            return 0;
        }
        if (empty($filematch)) {
            $filematch = '';
            if (!empty($fileName)) {
                // Note: resized images are named [filename]-[width]x[height].jpg - see resize() method
                //$filematch = '^' . $fileName . '-\d+x\d+';
                // Note: processed images are named [filename]-[setting].[ext] - see process_image() function
                $filematch = '^' . $fileName . '-.+';
            }
        }
        if (empty($filetype)) {
            // Note: resized images are JPEG files - see resize() method
            //$filetype = 'jpg';
            // Note: processed images can be JPEG, GIF or PNG files - see process_image() function
            $filetype = '(jpg|png|gif)';
        }

        $params = ['basedir'   => $thumbsdir,
            'filematch' => $filematch,
            'filetype'  => $filetype, ];

        $cachekey = md5(serialize($params));
        // get the number of images from temporary cache - see getderivatives()
        if (xarVar::isCached('Modules.Images', 'countderivatives.' . $cachekey)) {
            return xarVar::getCached('Modules.Images', 'countderivatives.' . $cachekey);
        } else {
            $files = xarMod::apiFunc(
                'dynamicdata',
                'admin',
                'browse',
                $params
            );
            if (!isset($files)) {
                return;
            }

            return count($files);
        }

        return 0;
    }
}
