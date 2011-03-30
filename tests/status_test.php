<?php

require_once(dirname(__FILE__) . "/../lib/status.php");

class StatusTest extends VaeUnitTestCase {
  
  function testVaeStatus() {
    _vae_status();
  }
  
  function testVaeStatusCmp() {
    $this->assertEqual(1, _vae_status_cmp(array("2", "meaningless"), array("4", "meaningless")));
    $this->assertEqual(-1, _vae_status_cmp(array("4", "meaningless"), array("2", "meaningless")));
  }
  
}

?>