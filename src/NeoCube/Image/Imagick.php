<?php

namespace NeoCube\Image;


class Imagick implements ImageInterface {
    /**
     * Working image
     * @var resource
     */
    protected $working_image;


    /**
     * Rotate fix
     *
     * @param integer $angle
     * @return NeoCube_Image
     */
    function fixOrientation() {

        if (method_exists($this->working_image, 'getImageProperty')) {
            $orientation = $this->working_image->getImageProperty('exif:Orientation');
        } else {
            $filename = $this->working_image->getImageFilename();

            if (empty($filename)) {
                $filename = 'data://image/jpeg;base64,' . base64_encode($this->working_image->getImageBlob());
            }

            $exif = exif_read_data($filename);
            $orientation = isset($exif['Orientation']) ? $exif['Orientation'] : null;
        }

        if (!empty($orientation)) {
            switch ($orientation) {
                case 3:
                    $this->working_image->rotateImage('#000000', 180);
                    break;

                case 6:
                    $this->working_image->rotateImage('#000000', 90);
                    break;

                case 8:
                    $this->working_image->rotateImage('#000000', -90);
                    break;
            }
        }
        return $this;
    }


    /**
     * Image infos
     * @var array
     */
    protected $info;

    /**
     * Original infos
     * @var array
     */
    protected $original_info;

    /**
     * Class constructor
     *
     * @param string $base_image The absolute path of the image
     */
    public function __construct($image_file) {
        if (extension_loaded('gd')) {
            if (file_exists($image_file)) {
                $info = getimagesize($image_file);

                // Create the image ressource depending of the mime type
                switch ($info['mime']) {
                    case 'image/png':
                        $base_image = imagecreatefrompng($image_file);
                        imagealphablending($base_image, false);
                        $colorTransparent = imagecolorallocatealpha($base_image, 255, 255, 255, 127);
                        imagefill($base_image, 0, 0, $colorTransparent);
                        imagesavealpha($base_image, true);
                        break;
                    case 'image/jpeg':
                        $base_image = imagecreatefromjpeg($image_file);
                        break;
                    case 'image/gif':
                        $base_image = imagecreatefromgif($image_file);
                        break;
                    default:
                        $base_image = null;
                        break;
                }

                if (is_null($base_image)) {
                    throw new \Exception('Base file is not an image');
                }

                $this->info['width'] = $info[0];
                $this->info['height'] = $info[1];
                $this->info['channels'] = isset($info['channels']) ? $info['channels'] : 1;
                if (isset($info['bits'])) $this->info['bits'] = $info['bits'];
                $this->info['mime'] = $info['mime'];

                $this->original_info = $this->info;
                $this->working_image = $base_image;
            } else {
                throw new \Exception('Base file not found.');
            }
        } else {
            throw new \Exception('The "gd" extension in not loader in your php configuration.');
        }
    }

    /**
     * Save the image
     *
     * @param string $filename The absolute path of the new image
     * @param integer $quality
     * @return NeoCube_Image
     */
    public function save($filename, $quality = 100) {
        $return = NULL;
        switch ($this->info['mime']) {
            case 'image/png':
                $quality = (intval($quality) > 90) ? 9 : round(intval($quality) / 10);
                $return = imagepng($this->working_image, $filename, $quality);
                break;
            case 'image/jpeg':
                $return = imagejpeg($this->working_image, $filename, $quality);
                break;
            case 'image/gif':
                $return = imagegif($this->working_image, $filename);
                break;
            default:
                break;
        }

        $this->clean();
        //return $this;
        return $return;
    }

    /**
     * Output the image
     *
     * @param integer $quality
     * @return NeoCube_Image
     */
    public function output($quality = 100) {
        $this->save(null, $quality);
        return $this;
    }

    /**
     * Resize an image
     *
     * @param integer $dest_w Destination width
     * @param integer $dest_h Destination height
     * @param mixed $ratio Define the ratio mode (false = no ratio, W = ratio width, H = ratio height, B = both)
     * @return NeoCube_Image
     */
    public function resize($dest_w, $dest_h, $ratio = false) {
        if (strtoupper($ratio) == 'W') {
            $ratio_w = $dest_w / $this->info['width'];
            $dest_h = $this->info['height'] * $ratio_w;
        } else if (strtoupper($ratio) == 'H') {
            $ratio_h = $dest_h / $this->info['height'];
            $dest_w = $this->info['width'] * $ratio_h;
        } else if (strtoupper($ratio) == 'B') {
            $h = $this->info['height'];
            $w = $this->info['width'];

            if ($w > $dest_w) {
                $perc = $dest_w / $w;
                $h = $h * $perc;
                $w = $dest_w;
            }
            if ($h > $dest_h) {
                $perc = $dest_h / $h;
                $w = $w * $perc;
                $h = $dest_h;
            }

            $dest_h = $h;
            $dest_w = $w;
        }

        $new_image = $this->createImage($dest_w, $dest_h);
        imagecopyresampled($new_image, $this->working_image, 0, 0, 0, 0, $dest_w, $dest_h, $this->info['width'], $this->info['height']);

        $this->working_image = $new_image;

        $this->info['width'] = imagesx($new_image);
        $this->info['height'] = imagesy($new_image);

        return $this;
    }

    /**
     * Rotate the image
     *
     * @param integer $angle
     * @return NeoCube_Image
     */
    public function rotate($angle) {
        $this->working_image = imagerotate($this->working_image, $angle, 0);
        return $this;
    }

    /**
     * Flip the image
     *
     * @param string $direction Axe direction (H = horizontal, V = vertical, B = both)
     * @return NeoCube_Image
     */
    public function flip($direction = 'V') {
        $new_image = $this->createImage($this->info['width'], $this->info['height']);

        if (strtoupper($direction) == 'V') {
            for ($x = 0; $x < $this->info['width']; $x++) {
                imagecopy($new_image, $this->working_image, $this->info['width'] - $x - 1, 0, $x, 0, 1, $this->info['height']);
            }
        } else if (strtoupper($direction) == 'H') {
            for ($y = 0; $y < $this->info['height']; $y++) {
                imagecopy($new_image, $this->working_image, 0, $this->info['height'] - $y - 1, 0, $y, $this->info['width'], 1);
            }
        } else {
            $this->flip('H')->flip('V');
            return $this;
        }

        $this->working_image = $new_image;
        return $this;
    }


    /**
     * Crop the image
     *
     * @param integer $base_width This is the width of the reference (sample: The width of the crop container)
     * @param integer $dst_w Destination width
     * @param integer $dst_h Destination height
     * @param integer $src_x position (x)
     * @param integer $src_y position (y)
     * @param integer $src_w Crop width
     * @param integer $src_h Crop height
     * @return NeoCube_Image
     */
    public function crop($dst_w, $dst_h, $src_x = 0, $src_y = 0, $src_w = 0, $src_h = 0) {

        $new_image = $this->createImage($dst_w, $dst_h);
        imagecopyresampled($new_image, $this->working_image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        $this->working_image = $new_image;
        $this->info['width'] = imagesx($new_image);
        $this->info['height'] = imagesy($new_image);

        return $this;
    }

    /**
     * Cut the image
     *
     * @param integer $dest_w Destination image width
     * @param integer $dest_h Destination image height
     * @param string $h_align Horizontal position (L = left, C = center, R = right)
     * @param string $v_align Vertical position (T = top, M = middle, B = bottom)
     * @return NeoCube_Image
     */
    public function cut($dest_w, $dest_h, $h_align = 'C', $v_align = 'T') {
        $w_ratio = $dest_w / $this->info['width'];
        $h_ratio = $dest_h / $this->info['height'];
        $h_align = strtoupper($h_align);
        $v_align = strtoupper($v_align);

        if ($this->info['width'] > $this->info['height'] || ($dest_h / $w_ratio) > $this->info['height']) {
            if ($dest_w > $dest_h && ($this->info['height'] * $w_ratio) > $dest_h) {
                $this->crop(($this->info['width'] * $w_ratio), $dest_w, $dest_h, $this->getX($h_align, $w_ratio, $dest_w), $this->getY($v_align, $w_ratio, $dest_h));
            } else {
                $this->crop(($this->info['width'] * $h_ratio), $dest_w, $dest_h, $this->getX($h_align, $h_ratio, $dest_w), $this->getY($v_align, $h_ratio, $dest_h));
            }
        } else {
            $this->crop($this->info['width'] * $w_ratio, $dest_w, $dest_h, 0, $this->getY($v_align, $w_ratio, $dest_w));
        }

        return $this;
    }

    /**
     * Create a new image
     *
     * @param integer $dest_w
     * @param integer $dest_h
     * @return resource
     */
    protected function createImage($dest_w, $dest_h) {
        $image = imagecreatetruecolor($dest_w, $dest_h);

        // Add the transparent support
        if ($this->info['mime'] == 'image/gif') {

            imagealphablending($image, true);

            $trnprt_indx = imagecolortransparent($this->working_image);

            if ($trnprt_indx >= 0) {
                $trnprt_color = imagecolorsforindex($this->working_image, $trnprt_indx);
                $trnprt_indx = imagecolorallocate($image, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                imagefill($image, 0, 0, $trnprt_indx);
                imagecolortransparent($image, $trnprt_indx);
            }
        } else if ($this->info['mime'] == 'image/png') {

            imagealphablending($image, false);
            $colorTransparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefill($image, 0, 0, $colorTransparent);
            imagesavealpha($image, true);
        }

        return $image;
    }

    /**
     * Get X position for crop
     *
     * @param string $align
     * @param float $ratio
     * @param integer $dest
     * @return float
     */
    protected function getX($align = 'C', $ratio, $dest) {
        if ($align == 'L') {
            return 0;
        } else if ($align == 'R') {
            return ($this->info['width'] * $ratio) - $dest;
        } else {
            return (($this->info['width'] * $ratio) / 2) - ($dest / 2);
        }
    }

    /**
     * Get Y position for crop
     *
     * @param string $align
     * @param float $ratio
     * @param integer $dest
     * @return float
     */
    protected function getY($align = 'T', $ratio, $dest) {
        if ($align == 'T') {
            return 0;
        } else if ($align == 'B') {
            return ($this->info['height'] * $ratio) - $dest;
        } else {
            return (($this->info['height'] * $ratio) / 2) - ($dest / 2);
        }
    }

    /**
     * Return the base image height
     *
     * @return integer
     */
    public function getImageHeight() {
        return $this->info['height'];
    }

    /**
     * Return the base image width
     *
     * @return integer
     */
    public function getImageWidth() {
        return $this->info['width'];
    }

    /**
     * Return the mime type of the image
     *
     * @return string
     */
    public function getMimeType() {
        return $this->info['mime'];
    }

    /**
     * Clean memory
     */
    protected function clean() {
        imagedestroy($this->working_image);
    }

    /**
     * Create an intance of the image class from a base image
     *
     * @param string $base_image The absolute path of the image
     * @return NeoCube_Image
     */
    public static function with($base_image) {
        return new self($base_image);
    }


    /**
     * HTML to RGB color
     *
     * @param string $color
     * @return array(R,G,B)
     */
    private function html2rgb($color) {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 6) {
            list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return false;
        }

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return array($r, $g, $b);
    }

    /**
     * Write text in Image
     *
     * @param string $text
     * @param int $x
     * @param int $y
     * @param int $size
     * @param string $color
     * @return NULL
     */
    public function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
        $rgb = $this->html2rgb($color);
        imagestring($this->working_image, $size, $x, $y, $text, imagecolorallocate($this->working_image, $rgb[0], $rgb[1], $rgb[2]));
    }

    /**
     * Write elipse in Image
     *
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @param string $color
     * @return NULL
     */
    public function elipse($x = 0, $y = 0, $w = 5, $h = 5, $color = '000000') {
        $rgb = $this->html2rgb($color);
        imageellipse($this->working_image, $x, $y, $w, $h, imagecolorallocate($this->working_image, $rgb[0], $rgb[1], $rgb[2]));
    }

    /**
     * Write elipse full in Image
     *
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @param string $color
     * @return NULL
     */
    public function elipseFull($x = 0, $y = 0, $w = 5, $h = 5, $color = '000000') {
        $rgb = $this->html2rgb($color);
        ImageFilledEllipse($this->working_image, $x, $y, $w, $h, imagecolorallocate($this->working_image, $rgb[0], $rgb[1], $rgb[2]));
    }


    /**
     * Write circle in Image
     *
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @param int $s
     * @param int $e
     * @param string $color
     * @return NULL
     */
    public function circle($x = 0, $y = 0, $w = 5, $h = 5, $s = 0, $e = 360, $color = '000000') {
        $rgb = $this->html2rgb($color);
        imagearc($this->working_image, $x, $y, $w, $h, $s, $e, imagecolorallocate($this->working_image, $rgb[0], $rgb[1], $rgb[2]));
    }


    /**
     * Image filter
     *
     * @param string $filter
     *
     * IMG_FILTER_NEGATE: Reverses all colors of the image.
     * IMG_FILTER_GRAYSCALE: Converts the image into grayscale.
     * IMG_FILTER_BRIGHTNESS: Changes the brightness of the image. Use arg1 to set the level of brightness.
     * IMG_FILTER_CONTRAST: Changes the contrast of the image. Use arg1 to set the level of contrast.
     * IMG_FILTER_COLORIZE: Like IMG_FILTER_GRAYSCALE, except you can specify the color. Use arg1, arg2 and arg3 in the form of red, green, blue and arg4 for the alpha channel. The range for each color is 0 to 255.
     * IMG_FILTER_EDGEDETECT: Uses edge detection to highlight the edges in the image.
     * IMG_FILTER_EMBOSS: Embosses the image.
     * IMG_FILTER_GAUSSIAN_BLUR: Blurs the image using the Gaussian method.
     * IMG_FILTER_SELECTIVE_BLUR: Blurs the image.
     * IMG_FILTER_MEAN_REMOVAL: Uses mean removal to achieve a "sketchy" effect.
     * IMG_FILTER_SMOOTH: Makes the image smoother. Use arg1 to set the level of smoothness.
     * IMG_FILTER_PIXELATE: Applies pixelation effect to the image, use arg1 to set the block size and arg2 to set the pixelation effect mode.
     *
     * @return NULL
     */
    public function filter($filter, $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL) {
        imagefilter($this->working_image, $filter, $arg1, $arg3, $arg3, $arg4);
    }
}
