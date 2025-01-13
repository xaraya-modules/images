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
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
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
        $adminapi = $this->getParent();

        /** @var MimeApi $mimeapi */
        $mimeapi = $adminapi->getMimeAPI();

        // Get all uploaded files of mimetype 'image' (cfr. uploads admin view)
        $typeinfo = $mimeapi->getType(['typeName' => $typeName]);
        if (empty($typeinfo)) {
            return;
        }

        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $adminapi->getUploadsAPI();

        $filters = [];
        $filters['mimetype'] = $typeinfo['typeId'];
        $filters['subtype']  = null;
        $filters['status']   = null;
        $filters['inverse']  = null;

        $options  = $uploadsapi->processFilters($filters);
        $filter   = $options['filter'];

        $numimages = $uploadsapi->dbCount($filter);

        return $numimages;
    }
}
