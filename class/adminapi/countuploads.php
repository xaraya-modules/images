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
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi countuploads function
 * @extends MethodClass<AdminApi>
 */
class CountuploadsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count the number of uploaded images (managed by the uploads module)
     * @author mikespub
     * @return int|void the number of uploaded images
     * @see AdminApi::countuploads()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($typeName)) {
            $typeName = 'image';
        }

        // Get all uploaded files of mimetype 'image' (cfr. uploads admin view)
        $typeinfo = xarMod::apiFunc('mime', 'user', 'get_type', ['typeName' => $typeName]);
        if (empty($typeinfo)) {
            return;
        }

        $filters = [];
        $filters['mimetype'] = $typeinfo['typeId'];
        $filters['subtype']  = null;
        $filters['status']   = null;
        $filters['inverse']  = null;

        $options  = xarMod::apiFunc('uploads', 'user', 'process_filters', $filters);
        $filter   = $options['filter'];

        $numimages = xarMod::apiFunc('uploads', 'user', 'db_count', $filter);

        return $numimages;
    }
}
