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
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarModVars;
use xarSec;
use xarController;
use xarLog;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images admin phpthumb function
 * @extends MethodClass<AdminGui>
 */
class PhpthumbMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @deprecated 2.0.0 phpThumb() is seriously dated and doesn't play nice as a library
     * @see AdminGui::phpthumb()
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!$this->checkAccess('AdminImages')) {
            return;
        }

        extract($args);

        if (!xarVar::fetch('fid', 'isset', $fileId, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($fileId) && is_array($fileId)) {
            $fileId = array_keys($fileId);
        }
        $admingui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $admingui->getAPI();
        /** @var AdminApi $adminapi */
        $adminapi = $admingui->getModule()->getAdminAPI();
        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $userapi->getUploadsAPI();

        // Get the base directories configured for server images
        $basedirs = $userapi->getbasedirs();

        if (!xarVar::fetch('bid', 'isset', $baseId, '', xarVar::NOT_REQUIRED)) {
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

        // we're defining a processing filter without image here
        if (empty($fileId)) {
            $data['selimage'] = [];

            // we're dealing with an uploads file here
        } elseif (is_numeric($fileId)) {
            $data['images'] = $adminapi->getuploads([
                'fileId'   => $fileId,
            ]);
            if (!empty($data['images'][$fileId])) {
                $data['selimage'] = $data['images'][$fileId];
            }

            // we're dealing with a derivative image here
        } elseif (preg_match('/^[0-9a-f]{32}$/i', $fileId)) {
            $data['thumbsdir'] = xarModVars::get('images', 'path.derivative-store');
            $data['images'] = $adminapi->getderivatives([
                'thumbsdir' => $data['thumbsdir'],
                'fileId'    => $fileId,
            ]);
            foreach ($data['images'] as $image) {
                if ($image['fileId'] == $fileId) {
                    $data['selimage'] = $image;
                    break;
                }
            }

            // we're dealing with a server image here
        } else {
            $data['images'] = $adminapi->getimages($data);
            if (!empty($data['images'][$fileId])) {
                $data['selimage'] = $data['images'][$fileId];
            }
        }

        // Get the pre-defined settings for phpThumb
        $data['settings'] = $userapi->getsettings();

        if (!xarVar::fetch('setting', 'str:1:', $setting, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('load', 'str:1:', $load, null, xarVar::DONT_SET)) {
            return;
        }
        //$data['setting'] = $setting;
        $data['setting'] = '';
        if (!empty($load) && !empty($setting)) {
            if (!empty($data['settings'][$setting])) {
                // use pre-defined settings and ignore input values here
                extract($data['settings'][$setting]);
                $skipinput = 1;
            }
        }

        if (empty($skipinput)) {
            // URL parameters for phpThumb() - cfr. xardocs/phpthumb.readme.txt
            if (!xarVar::fetch('w', 'int:1:', $w, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('h', 'int:1:', $h, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('f', 'enum:jpeg:png:gif', $f, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('q', 'int:1:', $q, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('sx', 'float:0:', $sx, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('sy', 'float:0:', $sy, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('sw', 'float:0:', $sw, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('sh', 'float:0:', $sh, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('zc', 'checkbox', $zc, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('bg', 'str:6:6', $bg, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('bc', 'str:6:6', $bc, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('fltr', 'isset', $fltr, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('xto', 'checkbox', $xto, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('ra', 'int', $ra, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('ar', 'enum:p:P:L:l:x', $ar, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('aoe', 'checkbox', $aoe, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('iar', 'checkbox', $iar, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('far', 'checkbox', $far, null, xarVar::DONT_SET)) {
                return;
            }
            if (!xarVar::fetch('maxb', 'int:1:', $maxb, null, xarVar::DONT_SET)) {
                return;
            }
            // Process filters via input form
            if (!xarVar::fetch('filter', 'isset', $filter, null, xarVar::DONT_SET)) {
                return;
            }
        }

        // The following URL parameters are (or will be) supported here
        $paramlist = ['w', 'h', 'f', 'q', 'sx', 'sy', 'sw', 'sh', 'zc', 'bc', 'bg', 'fltr', 'xto', 'ra', 'ar', 'aoe', 'far', 'iar', 'maxb'];
        // The following URL parameters are not supported here
        //$unsupported = array('src', 'new', 'bgt', 'file', 'goto', 'err', 'down', 'phpThumbDebug', 'hash', 'md5s');

        // Remove empty filters
        if (!empty($fltr)) {
            $newfltr = [];
            foreach ($fltr as $info) {
                if (empty($info)) {
                    continue;
                }
                $newfltr[] = $info;
            }
            if (count($newfltr) > 0) {
                $fltr = $newfltr;
            } else {
                $fltr = null;
            }
        }

        // Available filter names and their number of attributes
        $filterlist = ['gam' => 1, 'ds' => 1, 'gray' => 0, 'clr' => 2, 'sep' => 2, 'usm' => 3, 'blur' => 1, 'lvl' => 3, 'wb' => 1, 'hist' => 7, 'over' => 4, 'wmi' => 4, 'wmt' => 8, 'flip' => 1, 'elip' => 0, 'mask' => 1, 'bvl' => 3, 'bord' => 4, 'fram' => 5, 'drop' => 4];

        // FIXME: make this configurable in TColorPicker !?
        // Get rid of # in front of hex colors
        if (!empty($filter['wmt']) && !empty($filter['wmt'][3]) &&  substr($filter['wmt'][3], 0, 1) == '#') {
            $filter['wmt'][3] = substr($filter['wmt'][3], 1);
        }

        if (!xarVar::fetch('save', 'str:1:', $save, null, xarVar::DONT_SET)) {
            return;
        }
        if (empty($save) && !empty($data['selimage']['fileLocation'])) {
            $save = $data['selimage']['fileLocation'];
            $save = realpath($save);
            if ($save) {
                $save = preg_replace('/\.(\w+)$/', '_new.$1', $save);
            }
        }
        $data['save'] = $save;

        if (!xarVar::fetch('preview', 'str:1:', $preview, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($preview) || !empty($confirm)) {
            if (!empty($confirm)) {
                if (!xarSec::confirmAuthKey()) {
                    return;
                }
            }

            // Process filters via input form
            if (empty($fltr) && !empty($filter)) {
                $fltr = [];
                foreach ($filter as $name => $values) {
                    // skip invalid filter entries
                    if (!isset($filterlist[$name]) || !is_array($values) || count($values) < $filterlist[$name]) {
                        continue;
                    }
                    ksort($values, SORT_NUMERIC);
                    // skip empty filter entries
                    if ($filterlist[$name] > 0 && $values[0] === '') {
                        continue;
                    }
                    if ($filterlist[$name] > 0) {
                        $fltr[] = $name . '|' . join('|', $values);
                    } else {
                        $fltr[] = $name;
                    }
                }
            }

            $phpThumb = $adminapi->getPhpThumb();

            // CHECKME: document root may be incorrect in some cases

            if (file_exists($data['selimage']['fileLocation'])) {
                $file = realpath($data['selimage']['fileLocation']);
                $phpThumb->setSourceFilename($file);
            } elseif (is_numeric($fileId) && defined('\Xaraya\Modules\Uploads\Defines::STORE_DB_DATA') && ($data['selimage']['storeType'] & \Xaraya\Modules\Uploads\Defines::STORE_DB_DATA)) {
                // get the image data from the database
                $data = $uploadsapi->dbGetFileData(['fileId' => $fileId]);
                if (!empty($data)) {
                    $src = implode('', $data);
                    unset($data);
                    $phpThumb->setSourceData($src);

                    if (empty($save)) {
                        $tmpdir = xarModVars::get('uploads', 'path.uploads-directory');
                        if (is_dir($tmpdir) && is_writable($tmpdir)) {
                            $save = tempnam($tmpdir, 'xarimage-');
                        } else {
                            $save = tempnam(null, 'xarimage-');
                        }
                        $dbfile = 1;
                    }
                }
            } else {
            }

            // or $phpThumb->setSourceImageResource($gd_image_resource);

            foreach ($paramlist as $param) {
                if (isset($$param) && $$param !== false) {
                    $phpThumb->$param = $$param;
                }
            }

            if ($phpThumb->GenerateThumbnail()) {
                if (!empty($confirm) && !empty($save)) {
                    if (!$phpThumb->RenderToFile($save)) {
                        // do something with debug/error messages
                        $msg = implode("\n\n", $phpThumb->debugmessages);
                        throw new BadParameterException(null, $msg);
                    } else {
                        if (!empty($dbfile) || realpath($save) == realpath($data['selimage']['fileLocation'])) {
                            // update the uploads file entry if we overwrite a file !
                            if (is_numeric($fileId)) {
                                if (empty($f)) {
                                    $fileType = 'image/jpeg';
                                } else {
                                    $fileType = 'image/' . $f;
                                }
                                if (!$uploadsapi->dbModifyFile([
                                    'fileId'    => $fileId,
                                    'fileType'  => $fileType,
                                    'fileSize'  => filesize($save),
                                    // reset the extrainfo
                                    'extrainfo' => '',
                                ])) {
                                    return;
                                }
                                if (!empty($dbfile)) {
                                    if (!$uploadsapi->fileDump([
                                        'fileSrc' => $save,
                                        'fileId' => $fileId,
                                    ])) {
                                        return;
                                    }
                                }
                                // Redirect to viewing the updated image here (for now)
                                xarController::redirect(xarController::URL(
                                    'images',
                                    'admin',
                                    'uploads',
                                    ['action' => 'view',
                                        'fileId' => $fileId, ]
                                ), null, $this->getContext());
                                return true;
                            } elseif (preg_match('/^[0-9a-f]{32}$/i', $fileId)) {
                                // Redirect to viewing the updated image here (for now)
                                xarController::redirect(xarController::URL(
                                    'images',
                                    'admin',
                                    'derivatives',
                                    ['action' => 'view',
                                        'fileId' => $fileId, ]
                                ), null, $this->getContext());
                                return true;
                            } else {
                                // Redirect to viewing the updated image here (for now)
                                xarController::redirect(xarController::URL(
                                    'images',
                                    'admin',
                                    'browse',
                                    ['action' => 'view',
                                        'bid'    => $baseId,
                                        'fid'    => $fileId, ]
                                ), null, $this->getContext());
                                return true;
                            }
                        }
                        $data['message'] = $this->translate('The image has been saved as "#(1)"', $save);
                    }
                } else {
                    $phpThumb->OutputThumbnail();
                    xarLog::message('Images Phpthumb failed to generate thumbnail:' . $msg, xarLog::LEVEL_INFO);
                    // Stop processing here
                    exit;
                }
            } else {
                $msg = implode("\n\n", $phpThumb->debugmessages);
                xarLog::message('Images Phpthumb failed to generate thumbnail:' . $msg, xarLog::LEVEL_INFO);
                if (!empty($preview)) {
                    $phpThumb->ErrorImage($msg);
                    // Stop processing here
                    exit;
                } else {
                    throw new BadParameterException(null, $msg);
                }
            }
        }

        $previewargs = [];
        $previewargs['fid'] = $fileId;
        foreach ($paramlist as $param) {
            if (isset($$param) && $$param !== false) {
                $data[$param] = $$param;
                $previewargs[$param] = $$param;
            } else {
                $data[$param] = '';
            }
        }
        // Process filters via input form
        if (empty($previewargs['fltr']) && !empty($filter)) {
            $previewargs['fltr'] = [];
            foreach ($filter as $name => $values) {
                // skip invalid filter entries
                if (!isset($filterlist[$name]) || !is_array($values) || count($values) < $filterlist[$name]) {
                    continue;
                }
                ksort($values, SORT_NUMERIC);
                // skip empty filter entries
                if ($filterlist[$name] > 0 && $values[0] === '') {
                    continue;
                }
                if ($filterlist[$name] > 0) {
                    $previewargs['fltr'][] = $name . '|' . join('|', $values);
                } else {
                    $previewargs['fltr'][] = $name;
                }
            }
        }

        if (!xarVar::fetch('newset', 'str:1:', $newset, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('store', 'str:1:', $store, null, xarVar::DONT_SET)) {
            return;
        }
        if (!empty($store)) {
            if (!empty($newset)) {
                // if we have both setting and newset, "rename" the old setting to the new one
                if (!empty($setting) && isset($data['settings'][$setting])) {
                    unset($data['settings'][$setting]);
                }
                $setting = $newset;
                //$data['setting'] = $newset;
            }
            if (!empty($setting)) {
                $data['settings'][$setting] = $previewargs;
                if (isset($data['settings'][$setting]['fid'])) {
                    unset($data['settings'][$setting]['fid']);
                }

                $adminapi->setsettings($data['settings']);

                // Note: processed images are named md5(filelocation)-[setting].[ext] - see process_image() function
                $add = xarVar::prepForOS($setting);
                $add = strtr($add, [' ' => '']);
                $affected = $adminapi->getderivatives([
                    'filematch' => '^\w+-' . $add,
                ]);
                // Delete any derivative image using this setting earlier
                if (!empty($affected)) {
                    foreach ($affected as $info) {
                        @unlink($info['fileLocation']);
                    }
                }
            }
        }

        if (count($previewargs) > 1) {
            $previewargs['preview'] = 1;
            if (!empty($baseId)) {
                $previewargs['bid'] = $baseId;
            }
            $previewurl = xarController::URL(
                'images',
                'admin',
                'phpthumb',
                $previewargs
            );
            // restore | characters in fltr
            $previewurl = strtr($previewurl, ['%7C' => '|']);
            // show parameters
            $data['params'] = preg_replace('/^.*fid=[^&]*&amp;/', '', $previewurl);
            $data['params'] = preg_replace('/&amp;preview=1.*$/', '', $data['params']);
            if (!empty($data['selimage'])) {
                $data['selimage']['filePreview'] = $previewurl;
            }
        }

        // preset the format based on the current file type
        if (empty($data['f'])) {
            if (empty($data['selimage'])) {
                $data['f'] = 'jpeg';
            } else {
                switch ($data['selimage']['fileType']) {
                    case 'image/png':
                        $data['f'] = 'png';
                        break;
                    case 'image/gif':
                        $data['f'] = 'gif';
                        break;
                    case 'image/jpeg':
                    default:
                        $data['f'] = 'jpeg';
                        break;
                }
            }
        }

        // CHECKME: check combination of $fltr and $filter

        // preset the different filter attributes for the input form
        if (!empty($fltr) && empty($filter)) {
            $filter = [];
            foreach ($fltr as $id => $info) {
                if (empty($info)) {
                    continue;
                }
                $values = preg_split('/\|/', $info);
                $name = array_shift($values);
                // skip invalid filter entries
                if (!isset($filterlist[$name]) || count($values) < $filterlist[$name]) {
                    continue;
                }
                $filter[$name] = $values;
                // remove from the fltr fields
                $data['fltr'][$id] = '';
            }
        }
        if (empty($filter)) {
            $data['filter'] = [];
        } else {
            $data['filter'] = $filter;
        }
        foreach ($filterlist as $name => $attr) {
            if (empty($data['filter'][$name])) {
                $data['filter'][$name] = [];
            }
            for ($i = count($data['filter'][$name]); $i <= $attr; $i++) {
                $data['filter'][$name][] = '';
            }
        }
        // preset the fltr fields
        if (empty($fltr)) {
            $data['fltr'] = [];
        }
        for ($i = count($data['fltr']); $i <= 4; $i++) {
            $data['fltr'][] = '';
        }

        $data['authid'] = xarSec::genAuthKey('images');
        return $data;
    }
}
