<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\UserGui;

use Xaraya\Modules\Images\Defines;
use Xaraya\Modules\Images\UserGui;
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarMod;
use xarController;
use xarSession;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images user display function
 * @extends MethodClass<UserGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Pushes an image to the client browser
     *  @author   Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var string    $fileId              The id (from the uploads module) of the image to push
     * @return   bool|void|never           This function will exit upon succes and, returns False and throws an exception otherwise
     * @see UserGui::display()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->var()->get('fileId', $fileId, 'str:1:')) {
            return;
        }
        if (!$this->var()->find('width', $width, 'str:1:', '')) {
            return;
        }
        if (!$this->var()->find('height', $height, 'str:1:', '')) {
            return;
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (is_numeric($fileId)) {
            $data = ['fileId' => $fileId];
        } else {
            $fileLocation = base64_decode($fileId);
            if (empty($fileLocation) || !file_exists($fileLocation)) {
                return false;
            }
            $data = ['fileLocation' => $fileLocation];
        }

        $image = $userapi->loadImage($data);

        if (!is_object($image)) {
            $this->ctl()->redirect('modules/images/xarimages/admin.gif');
            return true;
        }

        $fileType = $image->mime;
        $fileName = $image->fileName;

        if (isset($width) && !empty($width)) {
            $width = urldecode($width);
            preg_match('/([0-9]+)(px|%|)/i', $width, $parts);
            $type = ($parts[2] == '%') ? Defines::UNIT_TYPE_PERCENT : Defines::UNIT_TYPE_PIXELS;
            switch ($type) {
                case Defines::UNIT_TYPE_PERCENT:
                    $image->setPercent(['wpercent' => $width]);
                    break;
                default:
                case Defines::UNIT_TYPE_PIXELS:
                    $image->setWidth($parts[1]);
            }

            if (!isset($height) || empty($height)) {
                $image->Constrain('width');
            }
        }

        if (isset($height) && !empty($height)) {
            $height = urldecode($height);
            preg_match('/([0-9]+)(px|%|)/i', $height, $parts);
            $type = ($parts[2] == '%') ? Defines::UNIT_TYPE_PERCENT : Defines::UNIT_TYPE_PIXELS;
            switch ($type) {
                case Defines::UNIT_TYPE_PERCENT:
                    $image->setPercent(['hpercent' => $height]);
                    break;
                default:
                case Defines::UNIT_TYPE_PIXELS:
                    $image->setHeight($parts[1]);
            }

            if (!isset($width) || empty($width)) {
                $image->Constrain('height');
            }
        }

        $fileLocation = $image->getDerivative();

        if (is_null($fileLocation)) {
            throw new BadParameterException([$fileId], 'Unable to find file: [#(1)]');
        }

        // Close the buffer, saving it's current contents for possible future use
        // then restart the buffer to store the file
        $pageBuffer = $this->mod()->apiFunc('base', 'user', 'get_output_buffer');

        ob_start();

        if (file_exists($fileLocation)) {
            $fileSize = @filesize($fileLocation);
            if (empty($fileSize)) {
                $fileSize = 0;
            }

            $fp = @fopen($fileLocation, 'rb');
            if (is_resource($fp)) {
                do {
                    $data = fread($fp, 65536);
                    if (strlen($data) == 0) {
                        break;
                    } else {
                        echo "$data";
                    }
                } while (true);

                fclose($fp);
            }

            // FIXME: make sure the file is indeed supposed to be stored in the database :-)
        } elseif (is_numeric($fileId) && $this->mod()->isAvailable('uploads')) {
            $fileSize = 0;

            /** @var UploadsApi $uploadsapi */
            $uploadsapi = $userapi->getUploadsAPI();

            // get the image data from the database
            $data = $uploadsapi->dbGetFileData(['fileId' => $fileId]);
            if (!empty($data)) {
                foreach ($data as $chunk) {
                    $fileSize += strlen($chunk);
                    echo $chunk;
                }
                unset($data);
            }
        } else {
            $this->ctl()->redirect('modules/images/xarimages/admin.gif');
            return true;
        }

        // Headers -can- be sent after the actual data
        // Why do it this way? So we can capture any errors and return if need be :)
        // not that we would have any errors to catch at this point but, mine as well
        // do it incase I think of some errors to catch

        // Make sure to check the browser / os type - IE 5.x on Mac (os9 / osX / etc) does
        // not like headers being sent for iamges - so leave them out for those particular cases
        $osName      = $this->session()->getVar('osname');
        $browserName = $this->session()->getVar('browsername');

        if (empty($osName) || $osName != 'mac' || ($osName == 'mac' && !stristr($browserName, 'internet explorer'))) {
            header("Pragma: ");
            header("Cache-Control: ");
            header("Content-type: $fileType[text]");
            header("Content-disposition: inline; filename=\"$fileName\"");

            if ($fileSize) {
                header("Content-length: $fileSize");
            }
        }
        // TODO: evaluate registering shutdown functions to take care of
        //       ending Xaraya in a safe manner
        $this->exit();
    }
}
