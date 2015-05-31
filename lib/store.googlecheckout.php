<?php

function _vae_google_checkout_get_arr_result($child_node) {
  $result = array();
  if(isset($child_node)) {
    if(_vae_google_checkout_is_associative_array($child_node)) {
      $result[] = $child_node;
    }
    else {
      foreach($child_node as $curr_node){
        $result[] = $curr_node;
      }
    }
  }
  return $result;
}

function _vae_google_checkout_go($a) {
  global $_VAE;
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googlecart.php");
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googleitem.php");
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googleshipping.php");
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googletax.php");
  $m = _vae_store_payment_google_checkout_method();
  $cart = new GoogleCart($m['merchant_id'], $m['merchant_key'], ($m['sandbox'] ? "sandbox" : "production"), _vae_store_currency()); 
  foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
    if ($_SESSION['__v:store']['user_shipping_methods']) {
      $r['user_shipping'] = serialize($_SESSION['__v:store']['user_shipping_methods']);
      $r['tag_attrs'] = serialize($a);
    }
    $item = new GoogleItem($r['name'], $r['option_value'], $r['qty'], $r['price']);
    $data = "";
    foreach ($r as $k => $v) {
      $data .= "$k=" . urlencode($v) . "&";
    }
    $item->SetMerchantPrivateItemData($data);
    if ($r['tax_class']) $item->SetTaxTableSelector($r['tax_class']);
    $cart->AddItem($item);
  }
  if (_vae_store_if_shippable()) {
    $_VAE['no_shipping_restrictions'] = true;
    _vae_google_checkout_set_domestic();
    _vae_store_compute_shipping();
    $shipping_options = $_SESSION['__v:store']['shipping']['options'];
    _vae_google_checkout_set_international();
    _vae_store_compute_shipping();
    $shipping_options = array_reverse(array_merge($_SESSION['__v:store']['shipping']['options'], $shipping_options), true);
    $shipping_added = array();
    foreach ($shipping_options as $r) {
      if (!isset($shipping_added[$r['title']])) {
        $ship = new GoogleMerchantCalculatedShipping($r['title'], $r['cost']);
        $cart->AddShipping($ship);
        $shipping_added[$r['title']] = true;
      }
    }
  }
  $alternate_tables = array();
  $rates = $_VAE['settings']['tax_rates'];
  if (is_array($rates) && count($rates) > 0) {
    foreach ($rates as $id => $rate) {
      $tax_rule = new GoogleDefaultTaxRule($rate['rate'] / 100.0, ($rate['include_shipping'] ? "true" : "false"));
      if (strlen($rate['zip'])) {
        $zips = array();
        foreach (explode(",", $rate['zip']) as $zip) {
          if (strlen($zip) < 5) $zip .= "*";
          $zips[] = $zip;
        }
        $tax_rule->SetZipPatterns($zips);
      }
      if (strlen($rate['state'])) $tax_rule->SetStateAreas($rate['state']);
      elseif (strlen($rate['country'])) $tax_rule->AddPostalArea($rate['country']);
      if ($r['tax_class']) {
        if (!isset($alternate_tables[$r['tax_class']])) $alternate_tables[$r['tax_class']] = new GoogleAlternateTaxTable($r['tax_class'], "true");
        $alternate_tables[$r['tax_class']]->AddAlternateTaxRules($tax_rule);
      } else {
        $cart->AddDefaultTaxRules($tax_rule);
      }
    }
  }
  foreach ($alternate_tables as $name => $table) {
    $cart->AddAlternateTaxTables($table);
  }
  $cart->SetMerchantCalculations("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?__v:store_payment_method_ipn=google_checkout", "true", "true", "true");
  list($status, $error) = $cart->CheckoutServer2Server();
  _vae_error("Google Checkout Error: " . $error);
}

function _vae_google_checkout_import_cart($cart) {
  global $_VAE;
  $out = "";
  foreach ($cart as $item) {
    $a = array();
    foreach (explode("&", $item['merchant-private-item-data']['VALUE']) as $r) {
      $e = explode("=", $r);
      $val = urldecode($e[1]);
      if ($e[0] == "user_shipping") {
        $_SESSION['__v:store']['user_shipping_methods'] = unserialize($val);
      } elseif ($e[0] == "tag_attrs") {
        $_VAE['google_checkout_attrs'] = unserialize($val);
      } elseif (strlen($e[0])) {
        $a[$e[0]] = $val;
      }
    }
    _vae_store_add_item_to_cart($a['id'], $a['option_id'], $a['qty'], $a);
  }
  _vae_log(serialize($_SESSION));
  return $out;
}

function _vae_google_checkout_ipn() {
  global $_VAE;
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googlemerchantcalculations.php");
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googlerequest.php");
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googleresult.php");
  require_once(dirname(__FILE__) . "/../vendor/checkout-php-1.3.0/library/googleresponse.php");
  $m = _vae_store_payment_google_checkout_method();
  $out = "";
  $Gresponse = new GoogleResponse($m['merchant_id'], $m['merchant_key']);
  $Grequest = new GoogleRequest($m['merchant_id'], $m['merchant_key'], ($m['sandbox'] ? "sandbox" : "production"), _vae_store_currency());
  $xml_response = file_get_contents("php://input");
  list($root, $data) = $Gresponse->GetParsedXML($xml_response);
  $Gresponse->SetMerchantAuthentication($merchant_id, $merchant_key);
  switch ($root) {
    case "request-received": {
      break;
    }
    case "error": {
      break;
    }
    case "diagnosis": {
      break;
    }
    case "checkout-redirect": {
      break;
    }
    case "merchant-calculation-callback": {
      $merchant_calc = new GoogleMerchantCalculations(_vae_store_currency());
      $out .= _vae_google_checkout_import_cart($data[$root]['shopping-cart']['items']['item']);
      $addresses = _vae_google_checkout_get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
      foreach ($addresses as $curr_address) {
        unset($_VAE['store_cached_shipping']);
        $_SESSION['__v:store']['user'] = array(
          'shipping_city' => $curr_address['city']['VALUE'],
          'shipping_state' => $curr_address['region']['VALUE'],
          'shipping_zip' => $curr_address['postal-code']['VALUE'],
          'shipping_country' => $curr_address['country-code']['VALUE']
        );
        $shipping_methods = array();
        if (isset($data[$root]['calculate']['shipping'])) {
          $shipping = _vae_google_checkout_get_arr_result($data[$root]['calculate']['shipping']['method']);
          foreach($shipping as $curr_ship) {
            $shipping_methods[] = array('name' => $curr_ship['name']);
          }
        } else {
          $shipping_methods[] = array();
        }
        unset($_VAE['store_cached_shipping']);
        _vae_store_compute_shipping();
        foreach ($shipping_methods as $s) { 
          unset($_SESSION['__v:store']['discount_code']);
          $merchant_result = new GoogleResult($curr_address['id']);
          if (isset($data[$root]['calculate']['merchant-code-strings']['merchant-code-string'])) {
            $_SESSION['__v:store']['discount_code_show_errors'] = false;
            $codes = _vae_google_checkout_get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
            foreach($codes as $curr_code) {
              $code = preg_replace("/[^a-z0-9]/", "", strtolower($curr_code['code']));
              if ($_SESSION['__v:store']['discount_code']) {
                $coupon = new GoogleCoupons("false", $code, 0, "You can only use one coupon code per order.");
              } else {
                $_SESSION['__v:store']['discount_code'] = $code;
                if ($amount = _vae_store_compute_discount(null, null, $tag['attrs']['flash'])) {
                  $coupon = new GoogleCoupons("true", $curr_code['code'], $amount, "Applied Coupon Code: " . $code);
                } else {
                  $_SESSION['__v:store']['discount_code'] = null;
                  $coupon = new GoogleCoupons("false", $curr_code['code'], 0, "Invalid Coupon Code: " . $code);
                }
              }
              $merchant_result->AddCoupons($coupon);
            }
          }
          if ($s['name']) {
            $cost = false;
            foreach ($_SESSION['__v:store']['shipping']['options'] as $r) {
              if ($r['title'] == $s['name']) {
                $cost = $r['cost'];
                $_VAE['store_cached_shipping'] = $cost;
                unset($_VAE['store_cached_tax']);
              }
            }
            if ($cost !== false) {
              $merchant_result->SetShippingDetails($s['name'], $cost, "true");
            } else {
              $merchant_result->SetShippingDetails($s['name'], 300.00, "false");
            }
          }
          if($data[$root]['calculate']['tax']['VALUE'] == "true") {
            $amount = _vae_store_compute_tax();
            $merchant_result->SetTaxDetails($amount);
          }
          $merchant_calc->AddResult($merchant_result);
        }
        
      }
      $Gresponse->ProcessMerchantCalculations($merchant_calc);
      break;
    }
    case "new-order-notification": {
      $Gresponse->SendAck();
      break;
    }
    case "order-state-change-notification": {
      $Gresponse->SendAck();
      $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
      $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];
      switch($new_financial_state) {
        case 'REVIEWING': {
          break;
        }
        case 'CHARGEABLE': {
          break;
        }
        case 'CHARGING': {
          break;
        }
        case 'CHARGED': {
          break;
        }
        case 'PAYMENT_DECLINED': {
          break;
        }
        case 'CANCELLED': {
          break;
        }
        case 'CANCELLED_BY_GOOGLE': {
          break;
        }
        default:
          break;
      }
      switch($new_fulfillment_order) {
        case 'NEW': {
          break;
        }
        case 'PROCESSING': {
          break;
        }
        case 'DELIVERED': {
          break;
        }
        case 'WILL_NOT_DELIVER': {
          break;
        }
        default:
          break;
      }
      break;
    }
    case "authorization-amount-notification": {
      $out .= _vae_google_checkout_import_cart($data[$root]['order-summary']['shopping-cart']['items']['item']);
      _vae_store_callback_checkout();  
      $Gresponse->SendAck();
      break;
    }
    case "charge-amount-notification": {
      $Gresponse->SendAck();
      break;
    }
    case "chargeback-amount-notification": {
      $Gresponse->SendAck();
      break;
    }
    case "refund-amount-notification": {
      $Gresponse->SendAck();
      break;
    }
    case "risk-information-notification": {
      $Gresponse->SendAck();
      break;
    }
    default:
      $Gresponse->SendBadRequestStatus("Invalid or not supported Message");
      break;
  }
  _vae_log($xml_response . ($merchant_calc ? "\n\n----\n\n" . $merchant_calc->GetXML() : ""));
}

function _vae_google_checkout_is_associative_array( $var ) {
  return is_array( $var ) && !is_numeric( implode( '', array_keys( $var ) ) );
}

function _vae_google_checkout_set_domestic() {
  $origin_country = $_VAE['settings']['store_shipping_origin_country'];
  if (strlen($origin_country) && $origin_country != "US") {
    $_SESSION['__v:store']['user'] = array('shipping_country' => $origin_country);
    return;
  }
  $_SESSION['__v:store']['user'] = array(
    'shipping_city' => "New York",
    'shipping_state' => "NY",
    'shipping_zip' => "10018",
    'shipping_country' => "US"
  );
}

function _vae_google_checkout_set_international() {
  $_SESSION['__v:store']['user'] = array('shipping_country' => "IT");
  return;
}
