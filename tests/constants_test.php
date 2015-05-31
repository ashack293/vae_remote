<?php

class ConstantsTest extends VaeUnitTestCase {
  
  function testVaeListCountries() {
    $this->assertIsA(_vae_list_countries(), "Array");
  }
  
  function testAllConstantsPresent() {
    global $_VAE;
    foreach (array('states','attributes','recaptcha','currency_names','currency_symbols','tags','form_items','callbacks') as $k) {
      $this->assertIsA($_VAE[$k], "Array");
    }
    $this->assertIsA($_VAE['store']['payment_methods'], "Array");
  }
  
}
