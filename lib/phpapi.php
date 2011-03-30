<?php

function verb_asset($id, $width = "", $height = "", $quality = "", $preserve_filename = false) {
  if (!strlen($id)) return "";
  $iden = $id . "-" . $width . "-" . $height;
  if ($quality) $iden .= "-qual-" . $quality;
  return _verb_file($iden, $id, "assets/get/" . $id, "&direct=1" . ($width ? "&width=" . $width : "") . ($height ? "&height=" . $height : "") . ($quality ? "&quality=" . $quality : ""), $preserve_filename);
}

function verb_cache($key, $timeout = 3600, $function = "", $global = false) {
  global $_VERB;
  if (!strlen($key)) _verb_error("You called <span class='c'>verb_cache()</span> but didn't provide a cache key.");
  if ($function == "") $function = $key;
  $key = ($global ? $_VERB['global_cache_key'] : $_VERB['cache_key']) . $key;
  $cached = memcache_get($_VERB['memcached'], $key);
  if (is_array($cached) && $cached[0] == "chks") {
    return $cached[1];
  }
  $out = $function();
  if (!$_REQUEST['__debug'] && !$_REQUEST['__verb_local']) memcache_set($_VERB['memcached'], $key, array("chks", $out), 0, $timeout);
  return $out;
}

function verb_cdn_url() {
  global $_VERB;
  if (isset($_VERB['config']['cdn_url'])) return $_VERB['config']['cdn_url'];
  if ($_REQUEST['__verb_local']) return "/";
  return "http" . (($_SERVER['HTTPS'] || $_REQUEST['__verb_ssl_router']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/";
}

function verb_create($structure_id, $row_id, $data) {
  if (!is_numeric($structure_id)) _verb_error("You called <span class='c'>verb_create()</span> but didn't provide a proper structure ID.");
  return _verb_create($structure_id, $row_id, $data);
}

function verb_customer($id) {
  if (!is_numeric($id)) _verb_error("You called <span class='c'>verb_customer()</span> but didn't provide a proper customer ID.");
  $raw = _verb_rest(array(), "customers/show/" . $id, "customer", array());
  if ($raw == false) return false;
  return _verb_array_from_rails_xml(simplexml_load_string($raw));
}

function verb_data_path() {
  global $_VERB;
  _verb_load_settings();
  return $_VERB['config']['data_path'];
}

function verb_data_url() {
  global $_VERB;
  _verb_load_settings();
  return $_VERB['config']['data_url'];
}

function verb_disable_verbml() {
  ob_end_clean();
  return true;
}

function verb_file($id, $preserve_filename = false) {
  if (!strlen($id)) return "";
  return _verb_file($id . "-file", $id, "file/" . $id, "", $preserve_filename);
}

function verb_flash($message, $type = 'msg') {
  if (!strlen($message)) _verb_error("You called <span class='c'>verb_flash()</span> but didn't provide a proper message.");
  return _verb_flash($message, $type);
}

function verb_image($id, $width = "", $height = "", $image_size = "", $grow = "", $quality = "", $preserve_filename = false) {
  $id = trim($id);
  if (!strlen($id)) return "";
  $iden = $id . "-" . $width . "-" . $height;
  if ($image_size) $iden .= "-" . $image_size;
  if ($quality) $iden .= "-q" . $quality;
  if ($grow) $iden .= "-g";
  return _verb_file($iden, $id, "image/" . $id, ($width ? "&width=" . $width : "") . ($height ? "&height=" . $height : "") . ($image_size ? "&size=" . rawurlencode($image_size) : "") . ($quality ? "&quality=" . $quality : "") . ($grow ? "&grow=1" : ""), $preserve_filename);
}

function _verb_image_filter_prepare($image, $iden_string, $func, $internal) {
  global $_VERB;
  $iden = $image . "-" . $iden_string;
  _verb_load_cache();
  if (isset($_VERB['file_cache'][$iden])) return $_VERB['file_cache'][$iden];
  $old = _verb_gd_handle($image);
  if (!$old) {
    if (!$internal) {
      _verb_error("You called <span class='c'>" . _verb_h($func) . "()</span> but provided an invalid image filename.  Please do not include any path information in the filename.");
    }
    return false;
  }
  $width = imagesx($old);
  $height = imagesy($old);
  $sep = explode(".", $image);
  $filename = ($_VERB['settings']['preserve_filenames'] ? $sep[0] : false);
  return array($iden, $old, $width, $height, $filename);
}

function verb_image_grey($image, $internal = false) {
  $ret = _verb_image_filter_prepare($image, "grey2", "verb_image_grey", $internal);
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
  return _verb_store_file($iden, $new, "jpg", $filename, "jpeg");
}

function verb_image_reflect($image, $reflection_size = 30, $opacity = 35, $internal = false) {
  $ret = _verb_image_filter_prepare($image, "reflect2-" . $reflection_size . "-" . $opacity, "verb_image_reflect", $internal);
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
  return _verb_store_file($iden, $new, "jpg", $filename, "jpeg");
}

function _verb_imagesize($d, $complain = false) {
  global $_VERB;
  if (!strlen($d)) return;
  $iden = $d."size";
  if (isset($_VERB['file_cache'][$iden])) return explode(",", $_VERB['file_cache'][$iden]);
  $tk = _verb_gd_handle($d);
  if ($tk == null) {
    if ($complain && !file_exists($_VERB['config']['data_path'] . $d)) {
      _verb_error("You called <span class='c'>verb_imagesize()</span> but provided an invalid image filename.  Please do not include any path information in the filename.");
    }
    return null;
  }
  $w = @imagesx($tk);
  $h = @imagesy($tk);
  _verb_store_files($iden, "$w,$h");
  return array($w, $h);
}

function verb_imagesize($d) {
  return _verb_imagesize($d, true);
}

function verb_include($path, $once = false) {
  global $_VERB;
  if (substr($path, 0, 1) != "/") $path = "/" . $path;
  if ($once) {
    if (isset($_VERB['required_once'][$path])) return;
    $_VERB['required_once'][$path] = true;
  }
  if ($_VERB['local']) {
    $php = memcache_get($_VERB['memcached'], $_VERB['local'] . $path);
    if (strlen($php)) {
      return _verb_local_exec($php);
    } else {
      return _verb_local_needs($path);
    }
  } else {
    _verb_dependency_add($path);
    require($_SERVER['DOCUMENT_ROOT'] . $path);
  }
}

function verb_include_once($path) {
  return verb_include($path, true);
}

function verb_loggedin() {
  _verb_session_deps_add('__v:logged_in');
  return ($_SESSION['__v:logged_in'] ? $_SESSION['__v:logged_in']['id'] : false);
}

function verb_multipart_mail($from, $to, $subject, $text, $html) {
  _verb_multipart_mail($from, $to, $subject, $text, $html);
  return true;
}

function verb_newsletter_subscribe($code, $email) {
  return _verb_newsletter_subscribe($code, $email);
}

function verb_permalink($id) {
  if (!is_numeric($id)) _verb_error("You called <span class='c'>verb_permalink()</span> but provided an invalid ID.");
  $context = _verb_fetch($id);
  if ($context) return "http://" . $_SERVER['HTTP_HOST'] . "/" . $context->permalink(false);
  return "";
}

function verb_redirect($url) {
  _verb_callback_redirect($url);
  return true;
}

function verb_register_hook($name, $options_or_callback) {
  if (!is_array($options_or_callback)) $options_or_callback = array('callback' => $options_or_callback);
  return _verb_register_hook($name, $options_or_callback);
}

function verb_register_tag($name, $options) {
  return _verb_register_tag($name, $options);
}

function verb_render_tags($tag, $context, $true = true) {
  return _verb_render_tags($tag, $context, null, $true);
}

function verb_require($path) {
  return verb_include($path);
}

function verb_require_once($path) {
  return verb_include_once($path);
}

function verb_richtext($text, $options) {
  return _verb_htmlarea($text, $options);
}

function verb_sizedimage($id, $size, $preserve_filename = false) {
  $id = trim($id);
  if (!strlen($id)) return "";
  return _verb_file($id . "-sized-" . $size, $id, "image/" . $id, "&size=" . urlencode($size), $preserve_filename);
}

function verb_store_add_item_to_cart($id, $option_id = null, $qty = 1, $a = null, $notes = "") {
  if ($a == null) $a = array('name_field' => 'name', 'price_field' => 'price');
  if ($ret = _verb_store_add_item_to_cart($id, $option_id, $qty, $a, $notes, true)) {
    _verb_store_verify_available();
    _verb_run_hooks("store:cart:updated");
  }
  return $ret;
}

function verb_store_add_shipping_method($options) {
  if (!isset($_SESSION['__v:store']['user_shipping_methods'])) $_SESSION['__v:store']['user_shipping_methods'] = array();
  foreach ($_SESSION['__v:store']['user_shipping_methods'] as $key => $method) {
    if ($method['title'] == $options['title']) {
      unset($_SESSION['__v:store']['user_shipping_methods'][$key]);
    }
  }
  $_SESSION['__v:store']['user_shipping_methods'][] = _verb_stringify_array($options);
}

function verb_store_cart_item($id) {
  _verb_session_deps_add('__v:store');
  return $_SESSION['__v:store']['cart'][$id];
}

function verb_store_cart_items() {
  _verb_session_deps_add('__v:store');
  return $_SESSION['__v:store']['cart'];
}

function verb_store_clear_discount_code() {
  $_SESSION['__v:store']['discount_code'] = null;
  _verb_store_compute_discount();
}

function verb_store_create_coupon_code($data) {
  $ret = _verb_rest($data, "store_discount_codes/create", "store_discount_code");
  return ($ret != false);
}

function verb_store_create_tax_rate($data) {
  $ret = _verb_rest($data, "store_tax_rates/create", "store_tax_rate");
  return ($ret != false);
}

function verb_store_current_user() {
  return _verb_store_current_user();
}

function verb_store_current_user_tags($tag = null) {
  $user = verb_store_current_user();
  if ($tag == null) {
    return $user['tags'];
  } else {
    return (in_array($tag, explode(", ", $user['tags'])));
  }
}

function verb_store_destroy_coupon_code($id = "") {
  if (!strlen($id)) _verb_error("You called <span class='c'>verb_destroy_coupon_code()</span> but didn't provide a proper ID.");
  $ret = _verb_rest(array(), "store_discount_codes/destroy/" . $id, "store_discount_code");
  return ($ret != false);
}

function verb_store_destroy_tax_rate($id = "") {
  if (!strlen($id)) _verb_error("You called <span class='c'>verb_destroy_tax_rate()</span> but didn't provide a proper ID.");
  $ret = _verb_rest(array(), "store_tax_rates/destroy/" . $id, "store_tax_rate");
  return ($ret != false);
}

function verb_store_destroy_all_tax_rates() {
  $ret = _verb_rest(array(), "store_tax_rates/destroy_all", "store_tax_rate");
  return ($ret != false);
}

function verb_store_discount_code($code = null, $force = false) {
  if ($code == null) {
    $disc = _verb_store_find_discount($_SESSION['__v:store']['discount_code']);
    if (!is_array($disc)) $disc = false;
    return $disc;
  } else {
    if (!$force && strlen($_SESSION['__v:store']['discount_code'])) return false;
    $_SESSION['__v:store']['discount_code'] = strtolower($code);
    _verb_store_compute_discount();
    return true;
  }
}

function verb_store_find_coupon_code($code) {
  if ($raw = _verb_rest(array(), "store_discount_codes/verify/" . trim($code), "customer")) {
    if ($raw == "BAD") {
      $data = false;
    } else {
      $data = _verb_array_from_rails_xml(simplexml_load_string($raw));
    }
  }
  return $data;
}

function verb_store_handling_charge($amount) {
  $_SESSION['__v:store']['custom_handling'] = $amount;
}

function verb_store_orders($finders = null) {
  if (!is_array($finders)) $finders = array();
  $raw = _verb_rest($finders, "store/orders", "order", array());
  return _verb_store_transform_orders($raw);
}

function verb_store_payment_method() {
  global $_VERB;
  _verb_store_set_default_payment_method();
  return $_VERB['store']['payment_methods'][$_SESSION['__v:store']['payment_method']]['name'];
}

function verb_store_recent_order($all = false) {
  _verb_session_deps_add('__v:store');
  if ($all) return $_SESSION['__v:store']['recent_order_data'];
  return $_SESSION['__v:store']['recent_order'];
}

function verb_store_remove_from_cart($cart_id) {
  _verb_session_deps_add('__v:store');
  unset($_SESSION['__v:store']['cart'][$cart_id]);
  foreach ($_SESSION['__v:store']['cart'] as $cid => $r) {
    if ($r['bundled_with'] == $cart_id) {
      unset($_SESSION['__v:store']['cart'][$cid]);
    }
  }
  _verb_run_hooks("store:cart:updated");
  return true;
}

function verb_store_shipping_method() {
  _verb_store_compute_shipping();
  return $_SESSION['__v:store']['shipping']['options'][$_SESSION['__v:store']['shipping']['selected_index']]['title'];
}

function verb_store_tax_rate() {
  return $_SESSION['__v:store']['tax_rate'];
}

function verb_store_total_weight($weight) {
  if (!is_array($weight)) $weight = array($weight);
  $_SESSION['__v:store']['total_weight'] = $weight;
}

function verb_store_update_cart_item($id, $data) {
  _verb_session_deps_add('__v:store');
  if (!isset($_SESSION['__v:store']['cart'][$id])) return false;
  $data = _verb_stringify_array($data);
  $new_data = array_merge($_SESSION['__v:store']['cart'][$id], $data);
  if (!$data['total'] && ($data['price'] || $data['qty'])) $new_data['total'] = $new_data['price'] * $new_data['qty'];
  $_SESSION['__v:store']['cart'][$id] = $new_data;
  return true;
}

function verb_store_update_coupon_code($id, $data) {
  if (!strlen($id)) _verb_error("You called <span class='c'>verb_update_coupon_code()</span> but didn't provide a proper ID.");
  $ret = _verb_rest($data, "store_discount_codes/update/" . $id, "store_discount_code");
  return ($ret != false);
}

function verb_store_update_tax_rate($id, $data) {
  if (!strlen($id)) _verb_error("You called <span class='c'>verb_update_tax_rate()</span> but didn't provide a proper ID.");
  $ret = _verb_rest($data, "store_tax_rates/update/" . $id, "store_tax_rate");
  return ($ret != false);
}

function verb_store_update_order($order_id, $attributes = null) {
  if (!is_array($attributes)) return false;
  $ret = _verb_rest($attributes, "store/update/" . $order_id, "store_order", array(), null, true);
  return ($ret != false);
}

function verb_store_update_order_status($order_id, $status) {
  if ($status != "Processing" && $status != "Ordered" && $status != "Shipped") return false;
  $ret =_verb_rest(array(), "store/update_status/" . $order_id . "?status=" . $status, "order", null, null, true);
  return ($ret != false);
}

function verb_style($r) {
  return nl2br(_verb_urlize(htmlspecialchars(trim(stripslashes($r)))));
}

function verb_template_mail($from, $to, $subject, $template, $text_yield = null, $html_yield = null) {
  $html_template = _verb_find_source($template);
  $text_template = _verb_find_source($template . ".txt");
  if (($html = _verb_proxy($html_template, "", true, $html_yield)) == false) return _verb_error("Unable to build Mail Template E-Mail (HTML version) file from <span class='c'>" . _verb_h($template) . "</span>.  You can debug this by loading that file directly in your browser.");
  if (($text = _verb_proxy($text_template, "", true, $text_yield)) == false) return _verb_error("Unable to build Mail Template E-Mail (text version) file from <span class='c'>" . _verb_h($template) . "</span>.  You can debug this by loading that file directly in your browser.");
  return verb_multipart_mail($from, $to, $subject, $text, $html);
}

function verb_text($text, $font_name = "", $font_size = "22", $color = "#000000", $kerning = 1, $padding = 5, $max_width = 10000) {
  global $_VERB;
  if (!is_numeric($font_size)) $font_size = 18;
  if (!is_numeric($kerning)) $kerning = 1;
  if (!is_numeric($padding)) $padding = 5;
  if (!is_numeric($max_width)) $max_width = 10000;
  $iden = "TEXT-$text-$font_name-$font_size-$color-$kerning-$max_width";
  _verb_load_cache();
  if (false && isset($_VERB['file_cache'][$iden])) {
    $file_name = $_VERB['file_cache'][$iden];
  } else {
    setlocale(LC_CTYPE, "ge");
    foreach (array($_VERB['config']['data_path'], dirname(__FILE__) . "/", $_SERVER['DOCUMENT_ROOT'] . "/", dirname($_SERVER['SCRIPT_FILENAME']) . "/") as $dir) {
      foreach (array("", ".otf", ".ttf") as $ext) {
        if (file_exists($dir . $font_name . $ext)) $font = $dir . $font_name . $ext;
      }
    }
    if (!isset($font)) return _verb_error("Could not find font <span class='c'>" . _verb_h($font_name) . "</span>");
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
    $color = _verb_html2rgb($color);
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
    $file_name = _verb_store_file($iden, $im, "png", null, "png");
  }
  return '<img src="' . $_VERB['config']['data_url'] . $file_name . '" alt="' . $text . '" title="' . $text . '" />';
}

function verb_tick($desc) {
  return _verb_tick($desc, true);
}

function verb_update($id, $data) {
  if (!is_numeric($id)) _verb_error("You called <span class='c'>verb_update()</span> but didn't provide a proper ID.");
  return _verb_update($id, $data);
}

function verb_users_current_user() {
  return _verb_users_current_user();
}

function verb_video($id, $video_size = "") {
  return _verb_file($id . "-video-" . $video_size, $id, "file/" . $id, ($video_size ? "&size=" . urlencode($video_size) : ""));
}

function verb_watermark($image, $watermark_image, $vertical_align = "", $align = "", $vertical_padding = "", $horizontal_padding = "") {
  global $_VERB;
  if ($vertical_padding == "") $vertical_padding = 5;
  if ($horizontal_padding == "") $horizontal_padding = 5;
  $iden = "$image-$watermark_image-$vertical_align-$align-$vertical_padding-$horizontal_padding";
  _verb_load_cache();
  if (isset($_VERB['file_cache'][$iden])) return $_VERB['file_cache'][$iden];
  $tk = _verb_gd_handle($image);
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
  return _verb_store_file($iden, $tk, "jpg", null, "jpeg");
}

function verb_zip_distance($from, $to, $zip_field = "zip") {
  $zips = array($from);
  if (is_array($to) || is_object($to)) {
    foreach ($to as $zip) {
      if (is_object($zip)) $zip = (string)$zip->$zip_field;
      $zips[] = $zip;
    }
  } else {
    $zips[] = $to;
  }
  $to_zips = verb_zip_lookup($zips);
  $answers = array();
  $lat1 = $to_zips[$from][0];
  $lon1 = $to_zips[$from][1];
  foreach ($to_zips as $zip => $point) {
    if ($zip == $from) continue;
    $lat2 = $point[0];
    $lon2 = $point[1];
    $answers[$zip] = acos(sin($lat1)*sin($lat2)+cos($lat1)*cos($lat2)*cos($lon2-$lon1)) * 3958.75;
  }
  if (is_array($to) || is_object($to)) {
    foreach ($to as $obj) {
      if (is_object($obj)) {
        $obj->distance = $answers[(string)$obj->$zip_field];
      }
    }
  } else {
    return $answers[$to];
  }
  return $answers;
}

function verb_zip_lookup($zips) {
  if (!is_array($zips)) $zips = array($zips);
  _verb_sql_connect();
  $realzips = array();
  foreach ($zips as $zip) {
    $realzips[] = _verb_sql_e($zip);
  }
  $data = array();
  $q = _verb_sql_q("SELECT `zip`,`latitude`,`longitude` FROM `zcta` WHERE `zip` IN ('" . implode("','", $realzips) . "')");
  while ($r = _verb_sql_r($q)) {
    $data[$r['zip']] = array(deg2rad($r['latitude']), deg2rad($r['longitude']));
  }
  return $data;
}
 
?>