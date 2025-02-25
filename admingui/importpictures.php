<?php

/**
 * @package modules\images
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Images\AdminGui;

use Xaraya\Modules\Images\AdminGui;
use Xaraya\Modules\Images\UserApi;
use Xaraya\Modules\Uploads\UserApi as UploadsApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarMod;
use xarSession;
use xarDB;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * images admin importpictures function
 * @extends MethodClass<AdminGui>
 */
class ImportpicturesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see AdminGui::importpictures()
     */
    public function __invoke(array $args = [])
    {
        // @todo remove Xaraya 1.x way of working
        //global $dd_26;
        //$dd_26 = 'http://epicsaga.com/what_do_you_know?';

        // Can use this or the dd_26 one
        global $uploads_var_overide;

        //Config
        $image_import_dir = '/home/epicsaga/public_html/var/uploads/images';
        $Picture_Publication_Type_ID = 5;

        $this->mod('uploads')->setVar('obfuscate_imports', 0);

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        /** @var UploadsApi $uploadsapi */
        $uploadsapi = $userapi->getUploadsAPI();

        echo "Import Pictures here<br/>";

        // Kick mod available
        echo "Checking mod avaliable (dynamicdata): ";
        $avail = $this->mod()->isAvailable("dynamicdata");
        if ($avail) {
            echo "yes<br/>";
        } else {
            echo "no<br/>";
        }

        // Get files to import
        $FilesInDir = $this->getFileList($image_import_dir);

        // Prune out dupes, and ones already in the system
        $prunedFiles = $this->pruneFiles($FilesInDir, $image_import_dir);


        // Setup Article Defaults
        $title   = '';
        $summary = '';
        $body    = '';
        $notes   = '';
        $pubdate = time();
        $status  = 2;        //Default to approved
        $ptid    = $Picture_Publication_Type_ID;
        $cids = [];

        $pubtypeid = $Picture_Publication_Type_ID;
        $authorid  = $this->user()->getId();
        $aid       = 0;

        $article = ['title' => $title,
            'summary' => $summary,
            'body' => $body,
            'notes' => $notes,
            'pubdate' => $pubdate,
            'status' => $status,
            'ptid' => $ptid,
            'cids' => $cids,
            // for preview
            'pubtypeid' => $ptid,
            'authorid' => $authorid,
            'aid' => 0,
        ];

        // Loop through files and import
        foreach ($prunedFiles as $filename) {
            $lastSlash = strlen($filename) - strpos(strrev($filename), '/');
            $title = ucwords(str_replace("_", " ", substr($filename, $lastSlash, strpos($filename, '.') - 1)));

            $shortname = substr($filename, $lastSlash, strlen($filename));
            echo "File: " . $filename . "<br/>";


            // import file into Uploads
            $filepath = $image_import_dir . $filename;

            // @todo this is no longer the right way to store files with the uploads module
            if (is_file($filepath)) {
                $data = ['ulfile'   => $shortname,'filepath' => $filepath,'utype'    => 'file','mod'      => 'uploads','modid'    => 0,'filesize' => filesize($filepath),'type'     => ''];

                echo "About to store<br/>";
                $info = $uploadsapi->fileStore($data);
                echo '<pre>';
                print_r($info);
                echo '</pre>';
                echo "Stored<br/>";
            }

            // Setup file specific title
            $article['title'] = $title;

            // Setup var to overide the uploads dd property when dd hook is called to place correct link
            $uploads_var_overide = $info['link'];
            //        $dd_26                  = $info['link'];

            // Create Picture Article
            echo "Creating Article<br/>";
            $aid = $this->mod()->apiFunc('articles', 'admin', 'create', $article);


            echo "Article Created :: ID :: $aid<br/>";
        }
        $this->exit();
    }

    protected function getFileList($import_directory)
    {
        // Recurse through import directories, getting files
        $DirectoriesToScan = [$import_directory];
        $DirectoriesScanned = [];
        while (count($DirectoriesToScan) > 0) {
            foreach ($DirectoriesToScan as $DirectoryKey => $startingdir) {
                if ($dir = @opendir($startingdir)) {
                    while (($file = readdir($dir)) !== false) {
                        if (($file != '.') && ($file != '..')) {
                            $RealPathName = realpath($startingdir . '/' . $file);
                            if (is_dir($RealPathName)) {
                                if (!in_array($RealPathName, $DirectoriesScanned) && !in_array($RealPathName, $DirectoriesToScan)) {
                                    $DirectoriesToScan[] = $RealPathName;
                                }
                            } elseif (is_file($RealPathName)) {
                                $FilesInDir[] = substr($RealPathName, strlen($import_directory));
                            }
                        }
                    }
                    closedir($dir);
                }
                $DirectoriesScanned[] = $startingdir;
                unset($DirectoriesToScan[$DirectoryKey]);
            }
        }

        return $FilesInDir;
    }

    protected function pruneFiles($FilesInDir, $image_import_dir)
    {
        // Now check to see if any of those files are already in the system
        if (isset($FilesInDir)) {
            // Get database setup
            $dbconn = $this->db()->getConn();
            $xartable = $this->db()->getTables();

            // table and column definitions
            $uploadstable = $xartable['uploads'];

            // Remove dupes and sort
            $FilesInDir = array_unique($FilesInDir);
            sort($FilesInDir);

            $prunedFiles = [];
            foreach ($FilesInDir as $filename) {
                // Get items
                $sql = "SELECT  xar_ulid,
                                xar_ulfile,
                                xar_ulhash,
                                xar_ulapp
                        FROM $uploadstable
                        WHERE xar_ulfile = '$filename' OR xar_ulhash = '$filename' OR xar_ulhash = '$image_import_dir$filename';";
                $result = $dbconn->Execute($sql);
                if (!$result) {
                    return;
                }

                // Check for no rows found, and if so, add file to pruned list
                if ($result->EOF) {
                    $insystem = 'No';
                    $prunedFiles[] = $filename;
                }
                //close the result set
                $result->Close();
            }
        }
        return $prunedFiles;
    }
}
