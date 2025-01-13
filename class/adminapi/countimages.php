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
use xarVar;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi countimages function
 * @extends MethodClass<AdminApi>
 */
class CountimagesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count the number of server images
     * @author mikespub
     * @param array<mixed> $args
     * @var string $basedir The directory where images are stored
     * @var string $baseurl (optional) The corresponding base URL for the images
     * @var string $filetypes (optional) The list of file extensions to look for
     * @var bool $recursive (optional) Recurse into sub-directories or not (default TRUE)
     * @var mixed $fileId (optional) The file id(s) of the image(s) we're looking for
     * @var string $fileName (optional) The name of the image we're looking for
     * @var string $filematch (optional) Specific file match for images
     * @return int|void the number of images
     * @see AdminApi::countimages()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($basedir)) {
            return 0;
        }
        if (!isset($baseurl)) {
            $baseurl = $basedir;
        }

        // Note: fileId is a base 64 encode of the image location here, or an array of fileId's
        if (!empty($fileId)) {
            $files = [];
            if (!is_array($fileId)) {
                $fileId = [$fileId];
            }
            foreach ($fileId as $id) {
                $file = base64_decode($id);
                if (file_exists($basedir . '/' . $file)) {
                    $files[] = $file;
                }
            }
            return count($files);
        } else {
            if (empty($filematch)) {
                $filematch = '';
                if (!empty($fileName)) {
                    $filematch = '^' . $fileName;
                }
            }
            if (empty($filetypes)) {
                $filetype = '(gif|jpg|png)';
            } elseif (is_array($filetypes)) {
                $filetype = '(' . join('|', $filetypes) . ')';
            } else {
                $filetype = '(' . $filetypes . ')';
            }
            if (!isset($recursive)) {
                $recursive = true;
            }

            $params = ['basedir'   => $basedir,
                'filematch' => $filematch,
                'filetype'  => $filetype,
                'recursive' => $recursive, ];

            $cachekey = md5(serialize($params));
            // get the number of images from temporary cache - see getimages()
            if (xarVar::isCached('Modules.Images', 'countimages.' . $cachekey)) {
                return xarVar::getCached('Modules.Images', 'countimages.' . $cachekey);
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
        }
    }
}
