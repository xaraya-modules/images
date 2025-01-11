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
use Xaraya\Modules\MethodClass;
use xarMod;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi dropdownlist function
 * @extends MethodClass<UserApi>
 */
class DropdownlistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get an array of images (id => field) for use in dropdown lists
     * Note : for additional optional parameters, see the getuploads() and getimages() functions
     * @param mixed $args ['bid'] (optional) baseId for server images, otherwise uploads images
     * @param mixed $args ['field'] field to use in the dropdown list (default 'fileName')
     * @return array of images, or false on failure
     */
    public function __invoke($args = [])
    {
        // Add default arguments
        if (!isset($args['sort'])) {
            $args['sort'] = 'name';
        }
        if (!isset($args['numitems'])) {
            $args['numitems'] = 9999;
        }
        if (!isset($args['bid'])) {
            // Get the uploads images
            $images = xarMod::apiFunc('images', 'admin', 'getuploads', $args);
        } else {
            // Get the base directories configured for server images
            $basedirs = xarMod::apiFunc('images', 'user', 'getbasedirs');
            if (empty($args['bid']) || empty($basedirs[$args['bid']])) {
                $args['bid'] = 0; // themes directory
            }
            $args = array_merge($basedirs[$args['bid']], $args);
            // Get the server images
            $images = xarMod::apiFunc('images', 'admin', 'getimages', $args);
        }
        if (!$images) {
            return;
        }

        if (!isset($args['field'])) {
            $args['field'] = 'fileName';
        }

        // Fill in the dropdown list
        $list = [];
        $list[0] = '';
        $field = $args['field'];
        foreach ($images as $image) {
            if (!isset($image[$field])) {
                continue;
            }
            // TODO: support other formatting options here depending on the field type ?
            $list[$image['fileId']] = xarVar::prepForDisplay($image[$field]);
        }

        return $list;
    }
}
