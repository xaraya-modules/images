<?php
/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2003 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
?>
<?php
class AlbumItem
{
    public $image;
    public $thumbnail;
    public $caption;
    public $hidden;
    public $highlight;
    public $highlightImage;
    public $isAlbumName;
    public $clicks;
    public $keywords;
    public $comments;      // array of comment objects
    public $uploadDate;    // date the item was uploaded
    public $itemCaptureDate;    // associative array of date the item was captured
                // not in EPOCH so we can support dates < 1970
    public $exifData;
    public $extraFields;
    public $version;

    public function AlbumItem()
    {
        global $gallery;
        $this->version = $gallery->album_version;
        $this->extraFields = [];
    }

    public function setUploadDate($uploadDate="")
    { //upload date should only be set at file upload time.
        global $gallery;

        if ($uploadDate) { // set the upload time from the time provided
            $this->uploadDate = $uploadDate;
        } else { // if nothing is passed in, get the upload time from the file creation time
            $dir = $gallery->album->getAlbumDir();
            $name = $this->image->name;
            $tag = $this->image->type;
            $file = "$dir/$name.$tag";
            $this->uploadDate = filectime($file);
        }
    }

    public function getUploadDate()
    {
        if (!$this->uploadDate) {
            return 0;
        } else {
            return $this->uploadDate;
        }
    }

    public function setItemCaptureDate($itemCaptureDate="")
    {
        global $gallery;
        /*$itemCaptureDate should be passed in as an associative array with the following elements:
         $itemCaptureDate["hours"]
        $itemCaptureDate["minutes"]
        $itemCaptureDate["seconds"]
        $itemCaptureDate["mon"]
        $itemCaptureDate["mday"]
        $itemCaptureDate["year"]
        */
        if (!$itemCaptureDate) {
            // we want to attempt to set the $itemCaptureDate from the information that
            // is available to us.  First, look in the exif data if it is a jpeg file.  If that
            // doesn't help us, then use the file creation date.
            $dir = $gallery->album->getAlbumDir();
            $name = $this->image->name;
            $tag = $this->image->type;
            $file = "$dir/$name.$tag";
            $itemCaptureDate = getItemCaptureDate($file);
        }

        $this->itemCaptureDate = $itemCaptureDate;
    }

    public function getItemCaptureDate()
    {
        // need to set this value for old photos that don't yet contain it.
        if (!$this->itemCaptureDate) {
            return 0;
        } else {
            return $this->itemCaptureDate;
        }
    }

    public function getExif($dir, $forceRefresh=0)
    {
        global $gallery;
        $file = $dir . "/" . $this->image->name . "." . $this->image->type;

        /*
         * If we don't already have the exif data, get it now.
         * Otherwise return what we have.
         */
        $needToSave = 0;
        if (!empty($this->exifData) && !$forceRefresh) {
            $status = 0;
        } else {
            [$status, $exifData] = getExif($file);
            if ($status == 0) {
                $this->exifData = $exifData;
                if (!strcmp($gallery->app->cacheExif, "yes")) {
                    $needToSave = 1;
                } else {
                    $needToSave = 0;
                }
            }
        }
        return [$status, $this->exifData, $needToSave];
    }

    public function numComments()
    {
        return sizeof($this->comments);
    }

    public function getComment($commentIndex)
    {
        return $this->comments[$commentIndex-1];
    }

    public function integrityCheck($dir)
    {
        global $gallery;
        $changed = 0;

        if (!isset($this->version)) {
            $this->version=0;
        }
        if ($this->version < 10) {
            if (!isset($this->extraFields) or !is_array($this->extraFields)) {
                $this->extraFields=[];
            }
        }
        if ($this->image) {
            if ($this->image->integrityCheck($dir)) {
                $changed = 1;
            }

            if ($this->thumbnail) {
                if ($this->thumbnail->integrityCheck($dir)) {
                    $changed = 1;
                }
            }

            if ($this->highlight && $this->highlightImage) {
                if ($this->highlightImage->integrityCheck($dir)) {
                    $changed = 1;
                }
            }
        }
        if (strcmp($this->version, $gallery->album_version)) {
            $this->version = $gallery->album_version;
            $changed = 1;
        }
        return $changed;
    }

    public function addComment($comment, $IPNumber, $name)
    {
        global $gallery;

        if ($gallery->user) {
            $UID = $gallery->user->getUID();
        } else {
            $UID = "";
        }

        $comment = new Comment($comment, $IPNumber, $name, $UID);

        $this->comments[] = $comment;

        return 0;
    }

    public function deleteComment($comment_index)
    {
        array_splice($this->comments, $comment_index-1, 1);
    }

    public function setKeyWords($kw)
    {
        $this->keywords = $kw;
    }

    public function getKeyWords()
    {
        return $this->keywords;
    }

    public function resetItemClicks()
    {
        $this->clicks = 0;
    }

    public function getItemClicks()
    {
        if (!isset($this->clicks)) {
            $this->resetItemClicks();
        }
        return $this->clicks;
    }

    public function incrementItemClicks()
    {
        if (!isset($this->clicks)) {
            $this->resetItemClicks();
        }
        $this->clicks++;
    }

    public function hide()
    {
        $this->hidden = 1;
    }

    public function unhide()
    {
        $this->hidden = 0;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function setHighlight($dir, $bool)
    {
        global $gallery;

        $this->highlight = $bool;

        /*
         * if it is now the highlight make sure it has a highlight
                 * thumb otherwise get rid of it's thumb (ouch!).
         */
        $name = $this->image->name;
        $tag = $this->image->type;

        if ($this->highlight) {
            if ($this->isAlbumName) {
                $nestedName = $this->isAlbumName;
                do {
                    $nestedAlbum = new Album();
                    $nestedAlbum->load($nestedName);
                    $dir = $nestedAlbum->getAlbumDir();
                    $nestedHighlightIndex = $nestedAlbum->getHighlight();
                    $nestedHighlight = $nestedAlbum->getPhoto($nestedHighlightIndex);
                    $nestedName = $nestedHighlight->isAlbumName;
                } while ($nestedName);

                $name = $nestedHighlight->image->name;
                $tag  = $nestedHighlight->image->type;
                $ret = 1;
            } else {
                if (($this->image->thumb_width > 0) || ($nestedHighlight->image->thumb_width > 0)) {
                    // Crop it first
                    if ($this->isAlbumName) {
                        $ret = cut_image(
                            "$dir/$name.$tag",
                            "$dir/$name.tmp.$tag",
                            $nestedHighlight->image->thumb_x,
                            $nestedHighlight->image->thumb_y,
                            $nestedHighlight->image->thumb_width,
                            $nestedHighlight->image->thumb_height
                        );
                    } else {
                        $ret = cut_image(
                            "$dir/$name.$tag",
                            "$dir/$name.tmp.$tag",
                            $this->image->thumb_x,
                            $this->image->thumb_y,
                            $this->image->thumb_width,
                            $this->image->thumb_height
                        );
                    }

                    // Then resize it down
                    if ($ret) {
                        $ret = resize_image(
                            "$dir/$name.tmp.$tag",
                            "$dir/$name.highlight.$tag",
                            $gallery->app->highlight_size
                        );
                    }
                    fs_unlink("$dir/$name.tmp.$tag");
                } else {
                    $ret = resize_image(
                        "$dir/$name.$tag",
                        "$dir/$name.highlight.$tag",
                        $gallery->app->highlight_size
                    );
                }
            }

            if ($ret) {
                [$w, $h] = getDimensions("$dir/$name.highlight.$tag");

                $high = new Image();
                $high->setFile($dir, "$name.highlight", "$tag");
                $high->setDimensions($w, $h);
                $this->highlightImage = $high;
            }
        } else {
            if (fs_file_exists("$dir/$name.highlight.$tag")) {
                fs_unlink("$dir/$name.highlight.$tag");
            }
        }
    }

    public function isHighlight()
    {
        return $this->highlight;
    }

    public function getThumbDimensions($size=0)
    {
        if ($this->thumbnail) {
            return $this->thumbnail->getDimensions($size);
        } else {
            return [0, 0];
        }
    }

    public function getDimensions()
    {
        if ($this->image) {
            return $this->image->getDimensions();
        } else {
            return [0, 0];
        }
    }

    public function isResized()
    {
        $im = $this->image;
        return $im->isResized();
    }

    public function rotate($dir, $direction, $thumb_size)
    {
        global $gallery;

        $name = $this->image->name;
        $type = $this->image->type;
        $retval = rotate_image("$dir/$name.$type", "$dir/$name.$type", $direction);
        if (!$retval) {
            return $retval;
        }
        [$w, $h] = getDimensions("$dir/$name.$type");
        $this->image->setRawDimensions($w, $h);

        if ($this->isResized()) {
            rotate_image("$dir/$name.sized.$type", "$dir/$name.sized.$type", $direction);
            [$w, $h] = getDimensions("$dir/$name.sized.$type");
            $this->image->setDimensions($w, $h);
        }

        /* Reset the thumbnail to the default before regenerating thumb */
        $this->image->setThumbRectangle(0, 0, 0, 0);
        $this->makeThumbnail($dir, $thumb_size);
    }

    public function setPhoto($dir, $name, $tag, $thumb_size, $pathToThumb="")
    {
        global $gallery;

        /*
          * Sanity: make sure we can handle the file first.
         */
        if (!isMovie($tag) &&
            !valid_image("$dir/$name.$tag")) {
            return "Invalid image: $name.$tag";
        }

        /* Set our image. */
        $this->image = new Image();
        $this->image->setFile($dir, $name, $tag);

        $ret = $this->makeThumbnail($dir, $thumb_size, $pathToThumb);
        return $ret;
    }

    public function makeThumbnail($dir, $thumb_size, $pathToThumb="")
    {
        global $gallery;
        $name = $this->image->name;
        $tag = $this->image->type;

        if (isMovie($tag)) {
            /* Use a preset thumbnail */
            fs_copy($gallery->app->movieThumbnail, "$dir/$name.thumb.jpg");
            $this->thumbnail = new Image();
            $this->thumbnail->setFile($dir, "$name.thumb", "jpg");

            [$w, $h] = getDimensions("$dir/$name.thumb.jpg");
            $this->thumbnail->setDimensions($w, $h);
        } else {
            /* Make thumbnail (first crop it spec) */
            if ($pathToThumb) {
                $ret = copy($pathToThumb, "$dir/$name.thumb.$tag");
            } elseif ($this->image->thumb_width > 0) {
                $ret = cut_image(
                    "$dir/$name.$tag",
                    "$dir/$name.thumb.$tag",
                    $this->image->thumb_x,
                    $this->image->thumb_y,
                    $this->image->thumb_width,
                    $this->image->thumb_height
                );
                if ($ret) {
                    $ret = resize_image(
                        "$dir/$name.thumb.$tag",
                        "$dir/$name.thumb.$tag",
                        $thumb_size
                    );
                }
            } else {
                $ret = resize_image(
                    "$dir/$name.$tag",
                    "$dir/$name.thumb.$tag",
                    $thumb_size
                );
            }

            if ($ret) {
                $this->thumbnail = new Image();
                $this->thumbnail->setFile($dir, "$name.thumb", $tag);

                [$w, $h] = getDimensions("$dir/$name.thumb.$tag");
                $this->thumbnail->setDimensions($w, $h);

                /* if this is the highlight, remake it */
                if ($this->highlight) {
                    $this->setHighlight($dir, 1);
                }
            } else {
                return "Unable to make thumbnail ($ret)";
            }
        }

        return 0;
    }


    public function getThumbnailTag($dir, $size=0, $attrs="")
    {
        if ($this->thumbnail) {
            return $this->thumbnail->getTag($dir, 0, $size, $attrs);
        } else {
            return "<i>No thumbnail</i>";
        }
    }

    public function getHighlightTag($dir, $size=0, $attrs)
    {
        if (is_object($this->highlightImage)) {
            return $this->highlightImage->getTag($dir, 0, $size, $attrs);
        } else {
            return "<i>No highlight</i>";
        }
    }

    public function getPhotoTag($dir, $full=0)
    {
        if ($this->image) {
            return $this->image->getTag($dir, $full);
        } else {
            return "about:blank";
        }
    }

    public function getPhotoPath($dir, $full=0)
    {
        if ($this->image) {
            return $this->image->getPath($dir, $full);
        } else {
            return "about:blank";
        }
    }

    public function getPhotoId($dir)
    {
        if ($this->image) {
            return $this->image->getId($dir);
        } else {
            return "unknown";
        }
    }

    public function delete($dir)
    {
        if ($this->image) {
            $this->image->delete($dir);
        }

        if ($this->thumbnail) {
            $this->thumbnail->delete($dir);
        }
    }

    public function setCaption($cap)
    {
        $this->caption = $cap;
    }

    public function getCaption()
    {
        return $this->caption;
    }

    public function setIsAlbumName($name)
    {
        $this->isAlbumName = $name;
    }

    public function getIsAlbumName()
    {
        return $this->isAlbumName;
    }

    public function isMovie()
    {
        return isMovie($this->image->type);
    }

    public function resize($dir, $target, $pathToResized="")
    {
        if ($this->image) {
            $this->image->resize($dir, $target, $pathToResized);
        }
    }
    public function setExtraField($name, $value)
    {
        $this->extraFields[$name]=$value;
    }
    public function getExtraField($name)
    {
        if (isset($this->extraFields[$name])) {
            return $this->extraFields[$name];
        }
        return null;
    }
}

?>
