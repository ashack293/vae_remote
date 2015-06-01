<?php

class StoreTest extends VaeUnitTestCase {
  
  function setUp() {
    $this->tag = array();
    $this->callback = array();
    $this->render_context = new Context();
    parent::setUp();
  }
  
  function testVaeStoreAddItemToCart() {
    $this->assertEqual(1, _vae_store_add_item_to_cart(13421, null, 1, array('name' => "test", 'price' => 12.34)));
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['name'], "test");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 1);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 12.34);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 12.34);
    $this->assertEqual(1, _vae_store_add_item_to_cart(13421, null, 3, array('name' => "test", 'price' => 12.34)));
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['name'], "test");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 3);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 12.34);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 37.02);
    $this->assertEqual(2, _vae_store_add_item_to_cart(13421, null, 3, array('name' => "test", 'price' => 12.34), "notEE"));
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['check_inventory'], true);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['name'], "test");
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['qty'], 3);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['price'], 12.34);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['total'], 37.02);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['notes'], "notEE");
    unset($_SESSION);
    $this->assertEqual(1, _vae_store_add_item_to_cart(13433, 13434, 3, array('name_field' => "name", 'price_field' => "price", 'notes_field' => 'description', 'barcode' => "123ABC", "weight" => 4, 'inventory_field' => 'inventory', 'disable_inventory_check' => true, 'option_field' => 'size', 'tax_class' => 'services', 'discount_class' => 'cheap!', 'shipping_class' => "big"), "ignored"));
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['name'], "Freefall 11x17\" Full Color Poster");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 3);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 2.99);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 8.97);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['original_price'], 2.99);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['barcode'], "123ABC");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['discount_class'], "cheap!");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['shipping_class'], "big");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['tax_class'], "services");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['weight'], 4);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['digital'], false);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['id'], 13433);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_id'], 13434);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "One Size");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['inventory_field'], 'inventory');
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['check_inventory'], false);
    $this->assertPattern('/Get this limited edition/', $_SESSION['__v:store']['cart'][1]['notes']);
    unset($_SESSION);
    $this->assertEqual(1, _vae_store_add_item_to_cart(13433, null, 3, array('name_field' => "name", 'price_field' => "price", 'weight_field' => 'weight', 'discount_field' => 'discount', 'barcode_field' => 'description', 'option_value' => "RED")));
    $this->assertPattern('/Get this limited edition/', $_SESSION['__v:store']['cart'][1]['barcode']);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['weight'], 1);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "RED");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 1.50);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 4.50);
    unset($_SESSION);
    $this->assertEqual(1, _vae_store_add_item_to_cart(13435, 13436, 3, array('name_field' => "name", 'price_field' => "price", 'notes_field' => 'size', 'barcode_field' => "inventory", "weight_field" => "weight", 'inventory_field' => 'inventory', 'option_field' => 'size,size', 'discount_field' => 'discount'), "ignored"));
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['weight'], 2);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "One Size/One Size");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['notes'], "One Size");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['barcode'], "0");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 75.00);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 225.00);

  }
    
  function testVaeStoreAddItemToCartProperAttributes() {
    $this->expectException();
    try {
      _vae_store_add_item_to_cart(13421, null, 3, array());
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/name_field.*is not specified/', $e->getMessage());
    }
    try {
      _vae_store_add_item_to_cart(13421, null, 3, array('name_field' => "cow"));
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/name field is blank/', $e->getMessage());
    }
    try {
      _vae_store_add_item_to_cart(13421, null, 3, array('name_field' => "name"));
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/price_field.*is not specified/', $e->getMessage());
    }
    try {
      _vae_store_add_item_to_cart(13421, null, 3, array('name_field' => "name", "price_field" => "name"));
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/price field is invalid/', $e->getMessage());
    }
    try {
      _vae_store_add_item_to_cart(13421, null, 3, array('name_field' => "name", "price_field" => "badness"));
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/price field is blank/', $e->getMessage());
    }
    try {
      _vae_store_add_item_to_cart(13433, null, 3, array('name_field' => "name", "price_field" => "price", "tax_class" => "cow"));
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/that tax class is not defined/', $e->getMessage());
    }
    try {
      _vae_store_add_item_to_cart(13433, 13434, 3, array('name_field' => "name", "price_field" => "price"));
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/option_field.*is not specified/', $e->getMessage());
    }
  }
  
  function testVaeStoreBannedCountry() {
    global $_VAE;
    $this->assertFalse(_vae_store_banned_country("jp"));
    $_VAE['settings']['store_banned_countries'] = "jp";
    $this->assertTrue(_vae_store_banned_country("jp"));
  }
  
  function testVaeStoreCallbackAddToCart() {
    global $_VAE;
    $this->assertEqual(1, _vae_store_add_item_to_cart(null, null, 1, array('name' => "meaningless", 'price' => 1.00)));
    vae_register_hook("store:cart:updated", "helperHook");
    $this->assertNull($_VAE['__test_hooked']);
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name="item" price="12.00" />');
    _vae_store_callback_add_to_cart($tag);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['name'], "item");
    $this->assertEqual($_VAE['__test_hooked'], 1);
    $this->assertSessionDep('__v:store');
    $this->assertRedirect("/cart");
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name="item" price_input="donation" clear_cart="true" notes_input="notes" />');
    $_REQUEST['notes'] = "MyNOTE";
    $_REQUEST['donation'] = 20;
    _vae_store_callback_add_to_cart($tag);
    $this->assertEqual($_SESSION['__v:store']['cart'][3]['price'], 20);
    $this->assertEqual($_SESSION['__v:store']['cart'][3]['name'], "item");
    $this->assertEqual($_SESSION['__v:store']['cart'][3]['notes'], "MyNOTE");
    $this->assertNull($_SESSION['__v:store']['cart'][2]);
    $this->assertNull($_SESSION['__v:store']['cart'][1]);
  }
  
  function testVaeStoreCallbackAddToCartBadOptionValue() {
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name_field="name" price_field="price" options_collection="inventory" option_field="size" />');
    $tag['callback']['item'] = 13435;
    _vae_store_callback_add_to_cart($tag);
    $this->assertErrors("select an option value");
  }
  
  function testVaeStoreCallbackAddToCartBadPriceInput() {
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name="item" price_input="donation" clear_cart="true" notes_input="notes" />');
    _vae_store_callback_add_to_cart($tag);
    $this->assertErrors("enter an amount");
  }
  
  function testVaeStoreCallbackAddToCartBadQty() {
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name_field="name" price_field="price" options_collection="inventory" option_field="size" inventory_field="inventory" />');
    $tag['callback']['item'] = 13433;
    $_REQUEST['options'] = 13434;
    $_REQUEST['quantity'] = 1000;
    _vae_store_callback_add_to_cart($tag);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 747);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "One Size");
  }
  
  function testVaeStoreCallbackAddToCartMultiple() {
    $_REQUEST['buy'] = array(11 => 13433, 12 => 13435);
    $_REQUEST['options'] = array(11 => 13434, 12 => 13436);
    $_REQUEST['noters'] = array(11 => "1st", 12 => "2nd");
    $_REQUEST['quantity'] = array(11 => 4, 12 => 5);
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" multiple="buy" notes_input="noters" name_field="name" price_field="price" options_collection="inventory" option_field="size" />');
    _vae_store_callback_add_to_cart($tag);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 4);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['notes'], "1st");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['id'], 13433);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_id'], 13434);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "One Size");
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['qty'], 5);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['notes'], "2nd");
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['id'], 13435);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['option_id'], 13436);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['option_value'], "One Size");
  }
  
  function testVaeStoreCallbackAddToCartMultipleError() {
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" multiple="buy" name_field="name" price_field="price" options_collection="inventory" option_field="size" />');
    _vae_store_callback_add_to_cart($tag);
    $this->assertErrors("No items found");
  }
  
  function testVaeStoreCallbackAddToCartQuantityArray() {
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name_field="name" price_field="price" options_collection="inventory" option_field="size" />');
    $tag['callback']['item'] = 13435;
    $_REQUEST['quantity'][13436] = 3;
    _vae_store_callback_add_to_cart($tag);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 3);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "One Size");
  }
  
  function testVaeStoreCallbackAddToCartWithOption() {
    $tag = $this->callbackTag('<v:store:add_to_cart redirect="/cart" name_field="name" price_field="price" options_collection="inventory" option_field="size" />');
    $tag['callback']['item'] = 13435;
    $_REQUEST['options'] = 13436;
    $_REQUEST['quantity'] = 3;
    _vae_store_callback_add_to_cart($tag);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 3);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['option_value'], "One Size");
  }
  
  function testVaeStoreCallbackAddressDelete() {
    $tag = $this->callbackTag('<v:store:address_delete />');
    $tag['callback']['id'] = 2;
    $_SESSION['__v:store']['customer_addresses'] = array(2 => array('name' => "a1", "address_type" => "billing"), 1 => array('name' => "a2", "address_type" => "billing"));
    $this->assertNotNull($_SESSION['__v:store']['customer_addresses'][2]);
    _vae_store_callback_address_delete($tag);
    $this->assertRest();
    $this->assertNull($_SESSION['__v:store']['customer_addresses'][2]);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackAddressDeleteRedirect() {
    $tag = $this->callbackTag('<v:store:address_delete redirect="/cow" />');
    _vae_store_callback_address_delete($tag);
    $this->assertRedirect("/cow");
  }
  
  function testVaeStoreCallbackAddressSelect() {
    $tag = $this->callbackTag('<v:store:address_select type="billing" />');
    $_REQUEST['address'] = "1";
    $_SESSION['__v:store']['customer_addresses'] = array(2 => array('name' => "a1", "address_type" => "billing"), 1 => array('name' => "a2", "address_type" => "billing"));
    _vae_store_callback_address_select($tag);
    $this->assertSessionDep('__v:store');
    $this->assertRedirect($_SERVER['PHP_SELF']);
    $this->assertEqual($_SESSION['__v:store']['user']['billing_name'], "a2");
  }
  
  function testVaeStoreCallbackCart() {
    global $_VAE;
    $this->populateCart();
    $_REQUEST['remove'][1] = "true";
    $_REQUEST['qty'][2] = "7";
    vae_register_hook("store:cart:updated", "helperHook");
    $this->assertNull($_VAE['__test_hooked']);
    $tag = $this->callbackTag('<v:store:cart />');
    _vae_store_callback_cart($tag);
    $this->assertSessionDep('__v:store');
    $this->assertEqual(count($_SESSION['__v:store']['cart']), 1);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['total'], 1049.93);
    $this->assertEqual($_SESSION['__v:store']['cart'][2]['qty'], 7);
    $this->assertEqual($_VAE['__test_hooked'], 1);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackCartPriceNoLogin() {
    global $_VAE;
    $this->populateCart();
    $_REQUEST['price'][1] = "2.00";
    $tag = $this->callbackTag('<v:store:cart />');
    _vae_store_callback_cart($tag);
    $this->assertEqual(count($_SESSION['__v:store']['cart']), 2);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 5.00);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 5.00);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 1);
  }
  
  function testVaeStoreCallbackCartPrice() {
    global $_VAE;
    $this->populateCart();
    $_SESSION['__v:user_id'] = true;
    $_REQUEST['price'][1] = "2.00";
    $tag = $this->callbackTag('<v:store:cart />');
    _vae_store_callback_cart($tag);
    $this->assertEqual(count($_SESSION['__v:store']['cart']), 2);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['price'], 2.00);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['total'], 2.00);
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 1);
  }
  
  function testVaeStoreCallbackCartRegister() {
    global $_VAE;
    $_REQUEST['checkout'] = "true";
    $tag = $this->callbackTag('<v:store:cart register_page="/register" />');
    _vae_store_callback_cart($tag);
    $this->assertRedirect("/register");
  }
  
  function testVaeStoreCallbackCheckout() {
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" />');
    $this->populateCart();
    _vae_store_callback_checkout($tag);
    $this->assertSessionDep('__v:store');
    $this->assertRest();
    $this->assertRedirect("/done");
  }
  
  function helperStoreCallbackData1($data, $tag) {
    $this->assertEqual($data, array(
      'line_items' => 
      array (
        0 => 
        array (
          'qty' => 1,
          'inventory_field' => NULL,
          'options' => NULL,
          'option_id' => NULL,
          'original_price' => '5.00',
          'row_id' => 13421,
          'price' => '5.00',
          'notes' => 'm0',
          'total' => 5,
          'tax' => 0,
          'name' => 'Item 1',
          'barcode' => 'abc123',
        ),
        1 => 
        array (
          'qty' => 3,
          'inventory_field' => NULL,
          'options' => NULL,
          'option_id' => NULL,
          'original_price' => '149.99',
          'row_id' => 13433,
          'price' => '149.99',
          'notes' => 'm0',
          'total' => 449.97,
          'tax' => 0,
          'name' => 'Item 2',
          'barcode' => 'abc123',
        ),
        2 => 
        array (
          'qty' => 1,
          'inventory_field' => 'inventory',
          'options' => 'One Size',
          'option_id' => 13434,
          'original_price' => '2.99',
          'row_id' => 13433,
          'price' => '2.99',
          'notes' => '',
          'total' => 2.99,
          'tax' => 0,
          'name' => 'Freefall 11x17" Full Color Poster',
          'barcode' => NULL,
        ),
      ),
      'remote_addr' => '66.0.12.12',
      'customer_id' => 123,
      'email' => 'kevin@actionverb.com',
      'discount_code' => 'bloglings',
      'discount' => 5,
      'shipping' => '63.70',
      'tax' => '37.94',
      'total' => 554.6,
      'shipping_method' => 'Standard Shipping',
      'tax_rate' => 'New York State 8.375%',
      'payment_method' => 'unittest',
      'weight' => 4,
      'token' => "Ptok",
      'payer_id' => "Pid",
      'billing_name' => 'Kevin Bombino',
      'billing_company' => NULL,
      'billing_address' => '1375 Broadway',
      'billing_city' => 'New York',
      'billing_state' => 'NY',
      'billing_country' => NULL,
      'billing_zip' => '10018',
      'billing_phone' => '800-286-8372',
      'shipping_name' => 'Kevin Bombino',
      'shipping_company' => NULL,
      'shipping_address' => '1375 Broadway',
      'shipping_address_2' => 'Floor 3',
      'shipping_city' => 'New York',
      'shipping_state' => 'NY',
      'shipping_zip' => '10018',
      'shipping_country' => NULL,
      'shipping_phone' => '800-286-8372',
    ));
  }
  
  function helperStoreCallbackData2($data, $tag) {
    $this->assertEqual($data['shipping_method'], "Digital Delivery");
  }
  
  function helperStoreCallbackData3($data, $tag) {
    $this->assertEqual($data['shipping_method'], "N/A");
  }
  
  function helperStoreCallbackEmails($data) {
    $this->assertEqual($data['order_confirmation_email_html'], '1');
    $this->assertEqual($data['order_confirmation_email_text'], '2');
    $this->assertEqual($data['order_received_email_html'], '3');
    $this->assertEqual($data['order_received_email_text'], '4');
    $this->assertEqual($data['shipping_info_email_html'], '5');
    $this->assertEqual($data['shipping_info_email_text'], '6');
  }
  
  function testVaeStoreCallbackCheckoutData() {
    global $_VAE;
    $_SESSION['__v:store']['payment_method'] = 'unittest';
    $_SESSION['__v:store']["paypal_express_checkout"] = array('token' => "Ptok", 'payer_id' => "Pid");
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStoreCallbackData1'));
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" />');
    $this->populateCart(array('barcode' => 'abc123', 'notes' => 'm0'));
    vae_store_add_item_to_cart(13433, 13434, 1, array("inventory_field" => "inventory", "name_field" => "name", "price_field" => "price", "option_field" => "size", "options_collection" => "inventory"));
    $this->populateCustomer(array('e_mail_address' => 'kevin@actionverb.com', 'billing_name' => "Kevin Bombino", 'billing_address' => "1375 Broadway", 'billing_address_2' => "Floor 3", 'billing_city' => "New York", 'billing_state' => "NY", 'billing_zip' => "10018", 'billing_phone' => "800-286-8372", 'shipping_name' => "Kevin Bombino", 'shipping_address' => "1375 Broadway", 'shipping_address_2' => "Floor 3", 'shipping_city' => "New York", 'shipping_state' => "NY", 'shipping_zip' => "10018", 'shipping_phone' => "800-286-8372"));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $_SERVER['REMOTE_ADDR'] = "66.0.12.12";
    _vae_store_callback_checkout($tag);
    $this->assertSessionDep('__v:store');
    unset($_SESSION['__v:store']['cart']);
    unset($_VAE['store_cached_shipping']);
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStoreCallbackData2'));
    $this->populateCart(array('digital' => true, 'weight' => null));
    _vae_store_callback_checkout($tag);
    unset($_SESSION['__v:store']['cart']);
    unset($_VAE['store_cached_shipping']);
    unset($_SESSION['__v:store']['shipping']);
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStoreCallbackData3'));
    $this->populateCart(array('weight' => 0));
    _vae_store_callback_checkout($tag);
  }
  
  function testVaeStoreCallbackEmails() {
    global $_VAE;
    $_SESSION['__v:store']['payment_method'] = 'unittest';
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStoreCallbackEmails'));
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" email_confirmation="emails/email_confirmation" email_received="emails/email_confirmation" email_shipping="emails/email_confirmation" />');
    $this->populateCart();
    $this->populateCustomer(array('e_mail_address' => 'kevin@actionverb.com', 'billing_name' => "Kevin Bombino", 'billing_address' => "1375 Broadway", 'billing_address_2' => "Floor 3", 'billing_city' => "New York", 'billing_state' => "NY", 'billing_zip' => "10018", 'billing_phone' => "800-286-8372", 'shipping_name' => "Kevin Bombino", 'shipping_address' => "1375 Broadway", 'shipping_address_2' => "Floor 3", 'shipping_city' => "New York", 'shipping_state' => "NY", 'shipping_zip' => "10018", 'shipping_phone' => "800-286-8372"));
    for ($i = 1; $i <= 6; $i++) {
      $this->mockRest($i);
    }
    _vae_store_callback_checkout($tag);
  }
  
  function testVaeStoreCallbackEmailsBad() {
    global $_VAE;
    $_SESSION['__v:store']['payment_method'] = 'unittest';
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStoreCallbackData1'));
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" email_confirmation="emails/bad_email_confirmation" />');
    $this->populateCart();
    $this->expectException();
    try {
      _vae_store_callback_checkout($tag);
      $this->fail();
    } catch (VaeException $e) {
      $this->assertPattern('/Unable to find Order Confirmation/', $e->getMessage());
    }
  }
  
  function testVaeStoreCallbackCheckoutItemsNotAvail() {
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" />');
    vae_store_add_item_to_cart(13433, 13434, 1, array("inventory_field" => "inventory", "name_field" => "name", "price_field" => "price", "option_field" => "size", "options_collection" => "inventory"));
    $_SESSION['__v:store']['cart'][1]['qty'] = 7000;
    _vae_store_callback_checkout($tag);
    $this->assertNoRest();
    $this->assertErrors("no longer available");
    $this->assertRedirect("/page");
  }
  
  function testVaeStoreCallbackCheckoutLockout() {
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" lockout_redirect="/bad" />');
    $_SESSION['__v:store']['checkout_attempts'] = 3;
    _vae_store_callback_checkout($tag);
    $this->assertRedirect("/bad");
  }
  
  function testVaeStoreCallbackCurrencySelect() {
    $tag = $this->callbackTag('<v:store:currency_select />');
    $_REQUEST['currency'] = "AUD";
    _vae_store_callback_currency_select($tag);
    $this->assertEqual($_SESSION['__v:store_display_currency'], "AUD");
  }
  
  function testVaeStoreCallbackDiscount() {
    $tag = $this->callbackTag('<v:store:discount />');
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>cow</code><fixed-amount>5</fixed-amount></store-discount-code>');
    $_REQUEST['discount'] = "cow";
    _vae_store_callback_discount($tag);
    $this->assertSessionDep('__v:store');
    $this->assertNoErrors();
    $this->assertRedirect($_SERVER['PHP_SELF']);
    $this->assertEqual(_vae_store_compute_discount(), "5.00");
  }
  
  function testVaeStoreCallbackDiscountEmpty() {
    $tag = $this->callbackTag('<v:store:discount />');
    _vae_store_callback_discount($tag);
    $this->assertSessionDep('__v:store');
    $this->assertErrors("enter a discount");
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackForgot() {
    $this->mockRestError();
    $tag = $this->callbackTag('<v:store:forgot />');
    _vae_store_callback_forgot($tag);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackForgotRedirect() {
    $tag = $this->callbackTag('<v:store:forgot redirect="/lalala" />');
    _vae_store_callback_forgot($tag);
    $this->assertRedirect("/lalala");
  }
  
  function testVaeStoreCallbackForgotRedirectFail() {
    $this->mockRestError();
    $tag = $this->callbackTag('<v:store:forgot redirect="/lalala" />');
    _vae_store_callback_forgot($tag);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackLogin() {
    $this->mockRest("<customer><id>123</id><name>Kevin Bombino</name><customer-addresses><customer-address><address-type>billing</address-type><city>Sydney</city></customer-address></customer-addresses></customer>");
    $tag = $this->callbackTag('<v:store:login />');
    _vae_store_callback_login($tag);
    $this->assertNoErrors();
    $this->assertEqual($_SESSION['__v:store']['user']['billing_city'], "Sydney");
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackLoginCustomRedirect() {
    $this->mockRest("<customer><id>123</id><name>Kevin Bombino</name><customer-addresses><customer-address><address-type>billing</address-type><city>Sydney</city></customer-address></customer-addresses></customer>");
    $tag = $this->callbackTag('<v:store:login redirect="/lalala" />');
    _vae_store_callback_login($tag);
    $this->assertNoErrors();
    $this->assertEqual($_SESSION['__v:store']['user']['billing_city'], "Sydney");
    $this->assertRedirect("/lalala");
  }
  
  function testVaeStoreCallbackLoginFail() {
    $this->mockRestError();
    $tag = $this->callbackTag('<v:store:login redirect="/lalala" />');
    _vae_store_callback_login($tag);
    $this->assertErrors("Login information incorrect.");
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackLoginFailCustomMessage() {
    $this->mockRestError();
    $tag = $this->callbackTag('<v:store:login redirect="/lalala" invalid="pwn3d" />');
    _vae_store_callback_login($tag);
    $this->assertErrors("pwn3d");
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackLogout() {
    $tag = $this->callbackTag('<v:store:logout />');
    _vae_store_callback_logout($tag);
    $this->assertNull($_SESSION['__v:store']['customer_id']);
    $this->assertNull($_SESSION['__v:store']['previous_orders']);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackLogoutRedirect() {
    $tag = $this->callbackTag('<v:store:logout redirect="/home" />');
    _vae_store_callback_logout($tag);
    $this->assertRedirect("/home");
  }
  
  function testVaeStoreCallbackPaymentMethodsSelect() {
    $_REQUEST['method'] = "testeuro";
    $this->assertNull($_SESSION['__v:store']['payment_method']);
    $tag = $this->callbackTag('<v:store:payment_methods_select />');
    _vae_store_callback_payment_methods_select($tag);
    $this->assertEqual($_SESSION['__v:store']['payment_method'], "testeuro");
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackPaymentMethodsSelectExpressCheckout() {
    $_REQUEST['method'] = "paypal_express_checkout";
    $this->mockRest("http://cow.com/");
    $tag = $this->callbackTag('<v:store:payment_methods_select />');
    _vae_store_callback_payment_methods_select($tag);
    $this->assertRest();
    $this->assertRedirect("http://cow.com/");
  }
  
  function testVaeStoreCallbackPaymentMethodsSelectExpressCheckoutToken() {
    $_REQUEST['token'] = "applesauce";
    $tag = $this->callbackTag('<v:store:payment_methods_select />');
    _vae_store_callback_payment_methods_select($tag);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackPaypalExpressCheckout() {
    $this->mockRest("http://cow.com/");
    $tag = $this->callbackTag('<v:store:paypal_express_checkout />');
    _vae_store_callback_paypal_express_checkout($tag);
    $this->assertRest();
    $this->assertRedirect("http://cow.com/");
  }
  
  function testVaeStoreCallbackPaypalExpressCheckoutToken() {
    $_REQUEST['token'] = 12345;
    $_REQUEST['PayerID'] = 6789;
    $this->mockRest("<response><address1>1375 Broadway</address1><address2>Floor 3</address2></response>");
    $tag = $this->callbackTag('<v:store:paypal_express_checkout redirect="/checkout" />');
    _vae_store_callback_paypal_express_checkout($tag);
    $this->assertEqual($_SESSION['__v:store']['paypal_express_checkout'], array('token' => 12345, 'payer_id' => 6789));
    $this->assertEqual($_SESSION['__v:store']['payment_method'], "paypal_express_checkout");
    $this->assertEqual($_SESSION['__v:store']['user']['billing_address'], "1375 Broadway");
    $this->assertEqual($_SESSION['__v:store']['user']['billing_address_2'], "Floor 3");
    $this->assertRedirect("/checkout");
  }
  
  function testVaeStoreCallbackPaypalExpressCheckoutTokenFail() {
    $_REQUEST['token'] = 12345;
    $_REQUEST['PayerID'] = 6789;
    $this->mockRestError();
    $tag = $this->callbackTag('<v:store:paypal_express_checkout />');
    _vae_store_callback_paypal_express_checkout($tag);
    $this->assertRest();
    $this->assertEqual($_SESSION['__v:store']['paypal_express_checkout'], array('token' => 12345, 'payer_id' => 6789));
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackPaypalExpressCheckoutRestError() {
    $this->mockRestError("http://cow.com/");
    $tag = $this->callbackTag('<v:store:paypal_express_checkout />');
    _vae_store_callback_paypal_express_checkout($tag);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackRegister() {
    $tag = $this->callbackTag("<v:store:register formmail='kevin@bombino.org' redirect='/checkout'><v:text path='billing_name' required='name' /></v:store:register>");
    _vae_store_callback_register($tag);
    $this->assertRest();
    $this->assertRedirect("/checkout");
  }
  
  function testVaeStoreCallbackRegisterErrors() {
    $this->mockRestError("wild error");
    $tag = $this->callbackTag("<v:store:register redirect='/checkout'><v:text path='billing_name' required='name' /></v:store:register>");
    _vae_store_callback_register($tag);
    $this->assertErrors("wild error");
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCallbackShippingMethodsSelect() {
    $_REQUEST['method'] = 1;
    $tag = $this->callbackTag('<v:store:shipping_methods_select />');
    $_SESSION['__v:store']['shipping']['options'] = array(array('cost' => '7.00'), array('cost' => '15.00'));
    $this->assertNull($_SESSION['__v:store']['shipping']['selected_index']);
    _vae_store_callback_shipping_methods_select($tag);
    $this->assertEqual($_SESSION['__v:store']['shipping']['selected_index'], 1);
    $this->assertEqual($_SESSION['__v:store']['shipping']['selected'], 15.00);
    $this->assertRedirect($_SERVER['PHP_SELF']);
  }
  
  function testVaeStoreCartItemName() {
    $this->assertEqual(_vae_store_cart_item_name(array('name' => 'cow')), 'cow');
    $this->assertEqual(_vae_store_cart_item_name(array('name' => 'cow', 'option_value' => 'red')), 'cow (red)');
  }
  
  function testVaeStoreCompleteCheckout() {
    $this->populateCart();
    $_SESSION['__v:store']['discount'] = "19.99";
    $_SESSION['__v:store']['discount_code'] = "abc123";
    $_SESSION['__v:store']['payment_method'] = "test";
    $_SESSION['__v:store']['checkout_attempts'] = 2;
    $this->mockRest("<reference-id>123</reference-id>");
    $data = array('payment_method' => 'test');
    $this->assertNotNull($_SESSION['__v:store']['cart']);
    $this->assertNotNull($_SESSION['__v:store']['discount']);
    $this->assertNotNull($_SESSION['__v:store']['discount_code']);
    $this->assertNotNull($_SESSION['__v:store']['payment_method']);
    $this->assertNotNull($_SESSION['__v:store']['checkout_attempts']);
    $oldcart = $_SESSION['__v:store']['cart'];
    foreach ($oldcart as $k => $v) {
      $oldcart[$k]['order_id'] = 123;
    }  
    $this->assertTrue(_vae_store_complete_checkout($data));
    $this->assertEqual($_SESSION['__v:store']['recent_order'], $oldcart);
    $data['id'] = 123;
    $this->assertEqual($_SESSION['__v:store']['recent_order_data'], $data);
    $this->assertNull($_SESSION['__v:store']['cart']);
    $this->assertNull($_SESSION['__v:store']['discount']);
    $this->assertNull($_SESSION['__v:store']['discount_code']);
    $this->assertNull($_SESSION['__v:store']['payment_method']);
    $this->assertNull($_SESSION['__v:store']['checkout_attempts']);
    $this->assertNoRedirect();
  }
  
  function testVaeStoreCompleteCheckoutBad() {
    $this->populateCart();
    $this->mockRestError();
    $data = array('payment_method' => 'test');
    $this->assertFalse(_vae_store_complete_checkout($data));
    $this->assertNull($_SESSION['__v:store']['recent_order']);
    $this->assertNull($_SESSION['__v:store']['recent_order_data']);
  }
  
  function testVaeStoreCompleteCheckoutRedirect() {
    $tag = $this->callbackTag('<v:store:checkout register_page="/register" redirect="/done" />');
    $this->populateCart();
    $data = array('payment_method' => 'test');
    _vae_store_complete_checkout($data, $tag);
    $this->assertRedirect("/done");
  }
  
  function testVaeStoreComputeDiscount() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 5.00);
    $this->assertEqual(_vae_store_compute_discount(null, 4), 4.00);
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreComputeDiscountCantFind() {
    $this->populateCart();
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertErrors("invalid coupon code");
  }
  
  function testVaeStoreComputeDiscountCountry() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>500</fixed-amount><country>jp</country></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $_SESSION['__v:store']['discount_code_show_errors'] = true;
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertErrors("not being shipped to");
  }
  
  function testVaeStoreComputeDiscountMax() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>500</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 454.97);
  }
  
  function testVaeStoreComputeDiscountMaxShipping() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>500</fixed-amount><free-shipping>true</free-shipping></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 518.67);
  }
  
  function testVaeStoreComputeDiscountMinOrderAmount() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><min-order-amount>600</min-order-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $_SESSION['__v:store']['discount_code_show_errors'] = true;
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertErrors("not big enough");
  }
  
  function testVaeStoreComputeDiscountMinOrderItems() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><min-order-items>7</min-order-items></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $_SESSION['__v:store']['discount_code_show_errors'] = true;
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertErrors("not enough items");
  }
  
  function testVaeStoreComputeDiscountNotAvailableAnymore() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><stop-at>Jan 1, 1990</stop-at></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertErrors("no longer available");
  }
  
  function testVaeStoreComputeDiscountNotYetAvailable() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><start-at>Jan 1, 2035</start-at></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertErrors("not available yet");
  }
  
  function testVaeStoreComputeDiscountNotUsedHideErrors() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>0</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
    $this->assertEqual("bloglings", $_SESSION['__v:store']['discount_code']);
  }
  
  function testVaeStoreComputeDiscountPerc() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><percentage-amount>50</percentage-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 227.49);
  }
  
  function testVaeStoreComputeDiscountIncludedClasses() {
    $this->populateCart(array('discount_class' => "goods,services"));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><included-classes>goods</included-classes></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 5.00);
  }
  
  function testVaeStoreComputeDiscountIncludedClasses2() {
    $this->populateCart(array('discount_class' => "services"));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><included-classes>goods</included-classes></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
  }
  
  function testVaeStoreComputeDiscountExcludedClasses() {
    $this->populateCart(array('discount_class' => "services"));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><excluded-classes>goods</excluded-classes></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 5.00);
  }
  
  function testVaeStoreComputeDiscountExcludedClasses2() {
    $this->populateCart(array('discount_class' => "goods,services"));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><excluded-classes>goods</excluded-classes></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 0.00);
  }
  
  function testVaeStoreComputeDiscountRequiredClasses() {
    $this->populateCart(array('discount_class' => "goods,services"));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><included-classes>goods</included-classes><required-classes>services</required-classes></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 5.00);
  }
  
  function testVaeStoreComputeDiscountShipping() {
    $this->populateCart();
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount><free-shipping>true</free-shipping></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_discount(), 68.70);
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreComputeNumberOfItems() {
    global $_VAE;
    $this->assertEqual(_vae_store_compute_number_of_items(), 0);
    $this->populateCart();
    $this->assertEqual(_vae_store_compute_number_of_items(), 4);
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_VAE['store_cached_number_of_items'], 4);
    $_VAE['store_cached_number_of_items'] = 7;
    $this->assertEqual(_vae_store_compute_number_of_items(), 7);
  }
  
  function testVaeStoreComputeShipping() {
    global $_VAE;
    $this->populateCustomer();
    $this->populateCart();
    $this->assertEqual(63.70, _vae_store_compute_shipping());
    $this->assertEqual(63.70, $_VAE['store_cached_shipping']);
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_SESSION['__v:store']['shipping'], array (
      'hash' => 'e16f02f1c0e13b92d90c9ad68563db12',
      'weight' => 4,
      'options' => 
        array(
          array(
            'title' => 'Standard Shipping',
            'cost' => '63.70',
            'keep_titles' => true,
            'rate_group' => NULL,
          )
        ),
      'selected' => '63.70',
      'selected_index' => 0));
  }
  
  function testVaeStoreComputeShippingCached() {
    global $_VAE;
    $_VAE['store_cached_shipping'] = 812.12;
    $this->assertEqual(812.12, _vae_store_compute_shipping());
    $this->assertNotEqual(812.12, _vae_store_compute_shipping("kevin.html"));
  }
  
  function testVaeStoreComputeShippingEmpty() {
    $this->populateCustomer();
    $this->assertEqual(0, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingUser() {
    $this->populateCustomer();
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'free_shipping_threshold' => 1000));
    $this->assertEqual(1.45, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingDestinationCountry() {
    global $_VAE;
    $this->populateCustomer();
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'destination_country' => "CA"));
    $this->assertEqual(63.70, _vae_store_compute_shipping());
    unset($_VAE['store_cached_shipping']);
    $this->populateCustomer(array('shipping_country' => "CA"));
    $this->assertEqual(1.45, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingDestinationContinent() {
    global $_VAE;
    $this->populateCustomer(array('shipping_country' => "US"));
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'destination_country' => "cont_NA"));
    $this->assertEqual(1.45, _vae_store_compute_shipping());
    unset($_VAE['store_cached_shipping']);
    $this->populateCustomer(array('shipping_country' => "AU"));
    $this->assertEqual(63.70, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingDomesticOnly() {
    global $_VAE;
    $this->populateCustomer(array('shipping_country' => "US"));
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'domestic_only' => "1"));
    $this->assertEqual(1.45, _vae_store_compute_shipping());
    unset($_VAE['store_cached_shipping']);
    $this->populateCustomer(array('shipping_country' => "AU"));
    $this->assertEqual(63.70, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingInternationalOnly() {
    global $_VAE;
    $this->populateCustomer(array('shipping_country' => "US"));
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'international_only' => "1"));
    $this->assertEqual(63.70, _vae_store_compute_shipping());
    unset($_VAE['store_cached_shipping']);
    $this->populateCustomer(array('shipping_country' => "AU"));
    $this->assertEqual(1.45, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingClass() {
    $this->populateCustomer();
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'class' => "bad"));
    $this->assertEqual(63.70, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingClass2() {
    $this->populateCustomer();
    $this->populateCart(array('shipping_class' => 'bad'));
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'class' => "bad"));
    $this->assertEqual(1.45, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingWeightsManual() {
    $this->populateCustomer();
    $this->populateCart();
    $_SESSION['__v:store']['total_weight'] = array(1, 1, 1, 4);
    $this->assertEqual(254.78, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingUserFree() {
    $this->populateCustomer();
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping', 'cost' => '1.45', 'free_shipping_threshold' => 1));
    $this->assertEqual(0.00, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeShippingRateGroups() {
    $this->populateCustomer();
    $this->populateCart();
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping1', 'cost' => '3.00', 'rate_group' => 'grp'));
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping2', 'cost' => '1.00', 'rate_group' => 'grp'));
    vae_store_add_shipping_method(array('title' => 'Cheap Shipping3', 'cost' => '2.00', 'rate_group' => 'grp'));
    $this->assertEqual(1.00, _vae_store_compute_shipping());
  }
  
  function testVaeStoreComputeSubtotal() {
    global $_VAE;
    $this->assertEqual(_vae_store_compute_subtotal(), 0.00);
    $this->populateCart();
    $this->assertEqual(_vae_store_compute_subtotal(), 454.97);
    $this->assertEqual($_VAE['store_cached_subtotal'], 454.97);
    $_VAE['store_cached_subtotal'] = 126.45;
    $this->assertEqual(_vae_store_compute_subtotal(), 126.45);
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreComputeTaxBase() {
    global $_VAE;
    $this->populateCart();
    $this->assertEqual(_vae_store_compute_tax(), 0.00);
    $this->assertSessionDep('__v:store');
    $this->populateCart();
    $this->assertEqual(_vae_store_compute_tax(), 0.00);
    $this->assertEqual($_VAE['store_cached_tax'], 0.00);
    $_VAE['store_cached_tax'] = 12.34;
    $this->assertEqual(_vae_store_compute_tax(), 12.34);
    $_VAE['store_cached_subtotal'] = -100;
    unset($_VAE['store_cached_tax']);
    $this->assertEqual($_VAE['store_cached_tax'], 0.00);
  }
  
  function testVaeStoreComputeTaxCountry() {
    global $_VAE;
    $this->populateCart();
    $this->populateCustomer(array('shipping_country' => 'AU'));
    $this->assertEqual(_vae_store_compute_tax(), 81.89);
    $this->assertEqual($_SESSION['__v:store']['tax_rate'], 'Australia GST');
  }
  
  function testVaeStoreComputeTaxDiscount() {
    $this->populateCart();
    $this->populateCustomer(array('shipping_state' => 'NY'));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $this->assertEqual(_vae_store_compute_tax(), 37.68);
  }
  
  function testVaeStoreComputeTaxMinimumPrice() {
    global $_VAE;
    $this->populateCart();
    $this->populateCustomer(array('shipping_state' => 'FL'));
    $this->assertEqual(_vae_store_compute_tax(), 18.00);
  }
  
  function testVaeStoreComputeTaxState() {
    global $_VAE;
    $this->populateCart();
    $this->populateCustomer(array('shipping_state' => 'NY'));
    $this->assertEqual(_vae_store_compute_tax(), 38.10);
    $this->assertEqual($_VAE['store_cached_tax'], 38.10);
    $this->assertEqual($_SESSION['__v:store']['tax_rate'], 'New York State 8.375%');
  }
  
  function testVaeStoreComputeTaxTaxClasses() {
    global $_VAE;
    $this->populateCart(array('tax_class' => "clothing"));
    $this->populateCustomer(array('shipping_state' => 'CA'));
    $this->assertEqual(_vae_store_compute_tax(), 22.75);
  }
  
  function testVaeStoreComputeTaxTaxClasses2() {
    global $_VAE;
    $this->populateCart(array('tax_class' => "services"));
    $this->populateCustomer(array('shipping_state' => 'CA'));
    $this->assertEqual(_vae_store_compute_tax(), 31.85);
  }
  
  function testVaeStoreComputeTaxTaxClasses3() {
    global $_VAE;
    $this->populateCart();
    $this->populateCustomer(array('shipping_state' => 'CA'));
    $this->assertEqual(_vae_store_compute_tax(), 0.00);
  }
  
  function testVaeStoreComputeTaxZip() {
    // note this also tests for shipping included in tax calculations
    $this->populateCart();
    $this->populateCustomer(array('shipping_zip' => 10009, 'shipping_state' => 'NY'));
    $this->assertEqual(_vae_store_compute_tax(), 40.70);
    $this->assertEqual($_SESSION['__v:store']['tax_rate'], 'New York State 8.375%/1000* 0.500%');
  }
  
  function testVaeStoreComputeTotal() {
    global $_VAE;
    $_VAE['store_cached_subtotal'] = 100.00;
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>4</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    $_VAE['store_cached_tax'] = 0.50;
    $_VAE['store_cached_shipping'] = 15.00;
    $this->assertEqual(_vae_store_compute_total(), 111.50);
    unset($_SESSION['__v:store']['discount_code']);
    $_VAE['store_cached_shipping'] = -1015.00;
    $this->assertEqual(_vae_store_compute_total(), 0.00);
  }
  
  function testVaeStoreCreateCustomer() {
    global $_VAE;
    $_VAE['settings']['store_shipping_use_ups_address_validation'] = true;
    vae_register_hook("store:register:success", "helperHook");
    $this->mockRest("<customer><e-mail-address>kevin@actionverb.com</e-mail-address><customer-addresses><customer-address><address_type>billing</address_type><name>Kevin Bombino</name><city>Sydney</city></customer-address></customer-addresses></customer>");
    $this->assertNull($_VAE['__test_hooked']);
    $data = array('e_mail_address' => 'kevin@actionverb.com', 'billing_name' => "Kevin");
    $this->assertTrue(_vae_store_create_customer($data));
    $this->assertSessionDep('__v:store');
    $this->assertNull($_SESSION['__v:store']['customer_addresses']);
    $this->assertEqual($_SESSION['__v:store']['user'], $data);
    $this->assertRest();
    $this->assertEqual($_VAE['__test_hooked'], 1);
  }
  
  function testVaeStoreCreateCustomerLoggedin() {
    global $_VAE;
    vae_register_hook("store:register:success", "helperHook");
    $_SESSION['__v:store']['customer_id'] = 123;
    $this->mockRest("<customer><e-mail-address>kevin@actionverb.com</e-mail-address><customer-addresses><customer-address><address_type>billing</address_type><name>Kevin Bombino</name><city>Sydney</city></customer-address></customer-addresses></customer>");
    $this->assertNull($_VAE['__test_hooked']);
    $this->assertTrue(_vae_store_create_customer(array('e_mail_address' => 'kevin@actionverb.com')));
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_SESSION['__v:store']['user'], array('e_mail_address' => 'kevin@actionverb.com', 'billing_name' => "Kevin Bombino", 'billing_city' => "Sydney"));
    $this->assertRest();
    $this->assertEqual($_VAE['__test_hooked'], 1);
  }
  
  function testVaeStoreCreateCustomerLoggedinFailure() {
    global $_VAE;
    $_SESSION['__v:store']['customer_id'] = 123;
    $this->mockRestError();
    $data = array('e_mail_address' => 'kevin@actionverb.com');
    $this->assertFalse(_vae_store_create_customer($data));
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreCreateCustomerNotLoggedinFailure() {
    global $_VAE;
    $this->mockRestError();
    $data = array('e_mail_address' => 'kevin@actionverb.com');
    $this->assertFalse(_vae_store_create_customer($data));
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreCurrency() {
    global $_VAE;
    $this->assertEqual(_vae_store_currency(), "USD");
    $_VAE['settings']['store_currency'] = "JPY";
    $this->assertEqual(_vae_store_currency(), "JPY");
  }
  
  function testVaeStoreCurrencyDisplay() {
    $this->assertEqual(_vae_store_currency_display(7.99, false), "<span class='currency currency_USD'>7.99</span>");
    $this->assertEqual(_vae_store_currency_display(7.99), "<span class='currency currency_USD'>$7.99</span>");
    $_SESSION['__v:store_display_currency'] = "AUD";
    $this->mockRest(file_get_contents(dirname(__FILE__) . "/data/coinmill_rss.xml"));
    $this->assertEqual(_vae_store_currency_display(7.99), "<span class='currency currency_AUD'>est. $7.03 AUD</span>");
  }
  
  function testVaeStoreCurrentUser() {
    $_SESSION['__v:store']['user'] = "k";
    $this->assertEqual(_vae_store_current_user(), "k");
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreExchangeRate() {
    $this->mockRest(file_get_contents(dirname(__FILE__) . "/data/coinmill_rss.xml"));
    $this->assertEqual(_vae_store_exchange_rate("USD", "AUD"), 0.88);
    $this->assertEqual(_vae_store_exchange_rate("USD", "AUD"), 0.88);
  }
  
  function testVaeStoreFindDiscount() {
    $this->populateCustomer();
    $this->mockRest('<store-discount-code><code>cow</code><fixed-amount>5</fixed-amount></store-discount-code>');
    $ret = _vae_store_find_discount("cow");
    $this->assertEqual($ret, array('code' => 'cow', 'fixed_amount' => '5'));
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreFindDiscountBad() {
    $this->populateCustomer();
    $this->mockRest('BAD');
    $this->assertFalse(_vae_store_find_discount("cow"));
  }
  
  function testVaeStoreIfDigitalDownloads() {
    $this->assertFalse(_vae_store_if_digital_downloads());
    $this->populateCart();
    $this->assertFalse(_vae_store_if_digital_downloads());
    $this->populateCart(array('digital' => true));
    $this->assertTrue(_vae_store_if_digital_downloads());
  }
  
  function testVaeStoreIfShippable() {
    $this->assertFalse(_vae_store_if_shippable());
    $this->populateCart();
    $this->assertTrue(_vae_store_if_shippable());
    unset($_SESSION['__v:store']);
    $this->populateCart(array('digital' => true, 'weight' => null));
    $this->assertFalse(_vae_store_if_shippable());
  }
  
  function testVaeStoreItemAvailable() {
    $this->assertTrue(_vae_store_item_available(_vae_fetch(13434), null, "inventory"));
    $this->assertTrue(_vae_store_item_available(_vae_fetch(13433), "inventory", "inventory"));
    $this->assertFalse(_vae_store_item_available(_vae_fetch(13433), "inventory", "bad"));
    $this->assertFalse(_vae_store_item_available(_vae_fetch(13433), null, "inventory"));
    $this->assertFalse(_vae_store_item_available(_vae_fetch(13435), "inventory", "inventory"));
    $this->assertFalse(_vae_store_item_available(_vae_fetch(13435), "bad", "inventory"));
  }
  
  function testVaeStoreItemHasOptions() {
    $this->assertTrue(_vae_store_item_has_options(13421, "items"));
    $this->assertFalse(_vae_store_item_has_options(13447, "items"));
  }
  
  function testVaeStoreLoadCustomer() {
    $raw = "<customer><id>124</id><e-mail-address>kevin@actionverb.com</e-mail-address><customer-addresses><customer-address><address_type>billing</address_type><name>Kevin Bombino</name><city>Sydney</city></customer-address></customer-addresses></customer>";
    _vae_store_load_customer($raw);
    $this->assertSessionDep('__v:store');
    $this->assertTrue($_SESSION['__v:store']['loggedin']);
    $this->assertEqual($_SESSION['__v:store']['user'], array('id' => 124, 'tags' => null, 'name' => "", 'e_mail_address' => 'kevin@actionverb.com', 'billing_name' => "Kevin Bombino", 'billing_city' => "Sydney"));
    $this->assertEqual($_SESSION['__v:store']['customer_id'], 124);
    $this->assertEqual($_SESSION['__v:store']['customer_addresses'], array(array('address_type' => 'billing', 'name' => 'Kevin Bombino', 'city' => 'Sydney')));
  }
  
  function testVaeStoreLoadCustomerNotLoggedIn() {
    $raw = "<customer><id>124</id><e-mail-address>kevin@actionverb.com</e-mail-address><customer-addresses><customer-address><address_type>billing</address_type><name>Kevin Bombino</name><city>Sydney</city></customer-address></customer-addresses></customer>";
    _vae_store_load_customer($raw, false);
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_SESSION['__v:store']['user'], array('e_mail_address' => 'kevin@actionverb.com', 'id' => 124, 'name' => "", 'tags' => null));
    $this->assertEqual($_SESSION['__v:store']['customer_id'], 124);
    $this->assertFalse($_SESSION['__v:store']['loggedin']);
    $this->assertNull($_SESSION['__v:store']['customer_addresses']);
  }
  
  function testVaeStoreMostSpecificField() {
    $this->assertEqual(_vae_store_most_specific_field(array(), "cow"), "");
    $this->populateCart();
    $a = array_shift($_SESSION['__v:store']['cart']);
    $this->assertEqual(_vae_store_most_specific_field($a, "name"), "Freefall");
    $a['option_id'] = 13423;
    $this->assertEqual(_vae_store_most_specific_field($a, "name"), "Road Trip EP");
  }
  
  function testVaeStorePaymentMethod() {
    $this->populateCart();
    _vae_store_set_default_payment_method();
    $this->assertEqual(_vae_store_payment_method(), array('method_name' => 'test', 'accept_visa' => '1', 'accept_master' => '1', 'accept_discover' => '1', 'accept_american_express' => '1'));
  }
  
  function helperStorePaymentPaypalCallback($data, $tag) {
    global $_VAE;
    _vae_store_payment_paypal_callback($data, $tag);
    $this->assertSessionDep('__v:store');
    $this->assertPattern('/https:\/\/www.paypal.com\/cgi-bin\/webscr\?cmd=_cart&upload=1&business=sales%40actionverb\.com&amount_1=5\.00&on0_1=Option&os0_1=&item_name_1=Item\+1&item_number_1=13421&quantity_1=1&amount_2=149\.99&on0_2=Option&os0_2=&item_name_2=Item\+2&item_number_2=13433&quantity_2=3&shipping_1=63\.70&tax_1=38\.10&notify_url=http%3A%2F%2Fbtg\.vaesite\.com%2Fpage%3F__v%3Astore_payment_method_ipn%3Dpaypal&return=http%3A%2F%2Fbtg\.vaesite\.com%2F%2Fdone&cancel_return=http%3A%2F%2Fbtg\.vaesite\.com%2Fpage&address_override=1&first_name=Kevin&last_name=Bombino&address1=1375\+Broadway&address2=Floor\+3&city=New\+York&state=NY&zip=10018&country=US&night_phone_a=800-286-8372&no_note=1&currency_code=USD&bn=PP%2dBuyNowBF&lc=US&custom=([a-f0-9]*)\.tmp/', $_VAE['force_redirect']);
    preg_match('/custom=([a-f0-9]*)\.tmp/', $_VAE['force_redirect'], $matches);
    $file = _vae_read_file($matches[1] . ".tmp");
    $this->assertEqual(unserialize($file), array('data' => $data, 'cart' => $_SESSION['__v:store']['cart']));
  }
  
  function helperStorePaymentPaypalCallback2($data, $tag) {
    global $_VAE;
    _vae_store_payment_paypal_callback($data, $tag);
    $this->assertEqual($data['discount'], 5);
    $this->assertEqual($data['total'], 551.35);
    $this->assertPattern('/https:\/\/www.paypal.com\/cgi-bin\/webscr\?/', $_VAE['force_redirect']);
  }
  
  function testVaeStorePaymentPaypalCallback() {
    global $_VAE;
    $_SESSION['__v:store']['payment_method'] = 'unittest';
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStorePaymentPaypalCallback'));
    $tag = $this->callbackTag('<v:store:checkout redirect="/done" register_page="/register" />');
    $this->populateCustomer(array('e_mail_address' => 'kevin@actionverb.com', 'billing_name' => "Kevin Bombino", 'billing_address' => "1375 Broadway", 'billing_address_2' => "Floor 3", 'billing_city' => "New York", 'billing_state' => "NY", 'billing_zip' => "10018", 'billing_phone' => "800-286-8372", 'shipping_name' => "Kevin Bombino", 'shipping_address' => "1375 Broadway", 'shipping_address_2' => "Floor 3", 'shipping_city' => "New York", 'shipping_state' => "NY", 'shipping_zip' => "10018", 'shipping_country' => "US", 'shipping_phone' => "800-286-8372"));
    $this->populateCart();
    $_VAE['store']['payment_methods']['unittest'] = array('name' => "Unit Test Payment Method", 'callback' => array($this, 'helperStorePaymentPaypalCallback2'));
    $this->mockRest('<store-discount-code><code>BLOGLINGS</code><fixed-amount>5</fixed-amount></store-discount-code>');
    vae_store_discount_code("BLOGLINGS");
    _vae_store_callback_checkout($tag);
  }
  
  function testVaeStorePaymentPaypalEmail() {
    $this->assertEqual(_vae_store_payment_paypal_email(), "sales@actionverb.com");
  }
  
  function testVaeStorePaymentPaypalIpnEmailMismatch() {
    $this->mockRest("VERIFIED");
    $_POST['custom'] = "12345";
    $_POST['payment_status'] = "Completed";
    $data = array('payment_method' => 'test');
    _vae_write_file($_POST['custom'], serialize(array('data' => $data, 'cart' => $_SESSION['__v:store']['cart'])));
    $tag = $this->callbackTag('<v:store:checkout register_page="/register" redirect="/done" />');
    $out = _vae_store_payment_paypal_ipn($tag);
    $this->assertPattern('/Status: 503 Service Temporarily Unavailable/', $out);
    $this->assertPattern('/E-Mail mismatch/', $out);
    $this->assertReportedError();
  }
  
  function testVaeStorePaymentPaypalIpnValidAndCompleted() {
    global $_VAE;
    $this->mockRest("VERIFIED");
    $this->mockRest("<reference-id>123</reference-id>");
    $this->populateCart();
    $_POST['custom'] = "12345";
    $_POST['payment_status'] = "Completed";
    $_POST['receiver_email'] = "sales@actionverb.com";
    $_POST['txn_id'] = "3242TXNID";
    $data = array('payment_method' => 'test');
    _vae_write_file($_POST['custom'], serialize(array('data' => $data, 'cart' => $_SESSION['__v:store']['cart'])));
    unset($_VAE['files_written']);
    $oldcart = $_SESSION['__v:store']['cart'];  
    foreach ($oldcart as $k => $v) {
      $oldcart[$k]['order_id'] = 123;
    }  
    $tag = $this->callbackTag('<v:store:checkout register_page="/register" redirect="/done" />');
    $out = _vae_store_payment_paypal_ipn($tag);
    $data['id'] = 123;
    $data['gateway_transaction_id'] = '3242TXNID';
    $this->assertPattern('/PayPal authenticity verified./', $out);
    $this->assertPattern('/Payment Completed, submitting order./', $out);
    $this->assertEqual($_SESSION['__v:store']['recent_order'], $oldcart);
    $this->assertEqual($_SESSION['__v:store']['recent_order_data'], $data);
    $this->assertNull($_SESSION['__v:store']['cart']);
    $this->assertNull($_SESSION['__v:store']['discount']);
    $this->assertNull($_SESSION['__v:store']['discount_code']);
    $this->assertNull($_SESSION['__v:store']['payment_method']);
    $this->assertNull($_SESSION['__v:store']['checkout_attempts']);
    $this->assertNoReportedErrors();
  }
   
  function testVaeStorePopulateAddress() {
    _vae_store_populate_address(array('address_type' => "billing", 'name' => "Kevin", 'city' => "Sydney"));
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_SESSION['__v:store']['user'], array('billing_name' => "Kevin", 'billing_city' => "Sydney"));
  }
  
  function testVaeStorePopulateAddresses() {
    $_SESSION['__v:store']['customer_addresses'] = array(array('address_type' => "billing", 'name' => "Kevin", 'city' => "Sydney"), array('address_type' => "shipping", 'name' => "Alex", 'city' => "Melbourne"));
    _vae_store_populate_addresses();
    $this->assertSessionDep('__v:store');
    $this->assertEqual($_SESSION['__v:store']['user'], array('billing_name' => "Kevin", 'billing_city' => "Sydney", 'shipping_name' => "Alex", 'shipping_city' => "Melbourne"));
  }
  
  function testVaeStoreRenderAddToCart() {
    _vae_store_render_add_to_cart(array(), $this->tag, _vae_fetch(13433), $this->callback, $this->render_context);
    $this->assertEqual($this->callback['item'], 13433);
  }
  
  function testVaeStoreRenderAddressDelete() {
    _vae_store_render_address_delete(array(), $this->tag, _vae_fetch("13421"), $this->callback, $this->render_context);
    $this->assertEqual($this->callback['id'], 13421);
  }
  
  function testVaeStoreSetDefaultPaymentMethod() {
    $this->assertNull($_SESSION['__v:store']['payment_method']);
    _vae_store_set_default_payment_method();
    $this->assertEqual($_SESSION['__v:store']['payment_method'], "No Payment Required");
    $this->populateCart();
    _vae_store_set_default_payment_method();
    $this->assertEqual($_SESSION['__v:store']['payment_method'], "test");
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreSuggestAlternateAddress() {
    $this->mockRest('<?xml version="1.0"?><AddressValidationResponse><Response><TransactionReference><CustomerContext>Data</CustomerContext><XpciVersion>1.0001</XpciVersion></TransactionReference><ResponseStatusCode>1</ResponseStatusCode><ResponseStatusDescription>Success</ResponseStatusDescription></Response><AddressValidationResult><Rank>1</Rank><Quality>1.0</Quality><Address><City>NEW YORK</City><StateProvinceCode>NY</StateProvinceCode></Address><PostalCodeLowEnd>10016</PostalCodeLowEnd><PostalCodeHighEnd>10041</PostalCodeHighEnd></AddressValidationResult></AddressValidationResponse>');
    $this->assertEqual(_vae_store_suggest_alternate_address("US", "nyc", "NY", "10018"), "NEW YORK");
    $this->assertEqual(_vae_store_suggest_alternate_address("CA", "whatever1", "NY", "10018"), "whatever1");
    $this->assertEqual(_vae_store_suggest_alternate_address("US", "whatever1", "AA", "10018"), "whatever1");
    $this->assertEqual(_vae_store_suggest_alternate_address("US", "whatever1", "AE", "10018"), "whatever1");
    $this->assertEqual(_vae_store_suggest_alternate_address("US", "whatever1", "AP", "10018"), "whatever1");
  }
  
  function testVaeStoreTransformOrders() {
    $xml = "<store-orders><store-order><id>1</id><email>sales@actionverb.com</email><total>7.00</total><created-at>4/5/2008 01:23:23</created-at></store-order><store-order><id>4</id><total>19.50</total><created-at>4/7/2008 01:23:23</created-at></store-order></store-orders>";
    $this->assertEqual(_vae_store_transform_orders($xml), array(
      1 => 
      array (
        'id' => '1',
        'total' => '7.00',
        'e_mail_address' => 'sales@actionverb.com',
        'created_at' => '4/5/2008 01:23:23',
        'date' => 'April 05, 2008',
        'subtotal' => '7.00',
        'order_id' => 1,
      ),
      4 => 
      array (
        'id' => '4',
        'total' => '19.50',
        'created_at' => '4/7/2008 01:23:23',
        'date' => 'April 07, 2008',
        'subtotal' => '19.50',
        'order_id' => 4,
      ),
    ));
  }
  
  function testVaeStoreVerifyAvailableBaseCase() {
    $this->populateCart();
    $this->assertTrue(_vae_store_verify_available());
    $this->assertNoErrors();
    $this->assertSessionDep('__v:store');
  }
  
  function testVaeStoreVerifyAvailableTooMany() {
    vae_store_add_item_to_cart(13433, 13434, 1, array("inventory_field" => "inventory", "name_field" => "name", "price_field" => "price", "option_field" => "size", "options_collection" => "inventory"));
    $_SESSION['__v:store']['cart'][1]['qty'] = 7000;
    $this->assertFalse(_vae_store_verify_available());
    $this->assertErrors("in the quantity you requested");
    $this->assertEqual($_SESSION['__v:store']['cart'][1]['qty'], 747);
  }
  
  function testVaeStoreVerifyAvailableTooManyIgnored() {
    vae_store_add_item_to_cart(13433, 13434, 1, array("disable_inventory_check" => true, "inventory_field" => "inventory", "name_field" => "name", "price_field" => "price", "option_field" => "size", "options_collection" => "inventory"));
    $_SESSION['__v:store']['cart'][1]['qty'] = 7000;
    $this->assertTrue(_vae_store_verify_available());
    $this->assertNoErrors();
  }
  
  function testVaeStoreVerifyAvailableNoneAvail() {
    vae_store_add_item_to_cart(13435, 13436, 1, array("disable_inventory_check" => true, "inventory_field" => "inventory", "name_field" => "name", "price_field" => "price", "option_field" => "size", "options_collection" => "inventory"));
    $this->assertTrue(_vae_store_verify_available());
    $_SESSION['__v:store']['cart'][1]['check_inventory'] = true;
    $_SESSION['__v:store']['cart'][1]['inventory_field'] = 'inventory';
    $this->assertFalse(_vae_store_verify_available());
    $this->assertErrors("removed from");
    $this->assertEqual($_SESSION['__v:store']['cart'], array());
  }

}

function helperHook() {
  global $_VAE;
  $_VAE['__test_hooked']++;
}

?>
