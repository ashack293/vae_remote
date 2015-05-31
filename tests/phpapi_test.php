<?php

class PhpapiTest extends VaeUnitTestCase {

  function testVaeAsset() {
    $this->assertEqual(vae_asset(false), "");
    $this->assertEqual(vae_asset(123), array("123--", 123, "api/site/v1/asset/123", "&direct=2", false));
    $this->assertEqual(vae_asset(123, 450, 500), array("123-450-500", 123, "api/site/v1/asset/123", "&direct=2&width=450&height=500", false));
    $this->assertEqual(vae_asset(123, 450, 500, 70), array("123-450-500-qual-70", 123, "api/site/v1/asset/123", "&direct=2&width=450&height=500&quality=70", false));
    $this->assertEqual(vae_asset(123, false, false, 70), array("123---qual-70", 123, "api/site/v1/asset/123", "&direct=2&quality=70", false));
  }
  
  function testVaeCacheOnEmptyKey() {
    $this->expectException();
    try {
      vae_cache("");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeCache() {
    $a = vae_cache("PhpApiTestVaeCacheTestFunction");
    $b = vae_cache("PhpApiTestVaeCacheTestFunction");
    $this->assertEqual($a, $b);
  }
  
  function testVaeCdnUrl() {
    global $_VAE;
    $old = $_VAE['config']['cdn_url'];
    $this->assertEqual(vae_cdn_url(), "http://btg.vaesite.net/");
    unset($_VAE['config']['cdn_url']);
    $this->assertEqual(vae_cdn_url(), "http://btg.vaesite.com/");
    $_VAE['config']['cdn_url'] = $old;
  }
 
  function textVaeContext() {
    $data = array(123 => array('cow' => "test", 'other' => "something else"));
    $res = vae_context($data);
    $this->assertEqual($res, _vae_array_to_xml($data)); 
    $this->assertEqual($res->formId, 123);
    $this->assertEqual($res->formId(), 123);
    $this->assertEqual($res->current()->formId, 123);
    $this->assertEqual($res->current()->formId(), 123);
    $this->assertIsA(vae_context(), "VaeQuery");
  }
  
  function testVaeCreate() {
    $this->mockrest("<row><id>123</id></row>");
    $this->assertEqual(123, vae_create(123, 0, array('name' => "New Band")));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_create(123, 0, array('name' => "New Band")));
    $this->assertNoErrors();
    $this->expectException();
    try {
      vae_create("bad", 0, array('name' => "New Band"));
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeCustomer() {
    $this->mockRest("<customer><name>Kevin</name></customer>");
    $this->assertEqual(vae_customer(123), array('name' => "Kevin"));
    $this->mockRestError();
    $this->assertFalse(vae_customer(123));
    $this->expectException();
    try {
      vae_customer("bad");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeDataPath() {
    global $_VAE;
    $this->assertEqual(vae_data_path(), $_VAE['config']['data_path']);
  }
  
  function testVaeDataUrl() {
    global $_VAE;
    $this->assertEqual(vae_data_url(), $_VAE['config']['data_url']);
  }
  
  function testVaeDisableVaeml() {
    // Can't test this because it breaks the unit test framework
  }
  
  function testVaeFile() {
    $this->assertEqual(vae_file(false), "");
    $this->assertEqual(vae_file(123), array("123-file", 123, "api/site/v1/file/123", "", false));
  }
  
  function testVaeFlash() {
    vae_flash("test");
    $this->assertFlash("test");
    $this->expectException();
    try {
      vae_flash("");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeImage() {
    $this->assertEqual(vae_image(false), "");
    $this->assertEqual(vae_image(123), array("123--", 123, "api/site/v1/image/123", "", false));
    $this->assertEqual(vae_image(123, 450, 500), array("123-450-500", 123, "api/site/v1/image/123", "&width=450&height=500", false));
    $this->assertEqual(vae_image(123, 450, 500, "Main"), array("123-450-500-Main", 123, "api/site/v1/image/123", "&width=450&height=500&size=Main", false));
    $this->assertEqual(vae_image(123, "", "", "Main"), array("123---Main", 123, "api/site/v1/image/123", "&size=Main", false));
    $this->assertEqual(vae_image(123, "", "", "Main", true), array("123---Main-g", 123, "api/site/v1/image/123", "&size=Main&grow=1", false));
    $this->assertEqual(vae_image(123, "", "", "Main", false, "80"), array("123---Main-q80", 123, "api/site/v1/image/123", "&size=Main&quality=80", false));
    $this->assertEqual(vae_image(123, "", "", "Main", true, "70"), array("123---Main-q70-g", 123, "api/site/v1/image/123", "&size=Main&quality=70&grow=1", false));
    $this->assertEqual(vae_image(123, 480, 320, "Main", true, "70"), array("123-480-320-Main-q70-g", 123, "api/site/v1/image/123", "&width=480&height=320&size=Main&quality=70&grow=1", false));
    $this->assertEqual(vae_image(123, 480, 320, "Main", true, "70", true), array("123-480-320-Main-q70-g", 123, "api/site/v1/image/123", "&width=480&height=320&size=Main&quality=70&grow=1", true));
  }
  
  function testVaeImageFilterPrepare() {
    $ret = _vae_image_filter_prepare("sample-nala.jpg", "grey2", "vae_image_grey", false);
    $this->assertEqual($ret[0], "sample-nala.jpg-grey2");
    $this->assertEqual($ret[2], 604);
    $this->assertEqual($ret[3], 453);
  }
  
  function testVaeImageFilterPrepareOnEmptyKey() {
    $this->expectException();
    try {
      _vae_image_filter_prepare("", "grey2", "vae_image_grey", false);
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeImageGrey() {
    $this->expectException();
    try {
      vae_image_grey("");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
    if ($_ENV['slow_tests']) vae_image_grey("sample-nala.jpg");
    $this->pass();
  }
  
  function testVaeImageGreyOnEmptyKey() {
    $this->expectException();
    try {
      vae_image_grey("");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeImageReflect() {
    $this->expectException();
    try {
      vae_image_reflect("");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
    if ($_ENV['slow_tests']) vae_image_reflect("sample-nala.jpg");
    $this->pass();
  }
  
  function testVaeImageReflectOnEmptyKey() {
    $this->expectException();
    try {
      vae_image_reflect("", "reflect2", "vae_image_reflect");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeImagesize() {
    $val = vae_imagesize("sample-nala.jpg");
    $this->assertEqual($val, array(604, 453));
  }
  
  function testVaeImagesizeNoOrBadFilename() {
    $this->assertEqual(_vae_imagesize(""), null);
    $this->assertEqual(_vae_imagesize("bad.jpg"), null);
    $this->assertEqual(vae_imagesize(""), null);
    $this->expectException();
    try {
      vae_imagesize("bad.jpg");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeLoggedin() {
    $this->assertFalse($_SESSION['__v:logged_in']);
    $this->assertFalse(vae_loggedin());
    $this->assertSessionDep("__v:logged_in");
    $_SESSION['__v:logged_in'] = array('id' => 456, 'path' => 'foo');
    $this->assertEqual(456, vae_loggedin());
  }
  
  function testVaeMultipartMail() {
    $this->assertTrue(vae_multipart_mail("kevin@actionverb.com", "to@actionverb.com", "subj", "text", "html"));
    $this->assertMail(1);
  }
  
  function testVaePermalink() {
    $this->assertEqual(vae_permalink(13421), "http://btg.vaesite.com/");
    $this->assertEqual(vae_permalink(13432), "http://btg.vaesite.com/artist/kevin-bombino");
  }
  
  function testVaeRedirect() {
    vae_redirect("/test");
    $this->assertRedirect("/test");
  }
  
  function testVaeRegisterHook() {
    global $_VAE;
    $this->assertNull($_VAE['hook']['diva']);
    vae_register_hook("diva", "beyonce1");
    $this->assertEqual($_VAE['hook']['diva'], array(array('callback' => "beyonce1")));
    vae_register_hook("diva", "beyonce2");
    $this->assertEqual($_VAE['hook']['diva'], array(array('callback' => "beyonce1"), array('callback' => "beyonce2")));
  }
  
  function testVaeRegisterTag() {
    global $_VAE;
    $this->assertNull($_VAE['tags']['cowtag']);
    $opts = array("handler" => "_vae_render_cowtag");
    vae_register_tag("cowtag", $opts);
    $this->assertEqual($_VAE['tags']['cowtag'], $opts);
    $this->assertNull($_VAE['callbacks']['cowtag']);
    vae_register_tag("cowtag", array("handler" => "_vae_render_cowtag", "callback" => "_vae_callback_cowtag", "filename" => "cowtag.php"));
    $this->assertEqual($_VAE['tags']['cowtag']['html'], "form");
    $this->assertNotNull($_VAE['callbacks']['cowtag']);
    $this->assertEqual($_VAE['callbacks']['cowtag']['filename'], "cowtag.php");
    $this->assertNull($_VAE['form_items']['cowtag']);
    $opts = array("handler" => "_vae_render_cowtag", "html" => "input");
    vae_register_tag("cowtag", $opts);
    $this->assertEqual($_VAE['tags']['cowtag'], $opts);
    $this->assertNotNull($_VAE['form_items']['cowtag']);
  }
  
  function testVaeRenderTags() {
    $tag = $this->callbackTag("<v:form><v:text path='13421/name' /> Kevin is awesome<v:else>Some other stuff</v:else></v:form>");
    $this->assertEqual(vae_render_tags($tag, null), "Freefall Kevin is awesome");
  }
  
  function testVaeRichtext() {
    $this->assertEqual("All Html has \nbeen\nstrippedout", vae_richtext("<html>All <p><strong>Html</strong> has </p>been<br />stripped<script type='text/javascript'>out</script></html>", array('nohtml' => true)));
    $this->assertEqual(vae_richtext("www.google.com", array()), '<a href="http://google.com" target="_blank">google.com</a>');
    $this->assertEqual(vae_richtext("http://www.google.com", array()), '<a href="http://www.google.com" target="_blank">http://www.google.com</a>');
    $this->assertEqual(vae_richtext("<a href=\"http://www.google.com\">http://www.google.com</a>", array('links_to_new_window' => 'all')), '<a target="_blank" href="http://www.google.com">http://www.google.com</a>');
    $this->assertEqual(vae_richtext("<a href=\"http://www.google.com\">http://www.google.com</a>", array('links_to_new_window' => 'external')), '<a target="_blank" href="http://www.google.com">http://www.google.com</a>');
    $this->assertEqual(vae_richtext("<a href=\"/cow.html\">Cow</a>", array('links_to_new_window' => 'all')), '<a target="_blank" href="/cow.html">Cow</a>');
    $this->assertEqual(vae_richtext("<a href=\"/cow.html\">Cow</a>", array('links_to_new_window' => 'external')), '<a href="/cow.html">Cow</a>');
    $this->assertEqual("Section dividers removed", vae_richtext("Section dividers<hr /> removed", array()));
    $this->assertEqual("Section 3Section 4", vae_richtext("Section 1<hr />Section 2<hr />Section 3<hr />Section 4", array('section' => '2+')));
    $this->assertEqual("Section 2", vae_richtext("Section 1<hr />Section 2<hr />Section 3<hr />Section 4", array('section' => '1')));
    $this->assertEqual("<img src=\"http://btg.vaesite.net/__data/Array\" />", vae_richtext("<img src='/VAE_HOSTED_IMAGE/123' />", array()));
  }
  
  function testVaeSizedImage() {
    $this->assertEqual(vae_sizedimage(false, "Size"), "");
    $this->assertEqual(vae_sizedimage(123, "Size"), array("123-sized-Size", 123, "api/site/v1/image/123", "&size=Size", false));
    $this->assertEqual(vae_sizedimage(123, "Size", true), array("123-sized-Size", 123, "api/site/v1/image/123", "&size=Size", true));
  }
  
  function testVaeStoreAddItemToCart() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null));
    $this->expectException();
    try {
      vae_store_add_item_to_cart(13423, null, 0);
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeStoreAddShippingMethod() {
    vae_store_add_shipping_method(array('title' => 'testmeth'));
    $this->assertEqual($_SESSION['__v:store']['user_shipping_methods'], array(0 => array('title' => 'testmeth')));
    vae_store_add_shipping_method(array('title' => 'testmeth'));
    $this->assertEqual($_SESSION['__v:store']['user_shipping_methods'], array(1 => array('title' => 'testmeth')));
  }
  
  function testVaeStoreCartItem() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null));
    $this->assertEqual($_SESSION['__v:store']['cart'][1], vae_store_cart_item(1));
  }
  
  function testVaeStoreCartItems() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null, 3));
    $out = vae_store_cart_items();
    $this->assertIsA($out[1], "Array");
    $this->assertEqual($out[1]['qty'], 3);
  }
  
  function testVaeStoreCreateCouponCode() {
    $this->assertTrue(vae_store_create_coupon_code(array('code' => "ABC123")));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_create_coupon_code(array('code' => "ABC123")));
  }
  
  function testVaeStoreCreateTaxRate() {
    $this->assertTrue(vae_store_create_tax_rate(array('rate' => "8")));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_create_tax_rate(array('rate' => "8")));
  }
  
  function testVaeStoreCurrentUser() {
    $_SESSION['__v:store']['user'] = 456;
    $this->assertEqual(vae_store_current_user(), 456);
    $this->assertStoreSessionDep();
  }
  
  function testVaeStoreDestroyCouponCode() {
    $this->assertTrue(vae_store_destroy_coupon_code("ABC123"));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_destroy_coupon_code("ABC123"));
    $this->assertNoErrors();
    $this->expectException();
    try {
      vae_store_destroy_coupon_code();
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeStoreDestroyAllTaxRates() {
    $this->assertTrue(vae_store_destroy_all_tax_rates());
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_destroy_all_tax_rates());
    $this->assertNoErrors();
  }
  
  function testVaeStoreDestroyTaxRate() {
    $this->assertTrue(vae_store_destroy_tax_rate("123"));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_destroy_tax_rate("123"));
    $this->assertNoErrors();
    $this->expectException();
    try {
      vae_store_destroy_tax_rate();
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeStoreDiscountCode() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null));
    $this->mockRest('<store-discount-code><code>bloglings</code><fixed-amount>5</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual($_SESSION['__v:store']['discount_code'], "bloglings");
    $this->assertEqual(vae_store_discount_code(), array('code' => "bloglings", "fixed_amount" => 5));
  }
  
  function testVaeStoreFindCouponCode() {
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount></store-discount-code>');
    $data = vae_store_find_coupon_code("BLOGLINGS");
    $this->assertEqual($data, array('code' => 'BLOGLINGS', 'fixed_amount' => 5));
    $this->mockRest('BAD');
    $this->assertFalse(vae_store_find_coupon_code("BLOGLINGS"));
  }
  
  function testVaeStoreOrders() {
    $this->mockRest('<store-orders><store-order><id>234</id><created-at>December 1, 2008 12:12:12</created-at><email>kevin@actionverb.com</email><discount>5.00</discount><tax>1.00</tax><shipping>1.50</shipping><total>43.75</total></store-order></store-orders>');
    $out = vae_store_orders();
    $this->assertEqual($out, array(234 => array('created_at' => 'December 1, 2008 12:12:12', 'e_mail_address' => 'kevin@actionverb.com', 'discount' => '5.00', 'tax' => '1.00', 'shipping' => '1.50', 'total' => '43.75', 'date' => 'December 01, 2008', 'subtotal' => '46.25', 'order_id' => 234, 'id' => 234)));
  }
  
  function testVaeStoreRecentOrder() {
    $_SESSION['__v:store']['recent_order'] = "test";
    $_SESSION['__v:store']['recent_order_data'] = "alltest";
    $this->assertEqual(vae_store_recent_order(), "test");
    $this->assertEqual(vae_store_recent_order(true), "alltest");
    $this->assertStoreSessionDep();
  }
  
  function testVaeStoreRemoveFromCart() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null, 3));
    $out = vae_store_cart_items();
    $this->assertIsA($out[1], "Array");
    $this->assertEqual($out[1]['qty'], 3);
    vae_store_remove_from_cart(1);
    $out = vae_store_cart_items();
    $this->assertEqual($out, array());
  }
  
  function testVaeStoreTotalWeight() {
    vae_store_total_weight(12);
    $this->assertEqual($_SESSION['__v:store']['total_weight'], array(12));
  }
  
  function testVaeStoreUpdateCartItem() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null));
    $this->assertTrue(vae_store_update_cart_item(1, array('qty' => 7)));
    $this->assertEqual(7, vae_store_cart_count());
  }
  
  function testVaeStoreUpdateCouponCode() {
    $this->assertTrue(vae_store_update_coupon_code("ABC123", array('fixed_amount' => 1)));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_update_coupon_code("ABC123", array('fixed_amount' => 1)));
    $this->assertNoErrors();
    $this->expectException();
    try {
      vae_store_update_coupon_code("", array('fixed_amount' => 1));
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeStoreUpdateTaxRate() {
    $this->assertTrue(vae_store_update_tax_rate("123", array('rate' => 8)));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_store_update_tax_rate("123", array('rate' => 8)));
    $this->assertNoErrors();
    $this->expectException();
    try {
      vae_store_update_coupon_code("", array('rate' => 8));
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeStoreUpdateOrderStatus() {
    $this->assertFalse(vae_store_update_order_status(1, "Bad"));
    $this->assertTrue(vae_store_update_order_status(1, "Processing"));
    $this->mockRestError();
    $this->assertFalse(vae_store_update_order_status(1, "Processing"));
  }
  
  function testVaeStyle() {
    $this->assertEqual(vae_style("  A     b "), "A     b");
    $this->assertEqual(vae_style("a\nb"), "a<br />\n b");
    $this->assertEqual(vae_style("www.google.com"), '<a href="http://google.com" target="_blank">google.com</a>');
    $this->assertEqual(vae_style("http://www.google.com"), '<a href="http://www.google.com" target="_blank">http://www.google.com</a>');
    $this->assertEqual(vae_style("http://google.com"), '<a href="http://google.com" target="_blank">http://google.com</a>');
    $this->assertEqual(vae_style("http://google.com/some/arbitrary/path"), '<a href="http://google.com/some/arbitrary/path" target="_blank">http://google.com/some/arbitrary/path</a>');
    $this->assertEqual(vae_style("http://google.com/some?qs=1"), '<a href="http://google.com/some?qs=1" target="_blank">http://google.com/some?qs=1</a>');
  }
  
  function testVaeText() {
    if ($_ENV['slow_tests']) {
      vae_text("Jesses girl");
      $this->pass();
    }
  }
  
  function testVaeTick() {
    global $_VAE;
    $_REQUEST['__time'] = true;
    $old_tick = $_VAE['tick'];
    $old_ticks = $_VAE['ticks'];
    vae_tick("test");
    $this->assertNotEqual($old_tick, $_VAE['tick']);
    $this->assertNotEqual($old_ticks, $_VAE['ticks']);
  }
  
  function testVaeUpdate() {
    $this->assertTrue(vae_update(13421, array('name' => "New Band")));
    $this->assertNoErrors();
    $this->mockRestError();
    $this->assertFalse(vae_update(13421, array('name' => "New Band")));
    $this->assertNoErrors();
    $this->expectException();
    try {
      vae_update("bad", array('name' => "New Band"));
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeUsersCurrentUser() {
    $_SESSION['__v:logged_in']['id'] = 13421;
    $ret = vae_users_current_user();
    $this->assertEqual($ret['name'], "Freefall");
  }
  
  function testVaeVideo() {
    $this->assertEqual(vae_video(false), "");
    $this->assertEqual(vae_video(123), array("123-video-", 123, "api/site/v1/file/123", "", false));
    $this->assertEqual(vae_video(123, "Main"), array("123-video-Main", 123, "api/site/v1/file/123", "&size=Main", false));
  }
  
  function testVaeWatermark() {
    if ($_ENV['slow_tests']) vae_watermark("sample-nala.jpg", "sample-nala.jpg");
    $this->pass();
  }
  
  function testVaeWatermarkBadInput() {
    $this->assertNull(vae_watermark("bad.jpg", "watermark.jpg"));
    $this->assertNull(vae_watermark("sample-nala.jpg", "watermark.jpg"));
  }

}

/* Helpers */

function PhpApiTestVaeCacheTestFunction() {
  global $PhpApiTestVaeCacheTestFunction;
  $PhpApiTestVaeCacheTestFunction++; /* if this function was evaluated twice it would return a different value */
  return "value" . $PhpApiTestVaeCacheTestFunction;
}

?>
