<?php

class RestTest extends VaeUnitTestCase {
  
  function testVaeArrayFromRailsXml() {
    $xml = simplexml_load_file(dirname(__FILE__) . "/webroot/rails-xml-test.xml");
    $php = array (
      'code' => 'BLOGLINGS',
      'created_at' => '2009-02-27T14:02:30-05:00',
      'description' => 'Blogger Discount Code',
      'discount_shipping' => false,
      'excluded_classes' => '42583,55009',
      'fixed_amount' => '',
      'free_shipping' => false,
      'id' => '186',
      'included_classes' => '',
      'max_per_customer' => '',
      'min_order_amount' => '',
      'min_order_items' => '',
      'number_available' => '',
      'percentage_amount' => '50.0',
      'required_classes' => '',
      'start_at' => '',
      'stop_at' => '',
      'updated_at' => '2009-09-24T19:30:11-04:00',
      'website_id' => '29',
    );
    $this->assertEqual($php, _vae_array_from_rails_xml($xml));
  }
  
  function testVaeBuildXml() {
    $arr = array('name' => 'Freefall', 'tour_dates' => array(37465 => array('venue' => 'Mercury Lounge')));
    $xml = "<content><name>Freefall</name><tour_dates><item><venue>Mercury Lounge</venue></item></tour_dates></content>";
    $this->assertEqual($xml, _vae_build_xml("content", $arr));
  }
  
  function testVaeCreate() {
    global $_VAE;
    $this->mockrest("<row><id>123</id></row>");
    $this->assertEqual(123, _vae_create(12345, 0, array('name' => "Freefall2")));
    $this->assertRest();
  }
  
  function testVaeCreateRestError() {
    global $_VAE;
    $this->mockRestError();
    $this->assertFalse(_vae_create(12345, 0, array('name' => "Freefall2")));
  }
  
  function testVaeProxy() {
    $this->mockRest("<img src=\"image.jpg\" /><img src=\"http://google.com/image.jpg\" /><img src='/image.jpg' /><img src='http://google.com/image.jpg' />");
    $this->assertEqual("<img src=\"http://btg.vaesite.com/image.jpg\" /><img src=\"http://google.com/image.jpg\" /><img src='http://btg.vaesite.com//image.jpg' /><img src='http://google.com/image.jpg' />", _vae_proxy("fruit", "apple=orange", true));
  }
  
  function testVaeRest() {
    $this->assertNotEqual(false, _vae_rest(array('name' => "Freefall"), "content/update/13421", "content"));
    $this->assertRest();
  }
  
  function testVaeRestTagErrors() {
    $tag = $this->callbackTag('<v:update path="/13421"><v:text_field path="name" required="true" /></v:update>');
    $this->assertFalse(_vae_rest(array('name' => "Freefall"), "content/update/13421", "content", $tag));
    $this->assertErrors("Name can't be blank");
    $this->assertNoRest();
  }
  
  function testVaeRestRestError() {
    $this->mockRestError();
    $this->assertFalse( _vae_rest(array('name' => "Freefall"), "content/update/13421", "content"));
    $this->assertRestError();
  }
  
  function testVaeRestRailsError() {
    $this->mockRestError("This is not a real situation.");
    $this->assertFalse( _vae_rest(array('name' => "Freefall"), "content/update/13421", "content"));
    $this->assertErrors("This is not a real situation.");
  }
  
  function testVaeRestRestErrorHidden() {
    $this->mockRestError();
    $this->assertFalse( _vae_rest(array('name' => "Freefall"), "content/update/13421", "content", null, null, true));
    $this->assertNoErrors();
  }
  
  function testVaeSendRest() {
    $this->mockRest("test");
    $errors = array();
    $this->assertEqual("test", _vae_send_rest($url, array(), $errors));
    $this->assertRest();
  }
  
  function testVaeSendRestError() {
    $this->mockRestError("test");
    $errors = array();
    $this->assertFalse(_vae_send_rest($url, array(), $errors));
  }
  
  function testVaeSimpleRest() {
    $this->mockRest("test");
    $this->assertEqual("test", _vae_simple_rest($url));
    $this->assertRest();
  }
  
  function testVaeUpdate() {
    global $_VAE;
    $this->assertNotEqual(false, _vae_update(13421, array('name' => "Freefall2")));
    $this->assertRest();
    $this->assertEqual($_VAE['run_hooks'], array(array("content:updated", 13421)));
  }
  
  function testVaeUpdateRestError() {
    global $_VAE;
    $this->mockRestError();
    $this->assertFalse(_vae_update(13421, array('name' => "Freefall2")));
    $this->assertNull($_VAE['run_hooks']);
  }
  
}

?>
