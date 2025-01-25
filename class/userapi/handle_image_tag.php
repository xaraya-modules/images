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
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi handle_image_tag function
 * @extends MethodClass<UserApi>
 */
class HandleImageTagMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Handle <xar:image-resize ... /> tags
     * Format : <xar:image-resize src="fileId | URL" width="[0-9]+(px|%)" [height="[0-9]+(px|%)" constrain="(yes|true|1|no|false|0)"] label="text" />
     * examples:
     *  <xar:image-resize src="32" width="50px" height="50px" label="resize an image using pixels" />
     *  <xar:image-resize src="somedir/some_image.jpg" width="25%" constrain="yes" label="resize an image using percentages" />
     *  <xar:image-resize src="32" setting="JPEG 800 x 600" label="process an image with predefined setting" />
     *  <xar:image-resize src="32" params="$params" label="process an image with phpThumb parameters" />
     * @param array<mixed> $args array containing the image that you want to resize and display
     * @return string the PHP code needed to invoke resize() in the BL template
     * @see UserApi::handleImageTag()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!isset($width) && !isset($height) && !isset($setting) && !isset($params)) {
            throw new BadParameterException(['width', 'height', 'setting', 'params'], "Required attributes '#(1)', '#(2)', '#(3)' or '#(4)' for tag <xar:image> are missing. See tag documentation.");
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $format = 'array(%s)';
        foreach ($args as $key => $value) {
            // preserve support for $info[fileId] as before
            if (substr($value, 0, 1) == '$' && strpos($value, '[') === false) {
                $items[] = "'$key' => $value";
            } else {
                $items[] = "'$key' => \"$value\"";
            }
        }
        $array = sprintf($format, implode(',', $items));

        $imgTag = sprintf("
            \$tag = $userapi->resize(%s);
            if (!\$tag) {
                return;
            } else {
                echo \$tag;
            }", $array);
        return $imgTag;
    }
}
