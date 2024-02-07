<?php

namespace NeoCube;

class File {

    protected $filename;
    protected $content;
    protected $path;

    public function getFilename() {
        return $this->filename;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getPath() {
        return $this->path;
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function __construct($path) {
        if (isset($path)) $this->path = trim($path);
    }

    public function save( $filename, $content){

        if (isset($filename)) $this->setFilename($filename);
        if (isset($content))  $this->setContent($content);

        if ( file_put_contents($this->path.$this->filename,serialize($this->content)) ) return TRUE;
        else return FALSE;
    }

    public function read( $filename ){
        if (isset($filename)) $this->setFilename($filename);
        if ( file_exists($this->path.$this->filename) ) $this->content = unserialize(file_get_contents($this->path.$this->filename));
        return $this->content;
    }

    public function delete( $filename=NULL ){
        if (!is_null($filename)) $this->setFilename($filename);
        if ( file_exists($this->path.$this->filename) )
            if ( unlink($this->path.$this->filename) )
                return true;
        return false;
    }


}
