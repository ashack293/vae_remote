<?php

function vae_asset($id, $width = "", $height = "", $quality = "", $preserve_filename = false) {
  if (!strlen($id)) return "";
  $iden = $id . "-" . $width . "-" . $height;
  if ($quality) $iden .= "-qual-" . $quality;
  return _vae_file($iden, $id, "api/site/v1/asset/" . $id, "&direct=2" . ($width ? "&width=" . $width : "") . ($height ? "&height=" . $height : "") . ($quality ? "&quality=" . $quality : ""), $preserve_filename);
}

function vae_cache($key, $timeout = 3600, $function = "", $global = false) {
  global $_VAE;
  if (!strlen($key)) _vae_error("You called <span class='c'>vae_cache()</span> but didn't provide a cache key.");
  if ($function == "") $function = $key;
  $key = ($global ? $_VAE['global_cache_key'] : $_VAE['cache_key']) . $key;
  $cached = _vae_short_term_cache_get($key);
  if (is_array($cached) && $cached[0] == "chks") {
    return $cached[1];
  }
  $out = $function();
  if (!$_REQUEST['__vae_local'] && !$_REQUEST['__verb_local']) _vae_short_term_cache_set($key, array("chks", $out), 0, $timeout);
  return $out;
}

function vae_cached_contingency($key, $timeout = 86400, $function = "", $global = false) {
  global $_VAE;
  if (!strlen($key)) _vae_error("You called <span class='c'>vae_cached_contingency()</span> but didn't provide a cache key.");
  if ($function == "") $function = $key;
  $key = ($global ? $_VAE['global_cache_key'] : $_VAE['cache_key']) . $key;
  $out = $function();
  if (is_null($out)) {
    $cached = _vae_short_term_cache_get($key);
    if (is_array($cached) && $cached[0] == "chks") {
      return $cached[1];
    }
    else {
      return NULL;
    }
  }
  if (!$_REQUEST['__vae_local'] && !$_REQUEST['__verb_local']) _vae_short_term_cache_set($key, array("chks", $out), 0, $timeout);
  return $out;
}

function vae_cache_with_contingency($key, $timeout = 3600, $contingency_timeout = 86400, $function = "", $global = false) {
  if (!strlen($key)) _vae_error("You called <span class='c'>vae_cache_with_contingency()</span> but didn't provide a cache key.");
  if ($function == "") $function = $key;
  $contingency_key = "weak:591debff:$key"; // Just need a key that won't conflict with other keys.
  // Uncomment the closure later in some future version of PHP or Vae where it works.
  //$mid_function = function() use($contingency_key, $contingency_timeout, $function, $global) {
  //  return vae_cached_contingency($contingency_key, $contingency_timeout, $function, $global);
  //};
  $mid_function = create_function("", "return vae_cached_contingency('$contingency_key', $contingency_timeout, '$function', $global);");
  return vae_cache($key, $timeout, $mid_function, $global);
}

function vae_cdn_url() {
  global $_VAE;
  if (isset($_VAE['config']['cdn_url'])) return $_VAE['config']['cdn_url'];
  if ($_VAE['local_full_stack'] || $_REQUEST['__vae_local'] || $_REQUEST['__verb_local']) return "/";
  return _vae_proto() . $_SERVER['HTTP_HOST'] . "/";
}

function vae_create($structure_id, $row_id, $data) {
  if (strlen($structure_id) && !is_numeric($structure_id)) {
    $createInfo = _vae_fetch_for_creating($structure_id);
    if ($createInfo && is_numeric($createInfo->structure_id)) {
      $structure_id = $createInfo->structure_id;
      $row_id = $createInfo->row_id;
    }
  }
  if (!is_numeric($structure_id)) _vae_error("You called <span class='c'>vae_create()</span> but didn't provide a proper structure ID.");
  return _vae_create($structure_id, $row_id, $data, true);
}

function vae_customer($id, $load = false) {
  if (!is_numeric($id) && substr($id, 0, 4) != "cus_" && !strstr($id, "@")) _vae_error("You called <span class='c'>vae_customer()</span> but didn't provide a proper customer ID.");
  $raw = _vae_rest(array(), "api/site/v1/customers/show?id=" . rawurlencode($id), "customer", array(), array(), ['404']);
  if ($raw == false) return false;
  if ($load) _vae_store_load_customer($raw);
  return _vae_array_from_rails_xml(simplexml_load_string($raw));
}

function vae_customer_create_address($customer_id, $address) {
  $raw = _vae_rest($address, "api/site/v1/customer_addresses/create/$customer_id", "customer_address");
  if ($raw == false) return false;
  return _vae_array_from_rails_xml(simplexml_load_string($raw), true);
}

function vae_customer_destroy_address($id) {
  $ret = _vae_rest(array(), "api/site/v1/customer_addresses/destroy/$id");
  return ($ret != false);
}

function vae_customer_list() {
  $raw = _vae_rest(array(), "api/site/v1/customers");
  if ($raw == false) return false;
  return _vae_array_from_rails_xml(simplexml_load_string($raw), true);
}

function vae_customer_create_or_update($data) {
  $raw = _vae_rest($data, "api/site/v1/customers/create_or_update", "customer");
  if ($raw == false) return false;
  return _vae_array_from_rails_xml(simplexml_load_string($raw));
}

function vae_customer_destroy($id) {
  $ret = _vae_rest(array(), "api/site/v1/customers/destroy/$id");
  return ($ret != false);
}

function vae_customer_order_ids($id) {
  $raw = _vae_rest(array(), "api/site/v1/customers/$id/orders");
  if ($raw == false) return false;
  $arr = (array)simplexml_load_string($raw);
  return $arr['fixnum'];
}

function vae_customer_update($id = null, $data) {
  if (!$id) $id = $_SESSION['__v:store']['customer_id'];
  $ret = _vae_rest($data, "api/site/v1/customers/update/" . $id, "customer");
  return ($ret != false);
}

function vae_data_path() {
  global $_VAE;
  _vae_load_settings();
  return $_VAE['config']['data_path'];
}

function vae_data_url() {
  return _vae_absolute_data_url();
}

function vae_destroy($id) {
  if (!is_numeric($id)) _vae_error("You called <span class='c'>vae_destroy()</span> but didn't provide a proper ID.");
  return _vae_destroy($id);
}

function vae_disable_vaeml() {
  ob_end_clean();
  return true;
}

function vae_enqueue_job() {
  if(func_num_args() == 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . func_get_arg(0)))
    _vae_error("Called vae_enqueue_job() with an invalid filename.");
  $data = array('data' => json_encode(func_get_args()), 'docroot' => $_SERVER['DOCUMENT_ROOT']);
  $ret = _vae_rest($data, "api/site/v1/enqueue_job", "data");
  return ($ret != false);
}

function vae_errors() {
  global $_VAE;
  return $_VAE['errors'];
}

function vae_file($id, $preserve_filename = false) {
  if (!strlen($id)) return "";
  return _vae_file($id . "-file", $id, "api/site/v1/file/" . $id, "", $preserve_filename);
}

function vae_flash($message, $type = 'msg', $which = "") {
  if (!strlen($message)) _vae_error("You called <span class='c'>vae_flash()</span> but didn't provide a proper message.");
  return _vae_flash($message, $type, $which);
}

function vae_image($id, $width = "", $height = "", $image_size = "", $grow = "", $quality = "", $preserve_filename = false, $trim = false) {
  $id = trim($id);
  if (!strlen($id)) return "";
  $iden = $id . "-" . $width . "-" . $height;
  if ($image_size) $iden .= "-" . $image_size;
  if ($quality) $iden .= "-q" . $quality;
  if ($grow) $iden .= "-g";
  if ($trim) $iden .= "-t";
  return _vae_file($iden, $id, "api/site/v1/image/" . $id, ($width ? "&width=" . $width : "") . ($height ? "&height=" . $height : "") . ($image_size ? "&size=" . rawurlencode($image_size) : "") . ($quality ? "&quality=" . $quality : "") . ($grow ? "&grow=1" : "") . ($trim ? "&trim=1" : ""), $preserve_filename);
}

function _vae_image_filter_prepare($image, $iden_string, $func, $internal) {
  global $_VAE;
  $iden = $image . "-" . $iden_string;
  if ($cache = _vae_long_term_cache_get($iden)) return $cache;
  $old = _vae_gd_handle($image);
  if (!$old) {
    if (!$internal) {
      _vae_error("You called <span class='c'>" . _vae_h($func) . "()</span> but provided an invalid image filename.  Please do not include any path information in the filename.");
    }
    return false;
  }
  $width = imagesx($old);
  $height = imagesy($old);
  $sep = explode(".", $image);
  $filename = ($_VAE['settings']['preserve_filenames'] ? $sep[0] : false);
  return array($iden, $old, $width, $height, $filename);
}

function vae_image_grey($image, $internal = false) {
  $ret = _vae_image_filter_prepare($image, "grey2", "vae_image_grey", $internal);
  if (!is_array($ret)) return $ret;
  list($iden, $old, $width, $height, $filename) = $ret;
  $new = imagecreatetruecolor($width, $height);
  for ($y = 0; $y < $height; ++$y) {
    for ($x = 0; $x < $width; ++$x) {
     $rgb = imagecolorat($old, $x, $y);
     $red   = ($rgb >> 16) & 0xFF;
     $green = ($rgb >> 8)  & 0xFF;
     $blue  = $rgb & 0xFF;
     $gray = round(.299*$red + .587*$green + .114*$blue);
     $grayR = $gray << 16;   // R: red
     $grayG = $gray << 8;    // G: green
     $grayB = $gray;         // B: blue
     $grayColor = $grayR | $grayG | $grayB;
     imagesetpixel($new, $x, $y, $grayColor);
     imagecolorallocate($new, $gray, $gray, $gray);
    }
  }
  return _vae_store_file($iden, $new, "jpg", $filename, "jpeg");
}

function vae_image_reflect($image, $reflection_size = 30, $opacity = 35, $internal = false) {
  $ret = _vae_image_filter_prepare($image, "reflect2-" . $reflection_size . "-" . $opacity, "vae_image_reflect", $internal);
  if (!is_array($ret)) return $ret;
  list($iden, $old, $width, $height, $filename) = $ret;
  if (strstr($reflection_size, "px")) $new_height = floor(str_replace("px", "", $reflection_size));
  else $new_height = floor($height * str_replace("%", "", $reflection_size) / 100);
  $new = imagecreatetruecolor($width, $new_height + $height);
	imagecopy($new, $old, 0, 0, 0, 0, $width, $height);
  for ($i = 0; $i < $new_height; $i++) {
    $alpha = (1 - sin($i * 1.5707 / $new_height)) * 1.27 * $opacity;
    imagecopy($new, $old, 0, $height + $i, 0, $height - $i - 1, $width, 1);
    imagefilledrectangle($new, 0, $height + $i, $width, $height + $i, imagecolorallocatealpha($new, 255, 255, 255, $alpha));
  }
  return _vae_store_file($iden, $new, "jpg", $filename, "jpeg");
}

function _vae_imagesize($d, $complain = false) {
  global $_VAE;
  if (!strlen($d)) return;
  $iden = $d."size";
  if ($cache = _vae_long_term_cache_get($iden)) return explode(",", $cache);
  $tk = _vae_gd_handle($d);
  if ($tk == null) {
    if ($complain && !file_exists($_VAE['config']['data_path'] . $d)) {
      _vae_error("You called <span class='c'>vae_imagesize()</span> but provided an invalid image filename.  Please do not include any path information in the filename.");
    }
    return null;
  }
  $w = @imagesx($tk);
  $h = @imagesy($tk);
  _vae_long_term_cache_set($iden, "$w,$h", 5);
  return array($w, $h);
}

function vae_imagesize($d) {
  return _vae_imagesize($d, true);
}

function vae_include($path, $once = false) {
  global $_VAE;
  if (substr($path, 0, 1) != "/") $path = "/" . $path;
  if ($once) {
    if (isset($_VAE['required_once'][$path])) return;
    $_VAE['required_once'][$path] = true;
  }
  if ($_VAE['local']) {
    $php = _vae_long_term_cache_get($_VAE['local'] . $path);
    if (strlen($php)) {
      return _vae_local_exec($php);
    } else {
      return _vae_local_needs($path);
    }
  } else {
    _vae_dependency_add($path);
    require($_SERVER['DOCUMENT_ROOT'] . $path);
  }
}

function vae_include_once($path) {
  return vae_include($path, true);
}

function vae_loggedin() {
  _vae_session_deps_add('__v:logged_in');
  return ($_SESSION['__v:logged_in'] ? $_SESSION['__v:logged_in']['id'] : false);
}

function vae_multipart_mail($from, $to, $subject, $text, $html) {
  _vae_multipart_mail($from, $to, $subject, $text, $html);
  return true;
}

function vae_newsletter_subscribe($code, $email) {
  return _vae_newsletter_subscribe($code, $email);
}

function vae_permalink($id) {
  if (!is_numeric($id)) _vae_error("You called <span class='c'>vae_permalink()</span> but provided an invalid ID.");
  $context = _vae_fetch($id);
  if ($context) return "http://" . $_SERVER['HTTP_HOST'] . "/" . $context->permalink(false);
  return "";
}

function vae_redirect($to, $trash_post_data = false) {
  global $_VAE;
  if ($_VAE['local_full_stack']) {
    $trace = debug_backtrace();
    foreach ($trace as $fn) {
      if ($fn['function'] == "vae_redirect") {
        $line = $fn['line'];
        continue;
      }
      $caller = $fn['function'] . ":" . $line;
      break;
    }
    _vae_local_log("[302]: $caller redirecting to $to");
  }
  if (!strlen($_VAE['force_redirect'])) {
    if (!_vae_is_xhr() && isset($_SESSION['__v:pre_ssl_host']) && !strstr($to, "://") && ($_SERVER['PHP_SELF'] != $to)) {
      $to = "http://" . $_SESSION['__v:pre_ssl_host'] . (substr($to, 0, 1) == "/" ? "" : "/") . $to;
      unset($_SESSION['__v:pre_ssl_host']);
    } elseif (strstr($to, "://") && !strstr($to, "://" . $_SERVER['HTTP_HOST'])) {
      $router = strstr($to, $_VAE['settings']['subdomain'] . ".vaesite.com") || strstr($to, $_VAE['settings']['subdomain'] . "-secure.vaesite.com");
      if ($_VAE['settings']['domain_ssl'] && strstr($to, $_VAE['settings']['subdomain'] . "." . $_VAE['settings']['domain_ssl'])) $router = true;
      if ($_VAE['settings']['domain_ssl'] && strstr($to, $_VAE['settings']['subdomain'] . "-staging." . $_VAE['settings']['domain_ssl'])) $router = true;
      foreach ($_VAE['settings']['domains'] as $domain => $garbage) {
        if (strstr($to, "://" . $domain) || strstr($to, "://www." . $domain)) {
          $router = true;
        }
      }
      if ($router) $to .= (strstr($to, "?") ? "&" : "?") . "__router=" . session_id();
    }
    $_VAE['force_redirect'] = $to;
    $_VAE['trash_post_data'] = $trash_post_data;
  }
  return "";
}

function vae_register_hook($name, $options_or_callback) {
  if (!is_array($options_or_callback)) $options_or_callback = array('callback' => $options_or_callback);
  return _vae_register_hook($name, $options_or_callback);
}

function vae_register_tag($name, $options) {
  return _vae_register_tag($name, $options);
}

function vae_render_tags($tag, $context, $true = true, $render_context = null) {
  return _vae_render_tags($tag, $context, $render_context, $true);
}

function vae_require($path) {
  return vae_include($path);
}

function vae_require_once($path) {
  return vae_include_once($path);
}

function vae_richtext($text, $options) {
  return _vae_htmlarea((string)$text, $options);
}

function vae_secure_token($name) {
  global $_VAE;
  return $_VAE['settings']['secure_tokens'][$name];
}

function vae_sizedimage($id, $size, $preserve_filename = false) {
  $id = trim($id);
  if (!strlen($id)) return "";
  return _vae_file($id . "-sized-" . $size, $id, "api/site/v1/image/" . $id, "&size=" . urlencode($size), $preserve_filename);
}

function vae_store_add_item_to_cart($id, $option_id = null, $qty = 1, $a = null, $notes = "") {
  if ($a == null) $a = array('name_field' => 'name', 'price_field' => 'price');
  if ($ret = _vae_store_add_item_to_cart($id, $option_id, $qty, $a, $notes, true)) {
    if (!_vae_store_verify_available(false)) {
      $ret = false;
    }
  }
  _vae_run_hooks("store:cart:updated");
  return $ret;
}

function vae_store_add_shipping_method($options) {
  global $_VAE;
  if (!isset($_SESSION['__v:store']['user_shipping_methods'])) $_SESSION['__v:store']['user_shipping_methods'] = array();
  foreach ($_SESSION['__v:store']['user_shipping_methods'] as $key => $method) {
    if ($method['title'] == $options['title']) {
      unset($_SESSION['__v:store']['user_shipping_methods'][$key]);
    }
  }
  $_SESSION['__v:store']['user_shipping_methods'][] = _vae_stringify_array($options);
  $_VAE['store_cached_shipping'] = null;
  unset($_SESSION['__v:store']['shipping']);
}

function vae_store_cart_item($id) {
  _vae_session_deps_add('__v:store');
  return $_SESSION['__v:store']['cart'][$id];
}

function vae_store_cart_items() {
  _vae_session_deps_add('__v:store');
  return $_SESSION['__v:store']['cart'];
}

function vae_store_checkout($a) {
  if ($a['payment_method']) $_SESSION['__v:store']['payment_method'] = $a['payment_method'];
  _vae_store_checkout($a);
}

function vae_store_clear_discount_code() {
  $_SESSION['__v:store']['discount_code'] = null;
  $_SESSION['__v:store']['user_discount'] = null;
  _vae_store_compute_discount();
}

function vae_store_add_cart_items_to_existing_order($order_id = null) {
  if ($order_id == null) {
    $order = vae_store_recent_order(true);
    $order_id = $order['id'];
  }
  $line_items = _vae_store_convert_cart_to_line_items();
  if (!count($line_items)) return false;
  $data = array('line_items' => $line_items);
  $ret = _vae_rest($data, "api/site/v1/store/orders/$order_id/add_line_items", "order", null, null, true);
  if ($ret != false) unset($_SESSION['__v:store']['cart']);
  return ($ret != false);
}

function vae_store_create_coupon_code($data) {
  $ret = _vae_rest($data, "api/site/v1/store_discount_codes/create", "store_discount_code");
  return ($ret != false);
}

function vae_store_create_order_comment($order_id, $data) {
  $ret = _vae_rest($data, "api/site/v1/store/orders/$order_id/create_comment", "comment");
  return ($ret != false);
}

function vae_store_create_tax_rate($data) {
  $ret = _vae_rest($data, "api/site/v1/store_tax_rates/create", "store_tax_rate");
  return ($ret != false);
}

function vae_store_current_user() {
  return _vae_store_current_user();
}

function vae_store_current_user_tags($tag = null) {
  $user = vae_store_current_user();
  if ($tag == null) {
    return $user['tags'];
  } else {
    return (in_array($tag, explode(", ", $user['tags'])));
  }
}

function vae_store_destroy_coupon_code($id = "") {
  if (!strlen($id)) _vae_error("You called <span class='c'>vae_destroy_coupon_code()</span> but didn't provide a proper ID.");
  $ret = _vae_rest(array(), "api/site/v1/store_discount_codes/destroy/" . $id, "store_discount_code");
  return ($ret != false);
}

function vae_store_destroy_tax_rate($id = "") {
  if (!strlen($id)) _vae_error("You called <span class='c'>vae_destroy_tax_rate()</span> but didn't provide a proper ID.");
  $ret = _vae_rest(array(), "api/site/v1/store_tax_rates/destroy/" . $id, "store_tax_rate");
  return ($ret != false);
}

function vae_store_destroy_all_tax_rates() {
  $ret = _vae_rest(array(), "api/site/v1/store_tax_rates/destroy_all", "store_tax_rate");
  return ($ret != false);
}

function vae_store_discount($amount, $code = "CUSTOM") {
  $_SESSION['__v:store']['discount_code'] = $code;
  $_SESSION['__v:store']['user_discount'] = number_format($amount, 2);
}

function vae_store_discount_code($code = null, $force = false) {
  if ($code == null) {
    $disc = _vae_store_find_discount($_SESSION['__v:store']['discount_code']);
    if (!is_array($disc)) $disc = false;
    return $disc;
  } else {
    if (!$force && strlen($_SESSION['__v:store']['discount_code'])) return false;
    $_SESSION['__v:store']['discount_code'] = preg_replace("/[^a-z0-9]/", "", strtolower($code));
    _vae_store_compute_discount(null, null, '', true);
    return true;
  }
}

function vae_store_find_coupon_code($code) {
  if (!strlen($code)) return false;
  if ($raw = _vae_rest(array(), "api/site/v1/store_discount_codes/verify/" . trim($code), "customer")) {
    if ($raw == "BAD") {
      $data = false;
    } else {
      $data = _vae_array_from_rails_xml(simplexml_load_string($raw));
    }
  }
  return $data;
}

function vae_store_handling_charge($amount) {
  $_SESSION['__v:store']['custom_handling'] = $amount;
}

function vae_store_logout() {
  unset($_SESSION['__v:store']['loggedin']);
  unset($_SESSION['__v:store']['previous_orders']);
}

function vae_store_orders($finders = null) {
  if (!is_array($finders)) $finders = array();
  if ($finders['ids'] && (is_array($finders['ids']))) {
    $finders['ids'] = implode(",", $finders['ids']);
  }
  $raw = _vae_rest($finders, "api/site/v1/store/orders", "order", array(), array(), true);
  return _vae_store_transform_orders($raw);
}

function vae_store_payment_method() {
  global $_VAE;
  _vae_store_set_default_payment_method();
  return $_VAE['store']['payment_methods'][$_SESSION['__v:store']['payment_method']]['name'];
}

function vae_store_previous_orders($populate_even_if_not_logged_in = false) {
  $pdata = array();
  if (isset($_SESSION['__v:store']['previous_orders']) && !$_REQUEST['__debug']) {
    $pdata = $_SESSION['__v:store']['previous_orders'];
  } elseif ($_SESSION['__v:store']['loggedin'] || ($populate_even_if_not_logged_in && $_SESSION['__v:store']['customer_id'])) {
    $raw = _vae_rest(array(), "api/site/v1/store/previous_orders/" . $_SESSION['__v:store']['customer_id'], "order", $tag);
    $_SESSION['__v:store']['previous_orders'] = $pdata = _vae_store_transform_orders($raw);
  }
  return $pdata;
}

function vae_store_recent_order($all = false) {
  _vae_session_deps_add('__v:store');
  if ($all) return $_SESSION['__v:store']['recent_order_data'];
  return $_SESSION['__v:store']['recent_order'];
}

function vae_store_register($new_data) {
  if (!isset($_SESSION['__v:store']['user'])) $_SESSION['__v:store']['user'] = array();
  $data = $_SESSION['__v:store']['user'];
  foreach ($new_data as $k => $v) {
    $data[$k] = $v;
  }
  if (_vae_store_create_customer($data, null, true)) {
    return true;
  }
  return false;
}

function vae_store_remove_from_cart($cart_id) {
  _vae_session_deps_add('__v:store');
  unset($_SESSION['__v:store']['cart'][$cart_id]);
  foreach ($_SESSION['__v:store']['cart'] as $cid => $r) {
    if ($r['bundled_with'] == $cart_id) {
      unset($_SESSION['__v:store']['cart'][$cid]);
    }
  }
  _vae_run_hooks("store:cart:updated");
  return true;
}

function vae_store_set_tax($value) {
  $_SESSION['__v:store']['tax_override'] = $value;
}

function vae_store_shipping_tax_class($val = null) {
  return _vae_store_shipping_tax_class($val);
}

function vae_store_shipping_method($val = null) {
  if ($val) {
    $_SESSION['__v:store']['shipping']['selected_index'] = $val;
  } else {
    _vae_store_compute_shipping();
  }
  return $_SESSION['__v:store']['shipping']['options'][$_SESSION['__v:store']['shipping']['selected_index']]['title'];
}

function vae_store_shipping_methods() {
  return $_SESSION['__v:store']['shipping']['options'];
}

function vae_store_tax_rate() {
  return $_SESSION['__v:store']['tax_rate'];
}

function vae_store_total_weight($weight) {
  if (!is_array($weight)) $weight = array($weight);
  $_SESSION['__v:store']['total_weight'] = $weight;
}

function vae_store_update_cart_item($id, $data) {
  _vae_session_deps_add('__v:store');
  unset($_VAE['store_cached_number_of_items']);
  unset($_VAE['store_cached_shipping']);
  unset($_VAE['store_cached_subtotal']);
  unset($_VAE['store_cached_tax']);
  if (!isset($_SESSION['__v:store']['cart'][$id])) return false;
  $data = _vae_stringify_array($data);
  $new_data = array_merge($_SESSION['__v:store']['cart'][$id], $data);
  if (!$data['total'] && ($data['price'] || $data['qty'])) $new_data['total'] = $new_data['price'] * $new_data['qty'];
  $_SESSION['__v:store']['cart'][$id] = $new_data;
  return true;
}

function vae_store_update_coupon_code($id, $data) {
  if (!strlen($id)) _vae_error("You called <span class='c'>vae_store_update_coupon_code()</span> but didn't provide a proper ID.");
  $ret = _vae_rest($data, "api/site/v1/store_discount_codes/update/" . $id, "store_discount_code");
  return ($ret != false);
}

function vae_store_update_tax_rate($id, $data) {
  if (!strlen($id)) _vae_error("You called <span class='c'>vae_store_update_tax_rate()</span> but didn't provide a proper ID.");
  $ret = _vae_rest($data, "api/site/v1/store_tax_rates/update/" . $id, "store_tax_rate");
  return ($ret != false);
}

function vae_store_update_order($order_id, $attributes = null) {
  if (!is_array($attributes)) return false;
  if (!is_numeric($order_id)) _vae_error("You called <span class='c'>vae_store_update_order()</span> but didn't provide a proper ID.");
  $ret = _vae_rest($attributes, "api/site/v1/store/orders/$order_id/update", "order", array(), null, true);
  return ($ret != false);
}

function vae_store_update_order_line_items($order_id = null, $line_items = null) {
  if ($order_id == null) {
    $order = vae_store_recent_order(true);
    $order_id = $order['id'];
  }
  if (!$line_items) $line_items = array();
  if (!count($line_items)) return false;
  $data = array();
  foreach ($line_items as $id => $qty) {
    $data["item_" . $id . "_qty"] = $qty;
  }
  $ret = _vae_rest($data, "api/site/v1/store/orders/$order_id/update_line_items", "order", null, null, true);
  return ($ret != false);
}

function vae_store_update_order_status($order_id, $status) {
  if ($status != "Processing" && $status != "Ordered" && $status != "Shipped") return false;
  if (!is_numeric($order_id)) _vae_error("You called <span class='c'>vae_store_update_order_status()</span> but didn't provide a proper ID.");
  $ret =_vae_rest(array(), "api/site/v1/store/orders/$order_id/update_status?status=" . $status, "order", null, null, true);
  return ($ret != false);
}

function vae_style($r, $urlize = true) {
  $r = htmlspecialchars(trim(stripslashes($r)));
  if ($urlize) $r = _vae_urlize($r);
  return nl2br($r);
}

function vae_template_mail($from, $to, $subject, $template, $text_yield = null, $html_yield = null) {
  $html_template = _vae_find_source($template);
  $text_template = _vae_find_source($template . ".txt");
  if (($html = _vae_proxy($html_template, "", true, $html_yield)) == false) return _vae_error("Unable to build Mail Template E-Mail (HTML version) file from <span class='c'>" . _vae_h($template) . "</span>.  You can debug this by loading that file directly in your browser.");
  if (($text = _vae_proxy($text_template, "", true, $text_yield)) == false) return _vae_error("Unable to build Mail Template E-Mail (text version) file from <span class='c'>" . _vae_h($template) . "</span>.  You can debug this by loading that file directly in your browser.");
  $html = _vae_rest(array('html' => $html), "api/site/v1/premailer", "premailer");
  return vae_multipart_mail($from, $to, $subject, $text, $html);
}

function vae_text($text, $font_name = "", $font_size = "22", $color = "#000000", $kerning = 1, $padding = 5, $max_width = 10000) {
  global $_VAE;
  if (!is_numeric($font_size)) $font_size = 18;
  if (!is_numeric($kerning)) $kerning = 1;
  if (!is_numeric($padding)) $padding = 5;
  if (!is_numeric($max_width)) $max_width = 10000;
  $iden = "TEXT-$text-$font_name-$font_size-$color-$kerning-$max_width";
  if ($cache = _vae_long_term_cache_get($iden)) {
    $file_name = $cache;
  } else {
    setlocale(LC_CTYPE, "ge");
    foreach (array($_VAE['config']['data_path'], dirname(__FILE__) . "/", $_SERVER['DOCUMENT_ROOT'] . "/", dirname($_SERVER['SCRIPT_FILENAME']) . "/") as $dir) {
      foreach (array("", ".otf", ".ttf") as $ext) {
        if (file_exists($dir . $font_name . $ext)) $font = $dir . $font_name . $ext;
      }
    }
    if (!isset($font)) return _vae_error("Could not find font <span class='c'>" . _vae_h($font_name) . "</span>");
    $nxpos = $width = 0;
    $rows = 1;
    $row_breaks = array();
    for ($i = 0; $i < strlen($text); $i++) {
      $char = substr($text, $i, 1);
      list($lx, $ly, $rx, $ry) = imagettfbbox($font_size, 0, $font, $char);
      $nxpos += $rx + $kerning;
      if ($char == " ") {
        $last_word_nxpos = $nxpos;
        $last_word_i = $i;
      }
      if ($nxpos > $max_width) {
        if (!isset($row_breaks[$last_word_i])) {
          $nxpos -= $last_word_nxpos;
          $rows++;
          $row_breaks[$last_word_i] = true;
        }
      } elseif ($nxpos > $width) {
        $width = $nxpos;
      }
    }
    $height = ($font_size * 1.2 * $rows) + ($padding * 2);
    $im = imagecreatetruecolor($width, $height);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $color = _vae_html2rgb($color);
    $background = imagecolorallocate($im, $bkg[0], $bkg[1], $bkg[2]);
    $font_color = imagecolorallocate($im, $color[0], $color[1], $color[2]);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    $row = 1;
    for ($i = 0; $i < strlen($text); $i++) {
      $value = substr($text, $i, 1);
      if ($pval) {
        list($lx, $ly, $rx, $ry) = imagettfbbox($font_size, 0, $font, $pval);
        $nxpos += $rx + $kerning;
      } else {
        $nxpos = 0;
      }
      imagettftext($im, $font_size, 0, $nxpos, (($font_size + $padding) * $row), $font_color, $font, $value);
      $pval = $value;
      if (isset($row_breaks[$i])) {
        $row++;
        unset($pval);
      }
    }
    $file_name = _vae_store_file($iden, $im, "png", null, "png");
  }
  return '<img src="' . $_VAE['config']['data_url'] . $file_name . '" alt="' . $text . '" title="' . $text . '" />';
}

function vae_tick($desc) {
  return _vae_tick($desc, true);
}

function vae_update($id, $data, $update_frontend = true) {
  if (!is_numeric($id)) _vae_error("You called <span class='c'>vae_update()</span> but didn't provide a proper ID.");
  return _vae_update($id, $data, $update_frontend);
}

function vae_users_current_user() {
  return _vae_users_current_user();
}

function vae_video($id, $video_size = "") {
  return _vae_file($id . "-video-" . $video_size, $id, "api/site/v1/file/" . $id, ($video_size ? "&size=" . urlencode($video_size) : ""));
}

function vae_watermark($image, $watermark_image, $vertical_align = "", $align = "", $vertical_padding = "", $horizontal_padding = "") {
  global $_VAE;
  if ($vertical_padding == "") $vertical_padding = 5;
  if ($horizontal_padding == "") $horizontal_padding = 5;
  $iden = "$image-$watermark_image-$vertical_align-$align-$vertical_padding-$horizontal_padding";
  if ($cache = _vae_long_term_cache_get($iden)) return $cache;
  $tk = _vae_gd_handle($image);
  if ($tk == null || !file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $watermark_image)) return null;
  $tlogo = @imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . "/" . $watermark_image);
  if ($tlogo == null) return null;
	$logowidth = ImageSX($tlogo);
	$logoheight = ImageSY($tlogo);
	$twidth = ImageSX($tk);
	$theight = ImageSY($tk);
	switch($vertical_align) {
		case 'top': $aoben = 0 + $vertical_padding; break;
		case 'middle': $aoben = $theight/2 - $logoheight/2; break;
		default: $aoben = $theight - $logoheight - $vertical_padding; break;
	}
	switch($align) {
		case 'left': $alinks =  $horizontal_padding; break;
		case 'center': $alinks = $twidth/2 - $logowidth/2; break;
	  default: $alinks = $twidth - $logowidth - $horizontal_padding; break;
	}
	imagecopy($tk, $tlogo, $alinks, $aoben, 0, 0, $logowidth, $logoheight);
  return _vae_store_file($iden, $tk, "jpg", null, "jpeg");
}
