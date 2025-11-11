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
        if (!$this->sec()->checkAccess('AdminImages')) {
            return;
        }

        // Note: fileId is a base 64 encode of the image location here, or an array of fileId's
        $this->var()->find('fid', $fileId);
        if (!empty($fileId) && is_array($fileId)) {
            $fileId = array_keys($fileId);
        }
        if (empty($fileId)) {
            $fileId = null;
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // Get the base directories configured for server images
        $basedirs = $userapi->getbasedirs();

        $this->var()->find('bid', $baseId);
        if (empty($baseId) || empty($basedirs[$baseId])) {
            $data = $basedirs[0]; // themes directory
            $baseId = null;
        } else {
            $data = $basedirs[$baseId];
        }
        $data['baseId'] = $baseId;
        $data['fileId'] = $fileId;

        $this->var()->check('startnum', $startnum, 'int:0:');
        $this->var()->check('numitems', $numitems, 'int:0:');
        $this->var()->find('sort', $sort, 'enum:name:type:width:height:size:time', 'name');
        $this->var()->find('action', $action, 'str:1:', '');
        $this->var()->check('getnext', $getnext, 'str:1:');
        $this->var()->check('getprev', $getprev, 'str:1:');
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        $data['startnum'] = $startnum;
        $data['numitems'] = $numitems;
        $data['sort'] = ($sort != 'name') ? $sort : null;
        $data['getnext'] = $getnext;
        $data['getprev'] = $getprev;

        // Check if we can cache the image list
        $data['cacheExpire'] = $this->mod()->getVar('file.cache-expire');

        $data['pager'] = '';
        if (!empty($fileId)) {
            $data['images'] = $adminapi->getimages($data);
        } else {
            $params = $data;
            if (!isset($numitems)) {
                $params['numitems'] = $this->mod()->getVar('view.itemsperpage');
            }
            // Check if we need to refresh the cache anyway
            $this->var()->check('refresh', $refresh, 'int:0:');
            $params['cacheRefresh'] = $refresh;

            $data['images'] = $adminapi->getimages($params);

            if ((!empty($getnext) || !empty($getprev))
                && !empty($data['images']) && count($data['images']) == 1) {
                $image = array_pop($data['images']);
                $this->ctl()->redirect($this->mod()->getURL(
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
                $data['pager'] = $this->tpl()->getPager(
                    $startnum,
                    $countitems,
                    $this->mod()->getURL(
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
        $this->var()->find('processlist', $processlist, 'str:1:', '');
        $this->var()->find('resizelist', $resizelist, 'str:1:', '');
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
                    $this->var()->find('width', $width, 'int:1:');
                    $this->var()->find('height', $height, 'int:1:');
                    $this->var()->find('replace', $replace, 'int:0:1', 0);
                    $this->var()->find('confirm', $confirm, 'str:1:', '');
                    if (!empty($confirm) && (!empty($width) || !empty($height))) {
                        if (!$this->sec()->confirmAuthKey()) {
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
                            $this->ctl()->redirect($this->mod()->getURL(
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
                            $this->ctl()->redirect($this->mod()->getURL(
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
                    $data['authid'] = $this->sec()->genAuthKey();
                    return $data;

                case 'delete':
                    $this->var()->find('confirm', $confirm, 'str:1:', '');
                    if (!empty($confirm)) {
                        if (!$this->sec()->confirmAuthKey()) {
                            return;
                        }
                        // delete the server image now
                        @unlink($found['fileLocation']);
                        $this->ctl()->redirect($this->mod()->getURL('admin', 'browse'));
                        return true;
                    }
                    $data['selimage'] = $found;
                    $data['action'] = 'delete';
                    $data['authid'] = $this->sec()->genAuthKey();
                    return $data;

                case 'resizelist':
                    $this->var()->find('width', $width, 'int:1:');
                    $this->var()->find('height', $height, 'int:1:');
                    $this->var()->find('replace', $replace, 'int:0:1', 0);
                    $this->var()->find('confirm', $confirm, 'str:1:', '');
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
                        $data['authid'] = $this->sec()->genAuthKey();
                        return $data;
                    }

                    if (!$this->sec()->confirmAuthKey()) {
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
                        $this->ctl()->redirect($this->mod()->getURL(
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
                        $this->ctl()->redirect($this->mod()->getURL(
                            'admin',
                            'derivatives',
                            ['sort'    => 'time',
                                // we need to refresh the cache here
                                'refresh' => 1, ]
                        ));
                    }
                    return true;

                case 'processlist':
                    $this->var()->find('saveas', $saveas, 'int:0:2', 0);
                    $this->var()->find('setting', $setting, 'str:1:');
                    $this->var()->find('confirm', $confirm, 'str:1:', '');
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
                        $data['authid'] = $this->sec()->genAuthKey();
                        return $data;
                    }

                    if (!$this->sec()->confirmAuthKey()) {
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
                            $this->ctl()->redirect($this->mod()->getURL(
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
                            $this->ctl()->redirect($this->mod()->getURL(
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
                            $this->ctl()->redirect($this->mod()->getURL(
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
        $data['thumbsdir'] = $this->mod()->getVar('path.derivative-store');
        if (is_writable($data['thumbsdir']) && !is_writable($data['basedir'])) {
            $data['thumbsonly'] = true;
        }

        // Return the template variables defined in this function
        return $data;
    }
}
