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
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images userapi transformhook function
 * @extends MethodClass<UserApi>
 */
class TransformhookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Primarily used by Articles as a transform hook to turn "upload tags" into various display formats
     * @param array<mixed> $args
     * @var mixed $extrainfo
     * @return mixed
     * @see UserApi::transformhook()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (is_array($extrainfo)) {
            if (isset($extrainfo['transform']) && is_array($extrainfo['transform'])) {
                foreach ($extrainfo['transform'] as $key) {
                    if (isset($extrainfo[$key])) {
                        $extrainfo[$key] = $this->transform($extrainfo[$key]);
                    }
                }
                return $extrainfo;
            }
            foreach ($extrainfo as $text) {
                $result[] = $this->transform($text);
            }
        } else {
            $result = $this->transform($extrainfo);
        }
        return $result;
    }

    public function transform($body)
    {
        $userapi = $this->getParent();

        while (preg_match('/#(image-resize):([0-9]+):([^#]*)#/i', $body, $parts)) {
            // first argument is always the complete haystack
            // get rid of it
            array_shift($parts);

            [$type, $id] = $parts;
            // get rid of the type and id so all we have left are the arguments now :)
            array_shift($parts);
            array_shift($parts);

            // The remaining indice should be the only one and should contain the arguments
            // that we will package and send to the resize function
            assert('count($parts) == 1');
            $parts = $parts[0];

            switch ($type) {
                case 'image-resize':
                    $parts = explode(':', $parts);
                    // with image-resize, all we want to pass back to the content is the url
                    // location of the resized image so it can be dropped in a <img> tag
                    // like so: <img src="#image-resize:23:200::true#" alt="some alt text" />
                    [$width, $height, $constrain] = $parts;
                    if (!empty($width)) {
                        $args['width'] = $width;
                    }

                    if (!empty($height)) {
                        $args['height'] = $height;
                    }

                    if (!empty($constrain)) {
                        $args['constrain'] = (int) ((bool) $constrain);
                    }

                    $args['label'] = 'empty';
                    $args['src']   = $id;

                    if (!$userapi->resize($args)) {
                        return;
                    } else {
                        unset($args['label']);
                        unset($args['constrain']);
                        unset($args['src']);

                        $args['fileId'] = $id;

                        $replacement = xarController::URL('images', 'user', 'display', $args);
                    }
                    break;
            }
            $parts = implode(':', $parts);
            $body = preg_replace("/#$type:$id:$parts#/", $replacement, $body);
        }

        return $body;
    }
}
