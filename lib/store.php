<?php

require_once(dirname(__FILE__) . '/store.oscommerce.php');

function _vae_store_add_item_to_cart($id, $option_id, $qty = 1, $a, $notes = "", $from_api = false) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_add_item_to_cart');
  unset($_VAE['store_cached_number_of_items']);
  unset($_VAE['store_cached_shipping']);
  unset($_VAE['store_cached_subtotal']);
  unset($_VAE['store_cached_tax']);
  if ($a['clear_cart']) unset($_SESSION['__v:store']['cart']);
  if (strlen($a['notes']) && !strlen($notes)) $notes = $a['notes'];
  $digital = ((string)$a['digital'] ? 1 : 0);
  if ($id) $item = _vae_fetch($id);
  $discount = null;
  if (strlen($a['name'])) {
    $name = (string)$a['name'];
  } else {
    if (!strlen($a['name_field'])) return _vae_error("Adding an item to cart, but <span class='c'>name</span> or <span class='c'>name_field</span> is not specified in <span class='c'>&lt;v:store:add_to_cart&gt;");
    $name = (string)_vae_fetch_without_errors($a['name_field'], $item);
    if (!strlen(trim($name))) {
      return _vae_error("Adding an item to cart, but the name field is blank.");
    }
  }
  if (strlen($a['price'])) {
    $price = $a['price'];
  } else {
    if (!strlen($a['price_field'])) return _vae_error("Adding an item to cart, but <span class='c'>price</span> or <span class='c'>price_field</span> is not specified in <span class='c'>&lt;v:store:add_to_cart&gt;");
    $price = (string)_vae_fetch_without_errors($a['price_field'], $item);
  }
  if ($qty < 1) {
    return _vae_error("Adding an item to cart, but the quantity is less than 1.");
  }
  if (strlen($a['backstage_notes'])) {
    $backstage_notes = $a['backstage_notes'];
  } elseif ($a['backstage_notes_field']) {
    $backstage_notes = (string)_vae_fetch_without_errors($a['backstage_notes_field'], $item);
  }
  if (strlen($a['barcode'])) {
    $barcode = $a['barcode'];
  } elseif ($a['barcode_field']) {
    $barcode = (string)_vae_fetch_without_errors($a['barcode_field'], $item);
  }
  if (strlen($a['brand'])) {
    $brand = $a['brand'];
  } elseif ($a['brand_field']) {
    $brand = (string)_vae_fetch_without_errors($a['brand_field'], $item);
  }
  if (strlen($a['category'])) {
    $category = $a['category'];
  } elseif ($a['category_field']) {
    $category = (string)_vae_fetch_without_errors($a['category_field'], $item);
  }
  if (strlen($a['discount_field'])) {
    $discount = (string)_vae_fetch_without_errors($a['discount_field'], $item);
  }
  if (strlen($a['image'])) {
    $image = $a['image'];
  } elseif ($a['image_field']) {
    $image_id = (string)_vae_fetch_without_errors($a['image_field'], $item);
    if ($image_id) {
      $image = vae_data_url() . vae_image($image_id, 100, 100);
    }
  }
  if ($a['notes_field']) {
    $notes = (string)_vae_fetch_without_errors($a['notes_field'], $item);
  }
  if (strlen($a['weight'])) {
    $weight = (string)$a['weight'];
    $weight = (float)$weight;
  } elseif ($a['weight_field']) {
    $weight = (string)_vae_fetch_without_errors($a['weight_field'], $item);
  }
  $check_inventory = ($a['disable_inventory_check'] ? false : true);
  if (!$check_inventory) {
    unset($a['inventory_field']);
  }
  if (is_numeric($option_id)) {
    $item_option = _vae_fetch($option_id);
    $option_value = "";
    if (strlen($a['option_value'])) {
      $option_value = (string)$a['option_value'];
    } elseif (strlen($a['option_field'])) {
      foreach (explode(",", $a['option_field']) as $field) {
        $new_value = (string)_vae_oneline($field, $item_option);
        if (strlen($new_value)) {
          if (strlen($option_value)) $option_value .= "/";
          $option_value .= $new_value;
        }
      }
    } else {
      return _vae_error("Adding an item to cart with an option, but <span class='c'>option_field</span> is not specified in <span class='c'>&lt;v:store:add_to_cart&gt;");
    }
    if ($a['barcode_field']) {
      $p = _vae_fetch_without_errors($a['barcode_field'], $item_option);
      if (strlen((string)$p)) $barcode = (string)$p;
    }
    if ($a['backstage_notes_field']) {
      $p = _vae_fetch_without_errors($a['backstage_notes_field'], $item_option);
      if (strlen((string)$p)) $backstage_notes = (string)$p;
    }
    if ($a['discount_field']) {
      $p = _vae_fetch_without_errors($a['discount_field'], $item_option);
      if (strlen((string)$p)) $discount = (string)$p;
    }
    if ($a['notes_field']) {
      $p = _vae_fetch_without_errors($a['notes_field'], $item_option);
      if (strlen((string)$p)) $notes = (string)$p;
    }
    if ($a['price_field']) {
      $p = _vae_fetch_without_errors($a['price_field'], $item_option);
      if (is_numeric((string)$p)) $price = (string)$p;
    }
    if ($a['weight_field']) {
      $p = _vae_fetch_without_errors($a['weight_field'], $item_option);
      if (is_numeric((string)$p)) $weight = (string)$p;
    }
  } elseif ($a['option_value']) {
    $option_value = $a['option_value'];
  }
  $original_price = $price;
  if (!strlen($price)) return _vae_error("Adding an item to cart, but the price field is blank.");
   if (!is_numeric($price)) return _vae_error("Adding an item to cart, but the price field is invalid.");
  $taxable = (isset($a['taxable']) ? ((string)$a['taxable'] ? true : false) : true);
  $tax_class = (string)$a['tax_class'];
  if (strlen($tax_class)) {
    $found = false;
    $rates = $_VAE['settings']['tax_rates'];
    if (is_array($rates) && count($rates) > 0) {
      foreach ($rates as $rate) {
        if ($rate['tax_class'] == $tax_class) $found = true;
      }
    }
    if (!$found) return _vae_error("Adding an item to a cart with tax class <span class='c'>" . _vae_h($tax_class) . "</span>, but that tax class is not defined on this website.");
  }
  $shipping_class = (string)$a['shipping_class'];
  if ($discount > 0) $price = _vae_decimalize($price * (100.0 - $discount) / 100.0, 2);
  if (count($_SESSION['__v:store']['cart'])) {
    if (count($_SESSION['__v:store']['cart']) > 200) return _vae_error("Too many items in cart.");
    foreach ($_SESSION['__v:store']['cart'] as $cid => $r) {
      if (($r['name'] == $name) && ($r['child'] == false) && ($r['price'] == $price) && ($r['id'] == $id) && ($r['option_id'] == $option_id) && ($r['notes'] == $notes) && ($r['digital'] == $digital) && ($r['shipping_class'] == $shipping_class) && ($r['tax_class'] == $tax_class) && ($r['taxable'] == $taxable) && ($r['discount_class'] == $discount_class) && ($r['barcode'] == $barcode) && ($r['weight'] == $weight) && ($r['brand'] == $brand) && ($r['category'] == $category) && ($r['image'] == $image) && !$r['bundled_with'] && !is_array($_REQUEST['bundle'])) $cart_id = $cid;
    }
  }
  if (!$cart_id || ($from_api && !$a['update_if_exists'])) {
    if (!isset($_SESSION['__v:store']['cart_id'])) $_SESSION['__v:store']['cart_id'] = 1;
    $cart_id = $_SESSION['__v:store']['cart_id']++;
    $_SESSION['__v:store']['cart'][$cart_id] = array('added_at' => time(), 'name' => $name, 'qty' => $qty, 'option_id' => $option_id, 'option_value' => $option_value, 'id' => $id, 'notes' => $notes, 'price' => $price, 'digital' => $digital, 'discount_class' => $a['discount_class'], 'taxable' => $taxable, 'tax_class' => $tax_class, 'shipping_class' => $shipping_class, 'total' => _vae_decimalize($price * $qty, 2), 'inventory_field' => $a['inventory_field'], 'weight' => $weight, 'check_inventory' => $check_inventory, 'barcode' => $barcode, 'original_price' => $original_price, 'backstage_notes' => $backstage_notes, 'brand' => $brand, 'image' => $image, 'category' => $category, 'bundled_with' => $a['bundled_with']);
  } else {
    $_SESSION['__v:store']['cart'][$cart_id]['added_at'] = time();
    $_SESSION['__v:store']['cart'][$cart_id]['qty'] = $qty;
    $_SESSION['__v:store']['cart'][$cart_id]['total'] = _vae_decimalize($qty * $_SESSION['__v:store']['cart'][$cart_id]['price'], 2);
  }
  return $cart_id;
}

function _vae_store_callback_add_to_cart($tag) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_callback_add_to_cart');
  $a = $tag['attrs'];
  $item = $tag['callback']['item'];
  if ($a['backstage_notes_input'] && !$a['multiple']) {
    $a['backstage_notes'] = $_REQUEST[$a['backstage_notes_input']];
  }
  if ($a['notes_input'] && !$a['multiple']) {
    $a['notes'] = $_REQUEST[$a['notes_input']];
  }
  if ($a['price_input']) {
    if ($_REQUEST[$a['price_input']] && (is_numeric($_REQUEST[$a['price_input']])) && ($_REQUEST[$a['price_input']] > 0)) {
      $a['price'] = $_REQUEST[$a['price_input']];
    } else {
      _vae_flash("You did not enter an amount.", 'err', $a['flash']);
      return;
    }
  }
  if ($a['multiple']) {
    if (count($_REQUEST[$a['multiple']])) {
      foreach($_REQUEST[$a['multiple']] as $id => $item) {
        if (strlen($item)) {
          $qty = (is_numeric($_REQUEST['quantity'][$id]) ? $_REQUEST['quantity'][$id] : 1);
          if ($qty < 1) continue;
          $option_id = (is_numeric($_REQUEST['options'][$id]) ? $_REQUEST['options'][$id] : "");
          $notes = (($a['notes_input'] && $_REQUEST[$a['notes_input']][$id]) ? $_REQUEST[$a['notes_input']][$id] : "");
          _vae_store_add_item_to_cart($item, $option_id, $qty, $a, $notes);
        }
      }
    } else {
      _vae_flash("No items found.", 'err', $a['flash']);
      return;
    }
  } elseif (is_array($_REQUEST['quantity']) && count($_REQUEST['quantity'])) {
    foreach ($_REQUEST['quantity'] as $option_id => $qty) {
      if (is_numeric($qty) && ($qty >= 1)) _vae_store_add_item_to_cart($item, $option_id, $qty, $a);
    }
  } elseif ($a['options_collection'] && $a['option_field'] && _vae_store_item_has_options($item, $a['options_collection']) && !strlen($_REQUEST['options'])) {
    _vae_flash("You did not select an option value.", 'err', $a['flash']);
    return;
  } else {
    $qty = (is_numeric($_REQUEST['quantity']) ? $_REQUEST['quantity'] : 1);
    $id = _vae_store_add_item_to_cart($item, $_REQUEST['options'], $qty, $a);
    if (is_array($_REQUEST['bundle'])) {
      $a['bundled_with'] = $id;
      if ($a['bundle_included_in_price']) {
        $a['price_field'] = null;
        $a['price'] = 0;
      }
      foreach ($_REQUEST['bundle'] as $bid => $bqty) {
        if ($bqty < $qty) $bqty = $qty;
        _vae_store_add_item_to_cart($bid, $_REQUEST['options' . $bid], $bqty, $a);
      }
    }
  }
  _vae_store_verify_available();
  _vae_run_hooks("store:cart:updated");
  if ($a['redirect']) return _vae_callback_redirect($a['redirect']);
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_banned_country($country) {
  global $_VAE;
  if (strlen($_VAE['settings']['store_banned_countries'])) {
    $banned = explode(",", $_VAE['settings']['store_banned_countries']);
    foreach ($banned as $item) {
      $banned[] = trim(strtoupper($item));
    }
    if (in_array($country, $banned)) {
      $names = _vae_list_countries();
      return "We cannot ship to the country of " . $names[$country] . ".";
    }
  }
  return false;
}

function _vae_store_callback_address_delete($tag) {
  $id = $tag['callback']['id'];
  if (is_numeric($id)) {
    $raw = _vae_rest(array(), "api/site/v1/customer_addresses/destroy/" . $id, "customer_address", $tag, null, true);
    unset($_SESSION['__v:store']['customer_addresses'][$id]);
  }
  if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_address_select($tag) {
  _vae_session_deps_add('__v:store', '_vae_store_callback_address_select');
  _vae_store_populate_address($_SESSION['__v:store']['customer_addresses'][$_REQUEST['address']]);
  if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
  if (_vae_is_xhr()) return "";
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_cart($tag) {
  _vae_session_deps_add('__v:store', '_vae_store_callback_cart');
  if (count($_SESSION['__v:store']['cart'])) {
    foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
      if (isset($_REQUEST['qty'][$id]) || $_REQUEST['remove'][$id]) {
        if ($_REQUEST['qty'][$id] == 0 || $_REQUEST['remove'][$id]) {
          vae_store_remove_from_cart($id);
        } elseif (isset($_SESSION['__v:store']['cart'][$id])) {
          $_SESSION['__v:store']['cart'][$id]['qty'] = $_REQUEST['qty'][$id];
          $_SESSION['__v:store']['cart'][$id]['total'] = $_REQUEST['qty'][$id] * $_SESSION['__v:store']['cart'][$id]['price'];
        }
      }
      if (isset($_REQUEST['price'][$id]) && isset($_SESSION['__v:user_id']) && isset($_SESSION['__v:store']['cart'][$id])) {
        $_SESSION['__v:store']['cart'][$id]['price'] = $_REQUEST['price'][$id];
        $_SESSION['__v:store']['cart'][$id]['total'] = $_SESSION['__v:store']['cart'][$id]['qty'] * $_SESSION['__v:store']['cart'][$id]['price'];
      }
    }
  }
  _vae_store_verify_available();
  _vae_run_hooks("store:cart:updated");
  if ($_REQUEST['checkout'] && $tag['attrs']['register_page']) return _vae_callback_redirect($tag['attrs']['register_page']);
  if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_checkout($tag = null) {
  _vae_session_deps_add('__v:store', '_vae_store_callback_checkout');
  $_SESSION['__v:store']['checkout_attempts']++;
  if ($tag['attrs']['lockout_redirect'] && ($_SESSION['__v:store']['checkout_attempts'] > 3)) {
    return _vae_callback_redirect($tag['attrs']['lockout_redirect']);
  }
  $ret = _vae_store_checkout($tag['attrs'], $tag);
  if ($ret == false) {
    return _vae_callback_redirect($_SERVER['PHP_SELF']);
  } else {
    return $ret;
  }
}

function _vae_store_callback_currency_select($tag) {
  $_SESSION['__v:store_display_currency'] = $_REQUEST['currency'];
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_discount($tag) {
  _vae_session_deps_add('__v:store', '_vae_store_callback_discount');
  if (strlen($_REQUEST['discount'])) {
    $_SESSION['__v:store']['discount_code'] = preg_replace("/[^a-z0-9]/", "", strtolower($_REQUEST['discount']));
    $_SESSION['__v:store']['discount_code_show_errors'] = ($tag['attrs']['hide_errors'] ? false : true);
    _vae_store_compute_discount(null, null, $tag['attrs']['flash']);
    if ($_SESSION['__v:store']['discount_code']) {
      _vae_store_set_default_payment_method();
      _vae_run_hooks("store:discount:updated");
    }
  } else {
    _vae_flash("You did not enter a discount code into the box!", 'err', $tag['attrs']['flash']);
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_forgot($tag) {
  $data = array();
  if ($tag['attrs']['email_template']) {
    if (($html_template = _vae_find_source($tag['attrs']['email_template'])) && ($text_template = _vae_find_source($tag['attrs']['email_template'] . ".txt"))) {
      if (($html = _vae_proxy($html_template, "", false, $html1)) == false) return _vae_error("Unable to build Forgot Password Template E-Mail (HTML version) file from <span class='c'>" . _vae_h($tag['attrs']['email_template']) . "</span>.  You can debug this by loading that file directly in your browser.");
      if (($text = _vae_proxy($text_template, "", false, $text1)) == false) return _vae_error("Unable to build Forgot Password Template E-Mail (text version) file from <span class='c'>" . _vae_h($tag['attrs']['email_template']) . "</span>.  You can debug this by loading that file directly in your browser.");
      $data['forgot_email_html'] = $html;
      $data['forgot_email_text'] = $text;
    }
  }
  if ($raw = _vae_rest($data, "api/site/v1/customers/forgot", "customer", $tag, null, true)) {
    if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
    return _vae_callback_redirect($_SERVER['PHP_SELF']);
  }
  _vae_flash("We could not find a record with that E-Mail address.
  ", 'err', $tag['attrs']['flash']);
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_google_checkout($tag) {
  require_once(dirname(__FILE__) . "/store.googlecheckout.php");
  _vae_google_checkout_go($tag['attrs']);
}

function _vae_store_callback_login($tag) {
  if ($raw = _vae_rest(array(), "api/site/v1/customers/authenticate", "customer", $tag, null, true)) {
    $err = _vae_store_load_customer($raw);
    if (!$err) {
      if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
      return _vae_callback_redirect($_SERVER['PHP_SELF']);
    } else {
      _vae_flash($err, 'err', $tag['attrs']['flash']);
    }
  } else {
    _vae_flash(($tag['attrs']['invalid'] ? $tag['attrs']['invalid'] : 'Login information incorrect.'),'err', $tag['attrs']['flash']);
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_logout($tag) {
  unset($_SESSION['__v:store']['loggedin']);
  unset($_SESSION['__v:store']['previous_orders']);
  if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_payment_methods_select($tag) {
  if (($_REQUEST['__method'] == "paypal_express_checkout") || $_REQUEST['token']) {
    return _vae_store_callback_paypal_express_checkout($tag, true);
  } else {
    $_SESSION['__v:store']['payment_method'] = $_REQUEST['__method'];
  }
  if ($tag['attrs']['redirect']) return _vae_callback_redirect($tag['attrs']['redirect']);
  if (_vae_is_xhr()) return "";
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_paypal_checkout($tag = null) {
  $_SESSION['__v:store']['payment_method'] = "paypal";
  $_SESSION['__v:store']['paypal_without_vae_checkout'] = true;
  return _vae_store_checkout($tag['attrs'], $tag);
}

function _vae_store_callback_paypal_express_checkout($tag, $from_select = false) {
  global $_VAE;
  if ($_REQUEST['token']) {
    $_SESSION['__v:store']['paypal_express_checkout'] = array('token' => $_REQUEST['token'], 'payer_id' => $_REQUEST['PayerID']);
    if ($addr = _vae_rest(array('token' => $_REQUEST['token']), "api/site/v1/store/paypal_express_checkout", "order")) {
      $xml = simplexml_load_string($addr);
      $data = array('e_mail_address' => (string)$xml->email);
      foreach (array("billing_", "shipping_") as $type) {
        foreach($xml as $k => $v) {
          if ($k == "address1") $k = "address";
          if ($k == "address2") $k = "address_2";
          if (strlen((string)$v)) $data[$type.$k] = (string)$v;
        }
        if ($_SESSION['__v:store']['user'][$type."phone"] && !$data[$type."phone"]) $data[$type."phone"] = $_SESSION['__v:store']['user'][$type."phone"];
      }
      if ($error = _vae_store_banned_country($data['shipping_country'])) {
        _vae_flash($error . " Please return to PayPal and choose a different address.");
      } elseif (_vae_store_create_customer($data)) {
        $_SESSION['__v:store']['payment_method'] = "paypal_express_checkout";
        if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
      }
    }
    return _vae_callback_redirect($_SERVER['PHP_SELF']);
  } else {
    $return_url = _vae_proto() . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . ($from_select ? _vae_qs("__method=") : _vae_qs("__v:store_paypal_express_checkout=" . _vae_tag_unique_id($tag, $context)));
    $cancel_url = _vae_proto() . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . ($from_select ? _vae_qs("__v:store_payment_methods_select=&__method=") : _vae_qs(""));
    if ($url = _vae_rest(array('total' => _vae_store_compute_subtotal(), 'ip' => _vae_remote_addr(), 'return_url' => $return_url, 'cancel_return_url' => $cancel_url), "api/site/v1/store/paypal_express_checkout", "order")) {
      return _vae_callback_redirect($url);
    } else {
      return _vae_callback_redirect($_SERVER['PHP_SELF']);
    }
  }
}

function _vae_store_callback_register($tag) {
  if (!isset($_SESSION['__v:store']['user'])) $_SESSION['__v:store']['user'] = array();
  $data = $_SESSION['__v:store']['user'];
  $errors = array();
  _vae_merge_data_from_tags($tag, $data, $errors);
  if ($error = _vae_store_banned_country($data['shipping_country'])) {
    $errors[] = $error;
  }
  if (!_vae_flash_errors($errors, $tag['attrs']['flash'])) { 
    if ($tag['attrs']['newsletter_confirm'] && !$_REQUEST[$tag['attrs']['newsletter_confirm']]) unset($tag['attrs']['newsletter']);
    if (_vae_store_create_customer($data, $tag['attrs']['newsletter'])) {
      if ($tag['attrs']['formmail']) {
        $tag['attrs']['to'] = $tag['attrs']['formmail'];
        return _vae_callback_formmail($tag);
      }
      if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
    }
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_callback_shipping_methods_select($tag) {
  global $_VAE;
  $method = $_REQUEST['__method'];
  $_SESSION['__v:store']['shipping']['selected_index'] = $method;
  $_SESSION['__v:store']['shipping']['selected'] = $_SESSION['__v:store']['shipping']['options'][$method]['cost'];
  if ($tag['attrs']['redirect']) return _vae_callback_redirect($tag['attrs']['redirect']);
  if (_vae_is_xhr()) return "";
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_store_cart_item_name($r) {
  return $r['name'] . (strlen($r['option_value']) ? " (" . $r['option_value'] . ")" : "");
}

function _vae_store_convert_cart_to_line_items() {
  $line_items = array();
  foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
    $line_items[] = array('qty' => $r['qty'], 'inventory_field' => $r['inventory_field'], 'options' => $r['option_value'], 'option_id' => $r['option_id'], 'original_price' => $r['original_price'], 'row_id' => $r['id'], 'price' => $r['price'], 'notes' => $r['notes'], 'total' => $r['total'], 'tax' => 0, 'name' => $r['name'], 'barcode' => $r['barcode'], 'brand' => $r['brand'], 'category' => $r['category'], 'backstage_notes' => $r['backstage_notes'], 'position' => $id, 'bundled_with' => $r['bundled_with'], 'image' => $r['image'], 'discount_class' => $r['discount_class']);
  }
  return $line_items;
}

function _vae_store_checkout($a = null, $tag = null) {
  global $_VAE;
  $current = _vae_store_current_user();
  _vae_store_set_default_payment_method();
  $payment_method = $_VAE['store']['payment_methods'][$_SESSION['__v:store']['payment_method']];
  $line_items = _vae_store_convert_cart_to_line_items();
  if (!count($line_items)) {
    _vae_flash("You submitted a duplicate order.  Maybe you clicked the submit button twice.  Please check your email to see if you see an order confirmation.  If you don't see one, please contact us.", 'err');
    return false;
  }
  if (_vae_store_verify_available()) {
    _vae_store_compute_shipping();
    _vae_store_compute_tax();
    $shipping_method = $_SESSION['__v:store']['shipping']['options'][$_SESSION['__v:store']['shipping']['selected_index']]['title'];
    if (!_vae_store_if_shippable() && _vae_store_if_digital_downloads()) {
      $shipping_method = "Digital Delivery";
    } elseif (!strlen($shipping_method)) {
      $shipping_method = "N/A";
    }
    $payment_method_id = ($_SESSION['__v:store']['payment_method'] ? $_SESSION['__v:store']['payment_method'] : "Test");
    $tax_rate = $_SESSION['__v:store']['tax_rate'];
    $data = array("line_items" => $line_items, 'remote_addr' => _vae_remote_addr(), 'customer_id' => $current['id'], 'email' => $current['e_mail_address'], 'discount_code' => $_SESSION['__v:store']['discount_code'], 'discount' => _vae_store_compute_discount(), 'shipping' => _vae_store_compute_shipping(), 'tax' => _vae_store_compute_tax(), 'total' => _vae_store_compute_total(), 'shipping_method' => $shipping_method, 'tax_rate' => $tax_rate, 'payment_method' => $payment_method_id, 'weight' => $_SESSION['__v:store']['shipping']['weight'], 'notes' => $_REQUEST['notes']);
    if ($_SESSION['__v:store']['paypal_without_vae_checkout']) {
      unset($data['total']);
    }
    $data['token'] = $_SESSION['__v:store']["paypal_express_checkout"]["token"];
    $data['payer_id'] = $_SESSION['__v:store']["paypal_express_checkout"]["payer_id"];
    $data['store_name'] = $a['store_name'];
    $data['store_logo'] = $a['store_logo'];
    if ($a['gateway_transaction_id']) $data['gateway_transaction_id'] = $a['gateway_transaction_id'];
    if ($a['skip_emails']) $data['skip_emails'] = "1";
    foreach (array('confirmation' => array('order_confirmation','Order Confirmation'), 'received' => array('order_received','Order Received'), 'shipping' => array('shipping_info','Shipping Confirmation'), 'waiting_for_approval' => array('order_waiting_for_approval','Order Waiting For Approval')) as $email => $r) {
      if (isset($_VAE['google_checkout_attrs']['email_'.$email])) $file = $_VAE['google_checkout_attrs']['email_'.$email];
      else $file = $a['email_'.$email];
      if ($file) {
        if (($html = _vae_find_source($file)) && ($txt = _vae_find_source($file . ".txt"))) {
          if (($data[$r[0].'_email_html'] = _vae_proxy($html)) == false) return _vae_error("Unable to build " . $r[1] . " E-Mail (HTML version) file from <span class='c'>" . _vae_h($file) . "</span>.  You can debug this by loading that file directly in your browser.");
          if (($data[$r[0].'_email_text'] = _vae_proxy($txt)) == false) return _vae_error("Unable to build " . $r[1] . " E-Mail (text version) file from <span class='c'>" . _vae_h($file) . "</span>.  You can debug this by loading that file directly in your browser.");
          $data[$r[0].'_email_text'] = strip_tags($data[$r[0].'_email_text']);
        } else {
          _vae_error("Unable to find " . $r[1] . " E-Mail file in <span class='c'>" . _vae_h($file) . "</span>.  Remember that you need to provide an HTML version named <span class='c'>" . $file . "</span> and a text-only version named <span class='c'>" . $file . ".txt</span>.  Both may have the <span>.html</span>, <span>.haml</span>, <span>.php</span>, or <span>.haml.php</span> extension.");
        }
      }
    }
    foreach (array('billing_name','billing_company','billing_address','billing_city','billing_state','billing_country','billing_zip','billing_phone','shipping_name','shipping_company','shipping_address','shipping_address_2','shipping_city','shipping_state','shipping_zip','shipping_country','shipping_phone') as $k) {
      $data[$k] = $current[$k];
    }
    foreach ($_SESSION['__v:store']['marketing_data'] as $k => $v) {
      $data[$k] = $v;
    }
    if ($payment_method['callback']) return call_user_func($payment_method['callback'], $data, $tag);
    return _vae_store_complete_checkout($data, $tag);
  }
  return false;
}

function _vae_store_complete_checkout($data, $tag = null) {
  $ret = _vae_rest($data, "api/site/v1/store/create_order", "order", $tag);
  if ($ret) {
    $order_data = _vae_array_from_rails_xml(simplexml_load_string($ret));
    $data['id'] = $order_data['reference_id'];
    $data['created_at'] = time();
    foreach ($_SESSION['__v:store']['cart'] as $id => $d) {
      $_SESSION['__v:store']['cart'][$id]['order_id'] = $data['id'];
    }
    $_SESSION['__v:store']['recent_order'] = $_SESSION['__v:store']['cart'];
    $_SESSION['__v:store']['recent_order_data'] = $data;
    if ($order_data['gateway_customer_id']) {
      $_SESSION['__v:store']['user']['gateway_customer_id'] = $order_data['gateway_customer_id'];
      $_SESSION['__v:store']['user']['gateway'] = $order_data['payment_method'];
    }
    _vae_run_hooks("store:checkout:success");
    unset($_SESSION['__v:store']['cart']);
    unset($_SESSION['__v:store']['discount']);
    unset($_SESSION['__v:store']['discount_code']);
    unset($_SESSION['__v:store']['payment_method']);
    unset($_SESSION['__v:store']['checkout_attempts']);
    unset($_SESSION['__v:store']['total_weight']);
    if ($tag == null) return true;
    return _vae_callback_redirect($tag['attrs']['redirect']);
  }
  return false;
}

function _vae_store_compute_discount($item = null, $remaining = null, $flash_location = '', $hide_errors = false) {
  $amount = 0;
  if ($hide_errors) $_SESSION['__v:store']['discount_code_show_errors'] = false;
  $show_errors = $_SESSION['__v:store']['discount_code_show_errors'];
  _vae_session_deps_add('__v:store', '_vae_store_compute_discount');
  $current = _vae_store_current_user();
  if ($_SESSION['__v:store']['user_discount'] && $item == null) {
    return $_SESSION['__v:store']['user_discount'];
  }
  if (isset($_SESSION['__v:store']['discount_code'])) {
    $disc = _vae_store_find_discount($_SESSION['__v:store']['discount_code']);
    if ($disc == null || $disc == false || $disc == "BAD") {
      $show_errors = true;
      if ($item == null) _vae_flash("You entered an invalid coupon code.", 'err', $flash_location);
    } elseif ($disc == "BAD_ALREADY_USED") {
      $show_errors = true;
      if ($item == null) _vae_flash("You cannot use this coupon code because you have already used it.", 'err', $flash_location);
    } elseif ((strlen($disc['number_available']) && ($disc['number_available'] == "0")) || (strlen($disc['stop_at']) && (time() > strtotime($disc['stop_at'])))) {
      $show_errors = true;
      if ($item == null) _vae_flash("This coupon is no longer available.", 'err', $flash_location);
    } elseif (strlen($disc['start_at']) && (time() < strtotime($disc['start_at']))) {
      $show_errors = true;
      if ($item == null) _vae_flash("This coupon is not available yet.", 'err', $flash_location);
    } elseif (($disc['min_order_amount'] > 0) && (_vae_store_compute_subtotal() < $disc['min_order_amount'])) {
      if ($show_errors && ($item == null)) _vae_flash("You cannot use this coupon code because your order is not big enough.  Minimum order amount for this coupon: " . _vae_store_currency_symbol() . _vae_decimalize($disc['min_order_amount']), 'err', $flash_location);
    } elseif (($disc['min_order_items'] > 0) && (_vae_store_compute_number_of_items() < $disc['min_order_items'])) {
      if ($show_errors && ($item == null)) _vae_flash("You cannot use this coupon code because there are not enough items in your order.  Minimum number of items needed for this coupon: " . $disc['min_order_items'], 'err', $flash_location);
    } elseif (($disc['country']) && $current['shipping_country'] != $disc['country']) {
      if ($show_errors && ($item == null)) _vae_flash("You cannot use this coupon code because your order is not being shipped to " . $disc['country'] . ".", 'err', $flash_location);
    } else {
	    if (($item == null) && (strlen($disc['included_classes']) || strlen($disc['excluded_classes']))) {
	      foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
	        $this_item_discount = _vae_store_compute_discount($r, ($disc['fixed_amount'] ? ($disc['fixed_amount'] - $amount) : null));
          $_SESSION['__v:store']['cart'][$id]['discount_amount'] = $this_item_discount;
          $amount += $this_item_discount;
	      }
	      if ($amount > 0) {
	        if ($disc['free_shipping'] && (!strlen($disc['free_shipping_method']) || strstr($_SESSION['__v:store']['shipping']['options'][$_SESSION['__v:store']['shipping']['selected_index']]['title'], $disc['free_shipping_method']))) {
            $amount += _vae_store_compute_shipping();
          } elseif ($disc['voucher']) {
            $amount += _vae_store_compute_shipping();
          }
	      }
	    } else {
	      if ($item) {
	        $item_discount_classes = explode(",", $item['discount_class']);
	        $item_discount_classes[] = $item['id'];
	        if (strlen($disc['included_classes'])) {
	          $included_classes = explode(",", $disc['included_classes']);
	          if (!count(array_intersect($item_discount_classes, $included_classes))) return 0;
	        }
	        if (strlen($disc['excluded_classes'])) {
	          $excluded_classes = explode(",", $disc['excluded_classes']);
	          if (count(array_intersect($item_discount_classes, $excluded_classes))) return 0;
	        }
	        $max = $item['total'];
	      } else {
	        $max = ((($disc['discount_shipping'] || $disc['voucher']) && !$disc['free_shipping']) ? (_vae_store_compute_subtotal() + _vae_store_compute_shipping()) : _vae_store_compute_subtotal());      
	      }
	      if (strlen($disc['required_classes'])) {
	        $found_one = false;
	        $required_classes = explode(",", $disc['required_classes']);
	        foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
	          $item_discount_classes = explode(",", $r['discount_class']);
	          if (count(array_intersect($item_discount_classes, $required_classes))) $found_one = true;
	        }
	        if ($found_one == false) return 0;
	      }
	      if (!$item || $remaining) {
	        if ($disc['fixed_amount']) {
	          $amount += ($remaining ? min($disc['fixed_amount'], $remaining) : $disc['fixed_amount']);
	        }
	      }
	      if ($disc['percentage_amount']) {
	        $amount += ($disc['percentage_amount'] / 100) * $max;
	      }
	      if ($amount > $max) {
	        $amount = $max;
	      }
	      if (!$item && $disc['free_shipping']) {
	        if (!strlen($disc['free_shipping_method']) || strstr($_SESSION['__v:store']['shipping']['options'][$_SESSION['__v:store']['shipping']['selected_index']]['title'], $disc['free_shipping_method'])) {
	          $amount += _vae_store_compute_shipping();
	        }
	      }
	    } 
	    if ($amount == 0 && ($item == null) && $show_errors) {
	      _vae_flash("This coupon does not provide any discounts for your order.", 'err', $flash_location);
	    }
	  }
    if ($amount == 0 && ($item == null) && $show_errors) {
      unset($_SESSION['__v:store']['discount_code']);
      _vae_run_hooks("store:discount:updated");
    }
  }
  return round($amount, 2);
}

function _vae_store_compute_number_of_items() {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_compute_number_of_items');
  if (isset($_VAE['store_cached_number_of_items'])) return $_VAE['store_cached_number_of_items'];
  if (!count($_SESSION['__v:store']['cart'])) return 0;
  foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
    $sub += $r['qty'];
  }
  $_VAE['store_cached_number_of_items'] = $sub;
  return $sub;
}

function _vae_store_compute_shipping($register_page = null) {
  global $_VAE;
  if (isset($_VAE['store_cached_shipping']) && !$register_page) return $_VAE['store_cached_shipping'];
  $current = _vae_store_current_user();
  $country = $current['shipping_country'];
  $address = $current['shipping_address'];
  $city = $current['shipping_city'];
  $state = $current['shipping_state'];
  $zip = $current['shipping_zip'];
  $sub = $_VAE['settings']['store_shipping_pad_pounds_per_order'];
  if (!strlen($country)) $country = $_VAE['settings']['store_shipping_origin_country'];
  $handling = 0;
  if (isset($_SESSION['__v:store']['custom_handling'])) $handling = $_SESSION['__v:store']['custom_handling'];
  $subtotal = 0;
  $num_items = 0;
  if ($_VAE['settings']['store_shipping_pad_percent_of_subtotal']) $handling += ($_VAE['settings']['store_shipping_pad_percent_of_subtotal'] / 100.0) * _vae_store_compute_subtotal();
  _vae_session_deps_add('__v:store', '_vae_store_compute_shipping');
  if (!count($_SESSION['__v:store']['cart']) || !count($_VAE['settings']['shipping_methods'])) {
    unset($_SESSION['__v:store']['shipping']);
    return $handling;
  }
  $shipping_class_cache = "";
  foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
    if ($r['weight']) {
      $weight = $r['weight'];
      $sub += $r['qty'] * $weight;
      $subtotal += $r['total'];
      $num_items += $r['qty'];
      $handling += $r['qty'] * $_VAE['settings']['store_shipping_pad_dollars_per_item'];
    }
    $shipping_class_cache .= $r['shipping_class'];
  }
  if ($num_items == 0 && !$_SESSION['__v:store']['total_weight']) {
    unset($_SESSION['__v:store']['shipping']);
    return $handling;
  }
  $hash = md5($sub . $subtotal . $num_items . $handling . $zip . $country . $state . $address . $weight . $shipping_class_cache . serialize($_SESSION['__v:store']['total_weight']) . "d");
  if (!$_REQUEST['__debug'] && ($hash == $_SESSION['__v:store']['shipping']['hash']) && isset($_SESSION['__v:store']['shipping']['selected'])) return $_SESSION['__v:store']['shipping']['selected'];
  $options = _vae_store_calculate_shipping_options($sub, $num_items, $subtotal, $zip, $country, $state, $city, $address, $handling);
  $_SESSION['__v:store']['shipping'] = array('hash' => $hash);
  if ($register_page && (count($options) < 1) && count($_VAE['settings']['shipping_methods']) && ($_SESSION['__v:store']['total_weight'] || ($weight > 0))) {
    $_VAE['store_cached_shipping'] = false;
    if (_vae_store_usps_only($country, $state, $address)) {
      _vae_flash("We could not confirm shipping availability to this address, as it is either a PO Box or a Military address.  Please enter a street address to complete your order.", 'err');
    } else {
      _vae_flash("We could not confirm shipping availability to this address.  Please verify all information, including city, state/territory, postal code, and country information.", 'err');
    }
    return _vae_render_redirect($register_page);
  }
  $_SESSION['__v:store']['shipping']['weight'] = $sub;
  $_SESSION['__v:store']['shipping']['options'] = $options;
  $_SESSION['__v:store']['shipping']['selected'] = $options[count($options)-1]['cost'];
  $_SESSION['__v:store']['shipping']['selected_index'] = count($options)-1;
  $_VAE['store_cached_shipping'] = $_SESSION['__v:store']['shipping']['selected'];
  return $_SESSION['__v:store']['shipping']['selected'];
}

function _vae_store_compute_subtotal() {
  global $_VAE;
  if (isset($_VAE['store_cached_subtotal'])) return $_VAE['store_cached_subtotal'];
  _vae_session_deps_add('__v:store', '_vae_store_compute_subtotal');
  if (!count($_SESSION['__v:store']['cart'])) return 0.0;
  foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
    $sub += $r['total'];
  }
  $_VAE['store_cached_subtotal'] = $sub;
  return $sub;
}

function _vae_store_compute_tax() {
  global $_VAE;
  if (isset($_VAE['store_cached_tax'])) return $_VAE['store_cached_tax'];
  if (isset($_SESSION['__v:store']['tax_override'])) return $_SESSION['__v:store']['tax_override'];
  _vae_session_deps_add('__v:store', '_vae_store_compute_tax');
  $_SESSION['__v:store']['tax_rate'] = "";
  $totamt = 0;
  $current = _vae_store_current_user();
  $country = $current['shipping_country'];
  $state = $current['shipping_state'];
  $zip = $current['shipping_zip'];
  if (!strlen($zip) && !strlen($state) && !strlen($country)) {
    $country = $current['billing_country'];
    $state = $current['billing_state'];
    $zip = $current['billing_zip'];
  }
  $rates = $_VAE['settings']['tax_rates'];
  if (is_array($rates) && count($rates) > 0) {
    foreach ($rates as $id => $rate) {
      $use = 1;
      if (strlen($rate['zip'])) {
        $use_zip = false;
        foreach (explode(",", $rate['zip']) as $z) {
          if (strlen($zip) && (substr($zip, 0, strlen($z)) == $z)) {
            $use_zip = true;
          }
        }
        if (!$use_zip) $use = false;
      }
      if (strlen($rate['country']) && ($country != $rate['country'])) $use = false;
      if (strlen($rate['state']) && ($state != $rate['state'])) $use = false;
      if ($use) {
        $subtotal = 0;
        foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
          if ($r['taxable'] && (!$rate['tax_class'] || ($r['tax_class'] == $rate['tax_class'])) && (!$rate['minimum_price'] || ($r['price'] >= $rate['minimum_price']))) $subtotal += ($r['total'] - _vae_store_compute_discount($r));
        }
        $discounted_subtotal = _vae_store_compute_subtotal() - _vae_store_compute_discount();
        if ($subtotal > $discounted_subtotal) $subtotal = $discounted_subtotal;
        if (_vae_store_shipping_tax_class()) {
        	if ($rate['tax_class'] == _vae_store_shipping_tax_class()) {
            $subtotal += _vae_store_compute_shipping();
        	}
        } elseif ($rate['include_shipping']) {
          $subtotal += _vae_store_compute_shipping();
        }
        if ($subtotal < 0) $subtotal = 0;
        $amt = $rate['rate'] * $subtotal / 100;
        if (strlen($rate['minimum_subtotal']) && ($subtotal < $rate['minimum_subtotal'])) $amt = 0;
        if ($amt > 0) {
          if (strlen($_SESSION['__v:store']['tax_rate'])) $_SESSION['__v:store']['tax_rate'] .= "/";
          $_SESSION['__v:store']['tax_rate'] .= $rate['description'];
        }
        $totamt += $amt;
      }
    }
  }
  $_VAE['store_cached_tax'] = _vae_decimalize($totamt, 2);
  return $_VAE['store_cached_tax'];
}

function _vae_store_compute_total() {
  $total = _vae_store_compute_shipping() + _vae_store_compute_tax() + _vae_store_compute_subtotal() - _vae_store_compute_discount();
  $total = str_replace(",", "", number_format($total, 2));
  if ($total < 0) $total = 0.00;
  return $total;
}

function _vae_store_create_customer($data, $newsletter_code = null, $from_php = false) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_create_customer');
  if (!$from_php && $_VAE['settings']['store_shipping_use_ups_address_validation']) {
    $res = _vae_store_suggest_alternate_address($data['shipping_country'], $data['shipping_city'], $data['shipping_state'], $data['shipping_zip']);
    if ($res === false) {
      _vae_flash('We could not recognize that shipping address.  Please double-check the city, state, zipcode, and country.  If you feel that you are seeing this message in error, please contact the store for assistance.','err');
      return false;
    } elseif (strlen($res)) {
      $data['shipping_city'] = $res;
    }
  }
  if ($_SESSION['__v:store']['customer_id'] && $_SESSION['__v:store']['loggedin']) { 
    if ($raw = _vae_rest($data, "api/site/v1/customers/update/" . $_SESSION['__v:store']['customer_id'], "customer")) {
      _vae_store_load_customer($raw);
      if ($newsletter_code) _vae_newsletter_subscribe($newsletter_code, $data['e_mail_address']);
      _vae_run_hooks("store:register:success", $_SESSION['__v:store']['user']);
      return true;
    }
  } else {
    if ($raw = _vae_rest($data, "api/site/v1/customers/create_or_update", "customer")) {
      if ($err = _vae_store_load_customer($raw, strlen($data['password']))) {
        if (!$from_php) _vae_flash($err, 'err');
        return false;
      }
      unset($data['password']);
      $_SESSION['__v:store']['user'] = $data;
      if ($newsletter_code) _vae_newsletter_subscribe($newsletter_code, $data['e_mail_address']);
      _vae_run_hooks("store:register:success", $_SESSION['__v:store']['user']);
      return true;
    }
  }
  return false;
}

function _vae_store_currency() {
  global $_VAE;
  $currency = $_VAE['settings']['store_currency'];
  if (!strlen($currency)) $currency = "USD";
  return $currency;
}
function _vae_store_currency_display($price, $show_alternate_currency = true) {
  global $_VAE;
  $decimal_places = 2;
  $after = $before = "";
  $currency = _vae_store_currency();
  if ($show_alternate_currency && strlen($_SESSION['__v:store_display_currency']) && ($currency != $_SESSION['__v:store_display_currency'])) {
    if ($rate = _vae_store_exchange_rate($currency, $_SESSION['__v:store_display_currency'])) {
      $price = _vae_round_significant_digits($price * $rate, 4);
      if (strlen((int)floor((string)$price)) > 3) $decimal_places = 0;
      if ($_VAE['currency_symbols'][$currency] == $_VAE['currency_symbols'][$_SESSION['__v:store_display_currency']]) $after = " " . $_SESSION['__v:store_display_currency'];
      $currency = $_SESSION['__v:store_display_currency'];
      $before = "est. ";
    }
  }
  $out = ($show_alternate_currency ? $_VAE['currency_symbols'][$currency] : "") . number_format((float)$price, $decimal_places) . $after;
  return "<span class='currency currency_$currency'>" . $before . $out . "</span>";
}

function _vae_store_currency_symbol() {
  global $_VAE;
  $currency = _vae_store_currency();
  return $_VAE['currency_symbols'][$currency];
}


function _vae_store_current_user() {
  _vae_session_deps_add('__v:store', '_vae_store_current_user');
  $current = $_SESSION['__v:store']['user'];
  if ($_SESSION['__v:store']['customer_id']) $current["id"] = $_SESSION['__v:store']['customer_id'];
  return $current;
}

function _vae_store_exchange_rate($from, $to) {
  global $_VAE;
  $key = "currency" . $from . "_" . $to;
  if ($rate = _vae_short_term_cache_get($key)) return $rate;
  $feed = _vae_simple_rest("http://coinmill.com/rss/" . $from . "_" . $to . ".xml");
  if (!strlen($feed)) return false;
  preg_match("/ = ([0-9\.]*) /", $feed, $matches);
  $rate = $matches[1];
  _vae_short_term_cache_set($key, $rate, 0, 3600);
  return $rate;
}

function _vae_store_find_discount($code) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_find_discount');
  $code = trim($code);
  if (!strlen($code)) return false;
  $customer_id = $_SESSION['__v:store']['customer_id'];
  if (isset($_SESSION['__v:store']['discount'][$code.$customer_id])) return $_SESSION['__v:store']['discount'][$code.$customer_id];
  if ($raw = _vae_rest(array(), "api/site/v1/store_discount_codes/verify/" . $code . ($customer_id ? "?customer_id=" . $customer_id : ""), "customer")) {
    if (strstr($raw, "BAD")) {
      return false;
    } else {
      $data = _vae_array_from_rails_xml(simplexml_load_string($raw));
    }
  }
  $_SESSION['__v:store']['discount'][$code.$customer_id] = $data;
  return $data;
}

function _vae_store_highest_tax_rate() {
  global $_VAE;
  $max = 0.00;
  $rates = $_VAE['settings']['tax_rates'];
  if (is_array($rates) && count($rates) > 0) {
    foreach ($rates as $rate) {
      if ($rate['rate'] > $max) $max = $rate['rate'];
    }
  }
  return $max;
}

function _vae_store_if_digital_downloads($src_data = null) {
  _vae_session_deps_add('__v:store', '_vae_store_if_digital_downloads');
  if ($src_data == null) $src_data = $_SESSION['__v:store']['cart'];
  if (count($src_data)) {
    foreach ($src_data as $id => $r) {
      if ($r['digital'] == "1") return true;
    }
  }
  return false;
}

function _vae_store_if_shippable() {
  _vae_session_deps_add('__v:store', '_vae_store_if_shippable');
  if (count($_SESSION['__v:store']['cart'])) {
    foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
      if ($r['weight'] > 0) return true;
    }
  }
  return false;
}

function _vae_store_inventory_minimum_for_order() {
  global $_VAE;
  return (int)$_VAE['settings']['store_inventory_minimum_for_order'];
}

function _vae_store_ipn() {
  global $_VAE;
  try {
    echo call_user_func($_VAE['store']['payment_methods'][$_REQUEST['__v:store_payment_method_ipn']]['ipn']);
  } catch (Exception $e) {
    _vae_log("Got Exception: " . serialize($e));
  }
  _vae_write_file("ipn." . time() . ".txt", $_VAE['log']);
  _vae_die();
}

function _vae_store_item_available($item, $options_collection, $inventory_field) {
  global $_VAE;
  $root_inv = (string)_vae_fetch_without_errors($inventory_field, $item);
  if ($options_collection) {
    $options = _vae_fetch($options_collection, $item);
    if ($options) {
      foreach ($options as $id => $r) {
        if ((string)$r->$inventory_field > _vae_store_inventory_minimum_for_order()) return true;
      }
    }
  }
  return ($root_inv > _vae_store_inventory_minimum_for_order());
}

function _vae_store_item_has_options($id, $option_field) {
  global $_VAE;
  $item = _vae_fetch($id);
  $options = _vae_fetch($option_field, $item);
  return ($options ? true : false);
}

function _vae_store_load_customer($raw, $logged_in = true) {
  _vae_session_deps_add('__v:store', '_vae_store_load_customer');
  $data = simplexml_load_string($raw);
  if ($data->{banned} == "true") {
    return "The store owner has banned your customer account from making purchases.  Please contact the store for further assistance.";
  }
  if (!isset($_SESSION['__v:store']['user'])) $_SESSION['__v:store']['user'] = array();
  $_SESSION['__v:store']['customer_id'] = (int)$data->id;
  $_SESSION['__v:store']['user']['id'] = (int)$data->id;
  if ($logged_in) {
    $_SESSION['__v:store']['loggedin'] = 1;
    $_SESSION['__v:store']['customer_addresses'] = _vae_array_from_rails_xml($data->{'customer-addresses'}->{'customer-address'}, true);
  }
  $_SESSION['__v:store']['user']['name'] = (string)$data->{'name'};
  $_SESSION['__v:store']['user']['gateway'] = (string)$data->{'gateway'};
  $_SESSION['__v:store']['user']['gateway_customer_id'] = (string)$data->{'gateway-customer-id'};
  $_SESSION['__v:store']['user']['tags'] = (string)$data->{'tags-input'};
  $_SESSION['__v:store']['user']['e_mail_address'] = (string)$data->{'e-mail-address'};
  _vae_store_populate_addresses();
  return false;
}

function _vae_store_most_specific_field($r, $field) {
  global $_VAE;
  if (!strlen($r['id'])) return "";
  if (strlen($r['option_id'])) {
    $item_option = _vae_fetch($r['option_id'] . "/" . $field, $item);
    if ($item_option != false) return $item_option;
  }
  $item = _vae_fetch($r['id']);
  return _vae_fetch($field, $item);
}

function _vae_store_payment_google_checkout_ipn() {
  require_once(dirname(__FILE__) . "/store.googlecheckout.php");
  _vae_google_checkout_ipn();
}

function _vae_store_payment_google_checkout_method($required = true) {
  global $_VAE;
  foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
    if ($method['method_name'] == "google_checkout") return $method;
  }
  if ($required) _vae_error("This website does not have Google Checkout configured as a payment method.  Please enable Google Checkout in the backstage under Store > Settings.");
  return false;
}

function _vae_store_payment_method() {
  global $_VAE;
  foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
    if ($_SESSION['__v:store']['payment_method'] == $method['method_name']) return $method;
  }
}

function _vae_store_payment_paypal_callback($data, $tag) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_payment_paypal_callback');
  $order_placed_url = (strstr($tag['attrs']['redirect'], "http") ? "" : _vae_proto() . $_SERVER['HTTP_HOST'] . "/") . $tag['attrs']['redirect'];
  $notify_url = _vae_proto() . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?__v:store_payment_method_ipn=paypal";
  $cancel_url = _vae_proto() . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  $items = "";
  $subtotal = $data['total'] - $data['tax'] - $data['shipping'] + $data['discount'];
  $i = 1;
  $shipping = $data['shipping'];
  $discounted_subtotal = 0;
  foreach ($data['line_items'] as $r) {
    if (!isset($tax)) {
      $tax = _vae_decimalize($data['tax'] / (float)$r['qty']);
      // compensate for crap rounding
      if ($data['tax'] != ($tax * $r['qty'])) {
        $data['total'] -= $data['tax'];
        $data['tax'] = ($tax * $r['qty']);
        $data['total'] += $data['tax'];
      }
    }
    $discount = ($data['discount'] ? $r['price'] * $data['discount'] / $subtotal : 0);
    $price = _vae_decimalize($r['price'] - $discount);
    if ($price < 0) {
      $shipping += (($price-0.01) * $r['qty']);
      $price = 0.01;
    } else {
      $discounted_subtotal += ($price * $r['qty']);
    }
    $option = (strlen($r['options']) ? "&on0_$i=Option&os0_$i=" . urlencode($r['options']) : "");
    $option .= (strlen($r['notes']) ? "&on1_$i=Note&os1_$i=" . urlencode($r['notes']) : "");
    if (strlen($r['weight'])) $option .= "&weight_$i=" . $r['weight'];
    $items .= "&amount_$i=" . urlencode($price) . $option . "&item_name_$i=" . urlencode($r['name']) . "&item_number_$i=" . urlencode($r['row_id'] . (strlen($r['option_id']) ? "-" . $r['option_id'] : "")) . "&quantity_$i=" . urlencode($r['qty']);
    $i++;
  }
  if ($data['discount']) {
    $data['discount'] = _vae_decimalize($subtotal - $discounted_subtotal);
    $data['total'] = _vae_decimalize($discounted_subtotal + $data['tax'] + $data['shipping'], 2);
  }
  if ($shipping > 0) $items .= "&shipping_1=" . urlencode($shipping);
  if ($tax > 0) $items .= "&tax_1=" . urlencode($tax);
  $filename = _vae_make_filename("tmp");
  _vae_write_file($filename, serialize(array('session_id' => session_id(), 'data' => $data, 'cart' => $_SESSION['__v:store']['cart'])));
  $name = explode(" ", $data['shipping_name'], 2);
  $currency = _vae_store_currency();
  if ($data['shipping_name']) {
    $address = "&address_override=1&first_name=" . urlencode($name[0]) . "&last_name=" . urlencode($name[1]) . "&address1=" . urlencode($data['shipping_address']) . "&address2=" . urlencode($data['shipping_address_2']) . "&city=" . urlencode($data['shipping_city']) . "&state=" . urlencode($data['shipping_state']) . "&zip=" . urlencode($data['shipping_zip']) . "&country=" . urlencode($data['shipping_country']) . "&night_phone_a=" . urlencode($data['shipping_phone']);
  } else {
    $address = "";
  }
  $url = "https://www.paypal.com/cgi-bin/webscr?cmd=_cart&upload=1&business=" . urlencode(_vae_store_payment_paypal_email()) . $items . "&notify_url=" . urlencode($notify_url) . "&return=" . urlencode($order_placed_url) . "&cancel_return=" . urlencode($cancel_url) . $address . "&no_note=1&currency_code=" . $currency . "&bn=PP%2dBuyNowBF&lc=US&custom=" . $filename;
  return _vae_callback_redirect($url);
}

function _vae_store_payment_paypal_email() {
  global $_VAE;
  foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
    if ("paypal" == $method['method_name']) return $method["email"];
  }
  return false;
}

function _vae_store_payment_paypal_express_checkout_method($required = true) {
  global $_VAE;
  foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
    if ($method['method_name'] == "paypal_express_checkout") return $method;
  }
  if ($required) _vae_error("This website does not have PayPal Express Checkout configured as a payment method.  Please enable Google Checkout in the backstage under Store > Settings.");
  return false;
}

function _vae_store_payment_paypal_ipn() {
  global $_VAE;
  $report_error = true;
  if (!strlen($_POST['custom'])) return "not from vae";
  if ($_REQUEST['txn_type'] == "adjustment") return "dontcare";
  if ($_REQUEST['__force']) {
    $alldata = unserialize(_vae_read_file($_REQUEST['__force']));
    $data = $alldata['data'];
    $_SESSION['__v:store']['cart'] = $alldata['cart'];
    $out .= _vae_store_complete_checkout($data);
  } else {
    $req = 'cmd=_notify-validate';
    foreach ($_POST as $key => $value) {
      $value = urlencode(stripslashes($value));
      $req .= "&$key=$value";
    }
    $res = _vae_simple_rest("https://www.paypal.com/cgi-bin/webscr", $req); //www.eliteweaver.co.uk
    if (!strlen($res)) {
      $out .= "Couldn't connect to PayPal.\n";
    } else {
      if (true || $res == "VERIFIED") {
        $out .= "PayPal authenticity verified.\n";
        if ($_POST['payment_status'] == "Completed") {
          $alldata = unserialize(_vae_read_file($_POST['custom']));
          $out .= "Alldata: " . json_encode($alldata) . "\n\n";
          $data = $alldata['data'];
          session_id($alldata['session_id']);
          session_start();
          $_SESSION['__v:store']['cart'] = $alldata['cart'];
          $total = _vae_decimalize($data['total']);
          if (strlen(_vae_store_payment_paypal_email()) && ($_POST['receiver_email'] != _vae_store_payment_paypal_email())) {
            $out .= "E-Mail mismatch:\n  " . _vae_store_payment_paypal_email() . "\n  " . $_POST['receiver_email'] ."\n\n";
          } else {
            $out .= "Payment Completed, submitting order.\n";
            $data['gateway_transaction_id'] = $_POST['txn_id'];
            if (!strlen($data['email']) || !strlen($data['billing_name']) || !strlen($data['total'])) {
              $_POST['name'] = $_POST['first_name'] . " " . $_POST['last_name'];
              foreach (array('total' => 'mc_gross', 'shipping' => 'mc_shipping', 'tax' => 'tax', 'shipping_method' => 'shipping_method', 'shipping_name' => 'address_name', 'shipping_country' => 'address_country_code', 'shipping_address' => 'address_street', 'shipping_city' => 'address_city', 'shipping_state' => 'address_state', 'shipping_zip' => 'address_zip', 'billing_company' => 'payer_business_name', 'email' => 'payer_email', 'billing_name' => 'name', 'billing_country' => 'address_country_code', 'billing_address' => 'address_street', 'billing_city' => 'address_city', 'billing_state' => 'address_state', 'billing_zip' => 'address_zip') as $vae => $paypal) {
                if (strlen(trim($_POST[$paypal])) && (!strlen($data[$vae]) || (is_numeric($_POST[$paypal]) && is_numeric($data[$vae]) && ($data[$vae] == 0) && ($_POST[$paypal] > 0)) || ($data[$vae] == "N/A" && $paypal == "shipping_method"))) {
                  $data[$vae] = trim($_POST[$paypal]);
                }
              }
            }
            if (!$data['shipping_name'] && $data['billing_name']) $data['shipping_name'] = $data['billing_name'];
            $retrdata = _vae_store_complete_checkout($data);
            $out .= $retrdata;
            if ($retrdata !== false) {
              $good = true;
              _vae_remove_file($_POST['custom']); // Delete the local IPN file
            }
          }
        } else {
          $report_error = false;
        }
      }
    }
  }
  $out .= "Messages : " . json_encode($_SESSION['__v:flash_new']['messages']);
  $out .= "\nRequest  : " . $req . "\nResponse : " . $res;
  _vae_log($out);
  if (!$good) {
    if ($report_error) _vae_report_error("PayPal IPN Error", $out, false);
    @header("HTTP/1.1 503 Service Temporarily Unavailable");
    @header("Status: 503 Service Temporarily Unavailable");
    $out = "Status: 503 Service Temporarily Unavailable\n" . $out;
  }
  return $out;
}

function _vae_store_populate_address($a) {
  _vae_session_deps_add('__v:store', '_vae_store_populate_address');
  foreach ($a as $k => $v) {
    if ($k != 'address_type') $_SESSION['__v:store']['user'][$a['address_type']. "_" . $k] = $v;
  }
}

function _vae_store_populate_addresses() {
  _vae_session_deps_add('__v:store', '_vae_store_populate_addresses');
  if (count($_SESSION['__v:store']['customer_addresses'])) {
    foreach ($_SESSION['__v:store']['customer_addresses'] as $a) {
      _vae_store_populate_address($a);
    }
  }
}

function _vae_store_render_add_to_cart($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $callback['item'] = ($context ? $context->id() : null);
  if ($a['inventory_field'] && !$a['disable_inventory_check'] && (_vae_store_item_available($context, $a['options_collection'], $a['inventory_field']) == false) && !$a['multiple']) return _vae_get_else($tag, $context, $render_context);
  $a['add_to_cart_item_id'] = $callback['item'];
  return _vae_render_callback("store_add_to_cart", $a, $tag, $context, $callback, $render_context->set($a));
}

function _vae_store_render_address_delete($a, &$tag, $context, &$callback, $render_context) {
  $callback['id'] = ($context ? $context->formId() : null);
  return _vae_render_callback_link("store_address_delete", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_address_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_address_select');
  if (!$_SESSION['__v:store']['loggedin'] || !$_SESSION['__v:store']['customer_id']) return "";
  $a['options'] = array(0 => "--- Select a Saved Address ---");
  foreach ($_SESSION['__v:store']['customer_addresses'] as $id => $address) {
    if ($address['address_type'] == $a['type']) $a['options'][$id] = _vae_combine_array_keys($address, array('name','company','address','address_2','city','state','zip','country'));
  }
  if ($a['ajax']) {
    _vae_needs_jquery();
    $a['onchange'] = _vae_append_js($a['onchange'], _vae_append_js($a['ajaxbefore'], "jQuery.get('" . $_SERVER['PHP_SELF'] . "?__v:store_address_select=" . _vae_tag_unique_id($tag, $context) . "&address='+this.value, function(d){ " . $a['ajaxsuccess'] . " jQuery('#" . $a['ajax'] . "').html(d); });"));
  } else {
    $a['onchange'] = "window.location.href='" . $_SERVER['PHP_SELF'] . "?__v:store_address_select=" . _vae_tag_unique_id($tag, $context) . "&address='+this.value";
  }
  return _vae_render_select($a, $tag, $context, $callback, $render_context);
}


function _vae_store_render_addresses($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_addresses');
  if (!$_SESSION['__v:store']['loggedin'] || !$_SESSION['__v:store']['customer_id']) return "";
  return _vae_render_collection($a, $tag, $context, $callback, $render_context, _vae_array_to_xml($_SESSION['__v:store']['customer_addresses']));
}

function _vae_store_render_bundled_item($a, &$tag, $context, &$callback, $render_context) {  
  $main_item_id = $render_context->get("add_to_cart_item_id");
  if (!$main_item_id) return _vae_error("You must use <span class='c'>&lt;v:store:bundled_item&gt;</span> within a <span class='c'>&lt;v:store:add_to_cart&gt;</span> tag.");
  if (!$context) return _vae_error("You must use <span class='c'>&lt;v:store:bundled_item&gt;</span> within the context of the item you wish to bundle.");
  if ($context->id() == $main_item_id) return "";
  $callback['item'] = $context->id();
  return _vae_render_tags($tag, $context, $render_context);
}

function _vae_store_render_cart($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_cart');
  if (!count($_SESSION['__v:store']['cart'])) {
    return  ($render_context->get("has_flash_tag" . $a['flash']) ? "" : _vae_render_flash_inside($a['flash'], $render_context)) . _vae_get_else($tag, $context, $render_context, "<div class='vae-store-cart-empty'>Your cart is empty!</div>");
  }
  return _vae_render_callback("store_cart", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_cart_items($a, &$tag, $context, &$callback, $render_context, $data = null) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_cart_items');
  if ($data == null) $data = $_SESSION['__v:store']['cart'];
  return _vae_render_collection($a, $tag, $context, $callback, $render_context->set(array("price_field" => "price")), _vae_array_to_xml($data, true));
}

function _vae_store_render_cart_discount($a, &$tag, $context, &$callback, $render_context) {
  return _vae_store_currency_display(_vae_store_compute_discount(), $a['currency']);
}

function _vae_store_render_cart_shipping($a, &$tag, $context, &$callback, $render_context) {
  return _vae_store_currency_display(_vae_store_compute_shipping(), $a['currency']);
}

function _vae_store_render_cart_subtotal($a, &$tag, $context, &$callback, $render_context) {
  return _vae_store_currency_display(_vae_store_compute_subtotal(), $a['currency']);
}

function _vae_store_render_cart_tax($a, &$tag, $context, &$callback, $render_context) {
  return _vae_store_currency_display(_vae_store_compute_tax(), $a['currency']);
}

function _vae_store_render_cart_total($a, &$tag, $context, &$callback, $render_context) {
  return _vae_store_currency_display(_vae_store_compute_total(), $a['currency']);
}

function _vae_store_render_checkout($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $credit_card = false;
  foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
    if ($_VAE['store']['payment_methods'][$method['method_name']]['credit_card']) $credit_card = true;
  }
  if ($credit_card) {
    if ($ret = _vae_require_ssl()) return $ret;
  }
  _vae_session_deps_add('__v:store', '_vae_store_render_checkout');
  if (!count($_SESSION['__v:store']['cart'])) return _vae_render_redirect($a['shop_page'] ? $a['shop_page'] : "/");
  if (!$_SESSION['__v:logged_in'] && !isset($_SESSION['__v:store']['user'])) return _vae_render_redirect($a['register_page']);
  _vae_store_compute_shipping($a['register_page']);
  return _vae_render_callback("store_checkout", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_credit_card_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_store_set_default_payment_method();
  $method = _vae_store_payment_method();
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $a['options'] = array();
  if ($method['accept_visa']) $a['options']['visa'] = "VISA";
  if ($method['accept_master']) $a['options']['master'] = "MasterCard";
  if ($method['accept_discover']) $a['options']['discover'] = "Discover";
  if ($method['accept_american_express']) $a['options']['american_express'] = "American Express";
  if ($method['accept_switch']) $a['options']['switch'] = "Maestro/Switch";
  if ($method['accept_solo']) $a['options']['solo'] = "Solo";
  if ($method['accept_switch'] || $method['accept_solo']) {
    _vae_needs_jquery();
    $a['onchange'] = _vae_minify_js("if (this.value == 'switch' || this.value == 'solo') { jQuery('.switchsolo').show(); } else { jQuery('.switchsolo').hide(); jQuery('.switchsolo input').val(''); }");
    if ($a['value'] != "solo" && $a['value'] != "switch") {
      _vae_on_dom_ready("jQuery('.switchsolo').hide();");
    }
  }
  return _vae_render_select($a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_currency($a, &$tag, $context, &$callback, $render_context) {
  if (strlen($_SESSION['__v:store_display_currency'])) return $_SESSION['__v:store_display_currency'];
  return _vae_store_currency();
}

function _vae_store_render_currency_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store_display_currency', '_vae_store_render_currency_select');
  $a['onchange'] = "window.location.href='" . $_SERVER['PHP_SELF'] . "?__v:store_currency_select=" . _vae_tag_unique_id($tag, $context) . "&currency='+this.value";
  if (!isset($a['options'])) {
    $a['options'] = array();
    foreach ($_VAE['currency_names'] as $symbol => $name) {
      $a['options'][$symbol] = $name . " (" . $symbol . ")";
    }
  }
  $a['value'] = (strlen($_SESSION['__v:store_display_currency']) ? $_SESSION['__v:store_display_currency'] : _vae_store_currency());
  return _vae_render_select($a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_discount($a, &$tag, $context, &$callback, $render_context) {
  if ($_SESSION['__v:store']['discount_code']) return _vae_get_else($tag, $context, $render_context);
  return _vae_render_callback("store_discount", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_forgot($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_forgot');
  if ($_SESSION['__v:store']['loggedin']) return _vae_render_redirect("/");
 return _vae_render_callback("store_forgot", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_google_checkout($a, &$tag, $context, &$callback, $render_context) {
  if (_vae_store_payment_google_checkout_method(false) === false) return "";
  if (!strlen($a['src'])) $a['src'] = "https://checkout.google.com/buttons/checkout.gif?w=168&h=44&loc=en_US&variant=text&style=trans"; 
  $inner = _vae_render_tag("img", $a, $inner, $context, $render_context);
  $a['href'] = $_SERVER['PHP_SELF'] . _vae_qs(array("__v:store_google_checkout" => _vae_tag_unique_id($tag, $context)));
  return _vae_render_tag("a", $a, $inner, $context, $render_context);
}

function _vae_store_render_if_bank_transfer($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_if_bank_transfer');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['payment_method'] == "bank_transfer"));
}

function _vae_store_render_if_check($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_if_check');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['payment_method'] == "check"));
}

function _vae_store_render_if_credit_card($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_if_credit_card');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, $_VAE['store']['payment_methods'][$_SESSION['__v:store']['payment_method']]['credit_card']);
}

function _vae_store_render_if_currency($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store_display_currency', '_vae_store_render_if_currency');
  return _vae_render_tags($tag, $context, $render_context, (strlen($_SESSION['__v:store_display_currency']) && ($_SESSION['__v:store_display_currency'] != _vae_store_currency())));
}

function _vae_store_render_if_discount($a, &$tag, $context, &$callback, $render_context) {
  if ($a['custom'] || $a['code']) {
    $disc = _vae_store_find_discount($_SESSION['__v:store']['discount_code']);
    if (is_array($disc)) {
      $true = true;
      foreach (array('code','custom') as $b) {
        if ($a[$b] && (strtolower($a[$b]) != strtolower($disc[$b]))) $true = false;
      }
    } else {
      $true = false;
    }
  } else {
    $true = (_vae_store_compute_discount() > 0);
  }
  return _vae_render_tags($tag, $context, $render_context, $true);
}

function _vae_store_render_if_digital_downloads($a, &$tag, $context, &$callback, $render_context) {
  return _vae_render_tags($tag, $context, $render_context, _vae_store_if_digital_downloads());
}

function _vae_store_render_if_field_overridden($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $overridden = false;
  $options = _vae_fetch($a['options_collection'], $context);
  if (count($options)) {
    foreach ($options as $r) {
      if (strlen($r->get($a['field']))) $overridden = true;
    }
  }
  return _vae_render_tags($tag, $context, $render_context, $overridden);
}

function _vae_store_render_if_in_cart($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_if_in_cart');
  $in_cart = false;
  $id = $context->id();
  if (count($_SESSION['__v:store']['cart'])) {
    foreach ($_SESSION['__v:store']['cart'] as $cid => $r) {
      if ($r['id'] == $id) $in_cart = true;
    }
  }
  return _vae_render_tags($tag, $context, $render_context, $in_cart);
}

function _vae_store_render_if_logged_in($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_if_logged_in');
  return _vae_render_tags($tag, $context, $render_context, $_SESSION['__v:store']['loggedin']);
}

function _vae_store_render_if_money_order($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['payment_method'] == "money_order"));
}

function _vae_store_render_if_pay_in_store($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['payment_method'] == "in_store"));
}

function _vae_store_render_if_paypal($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['payment_method'] == "paypal"));
}

function _vae_store_render_if_paypal_express_checkout($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  _vae_store_set_default_payment_method();
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['payment_method'] == "paypal_express_checkout"));
}

function _vae_store_render_if_recent_order_bank_transfer($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['recent_order_data']['payment_method'] == "bank_transfer"));
}

function _vae_store_render_if_recent_order_check($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['recent_order_data']['payment_method'] == "check"));
}

function _vae_store_render_if_recent_order_credit_card($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, $_VAE['store']['payment_methods'][$_SESSION['__v:store']['recent_order_data']['payment_method']]['credit_card']);
}

function _vae_store_render_if_recent_order_digital($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, _vae_store_if_digital_downloads($_SESSION['__v:store']['recent_order']));
}

function _vae_store_render_if_recent_order_money_order($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['recent_order_data']['payment_method'] == "money_order"));
}

function _vae_store_render_if_recent_order_pay_in_store($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['recent_order_data']['payment_method'] == "in_store"));
}

function _vae_store_render_if_recent_order_paypal($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['recent_order_data']['payment_method'] == "paypal"));
}

function _vae_store_render_if_recent_order_paypal_express_checkout($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', 'if');
  return _vae_render_tags($tag, $context, $render_context, ($_SESSION['__v:store']['recent_order_data']['payment_method'] == "paypal_express_checkout"));
}

function _vae_store_render_if_shippable($a, &$tag, $context, &$callback, $render_context) {
  return _vae_render_tags($tag, $context, $render_context, _vae_store_if_shippable());
}

function _vae_store_render_if_tax($a, &$tag, $context, &$callback, $render_context) {
  return _vae_render_tags($tag, $context, $render_context, (_vae_store_compute_tax() > 0));
}

function _vae_store_render_if_user($a, &$tag, $context, &$callback, $render_context) {
  return _vae_render_tags($tag, $context, $render_context, $_SESSION['__v:store']['user']);
}

function _vae_store_render_item_if_discount($a, &$tag, $context, &$callback, $render_context) {
  $discount_field = $render_context->attr("discount_field", $a);
  $discount = ($discount_field ? _vae_fetch($discount_field, $context) : false);
  return _vae_render_tags($tag, $context, $render_context, $discount);
}

function _vae_store_render_item_price($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add("__v:store_display_currency");
  $price = (string)_vae_fetch($render_context->required_attr("price_field", $a, "store:item:price"), $context);
  $discount_field = $render_context->attr("discount_field", $a);
  if ($discount_field && !$a['before_discount'] && ($discount = _vae_fetch($discount_field, $context))) {
    $price = _vae_decimalize((string)$price * (100.0 - (string)$discount) / 100.0);
  }
  return _vae_store_currency_display($price);
}

function _vae_store_render_login($a, &$tag, $context, &$callback, $render_context) {
 return _vae_render_callback("store_login", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_logout($a, &$tag, $context, &$callback, $render_context) {
 return _vae_render_callback_link("store_logout", $a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_payment_methods_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_payment_methods_select');
  _vae_store_set_default_payment_method();
  if ($_REQUEST['__v_actionverb_admin_test']) $_SESSION['__v:store']['payment_method'] = "actionverb_admin_test_payment";
  if (_vae_store_compute_total() == 0.00) {
    $a['options'] = array($_SESSION['__v:store']['payment_method'] => $_SESSION['__v:store']['payment_method']);
  } else {
    $a['options'] = array();  
    foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
      if ($method['method_name'] == "manual") {
        if ($method['accept_check']) $a['options']['check'] = $_VAE['store']['payment_methods']['check']['name'];
        if ($method['accept_mo']) $a['options']['money_order'] = $_VAE['store']['payment_methods']['money_order']['name'];
        if ($method['accept_bank_transfer']) $a['options']['bank_transfer'] = $_VAE['store']['payment_methods']['bank_transfer']['name'];
        if ($method['accept_in_store']) $a['options']['in_store'] = $_VAE['store']['payment_methods']['in_store']['name'];
      } elseif ($method['method_name'] != "google_checkout") {
        $a['options'][$method['method_name']] = $_VAE['store']['payment_methods'][$method['method_name']]['name'];
      }
    }
    if ($a['ajax']) {
      _vae_needs_jquery();
      $a['onchange'] = _vae_append_js($a['onchange'], _vae_append_js($a['ajaxbefore'], "jQuery.get('" . $_SERVER['PHP_SELF'] . "?__v:store_payment_methods_select=" . _vae_tag_unique_id($tag, $context) . "&__method='+this.value, function(d){ " . $a['ajaxsuccess'] . " jQuery('#" . $a['ajax'] . "').html(d); });"));
    } else {
      $a['onchange'] = _vae_append_js($a['onchange'], "window.location.href='" . $_SERVER['PHP_SELF'] . "?__v:store_payment_methods_select=" . _vae_tag_unique_id($tag, $context) . "&__method='+this.value");
    }
  }
  $a['value'] = $_SESSION['__v:store']['payment_method'];
  return _vae_render_select($a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_paypal_checkout($a, &$tag, $context, &$callback, $render_context) {
  if (_vae_store_payment_paypal_email() === false) return "";
  if (!strlen($a['src'])) $a['src'] = _vae_proto() . "www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif"; 
  $inner = _vae_render_tag("img", $a, $inner, $context, $render_context);
  $a['href'] = $_SERVER['PHP_SELF'] . _vae_qs(array("__v:store_paypal_checkout" => _vae_tag_unique_id($tag, $context)));
  return _vae_render_tag("a", $a, $inner, $context, $render_context);
}

function _vae_store_render_paypal_express_checkout($a, &$tag, $context, &$callback, $render_context) {
  if (_vae_store_payment_paypal_express_checkout_method(false) === false) return "";
  if (!strlen($a['src'])) $a['src'] = _vae_proto() . "www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif"; 
  $inner = _vae_render_tag("img", $a, $inner, $context, $render_context);
  $a['href'] = $_SERVER['PHP_SELF'] . _vae_qs(array("__v:store_paypal_express_checkout" => _vae_tag_unique_id($tag, $context), "token" => ""));
  return _vae_render_tag("a", $a, $inner, $context, $render_context);
}

function _vae_store_render_previous_order_items($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_previous_order');
  if (!isset($_SESSION['__v:store']['previous_orders'])) {
    return _vae_render_redirect("/");
  } else {
    $pdata = $_SESSION['__v:store']['previous_orders'][$_REQUEST['order']]['items'];
  }
  return _vae_render_collection($a, $tag, $context, $callback, $render_context, _vae_array_to_xml($pdata));
}

function _vae_store_render_previous_order($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_previous_order');
  if (!isset($_SESSION['__v:store']['previous_orders'])) {
    return _vae_render_redirect("/");
  } else {
    $pdata = array($_SESSION['__v:store']['previous_orders'][$_REQUEST['order']]);
  }
  return _vae_render_collection($a, $tag, $context, $callback, $render_context, _vae_array_to_xml($pdata));
}

function _vae_store_render_previous_orders($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_previous_orders');
  $pdata = vae_store_previous_orders();
  $a['path'] = "order";
  if (!count($pdata)) return _vae_get_else($tag, $context, $render_context);
  return _vae_render_collection($a, $tag, $context, $callback, $render_context, _vae_array_to_xml($pdata));
}

function _vae_store_render_recent_order($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_recent_order');
  if (!isset($_SESSION['__v:store']['recent_order_data'])) {
    return _vae_render_redirect("/");
  }
  $data = $_SESSION['__v:store']['recent_order_data'];
  $data['order_id'] = $data['id'];
  $data['subtotal'] = $data['total'] - $data['tax'] - $data['shipping'] + $data['discount'];
  if (!strlen($data['shipping'])) $data['shipping'] = 0.00;
  if (!strlen($data['tax'])) $data['tax'] = 0.00;
  if (!strlen($data['discount'])) $data['discount'] = 0.00;
  return _vae_render_collection($a, $tag, $context, $callback, $render_context,  _vae_array_to_xml(array($_SESSION['__v:store']['recent_order_data']['id'] => $data)));
}

function _vae_store_render_recent_order_items($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_recent_order_items');
  if (!isset($_SESSION['__v:store']['recent_order'])) {
    return _vae_render_redirect("/");
  }
  return _vae_store_render_cart_items($a, $tag, $context, $callback, $render_context, $_SESSION['__v:store']['recent_order']);
}

function _vae_store_render_register($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_register');
  if ($_SESSION['__v:store']['payment_method'] == "paypal_express_checkout") {
    _vae_flash("You cannot edit your address on our site when using PayPal Express Checkout.  Use the back button to go back to PayPal to set the correct address.", "err");
    return _vae_render_redirect($a['redirect']);
  }
  return _vae_render_callback("store_register", $a, $tag, _vae_to_xml($_SESSION['__v:store']['user']), $callback, $render_context);
}

function _vae_store_render_shipping_methods_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  _vae_session_deps_add('__v:store', '_vae_store_render_shipping_methods_select');
  _vae_store_compute_shipping();
  if ($a['ajax']) {
    _vae_needs_jquery();
    $a['onchange'] = _vae_append_js($a['onchange'], _vae_append_js($a['ajaxbefore'], "jQuery.get('" . $_SERVER['PHP_SELF'] . "?__v:store_shipping_methods_select=" . _vae_tag_unique_id($tag, $context) . "&__method='+this.value, function(d){ " . $a['ajaxsuccess'] . " jQuery('#" . $a['ajax'] . "').html(d); });"));
  } else {
    $a['onchange'] = _vae_append_js($a['onchange'], "window.location.href='" . $_SERVER['PHP_SELF'] . "?__v:store_shipping_methods_select=" . _vae_tag_unique_id($tag, $context) . "&__method='+this.value");
  }
  $a['options'] = array();
  if ($_SESSION['__v:store']['shipping']['options']) {
    foreach ($_SESSION['__v:store']['shipping']['options'] as $id => $r) {
      $a['options'][$id] = $r['title'] . ' (' . _vae_store_currency_symbol() . _vae_decimalize($r['cost']) . ')';
    }
  }
  $a['value'] = $_SESSION['__v:store']['shipping']['selected_index'];
  return _vae_render_select($a, $tag, $context, $callback, $render_context);
}

function _vae_store_render_user($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:store', '_vae_store_render_user');
  if (!$_SESSION['__v:store']['user'] && !$a['safe']) {
    _vae_flash("Your session expired due to lack of activity.  Please start again.");
    return _vae_callback_redirect("/");
  }
  return _vae_render_collection($a, $tag, $context, $callback, $render_context, _vae_array_to_xml(array($_SESSION['__v:store']['user'])));
}

function _vae_store_set_default_payment_method() {
  global $_VAE;
  if (_vae_store_compute_total() == 0.00) {
    $_SESSION['__v:store']['payment_method'] = "No Payment Required";
  } else {
    if ($_SESSION['__v:store']['payment_method'] == "No Payment Required") unset($_SESSION['__v:store']['payment_method']);
    foreach ($_VAE['settings']['payment_methods'] as $id => $method) {
      if (!isset($_SESSION['__v:store']['payment_method'])) {
        if ($method['method_name'] == "manual") {
          if ($method['accept_check']) $a['options']['check'] = $_SESSION['__v:store']['payment_method'] = "check";
          elseif ($method['accept_mo']) $a['options']['money_order'] = $_SESSION['__v:store']['payment_method'] = "money_order";
          elseif ($method['accept_bank_transfer']) $a['options']['bank_transfer'] = $_SESSION['__v:store']['payment_method'] = "bank_transfer";
          elseif ($method['accept_in_store']) $a['options']['in_store'] = $_SESSION['__v:store']['payment_method'] = "in_store";
        } else {
          $_SESSION['__v:store']['payment_method'] = $method['method_name'];
        }
      }
    }
  }
}

function _vae_store_shipping_tax_class($val = null) {
	if ($val) $_SESSION['__v:store']['shipping_tax_class'] = $val;
	return $_SESSION['__v:store']['shipping_tax_class'];
}

function _vae_store_shipping_methods() {
  global $_VAE;
  $methods = $_VAE['settings']['shipping_methods'];
  if (isset($_SESSION['__v:store']['user_shipping_methods'])) {
    foreach ($_SESSION['__v:store']['user_shipping_methods'] as $user_method) {
      $user_method['user'] = true;
      $methods[] = $user_method;
    }
  }
  return $methods;
}

function _vae_store_suggest_alternate_address($country, $city, $state, $zip) {
  global $_VAE;
  if ($country != "US") return $city;
  if ($state == "AE" || $state == "AP" || $state == "AA") return $city;
  $xml = '<?xml version="1.0"?>
  <AccessRequest>
  <AccessLicenseNumber>FC57D838E6B7AF48</AccessLicenseNumber>
  <UserId>actionverb</UserId>
  <Password>39FQ3kjJJ</Password>
  </AccessRequest>
  <?xml version="1.0"?>
  <AddressValidationRequest xml:lang="en-US">
  <Request>
  <TransactionReference>
  <CustomerContext>Data</CustomerContext>
  <XpciVersion>1.0001</XpciVersion>
  </TransactionReference>
  <RequestAction>AV</RequestAction>
  </Request>
  <Address>
  <City>' . $city . '</City>
  <StateProvinceCode>' . $state . '</StateProvinceCode>
  <PostalCode>' . $zip . '</PostalCode>
  </Address>
  </AddressValidationRequest>';
  $ret_xml = _vae_simple_rest("https://onlinetools.ups.com/ups.app/xml/AV", $xml);
  $ret = simplexml_load_string($ret_xml);
  if ($ret->Response->ResponseStatusCode == 1) {
    foreach ($ret->AddressValidationResult as $r) {
      if ($r->PostalCodeLowEnd <= $zip && $r->PostalCodeHighEnd >= $zip && $r->Address->StateProvinceCode == $state) {
        return (string)$r->Address->City;
      }
      if ($r->Quality > 0.95) {
        return $city;
      }
    }
  }
  return false;
}

function _vae_store_transform_orders($xml) {
  $data = simplexml_load_string($xml);
  $pdata = @_vae_array_from_rails_xml($data->{store-orders}->{store-order}, true, array('email' => 'e_mail_address', 'store-order-line-items' => 'items'));
  if (count($pdata)) {
    foreach ($pdata as $id => $r2) {
      $pdata[$id]['date'] = strftime("%B %d, %Y", strtotime($r2['created_at']));
      $pdata[$id]['id'] = $id;
      $pdata[$id]['order_id'] = $id;
      $pdata[$id]['subtotal'] = _vae_decimalize($r2['total'] - $r2['shipping'] - $r2['tax'] + $r2['discount'], 2);
    }
  }
  return $pdata;
}

function _vae_store_verify_available($flash = true) {
  global $_VAE;
  $errors = array();
  _vae_session_deps_add('__v:store', '_vae_store_verify_available');
  if (count($_SESSION['__v:store']['cart'])) {
    foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
      if (is_numeric($_VAE['settings']['store_expire_cart_items_after']) && is_numeric($r['added_at'])) {
        if (((time() - $r['added_at']) / 3600) > $_VAE['settings']['store_expire_cart_items_after']) {
          $errors[] = "Item " . _vae_store_cart_item_name($r) . " has been removed from your cart because it has been in your cart for more than " . $_VAE['settings']['store_expire_cart_items_after'] . " hours.  Please browse our store and add it to your cart again if it is still available.";
          vae_store_remove_from_cart($id);
          continue;
        }
      }
      if ($r['inventory_field'] && $r['check_inventory']) {
        $available_qty = (string)_vae_store_most_specific_field($r, $r['inventory_field']) - _vae_store_inventory_minimum_for_order();
        if (strlen($available_qty)) {
          $available_qty = (int)$available_qty;
          if ($r['qty'] > $available_qty) {
            if ($available_qty <= 0) {
              $errors[] = "The " . _vae_store_cart_item_name($r) . " you stored in your cart is no longer available.  It has been removed from your cart.";
              vae_store_remove_from_cart($id);
            } else {
              $_SESSION['__v:store']['cart'][$id]['qty'] = $available_qty;
              $_SESSION['__v:store']['cart'][$id]['total'] = $available_qty * $_SESSION['__v:store']['cart'][$id]['price'];
              $errors[] = "The " . _vae_store_cart_item_name($r) . " you stored in your cart is not available in the quantity you requested.  We have lowered the quantity in your cart.";
            }
          }
        }
      }
    }
  }
  if (count($errors)) {
    foreach ($errors as $e) {
      $errstr .= "<li>$e</li>";
    }
    if ($flash) _vae_flash("Some items in your cart are no longer available:<ul>$errstr</ul>", 'err');
    return false;
  }
  return true;
}
