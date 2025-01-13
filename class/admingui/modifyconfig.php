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

use Xaraya\Modules\Images\Defines;
use Xaraya\Modules\Images\AdminGui;
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarMod;
use xarModVars;
use xarSec;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!$this->checkAccess('AdminImages')) {
            return;
        }
        $admingui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $admingui->getAPI();

        xarMod::apiLoad('images');
        // Generate a one-time authorisation code for this operation

        // get the current module variables for display
        // *********************************************
        // Global
        $data['gdextension'] = extension_loaded('gd'); // True or false
        $data['libtype']['graphics-library']    = xarModVars::get('images', 'type.graphics-library'); // return gd
        $data['path']['derivative-store']       = xarModVars::get('images', 'path.derivative-store');
        $data['file']['cache-expire']           = xarModVars::get('images', 'file.cache-expire');
        if (!isset($data['file']['cache-expire'])) {
            xarModVars::set('images', 'file.cache-expire', 60);
        }
        $data['file']['imagemagick']            = xarModVars::get('images', 'file.imagemagick');
        if (!isset($data['file']['imagemagick'])) {
            xarModVars::set('images', 'file.imagemagick', '');
        }
        $data['authid']                         = xarSec::genAuthKey();
        $data['library']   = ['GD'          => Defines::LIBRARY_GD,
            'ImageMagick' => Defines::LIBRARY_IMAGEMAGICK,
            'NetPBM'      => Defines::LIBRARY_NETPBM, ];

        $shortURLs = xarModVars::get('images', 'SupportShortURLs');

        $data['shortURLs'] = empty($shortURLs) ? 0 : 1;

        $data['basedirs'] = $userapi->getbasedirs();
        $data['basedirs'][] = ['basedir' => '',
            'baseurl' => '',
            'filetypes' => '',
            'recursive' => false, ];

        $hooks = xarModHooks::call('module', 'modifyconfig', 'images', []);

        if (empty($hooks)) {
            $data['hooks'] = '';
        } elseif (is_array($hooks)) {
            $data['hooks'] = join('', $hooks);
        } else {
            $data['hooks'] = $hooks;
        }
        // Return the template variables defined in this function
        return $data;
    }
}
