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
use BadParameterException;

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
        $this->var()->find('libtype', $libtype, 'list:int:1:3', '');
        $this->var()->find('file', $file, 'list:str:1:', '');
        $this->var()->find('path', $path, 'list:str:1:', '');
        $this->var()->find('view', $view, 'list:str:1:', '');
        $this->var()->get('shortURLs', $shortURLs, 'checkbox', true);

        if (isset($shortURLs) && $shortURLs) {
            $this->mod()->setVar('SupportShortURLs', true);
        } else {
            $this->mod()->setVar('SupportShortURLs', false);
        }

        // Confirm authorisation code.
        if (!$this->sec()->confirmAuthKey()) {
            return;
        }

        if (isset($libtype) && is_array($libtype)) {
            foreach ($libtype as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                if (null !== $this->mod()->getVar('type.' . $varname)) {
                    $this->mod()->setVar('type.' . $varname, $value);
                }
            }
        }
        if (isset($file) && is_array($file)) {
            foreach ($file as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                if (null !== $this->mod()->getVar('file.' . $varname)) {
                    $this->mod()->setVar('file.' . $varname, $value);
                }
            }
        }
        if (isset($path) && is_array($path)) {
            foreach ($path as $varname => $value) {
                // check to make sure that the value passed in is
                // a real images module variable
                $value = trim(preg_replace('~\/$~', '', $value));
                if (null !== $this->mod()->getVar('path.' . $varname)) {
                    if (!file_exists($value) || !is_dir($value)) {
                        $msg = $this->ml('Location [#(1)] either does not exist or is not a valid directory!', $value);
                        throw new BadParameterException(null, $msg);
                    } elseif (!is_writable($value)) {
                        $msg = $this->ml('Location [#(1)] can not be written to - please check permissions and try again!', $value);
                        throw new BadParameterException(null, $msg);
                    } else {
                        $this->mod()->setVar('path.' . $varname, $value);
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
                $this->mod()->setVar('view.' . $varname, $value);
            }
        }

        $this->var()->find('basedirs', $basedirs);
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
            $this->mod()->setVar('basedirs', serialize($newdirs));
        }

        $this->mod()->callHooks('module', 'updateconfig', 'images', ['module' => 'images']);
        $this->ctl()->redirect($this->mod()->getURL('admin', 'modifyconfig'));

        // Return
        return true;
    }
}
