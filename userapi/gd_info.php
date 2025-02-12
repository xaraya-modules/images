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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi gd_info function
 * @extends MethodClass<UserApi>
 */
class GdInfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Images Module
     * @package modules
     * @copyright (C) 2002-2007 The Digital Development Foundation
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://www.xaraya.com
     * @subpackage Images Module
     * @link http://xaraya.com/index.php/release/152.html
     * @author Images Module Development Team
     * @see UserApi::gdInfo()
     */
    public function __invoke(array $args = [])
    {
        if (function_exists('gd_info')) {
            $gd_info = gd_info();
        } else {
            $gd_info = [
                'GD Version'          => 'not supported',
                'FreeType Support'    => false,
                'T1Lib Support'       => false,
                'GIF Read Support'    => false,
                'GIF Create Support'  => false,
                'JPG Support'         => false,
                'PNG Support'         => false,
                'WBMP Support'        => false,
                'XBM Support'         => false, ];

            ob_start();
            phpinfo(INFO_MODULES);
            $string = ob_get_contents();
            ob_end_clean();

            $pieces = explode('<h2>', $string);
            foreach ($pieces as $key => $piece) {
                if (!stristr($piece, 'module_gd')) {
                    unset($pieces[$key]);
                } else {
                    $gd_pre = $piece;
                    unset($pieces);
                    break;
                }
            }

            if (isset($gd_pre)) {
                $gd_multi = explode("\n", $gd_pre);

                foreach ($gd_multi as $key => $line) {
                    // skip the first & second key key cuz they're just garbage
                    if ($key <= 1) {
                        continue;
                    }

                    preg_match('/\<tr\>\<td class="e"\>([^<]*)\<\/td\>\<td class="v"\>([^<]*)\<\/td\>\<\/tr\>/i', $line, $matches);

                    $key   = trim($matches[1]);
                    $value = trim($matches[2]);

                    switch ($value) {
                        case 'enabled':
                            $value = true;
                            break;
                        case 'disabled':
                            $value = false;
                            break;
                    }
                    $gd_info[$key] = $value;
                }
            }
        }

        if (function_exists('imagetypes')) {
            $gd_info['typesBitmask'] = imagetypes();
        } else {
            $gd_info['typesBitmask'] = 0;
        }

        return $gd_info;
    }
}
