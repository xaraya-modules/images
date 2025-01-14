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
use Xaraya\Modules\MethodClass;
use xarVar;
use xarModVars;
use xarSec;
use xarModHooks;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images admin updateconfig function
 * @extends MethodClass<AdminGui>
 */
class UpdateconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update configuration
     * @return bool|void true on success of update
     * @see AdminGui::updateconfig()
     */
    public function __invoke(array $args = [])
    {
        // Get parameters
        if (!$this->fetch('libtype', 'list:int:1:3', $libtype, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('file', 'list:str:1:', $file, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('path', 'list:str:1:', $path, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('view', 'list:str:1:', $view, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('shortURLs', 'checkbox', $shortURLs, true)) {
            return;
        }

        if (isset($shortURLs) && $shortURLs) {
            $this->setModVar('SupportShortURLs', true);
        } else {
            $this->setModVar('SupportShortURLs', false);
        }

        // Confirm authorisation code.
        if (!$this->confirmAuthKey()) {
            return;
        }

        if (isset($libtype) && is_array($libtype)) {
            foreach ($libtype as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                if (null !== $this->getModVar('type.' . $varname)) {
                    $this->setModVar('type.' . $varname, $value);
                }
            }
        }
        if (isset($file) && is_array($file)) {
            foreach ($file as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                if (null !== $this->getModVar('file.' . $varname)) {
                    $this->setModVar('file.' . $varname, $value);
                }
            }
        }
        if (isset($path) && is_array($path)) {
            foreach ($path as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                $value = trim(preg_replace('~\/$~', '', $value));
                if (null !== $this->getModVar('path.' . $varname)) {
                    if (!file_exists($value) || !is_dir($value)) {
                        $msg = $this->translate('Location [#(1)] either does not exist or is not a valid directory!', $value);
                        throw new BadParameterException(null, $msg);
                    } elseif (!is_writable($value)) {
                        $msg = $this->translate('Location [#(1)] can not be written to - please check permissions and try again!', $value);
                        throw new BadParameterException(null, $msg);
                    } else {
                        $this->setModVar('path.' . $varname, $value);
                    }
                }
            }
        }
        if (isset($view) && is_array($view)) {
            foreach ($view as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                // TODO: add other view.* variables later ?
                if ($varname != 'itemsperpage') {
                    continue;
                }
                $this->setModVar('view.' . $varname, $value);
            }
        }

        if (!$this->fetch('basedirs', 'isset', $basedirs, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($basedirs) && is_array($basedirs)) {
            $newdirs = [];
            $idx = 0;
            foreach ($basedirs as $id => $info) {
                if (empty($info['basedir']) && empty($info['baseurl']) && empty($info['filetypes'])) {
                    continue;
                }
                $newdirs[$idx] = ['basedir' => $info['basedir'],
                    'baseurl' => $info['baseurl'],
                    'filetypes' => $info['filetypes'],
                    'recursive' => (!empty($info['recursive']) ? true : false), ];
                $idx++;
            }
            $this->setModVar('basedirs', serialize($newdirs));
        }

        xarModHooks::call('module', 'updateconfig', 'images', ['module' => 'images']);
        $this->redirect($this->getUrl('admin', 'modifyconfig'));

        // Return
        return true;
    }
}
