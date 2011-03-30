<?php

require_once(dirname(__FILE__) . "/../lib/haml.php");

class HamlTest extends VaeUnitTestCase {
  
  function testVaeHaml() {
    $haml = "%html\n  %head\n    %title Test\n  %body\n    %p Test";
    $html = "<html>\n  <head>\n    <title>Test</title>\n  </head>\n  <body>\n    <p>Test</p>\n  </body>\n</html>\n";
    $haml_parsed = _vae_haml($haml);
    $this->assertEqual($haml_parsed, $html);
  }
  
  function testVaeSass() {
    $sass = "body\n  :background url(test)";
    $css = "body{background:url(test)}\n";
    $css_parsed = _vae_sass($sass, false);
    $this->assertEqual($css_parsed, $css);
  }
  
  function testVaeSassDeps() {
    $sass = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/ready.sass");
    $actual = _vae_sass_deps($sass, $_SERVER['DOCUMENT_ROOT']);
    $expected = array(
      $_SERVER['DOCUMENT_ROOT'] . '/set.sass' => '9ee55948d6d5ece2b5ac2265415d53de',
      $_SERVER['DOCUMENT_ROOT'] . '/go.sass' => 'e371105bf1ecae86a9693432266a8583'
    );
    $this->assertEqual($actual, $expected);
  }
  
  function testVaeSassOb() {
    $sass = "body\n  :background url(test)";
    $css = "body{background:url(test)}\n";
    $css_parsed = _vae_sass_ob($sass, false);
    $this->assertEqual($css_parsed, $css);
  }
  
}

?>