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
        if (!$this->sec()->checkAccess('AdminImages')) {
            return;
        }

        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        $data = [];

        // Note: fileId is an MD5 hash of the derivative image location here
        $this->var()->find('fileId', $fileId, 'str:1:', '');
        $data['fileId'] = $fileId;

        $this->var()->check('startnum', $startnum, 'int:0:');
        $this->var()->check('numitems', $numitems, 'int:0:');
        $this->var()->find('sort', $sort, 'enum:name:width:height:size:time', 'name');

        $data['startnum'] = $startnum;
        $data['numitems'] = $numitems;
        $data['sort'] = ($sort != 'name') ? $sort : null;

        // Check if we can cache the image list
        $data['cacheExpire'] = $this->mod()->getVar('file.cache-expire');

        $data['thumbsdir'] = $this->mod()->getVar('path.derivative-store');

        $data['pager'] = '';
        if (!empty($fileId)) {
            $params = $data;
            $data['images'] = $adminapi->getderivatives($params);
        } else {
            $params = $data;
            if (!isset($numitems)) {
                $params['numitems'] = $this->mod()->getVar('view.itemsperpage');
            }
            // Check if we need to refresh the cache anyway
            $this->var()->check('refresh', $refresh, 'int:0:');
            $params['cacheRefresh'] = $refresh;

            $data['images'] = $adminapi->getderivatives($params);

            // Note: this must be called *after* getderivatives() to benefit from caching
            $countitems = $adminapi->countderivatives($params);

            // Add pager
            if (!empty($params['numitems']) && $countitems > $params['numitems']) {
                $data['pager'] = $this->tpl()->getPager(
                    $startnum,
                    $countitems,
                    $this->mod()->getURL(
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
        $this->var()->find('action', $action, 'str:1:', '');

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
                    $this->var()->find('confirm', $confirm, 'str:1:', '');
                    if (!empty($confirm)) {
                        if (!$this->sec()->confirmAuthKey()) {
                            return;
                        }
                        // delete the derivative image now
                        @unlink($found['fileLocation']);
                        $this->ctl()->redirect($this->mod()->getURL('admin', 'derivatives'));
                        return true;
                    }
                    $data['selimage'] = $found;
                    $data['action'] = 'delete';
                    $data['authid'] = $this->sec()->genAuthKey();
                    return $data;

                default:
                    break;
            }
        }

        // Return the template variables defined in this function
        return $data;
    }
}
