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

use Xaraya\Modules\Images\Defines;
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Images\AdminApi;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarMod;
use xarModVars;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi resize function
 * @extends MethodClass<UserApi>
 */
class ResizeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Resizes an image to the given dimensions and returns an img tag for the image
     * @param array<mixed> $args
     * @var mixed $src The (uploads) id or filename of the image to resize
     * @var string $basedir (optional) Base directory for the given filename
     * @var string $height The new height (in pixels or percent) ([0-9]+)(px|%)
     * @var string $width The new width (in pixels or percent)  ([0-9]+)(px|%)
     * @var bool $constrain if height XOR width, then constrain the missing value to the given one
     * @var string $label Text to be used in the ALT attribute for the <img> tag
     * @var string $setting The predefined settings to apply for processing
     * @var string $params The array of parameters to apply for processing
     * @var bool $static Use static link instead of dynamic one where possible (default FALSE)
     * @var string $baseurl (optional) Base URL for the static links
     * @var bool $returnpath (optional) Flag to return the image path instead of the image tag
     * @return string An <img> tag for the newly resized image
     * @see UserApi::resize()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($src) || empty($src)) {
            throw new BadParameterException(['src'], "Required parameter '#(1)' is missing or empty.");
        }

        if (!isset($label) || empty($label)) {
            throw new BadParameterException(['label'], "Required parameter '#(1)' is missing or empty.");
        }

        if (!isset($width) && !isset($height) && !isset($setting) && !isset($params)) {
            $msg = $this->translate(
                "Required parameters '#(1)', '#(2)', '#(3)' or '#(4)' for tag <xar:image> are missing. See tag documentation.",
                'width',
                'height',
                'setting',
                'params'
            );
            throw new BadParameterException(['width', 'height', 'setting', 'params'], "Required attributes '#(1)', '#(2)', '#(3)' or '#(4)' for tag <xar:image> are missing. See tag documentation.");
        } elseif (isset($height) && !xarVar::validate('regexp:/[0-9]+(px|%)/:', $height)) {
            throw new BadParameterException(['height'], "'#(1)' parameter is incorrectly formatted.");
        } elseif (isset($width) && !xarVar::validate('regexp:/[0-9]+(px|%)/:', $width)) {
            throw new BadParameterException(['width'], "'#(1)' parameter is incorrectly formatted.");
        }
        $userapi = $this->getParent();

        /** @var AdminApi $adminapi */
        $adminapi = $userapi->getModule()->getAdminAPI();

        if (!isset($returnpath)) {
            $returnpath = false;
        }

        $notSupported = false;

        // allow passing single DD Uploads values "as is" to xar:image-resize
        if (substr($src, 0, 1) == ';') {
            $src = substr($src, 1);
        }

        if (is_numeric($src)) {
            $imageInfo = $userapi->getimageinfo(['fileId' => $src]);
        } else {
            if (isset($basedir)) {
                $src = $basedir . '/' . $src;
            }
            $imageInfo = $userapi->getimageinfo(['fileLocation' => $src]);
        }
        if (!empty($imageInfo)) {
            // TODO: refactor to support other libraries (ImageMagick/NetPBM)
            $gd_info = $userapi->gdInfo();
            if (empty($imageInfo['imageType']) || (!$imageInfo['imageType'] & $gd_info['typesBitmask'])) {
                $notSupported = true;
            }
        } else {
            $notSupported = true;
        }
        if ($notSupported) {
            $errorMsg = $this->translate('Image type for file: #(1) is not supported for resizing', $src);
            return '<img src="" alt="' . $errorMsg . '" />';
        }

        $attribs = '';
        $allowedAttribs = ['border', 'class', 'id', 'style', 'align', 'hspace', 'vspace',
            'onclick', 'onmousedown', 'onmouseup', 'onmouseout', 'onmouseover', ];

        foreach ($args as $key => $value) {
            if (in_array(strtolower($key), $allowedAttribs)) {
                $attribs .= sprintf(' %s="%s"', $key, $value);
            }
        }

        // use predefined setting for processing
        if (!empty($setting)) {
            $settings = $userapi->getsettings();
            if (!empty($settings[$setting])) {
                $location = $adminapi->processImage([
                    'image'    => $imageInfo,
                    'saveas'   => 0, // derivative
                    'setting'  => $setting,
                    // don't process the image again if it already exists
                    'iscached' => true,
                ]);
                if (empty($location)) {
                    $msg = 'Oops with process_image';
                    return sprintf('<img src="" alt="%s" %s />', $msg, $attribs);
                }

                if (file_exists($location)) {
                    $sizeinfo = @getimagesize($location);
                    $attribs .= sprintf(' width="%s" height="%s"', $sizeinfo[0], $sizeinfo[1]);
                }

                if (!empty($static)) {
                    // if we have a base URL, use that together with the basename
                    if (!empty($baseurl)) {
                        $url = $baseurl . '/' . basename($location);

                        // or if it's an absolute URL, try to get rid of it
                    } elseif (substr($location, 0, 1) == '/' || substr($location, 1, 1) == ':') {
                        $thumbsdir = xarModVars::get('images', 'path.derivative-store');
                        $url = $thumbsdir . '/' . basename($location);
                    }
                    // if it's an absolute URL, try to get rid of it
                    if (empty($url)) {
                        $url = $location;
                    }
                } else {
                    // use the location of the processed image here
                    $url = xarController::URL(
                        'images',
                        'user',
                        'display',
                        ['fileId' => base64_encode($location)]
                    );
                }

                if ($returnpath == true) {
                    return $url;
                }

                return sprintf('<img src="%s" alt="%s" %s />', $url, $label, $attribs);
            }

            // use parameters for processing
        } elseif (!empty($params)) {
            $location = $adminapi->processImage([
                'image'    => $imageInfo,
                'saveas'   => 0, // derivative
                'params'   => $params,
                // don't process the image again if it already exists
                'iscached' => true,
            ]);
            if (empty($location)) {
                $msg = 'Oops with process_image';
                return sprintf('<img src="" alt="%s" %s />', $msg, $attribs);
            }

            if (file_exists($location)) {
                $sizeinfo = @getimagesize($location);
                $attribs .= sprintf(' width="%s" height="%s"', $sizeinfo[0], $sizeinfo[1]);
            }

            if (!empty($static)) {
                // if we have a base URL, use that together with the basename
                if (!empty($baseurl)) {
                    $url = $baseurl . '/' . basename($location);

                    // or if it's an absolute URL, try to get rid of it
                } elseif (substr($location, 0, 1) == '/' || substr($location, 1, 1) == ':') {
                    $thumbsdir = xarModVars::get('images', 'path.derivative-store');
                    $url = $thumbsdir . '/' . basename($location);
                }
                if (empty($url)) {
                    $url = $location;
                }
            } else {
                // use the location of the processed image here
                $url = xarController::URL(
                    'images',
                    'user',
                    'display',
                    ['fileId' => base64_encode($location)]
                );
            }

            if ($returnpath == true) {
                return $url;
            }

            return sprintf('<img src="%s" alt="%s" %s />', $url, $label, $attribs);
        }

        // just a flag for later
        $constrain_both = false;

        if (!isset($constrain)) {
            if (isset($width) xor isset($height)) {
                $constrain = true;
            } elseif (isset($width) && isset($height)) {
                $constrain = false;
            }
        } else {
            // we still want to constrain here, but we might need to be a little bit smarter about it
            // if we have both a height and a width, we don't want the image to be any larger than
            // any pf the supplied values, so we have to provide some logic to handle this
            if (isset($width) && isset($height)) {
                //$constrain = FALSE;
                $constrain_both = true;
            } //else {
            $constrain = (bool) $constrain;
            //}
        }

        // Load Image Properties based on $imageInfo
        $image = $userapi->loadImage($imageInfo);

        if (!is_object($image)) {
            return sprintf('<img src="" alt="%s" %s />', $this->translate('File not found.'), $attribs);
        }

        if (isset($width)) {
            preg_match('/([0-9]+)(px|%)/i', $width, $parts);
            $type = ($parts[2] == '%') ? Defines::UNIT_TYPE_PERCENT : Defines::UNIT_TYPE_PIXELS;
            switch ($type) {
                case Defines::UNIT_TYPE_PERCENT:
                    $image->setPercent(['wpercent' => $width]);
                    break;
                default:
                case Defines::UNIT_TYPE_PIXELS:
                    $image->setWidth($parts[1]);
            }

            if ($constrain) {
                $constrain_both ? $image->Constrain('both') : $image->Constrain('width');
            }
        }

        if (isset($height)) {
            preg_match('/([0-9]+)(px|%)/i', $height, $parts);
            $type = ($parts[2] == '%') ? Defines::UNIT_TYPE_PERCENT : Defines::UNIT_TYPE_PIXELS;
            switch ($type) {
                case Defines::UNIT_TYPE_PERCENT:
                    $image->setPercent(['hpercent' => $height]);
                    break;
                default:
                case Defines::UNIT_TYPE_PIXELS:
                    $image->setHeight($parts[1]);
            }

            if ($constrain) {
                $constrain_both ? $image->Constrain('both') : $image->Constrain('height');
            }
        }

        $attribs .= sprintf(' width="%s" height="%s"', $image->getWidth(), $image->getHeight());

        $location = $image->getDerivative();
        if (!$location) {
            if ($image->resize()) {
                $location = $image->saveDerivative();
                if (!$location) {
                    $msg = $this->translate('Unable to save resized image !');
                    return sprintf('<img src="%s" alt="%s" %s />', '', $msg, $attribs);
                }
            } else {
                $msg = $this->translate("Unable to resize image '#(1)'!", $image->fileLocation);
                return sprintf('<img src="%s" alt="%s" %s />', '', $msg, $attribs);
            }
        }

        if (!empty($static)) {
            // if we have a base URL, use that together with the basename
            if (!empty($baseurl)) {
                $url = $baseurl . '/' . basename($location);

                // or if it's an absolute URL, try to get rid of it
            } elseif (substr($location, 0, 1) == '/' || substr($location, 1, 1) == ':') {
                $thumbsdir = xarModVars::get('images', 'path.derivative-store');
                $url = $thumbsdir . '/' . basename($location);
            }
            if (empty($url)) {
                $url = $location;
            }
        } else {
            // put the mime type in cache for encode_shorturl()
            $mime = $image->getMime();
            if (is_array($mime) && isset($mime['text'])) {
                xarVar::setCached('Module.Images', 'imagemime.' . $src, $mime['text']);
            } else {
                xarVar::setCached('Module.Images', 'imagemime.' . $src, $mime);
            }
            $url = xarController::URL(
                'images',
                'user',
                'display',
                ['fileId' => is_numeric($src) ? $src : base64_encode($src),
                    'height' => $image->getHeight(),
                    'width'  => $image->getWidth(), ]
            );
        }

        if ($returnpath == true) {
            return $url;
        }

        $imgTag = sprintf('<img src="%s" alt="%s" %s />', $url, $label, $attribs);

        return $imgTag;
    }
}
