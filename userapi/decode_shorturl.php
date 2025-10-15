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
use sys;

sys::import('xaraya.modules.method');

/**
 * images userapi decode_shorturl function
 * @extends MethodClass<UserApi>
 */
class DecodeShorturlMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * extract function and arguments from short URLs for this module, and pass
     * them back to xarGetRequestInfo()
     * @author the Images module development team
     * @param array<mixed> $params array containing the different elements of the virtual path
     * @return array|void containing func the function to be called and args the query
     * string arguments, or empty if it failed
     * @see UserApi::decodeShorturl()
     */
    public function __invoke(array $params = [])
    {
        // Initialise the argument list we will return
        $args = [];

        // Analyse the different parts of the virtual path
        // $params[1] contains the first part after index.php/example

        // In general, you should be strict in encoding URLs, but as liberal
        // as possible in trying to decode them...
        if (empty($params[1])) {
            // nothing specified -> we'll go to the main function
            return ['display', $args];
        } elseif (preg_match('/^(\d+)\.(.*)/', $params[1], $matches)) {
            // something that starts with a number must be for the display function
            // Note : make sure your encoding/decoding is consistent ! :-)
            $fileId = $matches[1];
            $type   = $matches[2];

            // let the display function check whether this image exists or not
            $args['fileId'] = $fileId;
            return ['display', $args];
        }
    }
}
