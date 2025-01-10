<?php

/**
 * Handle module installer functions
 *
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images;

use Xaraya\Modules\InstallerClass;
use xarMod;
use xarModVars;
use xarMasks;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @todo add extra use ...; statements above as needed
 * @todo replaced images_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /**
     * Configure this module - override this method
     *
     * @todo use this instead of init() etc. for standard installation
     * @return void
     */
    public function configure()
    {
        $this->objects = [
            // add your DD objects here
            //'images_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * initialise the images module
     * @return bool true on success
     */
    public function init()
    {
        // Load any predefined constants
        xarMod::apiLoad('images', 'user');

        // Check for the required extensions
        // GD is only needed if the user wants to use resize.
        // True or False
        /*$data['gdextension']              = extension_loaded ('gd');*/


        // Set up module variables
        xarModVars::set('images', 'type.graphics-library', _IMAGES_LIBRARY_GD);
        xarModVars::set('images', 'path.derivative-store', 'Put a real directory in here...!');
        xarModVars::set('images', 'view.itemsperpage', 200);
        xarModVars::set('images', 'file.cache-expire', 60);
        xarModVars::set('images', 'file.imagemagick', '');

        /*
            xarMasks::register('ViewUploads',  'All','images','Image','All','ACCESS_READ');
            xarMasks::register('AddUploads',   'All','images','Image','All','ACCESS_ADD');
            xarMasks::register('EditUploads',  'All','images','Image','All','ACCESS_EDIT');
            xarMasks::register('DeleteUploads','All','images','Image','All','ACCESS_DELETE');
        */
        xarMasks::register('AdminImages', 'All', 'images', 'Image', 'All', 'ACCESS_ADMIN');

        if (!xarModHooks::register('item', 'transform', 'API', 'images', 'user', 'transformhook')) {
            $msg = xarML('Could not register hook.');
            throw new BadParameterException(null, $msg);
        }
        /*
        // Register the tag
        $imageAttributes = [new xarTemplateAttribute('src', XAR_TPL_REQUIRED | XAR_TPL_STRING),
                                 new xarTemplateAttribute('height', XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                 new xarTemplateAttribute('width', XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                 new xarTemplateAttribute('constrain', XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                 new xarTemplateAttribute('label', XAR_TPL_REQUIRED | XAR_TPL_STRING), ];
        xarTplRegisterTag('images', 'image-resize', $imageAttributes, 'images_userapi_handle_image_tag');
         */

        // Initialisation successful
        return true;
    }

    /**
     * upgrade the images module from an old version
     * @param string oldversion
     * @return bool true on success
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '1.0.0':
                // Code to upgrade from version 1.0.0 goes here
                $thumbsdir = xarModVars::get('images', 'path.derivative-store');
                if (!empty($thumbsdir) && is_dir($thumbsdir)) {
                    xarModVars::set('images', 'upgrade-1.0.0', 1);
                    // remove all old-style derivatives
                    /* skip this - too risky depending on site config
                        $images = xarMod::apiFunc('images','admin','getderivatives');
                        if (!empty($images)) {
                            foreach ($images as $image) {
                                @unlink($image['fileLocation']);
                            }
                        }
                    */
                }
                // Fall through to next upgrade

                // no break
            case '1.1.0':

            case '1.1.1': //current version
                break;
        }

        return true;
    }

    /**
     * delete the images module
     * @return bool
     */
    public function delete()
    {
        // Unregister template tag
        // xarTplUnregisterTag('image-resize');
        // Remove mask
        xarMasks::unregister('AdminImages');
        // Unregister the hook
        xarModHooks::unregister('item', 'transform', 'API', 'images', 'user', 'transformhook');
        // Delete module variables
        xarModVars::delete_all('images');
        // Deletion successful
        return true;
    }
}
