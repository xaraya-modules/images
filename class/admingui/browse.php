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
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarModVars;
use xarController;
use xarTplPager;
use xarSec;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images admin browse function
 * @extends MethodClass<AdminGui>
 */
class BrowseMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View a list of server images
     * @todo add startnum and numitems support
     * @see AdminGui::browse()
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!$this->checkAccess('AdminImages')) {
            return;
        }

        // Note: fileId is a base 64 encode of the image location here, or an array of fileId's
        if (!$this->fetch('fid', 'isset', $fileId, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($fileId) && is_array($fileId)) {
            $fileId = array_keys($fileId);
        }
        if (empty($fileId)) {
            $fileId = null;
        }
        $admingui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $admingui->getAPI();

        // Get the base directories configured for server images
        $basedirs = $userapi->getbasedirs();

        if (!$this->fetch('bid', 'isset', $baseId, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($baseId) || empty($basedirs[$baseId])) {
            $data = $basedirs[0]; // themes directory
            $baseId = null;
        } else {
            $data = $basedirs[$baseId];
        }
        $data['baseId'] = $baseId;
        $data['fileId'] = $fileId;

        if (!$this->fetch('startnum', 'int:0:', $startnum, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('numitems', 'int:0:', $numitems, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('sort', 'enum:name:type:width:height:size:time', $sort, 'name', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('action', 'str:1:', $action, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('getnext', 'str:1:', $getnext, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('getprev', 'str:1:', $getprev, null, xarVar::DONT_SET)) {
            return;
        }
        /** @var AdminApi $adminapi */
        $adminapi = $admingui->getModule()->getAdminAPI();

        $data['startnum'] = $startnum;
        $data['numitems'] = $numitems;
        $data['sort'] = ($sort != 'name') ? $sort : null;
        $data['getnext'] = $getnext;
        $data['getprev'] = $getprev;

        // Check if we can cache the image list
        $data['cacheExpire'] = $this->getModVar('file.cache-expire');

        $data['pager'] = '';
        if (!empty($fileId)) {
            $data['images'] = $adminapi->getimages($data);
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

            $data['images'] = $adminapi->getimages($params);

            if ((!empty($getnext) || !empty($getprev)) &&
                !empty($data['images']) && count($data['images']) == 1) {
                $image = array_pop($data['images']);
                $this->redirect($this->getUrl(
                    'admin',
                    'browse',
                    ['action' => empty($action) ? 'view' : $action,
                        'bid' => $baseId,
                        'fid' => $image['fileId'], ]
                ));
                return true;
            }

            // Note: this must be called *after* getimages() to benefit from caching
            $countitems = $adminapi->countimages($params);

            // Add pager
            if (!empty($params['numitems']) && $countitems > $params['numitems']) {
                sys::import('modules.base.class.pager');
                $data['pager'] = xarTplPager::getPager(
                    $startnum,
                    $countitems,
                    $this->getUrl(
                        'admin',
                        'browse',
                        ['bid'      => $baseId,
                            'startnum' => '%%',
                            'numitems' => $data['numitems'],
                            'sort'     => $data['sort'], ]
                    ),
                    $params['numitems']
                );
            }
        }

        $data['basedirs'] = $basedirs;

        // Get the pre-defined settings for phpThumb
        $data['settings'] = $userapi->getsettings();

        // Check if we need to do anything special here
        if (!$this->fetch('processlist', 'str:1:', $processlist, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('resizelist', 'str:1:', $resizelist, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($processlist)) {
            $action = 'processlist';
        } elseif (!empty($resizelist)) {
            $action = 'resizelist';
        }

        // Find the right server image
        if (!empty($action) && !empty($fileId)) {
            $found = '';

            // if we're dealing with a list of fileId's, make sure they exist
            if (is_array($fileId) && ($action == 'resizelist' || $action == 'processlist')) {
                $found = [];
                foreach ($fileId as $id) {
                    if (!empty($data['images'][$id])) {
                        $found[$id] = $data['images'][$id];
                    }
                }
                if (count($found) < 1) {
                    $action = '';
                }

                // if we're dealing with an individual fileId, get some additional information
            } elseif (is_string($fileId) && !empty($data['images'][$fileId])) {
                $found = $data['images'][$fileId];
                // Get derivative images for this image
                if (file_exists($found['fileLocation'])) {
                    $found['derivatives'] = $adminapi->getderivatives([
                        'fileLocation' => $found['fileLocation'],
                    ]);
                }
            }
        }

        if (!empty($action) && !empty($found)) {
            switch ($action) {
                case 'view':
                    $data['selimage'] = $found;
                    $data['action'] = 'view';
                    return $data;

                case 'resize':
                    if (!$this->fetch('width', 'int:1:', $width, null, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('height', 'int:1:', $height, null, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('replace', 'int:0:1', $replace, 0, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!empty($confirm) && (!empty($width) || !empty($height))) {
                        if (!$this->confirmAuthKey()) {
                            return;
                        }
                        if (!empty($replace) && !empty($found['fileLocation'])) {
                            $location = $adminapi->replaceImage([
                                'fileLocation' => $found['fileLocation'],
                                'width'  => (!empty($width) ? $width . 'px' : null),
                                'height' => (!empty($height) ? $height . 'px' : null),
                            ]);
                            if (!$location) {
                                return;
                            }
                            // Redirect to viewing the original image here (for now)
                            $this->redirect($this->getUrl(
                                'admin',
                                'browse',
                                ['action' => 'view',
                                    'bid' => $baseId,
                                    'fid' => $found['fileId'], ]
                            ));
                        } else {
                            $location = $adminapi->resizeImage([
                                'fileLocation' => $found['fileLocation'],
                                'width'  => (!empty($width) ? $width . 'px' : null),
                                'height' => (!empty($height) ? $height . 'px' : null),
                            ]);
                            if (!$location) {
                                return;
                            }
                            // Redirect to viewing the derivative image here (for now)
                            $this->redirect($this->getUrl(
                                'admin',
                                'derivatives',
                                ['action' => 'view',
                                    'fileId' => md5($location), ]
                            ));
                        }
                        return true;
                    }
                    $data['selimage'] = $found;
                    if (empty($width) && empty($height)) {
                        $data['width'] = $found['width'];
                        $data['height'] = $found['height'];
                    } else {
                        $data['width'] = $width;
                        $data['height'] = $height;
                    }
                    if (empty($replace)) {
                        $data['replace'] = 0;
                    } else {
                        $data['replace'] = 1;
                    }
                    $data['action'] = 'resize';
                    $data['authid'] = $this->genAuthKey();
                    return $data;

                case 'delete':
                    if (!$this->fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!empty($confirm)) {
                        if (!$this->confirmAuthKey()) {
                            return;
                        }
                        // delete the server image now
                        @unlink($found['fileLocation']);
                        $this->redirect($this->getUrl('admin', 'browse'));
                        return true;
                    }
                    $data['selimage'] = $found;
                    $data['action'] = 'delete';
                    $data['authid'] = $this->genAuthKey();
                    return $data;

                case 'resizelist':
                    if (!$this->fetch('width', 'int:1:', $width, null, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('height', 'int:1:', $height, null, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('replace', 'int:0:1', $replace, 0, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (empty($confirm) || (empty($width) && empty($height))) {
                        $data['selected'] = array_keys($found);
                        if (empty($width) && empty($height)) {
                            $data['width'] = '';
                            $data['height'] = '';
                            $data['action'] = '';
                        } else {
                            $data['width'] = $width;
                            $data['height'] = $height;
                            $data['action'] = 'resizelist';
                        }
                        if (empty($replace)) {
                            $data['replace'] = '';
                        } else {
                            $data['replace'] = '1';
                        }
                        $data['authid'] = $this->genAuthKey();
                        return $data;
                    }

                    if (!$this->confirmAuthKey()) {
                        return;
                    }
                    if (!empty($replace)) {
                        foreach ($found as $id => $info) {
                            if (empty($info['fileLocation'])) {
                                continue;
                            }
                            $location = $adminapi->replaceImage([
                                'fileLocation' => $info['fileLocation'],
                                'width'  => (!empty($width) ? $width . 'px' : null),
                                'height' => (!empty($height) ? $height . 'px' : null),
                            ]);
                            if (!$location) {
                                return;
                            }
                        }
                        // Redirect to viewing the server images here (for now)
                        $this->redirect($this->getUrl(
                            'admin',
                            'browse',
                            ['bid'     => $baseId,
                                'sort'    => 'time',
                                // we need to refresh the cache here
                                'refresh' => 1, ]
                        ));
                    } else {
                        foreach ($found as $id => $info) {
                            if (empty($info['fileLocation'])) {
                                continue;
                            }
                            $location = $adminapi->resizeImage([
                                'fileLocation' => $info['fileLocation'],
                                'width'  => (!empty($width) ? $width . 'px' : null),
                                'height' => (!empty($height) ? $height . 'px' : null),
                            ]);
                            if (!$location) {
                                return;
                            }
                        }
                        // Redirect to viewing the derivative images here (for now)
                        $this->redirect($this->getUrl(
                            'admin',
                            'derivatives',
                            ['sort'    => 'time',
                                // we need to refresh the cache here
                                'refresh' => 1, ]
                        ));
                    }
                    return true;

                case 'processlist':
                    if (!$this->fetch('saveas', 'int:0:2', $saveas, 0, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('setting', 'str:1:', $setting, null, xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (!$this->fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
                        return;
                    }
                    if (empty($confirm) || empty($setting) || empty($data['settings'][$setting])) {
                        $data['selected'] = array_keys($found);
                        if (empty($setting) || empty($data['settings'][$setting])) {
                            $data['setting'] = '';
                            $data['action'] = '';
                        } else {
                            $data['setting'] = $setting;
                            $data['action'] = 'processlist';
                        }
                        if (empty($saveas)) {
                            $data['saveas'] = 0;
                        } else {
                            $data['saveas'] = $saveas;
                        }
                        $data['authid'] = $this->genAuthKey();
                        return $data;
                    }

                    if (!$this->confirmAuthKey()) {
                        return;
                    }

                    // Process images
                    foreach ($found as $id => $info) {
                        if (empty($info['fileLocation'])) {
                            continue;
                        }
                        $location = $adminapi->processImage([
                            'image'   => $info,
                            'saveas'  => $saveas,
                            'setting' => $setting,
                        ]);
                        if (!$location) {
                            return;
                        }
                    }

                    switch ($saveas) {
                        case 1: // [image]_new.[ext]
                            // Redirect to viewing the server images here (for now)
                            $this->redirect($this->getUrl(
                                'admin',
                                'browse',
                                ['bid'     => $baseId,
                                    'sort'    => 'time',
                                    // we need to refresh the cache here
                                    'refresh' => 1, ]
                            ));
                            break;

                        case 2: // replace
                            // Redirect to viewing the server images here (for now)
                            $this->redirect($this->getUrl(
                                'admin',
                                'browse',
                                ['bid'     => $baseId,
                                    'sort'    => 'time',
                                    // we need to refresh the cache here
                                    'refresh' => 1, ]
                            ));
                            break;

                        case 0: // derivative
                        default:
                            // Redirect to viewing the derivative images here (for now)
                            $this->redirect($this->getUrl(
                                'admin',
                                'derivatives',
                                ['sort'    => 'time',
                                    // we need to refresh the cache here
                                    'refresh' => 1, ]
                            ));
                            break;
                    }
                    return true;

                default:
                    break;
            }
        }

        $data['thumbsonly'] = false;
        $data['thumbsdir'] = $this->getModVar('path.derivative-store');
        if (is_writable($data['thumbsdir']) && !is_writable($data['basedir'])) {
            $data['thumbsonly'] = true;
        }

        // Return the template variables defined in this function
        return $data;
    }
}
