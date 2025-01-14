<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\AdminGui;

use Xaraya\Modules\Images\AdminGui;
use Xaraya\Modules\Images\AdminApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarModVars;
use xarMod;
use xarTplPager;
use xarController;
use xarSec;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images admin derivatives function
 * @extends MethodClass<AdminGui>
 */
class DerivativesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View a list of derivative images (thumbnails, resized etc.)
     * @author mikespub
     * @todo add startnum and numitems support
     * @see AdminGui::derivatives()
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!$this->checkAccess('AdminImages')) {
            return;
        }
        $admingui = $this->getParent();

        /** @var AdminApi $adminapi */
        $adminapi = $admingui->getModule()->getAdminAPI();

        $data = [];

        // Note: fileId is an MD5 hash of the derivative image location here
        if (!$this->fetch('fileId', 'str:1:', $fileId, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        $data['fileId'] = $fileId;

        if (!$this->fetch('startnum', 'int:0:', $startnum, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('numitems', 'int:0:', $numitems, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('sort', 'enum:name:width:height:size:time', $sort, 'name', xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['startnum'] = $startnum;
        $data['numitems'] = $numitems;
        $data['sort'] = ($sort != 'name') ? $sort : null;

        // Check if we can cache the image list
        $data['cacheExpire'] = $this->getModVar('file.cache-expire');

        $data['thumbsdir'] = $this->getModVar('path.derivative-store');

        $data['pager'] = '';
        if (!empty($fileId)) {
            $params = $data;
            $data['images'] = $adminapi->getderivatives($params);
        } else {
            $params = $data;
            if (!isset($numitems)) {
                $params['numitems'] = $this->getModVar('view.itemsperpage');
            }
            // Check if we need to refresh the cache anyway
            if (!$this->fetch('refresh', 'int:0:', $refresh, null, xarVar::DONT_SET)) {
                return;
            }
            $params['cacheRefresh'] = $refresh;

            $data['images'] = $adminapi->getderivatives($params);

            // Note: this must be called *after* getderivatives() to benefit from caching
            $countitems = $adminapi->countderivatives($params);

            // Add pager
            if (!empty($params['numitems']) && $countitems > $params['numitems']) {
                sys::import('modules.base.class.pager');
                $data['pager'] = xarTplPager::getPager(
                    $startnum,
                    $countitems,
                    $this->getUrl(
                        'admin',
                        'derivatives',
                        ['startnum' => '%%',
                            'numitems' => $data['numitems'],
                            'sort'     => $data['sort'], ]
                    ),
                    $params['numitems']
                );
            }
        }

        // Check if we need to do anything special here
        if (!$this->fetch('action', 'str:1:', $action, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        // Find the right derivative image
        if (!empty($action) && !empty($fileId)) {
            $found = '';
            foreach ($data['images'] as $image) {
                if ($image['fileId'] == $fileId) {
                    $found = $image;
                    break;
                }
            }
        }

        if (!empty($action) && !empty($found)) {
            switch ($action) {
                case 'view':
                    $data['selimage'] = $found;
                    $data['action'] = 'view';
                    return $data;

                case 'delete':
                    if (!$this->fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!empty($confirm)) {
                        if (!$this->confirmAuthKey()) {
                            return;
                        }
                        // delete the derivative image now
                        @unlink($found['fileLocation']);
                        $this->redirect($this->getUrl('admin', 'derivatives'));
                        return true;
                    }
                    $data['selimage'] = $found;
                    $data['action'] = 'delete';
                    $data['authid'] = $this->genAuthKey();
                    return $data;

                default:
                    break;
            }
        }

        // Return the template variables defined in this function
        return $data;
    }
}
