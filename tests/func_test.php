<?php

class FuncTest extends VaeUnitTestCase {
  
  function testClubtime() {
    $this->assertIsA(vae_clubtime(), "Integer");
  }
  
  function testCurDay() {
    $this->assertEqual(vae_curday(), strftime("%Y-%m-%d"));
  }
  
  function testCurMonth() {
    $this->assertEqual(vae_curmonth(), strftime("%Y-%m"));
  }
  
  function testCurYear() {
    $this->assertEqual(vae_curyear(), strftime("%Y"));
  }
  
  function testDateRange() {
    $this->assertEqual(vae_daterange("2009"), array(1230786000, 1262322000));
  }
  
  function testHost() {
    $_SERVER['HTTP_HOST'] = "domain1.com";
    $this->assertEqual(vae_host(), "domain1.com");
    $_REQUEST['__host'] = "domain1.net";
    $this->assertEqual(vae_host(), "domain1.net");
  }
  
  function testLowercase() {
    $this->assertEqual(vae_lowercase("WeeZer"), "weezer");
  }
  
  function testNextDay() {
    $this->assertEqual(vae_nextday("2009-12-31"), "2010-01-01");
    $this->assertEqual(vae_nextday("2010-01-31"), "2010-02-01");
    $this->assertEqual(vae_nextday("2010-02-28"), "2010-03-01");
    $this->assertEqual(vae_nextday("2010-03-04"), "2010-03-05");
    $this->assertEqual(vae_nextday("2008-02-29"), "2008-03-01");
    $this->assertEqual(vae_nextday("2000-02-29"), "2000-03-01");
    $this->assertEqual(vae_nextday("1900-02-28"), "1900-03-01");
  }
  
  function testNextMonth() {
    $this->assertEqual(vae_nextmonth("2010-10"), "2010-11");
    $this->assertEqual(vae_nextmonth("2009-12"), "2010-01");
  }
  
  function testNextYear() {
    $this->assertEqual(vae_nextyear(1999), "2000");
    $this->assertEqual(vae_nextyear(2000), "2001");
  }
  
  function testNow() {
    $this->assertIsA(vae_now(), "Integer");
  }
  
  function testPath() {
    $_SERVER['PATH_INFO'] = "/Kevin";
    $this->assertEqual(vae_path(), "Kevin");
  }
  
  function testPrevDay() {
    $this->assertEqual(vae_prevday("2010-01-01"), "2009-12-31");
    $this->assertEqual(vae_prevday("2010-02-01"), "2010-01-31");
    $this->assertEqual(vae_prevday("2010-03-01"), "2010-02-28");
    $this->assertEqual(vae_prevday("2010-03-05"), "2010-03-04");
    $this->assertEqual(vae_prevday("2008-03-01"), "2008-02-29");
    $this->assertEqual(vae_prevday("2000-03-01"), "2000-02-29");
    $this->assertEqual(vae_prevday("1900-03-01"), "1900-02-28");
  }
  
  function testPrevMonth() {
    $this->assertEqual(vae_prevmonth("2010-10"), "2010-09");
    $this->assertEqual(vae_prevmonth("2010-01"), "2009-12");
  }
  
  function testPrevYear() {
    $this->assertEqual(vae_prevyear(1999), "1998");
    $this->assertEqual(vae_prevyear(2000), "1999");
  }
  
  function testRoman() {
    $this->assertEqual(vae_roman(123), "CXXIII");
  }
  
  function testVaeStoreCartCount() {
    $this->assertEqual(1, vae_store_add_item_to_cart(13423, null, 6, array('name' => 'foo', 'price' => 1)));
    $this->assertEqual(6, vae_store_cart_count());
  }
  
  function testVaeStoreCartDiscount() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(5.00, vae_store_cart_discount());
  }
  
  function testVaeStoreCartShipping() {
    $this->populateCustomer();
    $this->populateCart();
    $this->assertEqual(63.70, vae_store_cart_shipping());
  }
  
  function testVaeStoreCartSubtotal() {
    $this->populateCustomer();
    $this->populateCart();
    $this->assertEqual(454.97, vae_store_cart_subtotal());
  }
  
  function testVaeStoreCartTax() {
    global $_VAE;
    $_VAE['store_cached_tax'] = 12.34;
    $this->assertEqual(12.34, vae_store_cart_tax());
  }
  
  function testVaeStoreCartTotal() {
    global $_VAE;
    $_VAE['store_cached_subtotal'] = 100.00;
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>4</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $_VAE['store_cached_tax'] = 0.50;
    $_VAE['store_cached_shipping'] = 15.00;
    $this->assertEqual(111.50, vae_store_cart_total());
  }
  
  function testTop() {
    global $_VAE;
    $_VAE['context'] = _vae_fetch(13423, null);
    $this->assertEqual(vae_top(), 13423);
    unset($_VAE['context']);
  }
  
  function testUppercase() {
    $this->assertEqual(vae_uppercase("WeeZer"), "WEEZER");
  }
  
}

?>
