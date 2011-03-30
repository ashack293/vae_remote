<?php

class VaeExceptionTest extends VaeUnitTestCase {
  
  function testVariableAssignments() {
    $e = new VaeException("message", "debugging", "filename");
    $this->assertEqual($e->getMessage(), "message");
    $this->assertEqual($e->filename, "filename");
    $this->assertEqual($e->debugging_info, "debugging");
    $this->assertNotNull($e->backtrace);
  }
  
}

?>