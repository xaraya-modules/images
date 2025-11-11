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

/**
 * images userapi getsettings function
 * @extends MethodClass<UserApi>
 */
class GetsettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get the predefined settings for image processing
     * @return array containing the predefined settings for image processing
     * @see UserApi::getsettings()
     */
    public function __invoke(array $args = [])
    {
        $settings = $this->mod()->getVar('phpthumb-settings');
        if (empty($settings)) {
            $settings = [];
            $settings['JPEG 800 x 600'] = ['w' => 800,
                'h' => 600,
                'f' => 'jpg', ];
            $this->mod()->setVar('phpthumb-settings', serialize($settings));
        } else {
            $settings = unserialize($settings);
        }

        return $settings;
    }
}
