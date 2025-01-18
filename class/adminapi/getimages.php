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
use xarModVars;
use xarMod;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi getimages function
 * @extends MethodClass<AdminApi>
 */
class GetimagesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the list of server images
     * @author mikespub
     * @param array<mixed> $args
     * @var string $basedir The directory where images are stored
     * @var string $baseurl (optional) The corresponding base URL for the images
     * @var string $filetypes (optional) The list of file extensions to look for
     * @var bool $recursive (optional) Recurse into sub-directories or not (default TRUE)
     * @var mixed $fileId (optional) The file id(s) of the image(s) we're looking for
     * @var string $fileName (optional) The name of the image we're looking for
     * @var string $filematch (optional) Specific file match for images
     * @var int $cacheExpire (optional) Cache the result for a number of seconds
     * @var bool $cacheRefresh (optional) Force refresh of the cache
     * @return array|null containing the list of images
     * @see AdminApi::getimages()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($basedir)) {
            return [];
        }
        if (!isset($baseurl)) {
            $baseurl = $basedir;
        }

        // Note: fileId is a base 64 encode of the image location here, or an array of fileId's
        if (!empty($fileId)) {
            $files = [];
            if (!is_array($fileId)) {
                $fileId = [$fileId];
            }
            foreach ($fileId as $id) {
                $file = base64_decode($id);
                if (file_exists($basedir . '/' . $file)) {
                    $files[] = $file;
                }
            }
        } else {
            if (empty($filematch)) {
                $filematch = '';
                if (!empty($fileName)) {
                    $filematch = '^' . $fileName;
                }
            }
            if (empty($filetypes)) {
                $filetype = '(gif|jpg|png)';
            } elseif (is_array($filetypes)) {
                $filetype = '(' . join('|', $filetypes) . ')';
            } else {
                $filetype = '(' . $filetypes . ')';
            }
            if (!isset($recursive)) {
                $recursive = true;
            }

            $params = ['basedir'   => $basedir,
                'filematch' => $filematch,
                'filetype'  => $filetype,
                'recursive' => $recursive, ];

            $cachekey = md5(serialize($params));
            if (!empty($cacheExpire) && is_numeric($cacheExpire) && empty($cacheRefresh)) {
                $cacheinfo = $this->mod()->getVar('file.cachelist.' . $cachekey);
                if (!empty($cacheinfo)) {
                    $cacheinfo = @unserialize($cacheinfo);
                    if (!empty($cacheinfo['time']) && $cacheinfo['time'] > time() - $cacheExpire) {
                        $imagelist = $cacheinfo['list'];
                    }
                    unset($cacheinfo);
                }
            }

            if (!isset($imagelist)) {
                $files = xarMod::apiFunc(
                    'dynamicdata',
                    'admin',
                    'browse',
                    $params
                );
                if (!isset($files)) {
                    return;
                }
            }
        }

        if (!isset($imagelist)) {
            $imagelist = [];
            foreach ($files as $file) {
                $statinfo = @stat($basedir . '/' . $file);
                $sizeinfo = @getimagesize($basedir . '/' . $file);
                if (empty($statinfo) || empty($sizeinfo)) {
                    continue;
                }
                // Note: we're using base 64 encoded fileId's here
                $id = base64_encode($file);
                $imagelist[$id] = ['fileLocation' => $basedir . '/' . $file,
                    'fileDownload' => $baseurl . '/' . $file,
                    'fileName'     => $file,
                    'fileType'     => $sizeinfo['mime'],
                    'fileSize'     => $statinfo['size'],
                    'fileId'       => $id,
                    'fileModified' => $statinfo['mtime'],
                    'isWritable'   => @is_writable($basedir . '/' . $file),
                    'width'        => $sizeinfo[0],
                    'height'       => $sizeinfo[1], ];
            }

            // we're done here
            if (!empty($fileId)) {
                return $imagelist;
            }

            if (!empty($cacheExpire) && is_numeric($cacheExpire)) {
                $cacheinfo = ['time' => time(),
                    'list' => $imagelist, ];
                $cacheinfo = serialize($cacheinfo);
                $this->mod()->setVar('file.cachelist.' . $cachekey, $cacheinfo);
                unset($cacheinfo);
            }
        }

        // save the number of images in temporary cache for countimages()
        $this->var()->setCached('Modules.Images', 'countimages.' . $cachekey, count($imagelist));

        if (empty($sort)) {
            $sort = '';
        }
        switch ($sort) {
            case 'name':
                // handled by browse above
                $strsort = 'fileName'; // obviously not handled by browse
                break;
            case 'type':
                $strsort = 'fileType';
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

        if (!empty($getnext)) {
            $found = 0;
            $newlist = [];
            foreach (array_keys($imagelist) as $id) {
                if ($id == $getnext) {
                    $found++;
                    continue;
                } elseif ($found) {
                    $newlist[$id] = $imagelist[$id];
                    break;
                }
            }
            $imagelist = $newlist;
            unset($newlist);
        } elseif (!empty($getprev)) {
            $oldid = '';
            $newlist = [];
            foreach (array_keys($imagelist) as $id) {
                if ($id == $getprev) {
                    if (!empty($oldid)) {
                        $newlist[$oldid] = $imagelist[$oldid];
                    }
                    break;
                }
                $oldid = $id;
            }
            $imagelist = $newlist;
            unset($newlist);
        } elseif (!empty($numitems) && is_numeric($numitems)) {
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
