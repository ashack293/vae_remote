<?php

$_VAE['settings'] = array(
  'simplecdn_bucket' => 'btg.vaesite.com',
  'store_shipping_origin_zip' => "10001",
  'store_currency' => "USD",
  'preserve_filenames' => "1",
  'redirects' => array("old-page.html" => "newpage"),
  'subdomain' => "btg",
  'child_v_else' => true,
  'domains' => array('btgrecords.com' => array('home' => ''), 'bridgingthegapmusic.com' => array('home' => '/music')),
  'timezone' => "Eastern Time (US & Canada)",
  'tax_rates' => array(1 => array('rate' => '8.375', 'description' => 'New York State 8.375%', 'state' => 'NY'), 2 => array('rate' => '0.500', 'description' => '1000* 0.500%', 'zip' => '1000', 'include_shipping' => 1), 3 => array('rate' => '18.000', 'description' => 'Australia GST', 'country' => 'AU'), 4 => array('rate' => '5', 'description' => 'CA Clothing', 'state' => 'CA', 'tax_class' => 'clothing'), 5 => array('rate' => '7', 'description' => 'CA Services', 'state' => 'CA', 'tax_class' => 'services'), 6 => array('rate' => '4', 'description' => 'FL Luxury Tax', 'state' => 'FL', 'minimum_price' => '100')),
  'payment_methods' => array(1 => array('method_name' => 'test', 'accept_visa' => '1', 'accept_master' => '1', 'accept_discover' => '1', 'accept_american_express' => '1'), 2 => array('method_name' => 'paypal_express_checkout'), 3 => array('method_name' => 'testeuro', 'accept_switch' => 1, 'accept_solo' => 1), 4 => array('method_name' => 'paypal', 'email' => 'sales@actionverb.com'), 5 => array('method_name' => 'manual', 'accept_mo' => 1)),
  'shipping_methods' => array(5 => array('method_name' => 'preset_rates', 'percentage' => '14'))
);

?>