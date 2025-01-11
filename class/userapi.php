<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the images user API
 *
 * @method mixed decodeShorturl(array $args)
 * @method mixed dropdownlist(array $args)
 * @method mixed encodeShorturl(array $args)
 * @method mixed gdInfo(array $args)
 * @method mixed getbasedirs(array $args)
 * @method mixed getimageinfo(array $args)
 * @method mixed getimagesize(array $args)
 * @method mixed getsettings(array $args)
 * @method mixed handleImageTag(array $args)
 * @method mixed loadImage(array $args)
 * @method mixed resize(array $args)
 * @method mixed transformhook(array $args)
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...
}
