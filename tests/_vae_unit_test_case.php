<?php

function _vae_test_xml_path() {
  return dirname(__FILE__) . "/data/feed1.xml";
}

$_VAE['config']['data_path'] = dirname(__FILE__) . "/data/";

class VaeUnitTestCase extends UnitTestCase {
  
  function __construct() {
    global $_VAE;
    $_VAE['config']['data_path'] = dirname(__FILE__) . "/data/";
    $_VAE['config']['asset_url'] = "/__assets/";
    $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__) . "/webroot";
    $_SERVER['HTTP_HOST'] = "btg.vaesite.com";
    $_SERVER['PHP_SELF'] = $_SERVER['REQUEST_URI'] = "/page";
    _vae_load_settings();
  }
  
  function setUp() {
    global $_VAE;
    $this->oldGet = $_GET;
    $this->oldPost = $_POST;
    $this->oldRequest = $_REQUEST;
    $this->oldServer = $_SERVER;
    $this->oldSession = $_SESSION;
    $this->oldVae = $_VAE;
    $_VAE['files_written'] = array();
    _vae_long_term_cache_empty();
  }
  
  function tearDown() {
    global $_VAE;
    if (count($_VAE['files_written'])) {
      foreach ($_VAE['files_written'] as $name) {
        unlink($_VAE['config']['data_path'] . $name);
      }
    }
    if ($this->memcache_keys) {
      foreach ($this->memcache_keys as $key) {
        $this->short_term_cache_set($key, null);
      }
      unset($this->memcache_keys);
    }
    $_GET = $this->oldGet;
    $_POST = $this->oldPost;
    $_REQUEST = $this->oldRequest;
    $_SESSION = $this->oldSession;
    $_SERVER = $this->oldServer;
    if (isset($this->oldVae)) $_VAE = $this->oldVae;
  }
  
  function assertDep($key, $value = "") {
    global $_VAE;
    if ($value) {
      $this->assertEqual($_VAE['dependencies'][$key], $value, "Expected Dependency [$key] to have value [$value]");
    } else {
      $this->assertNotNull($_VAE['dependencies'][$key], "Expected Dependency [$key]");
    }
  }
  
  function assertErrors($error_text = null) {
    if (count($_SESSION['__v:flash_new']['messages'])) {
      foreach ($_SESSION['__v:flash_new']['messages'] as $msg) {
        if (!$error_text || strstr($msg['msg'], $error_text)) {
          if ($msg['type'] == 'err') {
            $this->pass();
            return;
          }
        }
      }
    }
    $this->fail("Expected errors with text [$error_text]");
  }
  
  function assertFinal($value) {
    global $_VAE;
    $this->assertEqual($value, $_VAE['final'], "Expected final rendering to be [$final]");
  }
  
  function assertFlash($text = null) {
    if (count($_SESSION['__v:flash_new']['messages'])) {
      foreach ($_SESSION['__v:flash_new']['messages'] as $msg) {
        if (!$text || strstr($msg['msg'], $text)) {
          if ($msg['type'] != 'err') {
            $this->pass();
            return;
          }
        }
      }
    }
    $this->fail("Expected flash with text [$text]");
  }
  
  function assertMail($how_many = 1) {
    global $_VAE;
    $this->assertTrue($_VAE['mail_sent'] >= $how_many, "Expected $how_many mail(s) to be sent.");
  }
  
  function assertNoFinal() {
    global $_VAE;
    $this->assertNull($_VAE['final'], "Expected that no final rendering occur.");
  }
  
  function assertNoMail() {
    global $_VAE;
    $this->assertNull($_VAE['mail_sent'], "Expected that no mail be sent.");
  }
  
  function assertNoRedirect() {
    global $_VAE;
    $this->assertNull($_VAE['force_redirect'], "Expected no redirect.");
  }
  
  function assertNoReportedErrors() {
    $this->assertEqual($_VAE['honeybadger_sent'], 0, "Expected no errors reported to Honeybadger.");
  }
  
  function assertNoRest() {
    global $_VAE;
    $this->assertNull($_VAE['rest_sent'], "Expected no REST actions");
  }
  
  function assertNotDep($key) {
    global $_VAE;
    $this->assertNull($_VAE['dependencies'][$key], "Expected [$key] not a dependency.");
  }
  
  function assertPatternInArray($pattern, $array) {
    $this->assertPattern($pattern, serialize($array), "Expected [$pattern] in array.");
  }
  
  function assertRedirect($url) {
    global $_VAE;
    $this->assertEqual($_VAE['force_redirect'], $url, "Expected redirect to [$url], instead got [" . $_VAE['force_redirect'] . "]");
  }
  
  function assertRest($how_many = 1) {
    global $_VAE;
    $this->assertEqual($how_many, $_VAE['rest_sent'], "Expected $how_many RESTs to be sent");
  }
  
  function assertReportedError($pattern = "") {
    $this->assertTrue($_VAE['honeybadger_sent'] > 0, "Expected errors reported to Honeybadger.");
  }
  
  function assertRestError() {
    $this->assertErrors("A network error occured.", "Expected a REST Error");
  }
  
  function assertSessionDep($key) {
    global $_VAE;
    $this->assertEqual($_VAE['dependencies'][$key], "s", "Expected [$key] to be a session dependency");
  }
  
  function assertStoreSessionDep() {
    $this->assertSessionDep("__v:store");
  }
  
  function callbackTag($fragment) {
    $tag = $this->tag($fragment);
    foreach ($tag['tags'] as $id => $itag) {
      $tag['tags'][$id]['callback'] = array();    
      _vae_form_prepare($itag['attrs'], $tag['tags'][$id], null, new Context());
    }
    return $tag;
  }
  
  function clearLocal() {
    global $_VAE;
    unset($_VAE['local']);
    unset($_REQUEST['__vae_local']);
  }
  
  function dontMockRest() {
    $_SESSION['real_rest'] = true;
  }

  function expectException() {
    global $_VAE;
    $_VAE['expected_exception'] = true;
  }
  
  function export($var) {
    echo var_export($var, true) . ";\n";
  }
  
  function short_term_cache_set($key, $value) {
    global $_VAE;
    _vae_short_term_cache_set($key, $value);
    if (!isset($this->memcache_keys)) $this->memcache_keys = array();
    $this->memcache_keys[] = $key;
  }
  
  function mockRest($data) {
    global $_VAE;
    if (!isset($_VAE['mock_rest'])) $_VAE['mock_rest'] = array();
    $_VAE['mock_rest'][] = $data;
  }
  
  function mockRestError($data = false) {
    global $_VAE;
    $_VAE['mock_rest_error'] = $data;
  }
  
  function populateCart($options = null) {
    unset($_SESSION['__v:store']['cart']);
    if ($options == null) $options = array();
    vae_store_add_item_to_cart(13421, null, 1, array_merge(array("name" => "Item 1", "price" => "5.00", "weight" => 1), $options));
    vae_store_add_item_to_cart(13433, null, 3, array_merge(array("name" => "Item 2", "price" => "149.99", "weight" => 1), $options));
  }
  
  function populateCustomer($options = null) {
    $_SESSION['__v:store']['customer_id'] = 123;
    $_SESSION['__v:store']['loggedin'] = 1;
    if ($options == null) $options = array();
    $_SESSION['__v:store']['user'] = array_merge(array('billing_name' => "Kevin Bombino", 'id' => 123), $options);
  }
  
  function populateDiscount() {
    $code = "cow123";
    $customer_id = $_SESSION['__v:store']['customer_id'];
    $_SESSION['__v:store']['discount_code'] = $code;
    $_SESSION['__v:store']['discount'][$code.$customer_id] = array('fixed_amount' => 1);
  }
  
  function setLocal() {
    global $_VAE;
    $_VAE['local'] = "local";
    $_REQUEST['__vae_local'] = "local";
  }
  
  function tag($fragment) {
    $parsed = _vae_parse_vaeml($fragment);
    return $parsed[0]['tags'][0];
  }
  
  
}

?>
