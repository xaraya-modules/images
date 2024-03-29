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
class Image
{
    public $name;
    public $type;
    public $width;
    public $height;
    public $resizedName;
    public $thumb_x;
    public $thumb_y;
    public $thumb_width;
    public $thumb_height;
    public $raw_width;
    public $raw_height;
    public $version;

    public function Image()
    {
        global $gallery;

        // Seed new images with the appropriate version.
        $this->version = $gallery->album_version;
    }

    public function setFile($dir, $name, $type)
    {
        $this->name = $name;
        $this->type = $type;

        if (!isMovie($this->type)) {
            [$w, $h] = getDimensions("$dir/$this->name.$this->type");
            $this->raw_width = $w;
            $this->raw_height = $h;
            $this->width = $w;
            $this->height = $h;
        }
    }

    public function integrityCheck($dir)
    {
        global $gallery;

        if (!strcmp($this->version, $gallery->album_version)) {
            return 0;
        }

        $changed = 0;

        /*
         * Fix a specific bug where the width/height are reversed
         * for sized images
         */
        if ($this->version < 3) {
            if ($this->resizedName) {
                [$w, $h] = getDimensions("$dir/$this->resizedName.$this->type");
                $this->width = $w;
                $this->height = $h;
                $changed = 1;
            }
        }

        $filename = "$dir/$this->name.$this->type";
        if (!isMovie($this->type)) {
            if (!$this->raw_width) {
                [$w, $h] = getDimensions($filename);
                $this->raw_width = $w;
                $this->raw_height = $h;
                $changed = 1;
            }
        }

        if (strcmp($this->version, $gallery->album_version)) {
            $this->version = $gallery->album_version;
            $changed = 1;
        }

        return $changed;
    }

    public function resize($dir, $target, $pathToResized="")
    {
        global $gallery;

        /* getting rid of the resized image */
        if (stristr($target, "orig")) {
            [$w, $h] = getDimensions("$dir/$this->name.$this->type");
            $this->width = $w;
            $this->height = $h;
            if (fs_file_exists("$dir/$this->resizedName.$this->type")) {
                fs_unlink("$dir/$this->resizedName.$this->type");
            }
            $this->resizedName = "";
            /* doing a resize */
        } else {
            $name = $this->name;
            $type = $this->type;

            if ($pathToResized) {
                $ret = copy($pathToResized, "$dir/$name.sized.$this->type");
            } else {
                $ret = resize_image(
                    "$dir/$name.$type",
                    "$dir/$name.sized.$this->type",
                    $target
                );
            }

            #-- resized image is not always a jpeg ---
            if ($ret) {
                $this->resizedName = "$name.sized";
                [$w, $h] = getDimensions("$dir/$name.sized.$this->type");
                $this->width = $w;
                $this->height = $h;
            }
        }
    }

    public function delete($dir)
    {
        if (fs_file_exists("$dir/$this->resizedName.$this->type")) {
            fs_unlink("$dir/$this->resizedName.$this->type");
        }
        if (fs_file_exists("$dir/$this->name.highlight.$this->type")) {
            fs_unlink("$dir/$this->name.highlight.$this->type");
        }
        fs_unlink("$dir/$this->name.$this->type");
    }

    public function getTag($dir, $full=0, $size=0, $attrs="")
    {
        global $gallery;

        $name = $this->getName($dir);

        $attrs .= " border=0";
        if ($size) {
            if ($this->width > $this->height) {
                $width = $size;
                $height = $size * ($this->height / $this->width);
            } else {
                $width = $size * ($this->width / $this->height);
                $height = $size;
            }
            $size_val = "width=\"$width\" height=\"$height\"";
        } elseif ($full || !$this->resizedName) {
            $size_val = "width=\"$this->raw_width\" height=\"$this->raw_height\"";
        } else {
            $size_val = "width=\"$this->width\" height=\"$this->height\"";
        }

        if ($this->resizedName) {
            if ($full) {
                return "<img src=\"$dir/$this->name.$this->type\" " .
                    "width=\"$this->raw_width\" height=\"$this->raw_height\" $attrs>";
            } else {
                return "<img src=\"$dir/$this->resizedName.$this->type\" " .
                    "width=\"$this->width\" height=\"$this->height\" " .
                    "$attrs>";
            }
        } else {
            return "<img src=\"$dir/$this->name.$this->type\" $size_val $attrs>";
        }
    }

    public function getName($dir, $full=0)
    {
        if ((!$full) && (fs_file_exists("$dir/$this->resizedName.$this->type"))) {
            return $this->resizedName;
        } else {
            return $this->name;
        }
    }

    public function getId($dir)
    {
        return $this->name;
    }

    public function getPath($dir, $full=0)
    {
        if ($full || !$this->resizedName) {
            $name = $this->name;
        } else {
            $name = $this->resizedName;
        }
        return "$dir/$name.$this->type";
    }

    public function isResized()
    {
        if ($this->resizedName) {
            return 1;
        } else {
            return 0;
        }
    }

    public function setDimensions($w, $h)
    {
        $this->width = $w;
        $this->height = $h;
    }

    public function setRawDimensions($w, $h)
    {
        $this->raw_width = $w;
        $this->raw_height = $h;
    }

    public function getDimensions($size=0)
    {
        if ($size) {
            if ($this->width > $this->height) {
                $width = $size;
                $height = round($size * ($this->height / $this->width));
            } else {
                $width = round($size * ($this->width / $this->height));
                $height = $size;
            }
        } else {
            $width = $this->width;
            $height = $this->height;
        }

        return [$width, $height];
    }

    public function setThumbRectangle($x, $y, $w, $h)
    {
        $this->thumb_x = $x;
        $this->thumb_y = $y;
        $this->thumb_width = $w;
        $this->thumb_height = $h;
    }

    public function getThumbRectangle()
    {
        return [$this->thumb_x, $this->thumb_y,
                     $this->thumb_width, $this->thumb_height, ];
    }

    public function getRawDimensions()
    {
        return [$this->raw_width, $this->raw_height];
    }
}

?>
