<?php
/**
 * get the list of derivative images (thumbnails and resized)
 *
 * @param   string  $fileName  (optional) The name of the image we're getting derivatives for
 * @param   string  $thumbsdir (optional) The directory where derivative images are stored
 * @param   string  $filematch (optional) Specific file match for derivative images
 * @returns array
 * @return array containing the list of derivatives
 * @todo add startnum and numitems support + cache for large # of images
 */
function images_adminapi_getderivatives($args)
{
    extract($args);
    if (empty($thumbsdir)) {
        $thumbsdir = xarModGetVar('images', 'path.derivative-store');
    }
    if (empty($thumbsdir)) {
        return array();
    }
    if (empty($filematch)) {
        $filematch = '';
        if (!empty($fileName)) {
            // Note: resized images are named [filename]-[width]x[height].jpg - see resize() method
            $filematch = '^' . $fileName . '-\d+x\d+';
        }
    }

    // Note: resized images are JPEG files - see resize() method
    $files = xarModAPIFunc('dynamicdata','admin','browse',
                           array('basedir'   => $thumbsdir,
                                 'filematch' => $filematch,
                                 'filetype'  => 'jpg'));
    if (!isset($files)) return;

    $imagelist = array();
    $filenames = array();
    foreach ($files as $file) {
        // Note: resized images are named [filename]-[width]x[height].jpg - see resize() method
        if (preg_match('/^(.+?)-(\d+)x(\d+)\.jpg$/',$file,$matches)) {
            $info = stat($thumbsdir . '/' . $file);
            $imagelist[] = array('fileLocation' => $thumbsdir . '/' . $file,
                                 'fileName'     => $matches[1],
                                 'fileType'     => 'image/jpeg',
                                 'fileSize'     => $info['size'],
                                 'fileId'       => md5($thumbsdir . '/' . $file),
                                 'fileModified' => $info['mtime'],
                                 'width'        => $matches[2],
                                 'height'       => $matches[3]);
            $filenames[$matches[1]] = 1;
        }
    }

// TODO: find original file info in uploads module if obfuscated

    if (empty($fileName) && xarModIsAvailable('uploads') && 
        (xarModGetVar('uploads', 'file.obfuscate-on-import') ||
         xarModGetVar('uploads', 'file.obfuscate-on-upload'))) {

        $fileinfo = array();
        foreach (array_keys($filenames) as $file) {
            // this is probably an obfuscated hash for some uploaded/imported file
        // CHECKME: verify this once derivatives can be created in sub-directories of thumbsdir
            if (preg_match('/^(.*\/)?[0-9a-f]{8}\d+$/i',$file)) {

                $fileinfo[$file] = xarModAPIFunc('uploads','user','db_get_file',
                                                 array('fileHash' => $file));

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

    return $imagelist;
}

?>