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
use xarTpl;

/**
 * images admin overview function
 * @extends MethodClass<AdminGui>
 */
class OverviewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Overview displays standard Overview page
     * @return array|string|null xarTpl::module with $data containing template data
     * containing the menulinks for the overview item on the main manu
     * @since 14 Oct 2005
     * @see AdminGui::overview()
     */
    public function __invoke(array $args = [])
    {
        /* Security Check */
        if (!$this->sec()->checkAccess('AdminImages', 0)) {
            return;
        }

        $data = [];

        /* if there is a separate overview function return data to it
         * else just call the main function that usually displays the overview
         */
        $data['context'] = $this->getContext();
        return $this->mod()->template('main', $data, 'main');
    }
}
