<?php

namespace App\Custom;

class FlowType {
    public function __toString() { return "FlowType"; }
}

class ContinueType extends FlowType {
    public function __toString() { return "ContinueType"; }
}

class BreakType extends FlowType {
    public function __toString() { return "BreakType"; }
}

class ReturnType extends FlowType {
    public $retVal;
    public function __construct($retVal = null) {
        $this->retVal = $retVal;
    }
    public function __toString() { return "ReturnType"; }
}