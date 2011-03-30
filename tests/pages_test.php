<?php

class PagesTest extends VaeUnitTestCase {
 
  function testVaePage() {
    _vae_page();
    $this->pass();
  }
  
  function testVaePage404() {
    _vae_page_404();
    $this->pass();
  }
  
  function testVaePageCheckDomain() {
    $_SERVER['HTTP_HOST'] = "www.bridgingthegapmuisc.com";
    _vae_page_check_domain();
    $this->pass();
  }
  
  function testVaePageCheckRedirects() {
    _vae_page_check_redirects();
    $this->pass();
  }
  
  function testVaePageFind() {
    _vae_page_find("artist");
    $this->pass();
  }
  
  function testVaePageRedirectTo() {
    _vae_page_redirect_to("http://myotherdomain.com/page");
    $this->pass();
  }

  function testVaePageRun() {
    _vae_page_run("artist", "artist", null);
    $this->pass();
  }
}

?>