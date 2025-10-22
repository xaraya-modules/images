<?php

namespace Xaraya\Modules\Images;

use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use xarMod;
use sys;

sys::import('modules.images.class.image_properties');

class Image_GD extends Image_Properties
{
    public function __construct($fileLocation, $thumbsdir = null)
    {
        parent::__construct($fileLocation, $thumbsdir);
    }

    /**
     * Concept borrowed from:
     *    http://nyphp.org/content/presentations/GDintro/gd20.php
     * by:
     *    Jeff Knight of New York PHP
     **
     */
    public function resize($forceResize = false)
    {
        // If the original height and widht are the same
        // as the new height and width, return true
        if ($this->_owidth == $this->width
            && $this->_oheight == $this->height) {
            return true;
        }

        if (empty($forceResize) && $this->getDerivative()) {
            return true;
        }

        $origImage = & $this->_open();

        if (is_resource($origImage) || is_object($origImage)) {
            if (is_dir($this->_thumbsdir) && is_writable($this->_thumbsdir)) {
                $this->_tmpFile = tempnam($this->_thumbsdir, 'xarimage-');
            } else {
                $this->_tmpFile = tempnam(sys_get_temp_dir(), 'xarimage-');
            }
            $newImage = imageCreateTrueColor($this->width, $this->height);
            imageCopyResampled($newImage, $origImage, 0, 0, 0, 0, $this->width, $this->height, $this->_owidth, $this->_oheight);
            imageJPEG($newImage, $this->_tmpFile);
            imageDestroy($newImage);
            imageDestroy($origImage);
        } else {
            return false;
        }
        return true;
    }

    public function &_open()
    {
        $origImage = null;

        if (!file_exists($this->fileLocation) && !empty($this->_fileId)) {
            /** @var UploadsApi $uploadsapi */
            $uploadsapi = xarMod::userapi('uploads');
            // get the image data from the database
            $data = $uploadsapi->dbGetFileData(['fileId' => $this->_fileId]);
            if (!empty($data)) {
                $src = implode('', $data);
                unset($data);
                $origImage = imagecreatefromstring($src);
            }
            return $origImage;
        }
        switch ($this->mime['text']) {
            case 'image/gif':
                // this will fail for GIF Read Support !
                //if (imagetypes() & IMG_GIF)  {
                if (function_exists('imageCreateFromGIF')) {
                    $origImage = @imageCreateFromGIF($this->fileLocation) ;
                }
                break;
            case 'image/jpeg':
            case 'image/jpg':
                if (imagetypes() & IMG_JPG) {
                    $origImage = imageCreateFromJPEG($this->fileLocation) ;
                }
                break;
            case 'image/png':
                if (imagetypes() & IMG_PNG) {
                    $origImage = imageCreateFromPNG($this->fileLocation) ;
                }
                break;
            case 'image/wbmp':
                if (imagetypes() & IMG_WBMP) {
                    $origImage = imageCreateFromWBMP($this->fileLocation) ;
                }
                break;
        }

        return $origImage;
    }

    public function rotate() {}

    public function scale() {}

    public function crop() {}
}
