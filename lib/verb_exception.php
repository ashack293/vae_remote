<?php

final class VerbException extends Exception {
  public $debugging_info;
  public $backtrace;
  public $filename;
  
  public function __construct($message = "", $debugging_info = "", $filename = null) {
    $this->debugging_info = $debugging_info;
    $this->backtrace = debug_backtrace();
    $this->filename = $filename;
    parent::__construct($message);
  }

}

final class VerbFragment extends Exception {
}

?>