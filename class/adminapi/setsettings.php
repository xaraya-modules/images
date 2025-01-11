<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\AdminApi;


use Xaraya\Modules\Images\AdminApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi setsettings function
 * @extends MethodClass<AdminApi>
 */
class SetsettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Set the predefined settings for image processing
     * @author mikespub
     * @param mixed $args array containing the predefined settings for image processing
     * @return void
     */
    public function __invoke(array $args = [])
    {
        if (empty($args) || !is_array($args)) {
            $args = [];
        }

        xarModVars::set('images', 'phpthumb-settings', serialize($args));
    }
}
