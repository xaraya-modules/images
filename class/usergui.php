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

use Xaraya\Modules\UserGuiClass;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use xarMod;
use sys;

sys::import('xaraya.modules.usergui');
sys::import('modules.images.class.userapi');

/**
 * Handle the images user GUI
 *
 * @method mixed display(array $args) Pushes an image to the client browser
 *  array{fileId: string}
 * @method mixed main(array $args = []) Empty main user function.
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    /**
     * User main GUI function
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = [])
    {
        $args['description'] ??= 'Description of images';

        // Pass along the context for xarTpl::module() if needed
        $args['context'] ??= $this->getContext();
        return $args;
    }
}
