<?php

namespace NeoCube\Image;

interface IImage {

    public function __construct(string $image_file);

    public function save(string $filename, int $quality = 100);

    public function output(int $quality = 100);

    public function resize(int $width, int $height,string $ratio='');

    public function rotate(int $angle);

    public function fixOrientation();

    public function flip(string $direction = 'V');

    public function crop(int $dst_w, int $dst_h, int $src_x=0, int $src_y=0, int $src_w=0, int $src_h=0);

    public function cut(int $dest_w, int $dest_h, string $h_align = 'C', string $v_align = 'T');

    public function text(string $text, int $x = 0, int $y = 0, int $size=5, string $color='000000');

    public function elipse(int $x=0, int $y=0, int $w=5 , int $h=5, string $color='000000');

    public function elipseFull(int $x=0, int $y=0, int $w=5 , int $h=5, string $color='000000');

    public function circle(int $x=0, int $y=0, int $w=5 ,int $h=5,int $s=0, int $e=360, string $color='000000');

}
