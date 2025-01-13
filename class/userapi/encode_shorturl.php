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
use xarVar;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi encode_shorturl function
 * @extends MethodClass<UserApi>
 */
class EncodeShorturlMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * return the path for a short URL to xarController::URL for this module
     * @author the Images module development team
     * @param array<mixed> $args the function and arguments passed to xarController::URL
     * @return string|void path to be added to index.php for a short URL, or empty if failed
     * @see UserApi::encodeShorturl()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Check if we have something to work with
        if (!isset($func)) {
            return;
        }
        $userapi = $this->getParent();

        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $userapi->getUploadsAPI();

        // if we don't have a numeric fileId, can't do too much
        if (empty($fileId) || !is_numeric($fileId)) {
            return;
        } else {
            // get the mime type from the arguments
            if (!empty($fileType)) {
                $type = explode('/', $fileType);

                // get the mime type from cache for resize()
            } elseif (xarVar::isCached('Module.Images', 'imagemime.' . $fileId)) {
                $fileType = xarVar::getCached('Module.Images', 'imagemime.' . $fileId);
                $type = explode('/', $fileType);

                // get the mime type from the database (urgh)
            } else {
                // Bug 5410 Make a two step process
                $imageinfo = $uploadsapi->dbGetFile(['fileId' => $fileId]);
                $image = end($imageinfo);

                if (empty($image)) {
                    // fileId is nonexistant...
                    return;
                }

                $type = explode('/', $image['fileType']);
            }

            if ($type[1] == 'jpeg') {
                $type[1] = 'jpg';
            }

            $fileName = $fileId . '.' . $type[1];
        }
        // default path is empty -> no short URL
        $path = '';
        // if we want to add some common arguments as URL parameters below
        $join = '?';
        // we can't rely on xarMod::getName() here -> you must specify the modname !
        $module = 'images';

        // clean the array of the items we already have
        // so we can add any other values to the end of the url
        unset($args['func']);
        unset($args['fileId']);

        if (!empty($args)) {
            foreach ($args as $name => $value) {
                $extra[] = "$name=$value";
            }

            $extras = $join . implode('&', $extra);
        }

        // specify some short URLs relevant to your module
        if ($func == 'display') {
            // check for required parameters
            if (!empty($fileId) && is_numeric($fileId)) {
                $path = '/' . $module . '/' . $fileName . ($extras ?? '');
            }
        } else {
            // anything else that you haven't defined a short URL equivalent for
            // -> don't create a path here
        }

        return $path;
    }
}
