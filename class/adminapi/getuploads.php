<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\AdminApi;


use Xaraya\Modules\Images\AdminApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi getuploads function
 * @extends MethodClass<AdminApi>
 */
class GetuploadsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the list of uploaded images (managed by the uploads module)
     * @return array|null containing the list of uploads
     * @todo add cache for large # of images ?
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($fileId)) {
            $filter = ['fileId' => $fileId];
        } else {
            if (empty($typeName)) {
                $typeName = 'image';
            }
            // Get all uploaded files of mimetype 'image' (cfr. uploads admin view)
            $typeinfo = xarMod::apiFunc('mime', 'user', 'get_type', ['typeName' => $typeName]);
            if (empty($typeinfo)) {
                return;
            }

            $filters = [];
            $filters['mimetype'] = $typeinfo['typeId'];
            $filters['subtype']  = null;
            $filters['status']   = null;
            $filters['inverse']  = null;

            $options  = xarMod::apiFunc('uploads', 'user', 'process_filters', $filters);
            $filter   = $options['filter'];

            if (!empty($getnext)) {
                $filter['getnext'] = $getnext;
            }
            if (!empty($getprev)) {
                $filter['getprev'] = $getprev;
            }

            // Pass sort, startnum and numitems to db_get_file where possible (i.e. for id, name and type)
            if (!empty($numitems) && is_numeric($numitems) &&
                (empty($sort) || $sort == 'name' || $sort == 'type')) {
                if (empty($startnum) || !is_numeric($startnum)) {
                    $startnum = 1;
                }
                $filter['startnum'] = $startnum;
                $filter['numitems'] = $numitems;
                $filter['sort'] = empty($sort) ? null : $sort;
            }
        }

        $imagelist = xarMod::apiFunc('uploads', 'user', 'db_get_file', $filter);

        foreach ($imagelist as $id => $image) {
            if (!empty($image['fileLocation'])) {
                $imageInfo = xarMod::apiFunc('images', 'user', 'getimagesize', $image);
                if (!empty($imageInfo)) {
                    $imagelist[$id]['width']  = $imageInfo[0];
                    $imagelist[$id]['height'] = $imageInfo[1];
                } else {
                    $imagelist[$id]['width']  = '';
                    $imagelist[$id]['height'] = '';
                }
                $imagelist[$id]['fileModified'] = @filemtime($image['fileLocation']);
            } else {
                $imagelist[$id]['width']  = '';
                $imagelist[$id]['height'] = '';
                $imagelist[$id]['fileModified'] = '';
            }
        }

        // we're done here
        if (!empty($fileId) || !empty($getnext) || !empty($getprev)) {
            return $imagelist;
        }

        if (empty($sort)) {
            $sort = '';
        }
        switch ($sort) {
            case 'name':
                // handled by db_get_file above
                //$strsort = 'fileName';
                break;
            case 'type':
                // handled by db_get_file above
                //$strsort = 'fileType';
                break;
            case 'width':
            case 'height':
                $numsort = $sort;
                break;
            case 'size':
                $numsort = 'fileSize';
                break;
            case 'time':
                $numsort = 'fileModified';
                break;
            default:
                break;
        }
        if (!empty($numsort)) {
            $sortfunc = function ($a, $b) use ($numsort) {
                if ($a[$numsort] == $b[$numsort]) {
                    return 0;
                }
                return ($a[$numsort] > $b[$numsort]) ? -1 : 1;
            };
            usort($imagelist, $sortfunc);
        } elseif (!empty($strsort)) {
            $sortfunc = function ($a, $b) use ($strsort) {
                return strcmp($a[$strsort], $b[$strsort]);
            };
            usort($imagelist, $sortfunc);
        }

        if (!empty($numitems) && is_numeric($numitems)) {
            if (empty($startnum) || !is_numeric($startnum)) {
                $startnum = 1;
            }
            if (count($imagelist) > $numitems) {
                // use array slice on the keys here (at least until PHP 5.0.2)
                $idlist = array_slice(array_keys($imagelist), $startnum - 1, $numitems);
                $newlist = [];
                foreach ($idlist as $id) {
                    $newlist[$id] = $imagelist[$id];
                }
                $imagelist = $newlist;
                unset($newlist);
            }
        }

        return $imagelist;
    }
}
