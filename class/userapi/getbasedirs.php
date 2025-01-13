<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\UserApi;

use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi getbasedirs function
 * @extends MethodClass<UserApi>
 */
class GetbasedirsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get the configured base directories for server images
     * @return array containing the base directories for server images
     * @see UserApi::getbasedirs()
     */
    public function __invoke(array $args = [])
    {
        $basedirs = xarModVars::get('images', 'basedirs');
        if (!empty($basedirs)) {
            $basedirs = unserialize($basedirs);
        }
        if (empty($basedirs)) {
            $basedirs = [];
            $basedirs[0] = ['basedir'   => 'themes',
                'baseurl'   => 'themes',
                'filetypes' => 'gif|jpg|png',
                'recursive' => true, ];
            xarModVars::set('images', 'basedirs', serialize($basedirs));
        }

        return $basedirs;
    }
}
