<?php

class StoreOscommerceTest extends VaeUnitTestCase {
  
  function testTepGetCountries() {
    $this->assertEqual(array('countries_iso_code_2' => "US"), tep_get_countries(true, trye));
  }
  
  function testTepImage() {
    $this->assertNull(tep_image("cow", true));
  }
  
  function testTepNotNull() {
    $this->assertTrue(tep_not_null("stuff"));
    $this->assertFalse(tep_not_null(null));
  }
  
  function testTepOrder() {
    $order = new tep_order("10001", "US", "NY", "New York", "123 Main Street", 102.54);
    $this->assertEqual($order->info['total'], 102.54);
    $this->assertEqual($order->delivery['state'], "NY");
    $this->assertEqual($order->delivery['city'], "New York");
    $this->assertEqual($order->delivery['street_address'], "123 Main Street");
    $this->assertEqual($order->delivery['postcode'], "10001");
    $this->assertEqual($order->delivery['country'], array('id' => 1, 'iso_code_2' => "US"));
  }
  
  function testTepRoundUp() {
    $this->assertEqual(tep_round_up("2.44444", 2), 2.45);
    $this->assertEqual(tep_round_up("2.44444", 3), 2.445);
  }
  
  function testVaeStoreContinentMatch() {
    $this->assertTrue(_vae_store_continent_match("NA", "US"));
    $this->assertFalse(_vae_store_continent_match("SA", "US"));
  }
  
  function testVaeStoreOsCommerceLoad() {
    _vae_store_oscommerce_load();
    $this->assertEqual(SHIPPING_ORIGIN_COUNTRY, "1");
    $this->assertEqual(MODULE_SHIPPING_USPS_USERID, "533MISHK7183");
    $this->assertEqual(MODULE_SHIPPING_USPS_PASSWORD, "533MISHK7183");
    $this->assertEqual(MODULE_SHIPPING_USPS_SERVER, "production");
    $this->assertEqual(MODULE_SHIPPING_USPS_TEXT_DAY, "day");
    $this->assertEqual(MODULE_SHIPPING_USPS_TEXT_DAYS, "days");
    $this->assertEqual(MODULE_SHIPPING_USPS_TEXT_WEEKS, "weeks");
    $this->assertEqual(SHIPPING_ORIGIN_ZIP, "10001");
    $this->assertEqual(MODULE_SHIPPING_FEDEX1_WEIGHT, "LBS");
    $this->assertEqual(MODULE_SHIPPING_FEDEX1_DROPOFF, 1);
  }
  
  function testVaeStoreSortShippingMethods() {
    $this->assertEqual(-1, _vae_store_sort_shipping_methods(array('cost' => "2", "meaningless" => 'true'), array('cost' => "4", "meaningless" => 'true')));
    $this->assertEqual(1, _vae_store_sort_shipping_methods(array('cost' => "7", "meaningless" => 'true'), array('cost' => "4", "meaningless" => 'true')));
  }
  
}

?>
