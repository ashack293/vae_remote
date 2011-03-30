<?php

class ContextTest extends VaeUnitTestCase {
  
  function setUp() {
    $this->context = new Context();
    parent::setUp();
  }
  
  function testAttr() {
    $this->assertEqual("value", $this->context->attr("key", array("key" => "value")));
    $this->assertFalse($this->context->attr("key2", array("key" => "value")));
    $this->context->set_in_place("key", "setval");
    $this->assertEqual("value", $this->context->attr("key", array("key" => "value")));
  }
  
  function testRequiredAttr() {
    try {
      $this->context->required_attr("cow", array(), "test");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
    $this->context->required_attr("cow", array("cow" => "value"), "test");
    $this->context->set_in_place("cow2", "value");
    $this->context->required_attr("cow2", array("cow" => "value"), "test");
  }
  
  function testSet() {
    $context = $this->context->set(array("setkey" => "setval"));
    $this->assertEqual("setval", $context->get("setkey"));
    $this->assertEqual("setval", $context->attr("setkey", array()));
  }
  
  function testSetNoArray() {
    $context = $this->context->set("setkey", "setval");
    $this->assertEqual("setval", $context->get("setkey"));
    $this->assertEqual("setval", $context->attr("setkey", array()));
  }
  
  function testSetInPlace() {
    $this->context->set_in_place(array("setkey" => "setval"));
    $this->assertEqual("setval", $this->context->get("setkey"));
    $this->assertEqual("setval", $this->context->attr("setkey", array()));
  }
  
  function testSetInPlaceNoArray() {
    $this->context->set_in_place("setkey", "setval");
    $this->assertEqual("setval", $this->context->get("setkey"));
    $this->assertEqual("setval", $this->context->attr("setkey", array()));
  }
  
}