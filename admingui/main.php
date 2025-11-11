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

/**
 * images admin main function
 * @extends MethodClass<AdminGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * the main administration function
     * @return bool|null true on succes of redirect
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->sec()->checkAccess('AdminImages')) {
            return;
        }
        $this->ctl()->redirect($this->mod()->getURL('admin', 'modifyconfig'));
        // success
        return true;
    }
}
