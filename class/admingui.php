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

use Xaraya\Modules\AdminGuiClass;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use xarMod;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.images.class.adminapi');

/**
 * Handle the images admin GUI
 *
 * @method mixed browse(array $args = []) View a list of server images
 * @method mixed derivatives(array $args = []) View a list of derivative images (thumbnails, resized etc.)
 * @method mixed importpictures(array $args = []) Images Module
 * @method mixed main(array $args = []) the main administration function
 * @method mixed modifyconfig(array $args = []) Images module
 * @method mixed overview(array $args = []) Overview displays standard Overview page
 * @method mixed phpthumb(array $args = [])
 * @method mixed updateconfig(array $args = []) Update configuration
 * @method mixed uploads(array $args = []) View a list of uploaded images (managed by the uploads module)
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
