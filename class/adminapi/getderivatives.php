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
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarMod;
use xarController;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images adminapi getderivatives function
 * @extends MethodClass<AdminApi>
 */
class GetderivativesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the list of derivative images (thumbnails and resized)
     * @author mikespub
     * @param array<mixed> $args
     * @var mixed $fileId (optional) The file id(s) of the image(s) we're looking for
     * @var string $fileName (optional) The name of the image we're getting derivatives for
     * @var string $fileLocation (optional) The location of the image we're getting derivatives for
     * @var string $thumbsdir (optional) The directory where derivative images are stored
     * @var string $filematch (optional) Specific file match for derivative images
     * @var int $cacheExpire (optional) Cache the result for a number of seconds
     * @var bool $cacheRefresh (optional) Force refresh of the cache
     * @return array|null containing the list of derivatives
     * @see AdminApi::getderivatives()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($thumbsdir)) {
            $thumbsdir = $this->mod()->getVar('path.derivative-store');
        }
        if (empty($thumbsdir)) {
            return [];
        }
        if (empty($filematch)) {
            $filematch = '';
            if (!empty($fileName)) {
                // Note: resized images are named [filename]-[width]x[height].jpg - see resize() method
                // Note: processed images are named [filename]-[setting].[ext] - see process_image() function
                $filematch = '^' . $fileName . '-.+';
            } elseif (!empty($fileLocation)) {
                // Note: resized images are named md5(filelocation)-[width]x[height].jpg - see resize() method
                // Note: processed images are named md5(filelocation)-[setting].[ext] - see process_image() function
                if (!is_numeric($fileLocation)) {
                    $fileLocation = md5($fileLocation);
                }
                $filematch = '^' . $fileLocation . '-.+';
            }
        }
        if (empty($filetype)) {
            // Note: resized images are JPEG files - see resize() method
            //$filetype = 'jpg';
            // Note: processed images can be JPEG, GIF or PNG files - see process_image() function
            $filetype = '(jpg|png|gif)';
        }

        $params = ['basedir'   => $thumbsdir,
            'filematch' => $filematch,
            'filetype'  => $filetype, ];

        if (!empty($fileId)) {
            if (!is_array($fileId)) {
                $fileId = [$fileId];
            }
        } else {
            $cachekey = md5(serialize($params));
            if (!empty($cacheExpire) && is_numeric($cacheExpire) && empty($cacheRefresh)) {
                $cacheinfo = $this->mod()->getVar('file.cachederiv.' . $cachekey);
                if (!empty($cacheinfo)) {
                    $cacheinfo = @unserialize($cacheinfo);
                    if (!empty($cacheinfo['time']) && $cacheinfo['time'] > time() - $cacheExpire) {
                        $imagelist = $cacheinfo['list'];
                    }
                    unset($cacheinfo);
                }
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

            $imagelist = [];
            $filenames = [];
            foreach ($files as $file) {
                // Note: resized images are named [filename]-[width]x[height].jpg - see resize() method
                if (preg_match('/^(.+?)-(\d+)x(\d+)\.jpg$/', $file, $matches)) {
                    $id = md5($thumbsdir . '/' . $file);
                    if (!empty($fileId)) {
                        if (!in_array($id, $fileId)) {
                            continue;
                        }
                    }
                    $info = stat($thumbsdir . '/' . $file);
                    $imagelist[] = ['fileLocation' => $thumbsdir . '/' . $file,
                        'fileDownload' => $this->mod()->getURL(
                            'user',
                            'display',
                            ['fileId' => base64_encode($thumbsdir . '/' . $file)]
                        ),
                        'fileName'     => $matches[1],
                        'fileType'     => 'image/jpeg',
                        'fileSize'     => $info['size'],
                        'fileId'       => $id,
                        'fileModified' => $info['mtime'],
                        'width'        => $matches[2],
                        'height'       => $matches[3], ];
                    $filenames[$matches[1]] = 1;

                    // Note: processed images are named [filename]-[setting].[ext] - see process_image() function
                } elseif (preg_match('/^(.+?)-(.+?)\.\w+$/', $file, $matches)) {
                    $id = md5($thumbsdir . '/' . $file);
                    if (!empty($fileId)) {
                        if (!in_array($id, $fileId)) {
                            continue;
                        }
                    }
                    $statinfo = stat($thumbsdir . '/' . $file);
                    $sizeinfo = getimagesize($thumbsdir . '/' . $file);
                    $imagelist[] = ['fileLocation' => $thumbsdir . '/' . $file,
                        'fileDownload' => $this->mod()->getURL(
                            'user',
                            'display',
                            ['fileId' => base64_encode($thumbsdir . '/' . $file)]
                        ),
                        'fileName'     => $matches[1],
                        'fileType'     => $sizeinfo['mime'],
                        'fileSize'     => $statinfo['size'],
                        'fileId'       => $id,
                        'fileModified' => $statinfo['mtime'],
                        'fileSetting'  => $matches[2],
                        'width'        => $sizeinfo[0],
                        'height'       => $sizeinfo[1], ];
                    $filenames[$matches[1]] = 1;
                }
            }
            /** @var AdminApi $adminapi */
            $adminapi = $this->adminapi();

            // CHECKME: keep track of originals for server images too ?

            if (empty($fileName) && $this->mod()->isAvailable('uploads')) {
                /** @var UploadsApi $uploadsapi */
                $uploadsapi = $adminapi->getUploadsAPI();

                $fileinfo = [];
                foreach (array_keys($filenames) as $file) {
                    // CHECKME: verify this once derivatives can be created in sub-directories of thumbsdir
                    // this is probably the file id for some uploaded/imported file stored in the database
                    if (preg_match('/^(.*\/)?(\d+)$/', $file, $matches)) {
                        $fileinfo[$file] = $uploadsapi->dbGetFile([
                            'fileId' => $matches[2],
                        ]);

                        // this may be the md5 hash of the file location for some uploaded/imported file
                    } elseif (preg_match('/^(.*\/)?([0-9a-f]{32})$/i', $file, $matches)) {
                        // CHECKME: watch out for duplicates here too
                        $fileinfo[$file] = $uploadsapi->dbGetFile([
                            'fileLocationMD5' => $matches[2],
                        ]);
                    }
                }
                if (count($fileinfo) > 0) {
                    foreach (array_keys($imagelist) as $id) {
                        $fileHash = $imagelist[$id]['fileName'];
                        if (!empty($fileinfo[$fileHash])) {
                            $info = $fileinfo[$fileHash];
                            // CHECKME: assume only one match here ?
                            $imagelist[$id]['original'] = array_pop($info);
                        }
                    }
                }
            }

            // we're done here
            if (!empty($fileId)) {
                return $imagelist;
            }

            if (!empty($cacheExpire) && is_numeric($cacheExpire)) {
                $cacheinfo = ['time' => time(),
                    'list' => $imagelist, ];
                $cacheinfo = serialize($cacheinfo);
                $this->mod()->setVar('file.cachederiv.' . $cachekey, $cacheinfo);
                unset($cacheinfo);
            }
        }

        // save the number of images in temporary cache for countderivatives()
        $this->var()->setCached('Modules.Images', 'countderivatives.' . $cachekey, count($imagelist));

        if (empty($sort)) {
            $sort = '';
        }
        switch ($sort) {
            case 'name':
                // handled by browse above
                //$strsort = 'fileName';
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
