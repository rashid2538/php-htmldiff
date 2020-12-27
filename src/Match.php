<?php

namespace HtmlDiff;

class Match
{

    public $startInOld;
    public $startInNew;
    public $size;

    public function __construct($startInOld, $startInNew, $size)
    {
        $this->startInOld = $startInOld;
        $this->startInNew = $startInNew;
        $this->size = $size;
    }

    public function __get($name)
    {
        if ($name == 'endInOld') {
            return $this->startInOld + $this->size;
        } else if ($name == 'endInNew') {
            return $this->startInNew + $this->size;
        }
        throw new \Exception("Undefined property '$name'!");
    }
}
