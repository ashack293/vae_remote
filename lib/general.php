<?php

function _verb_absolute_data_url($path = "") {
  global $_VERB;
  if (substr($_VERB['config']['data_url'], 0, 4) == "http") return $_VERB['config']['data_url'] . $path;
  $proto = (($_REQUEST['__verb_ssl_router'] || $_SERVER['HTTPS']) ? "https" : "http");
  return $proto . "://" . $_SERVER['HTTP_HOST'] . $_VERB['config']['data_url'] . $path;
}

function _verb_akismet($a) {
  if (!strlen($a['akismet'])) return false;
  $comment['comment_author'] = $_REQUEST[$a['akismet_name_field']];
  $comment['comment_author_email'] = $_REQUEST[$a['akismet_email_field']];
  $comment['comment_author_url'] = $_REQUEST[$a['akismet_url_field']];
  $comment['comment_content'] = $_REQUEST[$a['akismet_comment_field']];
	$comment['user_ip']    = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
	$comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
	$comment['referrer']   = $_SERVER['HTTP_REFERER'];
	$comment['blog']       = "http://" . $_SERVER['HTTP_HOST'];
	$ignore = array('HTTP_COOKIE','PHP_SESS_ID','PATH_TRANSLATED','SCRIPT_NAME','SCRIPT_FILENAME','SERVER_ADMIN','DOCUMENT_ROOT','PHPSESSID','PATH');
	foreach ($_SERVER as $key => $value) {
		if (!in_array($key, $ignore)) {
			$comment[$key] = $value;
		}
  }
	$query_string = '';
	foreach ($comment as $k => $v) {
	  if (is_string($v) && strlen($v)) $query_string .= $k . '=' . urlencode($v) . '&';
  }
  $host = $a['akismet'] . '.rest.akismet.com';
  $http_request  = "POST /1.1/comment-check HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
	$http_request .= "Content-Length: " . strlen($query_string) . "\r\n";
	$http_request .= "User-Agent: Verb/0.2.8 | Akismet/2.0\r\n";
	$http_request .= "\r\n";
	$http_request .= $query_string;
	$response = '';
	if ($_ENV['TEST']) return true;
	if (false != ($fs = @fsockopen($host, 80, $errno, $errstr, 10))) {
		fwrite($fs, $http_request);
		while (!feof($fs)) $response .= fgets($fs, 1160);
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	} 
	return ($response[1] == "true");
}

function _verb_append_js($old, $new) {
  $new = str_replace(array("\n", "\r"), "", $new);
  if (strlen($old)) {
    $old = trim($old);
    if (substr($old, strlen($old)-1, 1) != ";") $old .= ";";
  }
  return $old . " " . $new;
}

function _verb_asset_html($type, $src) {
  if ($type == "js") {
    return '<script type="text/javascript" src="' . $src . '"></script>' . "\n";
  } else {
    return '<link rel="stylesheet" type="text/css" media="' . $type . '" href="' . $src . '" />' . "\n";
  }
}

function _verb_attrs($attrs, $tagname) {
  global $_VERB;
  $out = "";
  if (count($attrs)) {
    foreach ($attrs as $a => $v) {
      if (in_array($a, $_VERB['attributes']['standard']) || (isset($_VERB['attributes'][$tagname]) && in_array($a, $_VERB['attributes'][$tagname])) && !in_array($a, array("ajax","default","validateinline"))) {
        if (!is_array($v)) $out .= " " . $a . "=\"" . htmlspecialchars($v) . "\"";
      }
    }
  }
  return $out;
}

function _verb_callback_redirect($to, $trash_post_data = false) {
  return _verb_render_redirect($to, $trash_post_data);
}

function _verb_cdn_timestamp_url($url) {
  if  (strstr($url, "://")) return $url;
  $timestamp = @filemtime($_SERVER['DOCUMENT_ROOT'] . $url);
  if ($timestamp < 1) $timestamp = time();
  return "/__cache/a" . $timestamp . $url;
}

function _verb_clear_login() {
  $_SESSION['__v:logged_in'] = false;
  if (!$_ENV['TEST']) echo "203 Logged Out";
  _verb_die();
}

function _verb_combine_array_keys($array, $keys) {
  $out = "";
  foreach ($keys as $key) {
    if (strlen($array[$key])) {
      if (strlen($out)) $out .= ", ";
      $out .= $array[$key];
    }
  }
  return $out;
}

function _verb_configure_php() {
  global $_VERB;
  error_reporting(E_ALL ^ E_NOTICE);
  set_exception_handler("_verb_exception_handler");
  //set_error_handler('_verb_error_handler', E_ALL ^ E_NOTICE ^ E_WARNING);
  date_default_timezone_set("America/New_York");
  ini_set('display_errors', isset($_REQUEST['__debug']));
  if ($_REQUEST['__router']) {
    session_id($_REQUEST['__router']);
    session_start();
    $uri = str_replace("__router=" . $_REQUEST['__router'], "", $_SERVER['REQUEST_URI']);
    $s = substr($uri, -1, 1);
    if ($s == "?" || $s == "&") $uri = substr($uri, 0, strlen($uri) - 1);
    @header("Location: " . $uri);
    _verb_die();
  }
  if (!$_REQUEST['__v:store_payment_method_ipn']) {
    session_start();
  }
  if ($_REQUEST['__skip_pdf']) $_VERB['skip_pdf'] = true;
  if ($_REQUEST['__proxy']) {
    $_SESSION = unserialize(memcache_get($_VERB['memcached'], "_proxy_" . $_REQUEST['__proxy']));
    if ($_REQUEST['__get_request_data']) {
      $_POST = unserialize(memcache_get($_VERB['memcached'], "_proxy_post_" . $_REQUEST['__proxy']));
      $_REQUEST = unserialize(memcache_get($_VERB['memcached'], "_proxy_request_" . $_REQUEST['__proxy']));
    }
    if ($_REQUEST['__get_yield']) {
      $_VERB['yield'] = memcache_get($_VERB['memcached'], "_proxy_yield_" . $_REQUEST['__proxy']);
    }
    $_VERB['from_proxy'] = true;
  }
  if ($_REQUEST['__host']) $_SERVER['HTTP_HOST'] = $_REQUEST['__host'];
}

function _verb_debug($msg) {
  global $_VERB;
  if (!is_string($msg)) $msg = serialize($msg);
  $_VERB['debug'] .= $msg . "\n";
}

function _verb_decimalize($amount, $decimal_places = 2) {
  return number_format($amount, $decimal_places, ".", "");
}

function _verb_dependency_add($filename, $md5 = null) {
  global $_VERB;
  if ($md5 == null) $md5 = @md5_file($_SERVER['DOCUMENT_ROOT'] . "/" . $filename);
  if (!isset($_VERB['dependencies'])) $_VERB['dependencies'] = array();
  $_VERB['dependencies'][$filename] = $md5;
}

function _verb_die() {
  if ($_ENV['TEST']) return;
  die();
}

function _verb_ele($a, $b, $c = null) {
  if (isset($c)) return $a[$b][$c];
  return $a[$b];
}

function _verb_escape_for_js($html) {
  return str_replace('"', "\\'", $html);
}

function _verb_error($msg, $debugging_info = "", $filename = null) {
  global $_VERB;
  if (_verb_in_ob() || $_REQUEST['__v:store_payment_method_ipn']) {
    throw new VerbException($msg, $debugging_info, $filename);
  } else {
    echo _verb_render_error(new VerbException($msg, $debugging_info, $filename));
  }
  _verb_die();
}

function _verb_error_handler($errno, $errstr) {
  _verb_error($errstr);
}

function _verb_exception_handler($e) {
  if ($_ENV['TEST']) return;
  ob_end_clean();
  echo _verb_render_error($e);
}

function _verb_fetch_multiple($path = "*", $context = null) {
  global $_VERB;
  $out = "";
  if ($options == null) $options = array();
  if ($options['asset_width'] == null) $options['asset_width'] = 500;
  if ($options['asset_height'] == null) $options['asset_height'] = $options['asset_width'];
  foreach (explode(",", $path) as $p) {
    if (substr($p, 0, 1) != "@") $p = "@" . $p;
    $value = _verb_fetch($p, $context);
    if (strlen($value) && strlen($out)) $out .= " - "; 
    if ($value->type == "ImageItem") {
      $out .= '<img src="' . _verb_absolute_data_url(verb_image($value, $options['asset_width'], $options['asset_height'])) . '" />';
    } elseif ($value->type == "HtmlAreaItem") {
      $out .= _verb_htmlarea($value, $options, true);
    } else {
      $out .= $value;
    }
  }
  return $out;
}

function _verb_file($iden, $id, $path, $qs = "", $preserve_filename = false) {
  global $_VERB;
  if (!strlen($id)) return "";
  if ($_ENV['TEST']) return array($iden, $id, $path, $qs, $preserve_filename);
  _verb_load_cache();
  $filename = null;
  if ($preserve_filename) $iden .= ($preserve_filename === true ? "-p" : "-" . $preserve_filename);
  if (isset($_VERB['file_cache'][$iden])) return $_VERB['file_cache'][$iden];
  _verb_lock_acquire();
  if (isset($_VERB['file_cache'][$iden])) return _verb_lock_release($_VERB['file_cache'][$iden]);
  $url = $_VERB['config']['backlot_url'] . "/"  . $path . "?secret_key=" . $_VERB['config']['secret_key'] . $qs;
  $fp = @fopen($url, 'rb');
  if ($fp) {
    $meta_data = stream_get_meta_data($fp);
    foreach($meta_data['wrapper_data'] as $response) {
      if (strstr($response, "Content-Disposition: attachment; filename=")) {
        $sep = explode(".", str_replace(array("Content-Disposition: attachment; filename=", "\""), "", $response));
        $ext = array_pop($sep);
        if ($preserve_filename) {
          $filename = ($preserve_filename === true ? implode(".", $sep) : $preserve_filename);
        }
      }
    }
    while (!feof($fp)) $file .= fread($fp, 8192);
    fclose($fp);
  }
  if ($file == "693 Not yet encoded") return "tryagain.flv";
  if (!strlen($file)) return _verb_debug("Couldn't fetch remote file " . $id);
  return _verb_store_file($iden, $file, $ext, $filename);
}

function _verb_final($out) {
  throw new VerbFragment($out);
}

function _verb_find_dividers($tag) {
  $dividers = array();
  foreach ($tag['tags'] as $itag) {
    if ($itag['type'] == "divider" || $itag['type'] == "nested_divider") {
      $divider = array('type' => $itag['type']);
      $divider['every'] = (is_numeric($itag['attrs']['every']) ? $itag['attrs']['every'] : 1);
      if (_verb_contains_yield($itag)) {
        $divider['to_merge'] = $itag;
      } else {
        $divider['out'] = _verb_render_tags($itag, $context, $render_context);
      }
      $dividers[] = $divider;
    }
  }
  return $dividers;
}

function _verb_find_source($file, $ext = "") {
  foreach (array(".html", ".haml", ".haml.php", ".php", "") as $ext) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $file . $ext)) return $file . $ext;
  }
  return false;
}

function _verb_flash($what, $type = 'msg', $which = "") {
  if (isset($_SESSION['__v:flash_new']) && isset($_SESSION['__v:flash_new']['messages']) && count($_SESSION['__v:flash_new']['messages'])) {
    foreach ($_SESSION['__v:flash_new']['messages'] as $msg) {
      if ($msg['msg'] == $what) return;
    }
  }
  $_SESSION['__v:flash_new']['messages'][] = array('msg' => $what, 'type' => $type, 'which' => $which);
  return true;
}

function _verb_flash_are_errors() {
  if (count($_SESSION['__v:flash']['messages'])) {
    foreach ($_SESSION['__v:flash']['messages'] as $msg) {
      if ($msg['type'] == 'err') return true;
    }
  }
  return false;
}

function _verb_flash_errors($errors, $which = "") {
  if (count($errors)) {
    foreach ($errors as $e) {
      $errstr .= "<li>$e</li>";
    }
    _verb_flash("We found the following errors with your submission.  Please correct them and try again:<ul>$errstr</ul>", 'err', $which);
    return true;
  }
  return false;
}

function _verb_form_prepare($a, &$tag, $context, $render_context) {
  global $_VERB;
  if ($a['_verb_form_prepared']) return $a;
  if ($a['path']) {
    $find_path =  ((substr($a['path'], 0, 8) == "confirm_") ? substr($a['path'], 8) : $a['path']);
    if (($value = _verb_request_param($find_path, true)) && !is_array($value)) {
      $a['value'] = htmlentities($value);
    } elseif (!$render_context->get("form_create_mode")) {
      $a['value'] = _verb_fetch_without_errors($find_path, $context);
    }
    if (!isset($a['name'])) {
      $a['name'] = $a['path'];
      if ($context) {
        $id = $context->formId();
        if (($id > 0) && ($render_context->get("form_context") != $context)) {
          if (!isset($a['id'])) $a['id'] = $a['name'] . "_" . $id; 
          $a['name'] .= "[" . $id . "]";
        }
      }
    }
    if (!isset($a['id'])) $a['id'] = $a['name'];    
  } else {
    if (($value = _verb_request_param($a['name'], true)) && _verb_flash_are_errors() && !is_array($value)) {
      $a['value'] = htmlentities($value);
    }
  }
  if ($a['required']) {
    $special_requires = array('email','url','date','name','number','digits','creditcard');
    $class = (in_array($a['required'], $special_requires) ? "required " . $a['required'] : "required");
    $a['class'] .= " " . $class;
  }
  if ($a['default'] && !strlen($a['value'])) $a['value'] = $a['default'];
  $tag['callback']['_form_prepared'] = true;
  $a['_verb_form_prepared'] = true;
  return $a;
}

function _verb_format_for_rss($input) {
  return htmlspecialchars(str_replace(array("\r", "\n"), " ", $input));
}

function _verb_gd_handle($d) {
  global $_VERB;
  $ll = $_VERB['config']['data_path'] . $d;
  if (!file_exists($ll) || is_dir($ll)) return null;
  if (strstr(strtolower($d), ".gif")) $tk = @imagecreatefromgif($ll);
  elseif (strstr(strtolower($d), ".png")) $tk = @imagecreatefrompng($ll);
  else $tk = @imagecreatefromjpeg($ll);
  return $tk;
}

function _verb_get_else(&$tag, $context, $render_context, $message = "") {
  global $_VERB;
  if (is_object($render_context)) {
    $render_context->set_in_place("else");
    $render_context->set_in_place("else2");
    if (!isset($_VERB['settings']['child_v_else'])) $render_context->set_in_place("else_message", $message);
  }
  if (count($tag['tags']) && isset($_VERB['settings']['child_v_else'])) {
    for ($i = 0; $i < count($tag['tags']); $i++) {
      if ($tag['tags'][$i]['type'] == "else") {
        return _verb_render_tags($tag['tags'][$i], $context, $render_context);
      }
    }
  }
  return (isset($_VERB['settings']['child_v_else']) ? $message : "");
}

function _verb_global_id($index = "") {
  global $_VERB;
  if (!strlen($index)) {
    if ($_ENV['TEST']) $index = "TESTGLOBID";
    else $index = md5(rand().microtime());
  }
  if (!isset($_VERB['globalid'])) $_VERB['globalid'] = "verb_generated_might_change";
  return $_VERB['globalid'] . "_" . $index;
}

function _verb_h($text, $charset = null) {
  if (is_array($text)) return array_map('h', $text);
  if (empty($charset)) $charset = 'UTF-8';
  return htmlspecialchars($text, ENT_QUOTES, $charset);
}

function _verb_handleob($verbml) {
  global $_VERB;
  try {
    _verb_tick("Parse PHP/HTML code and execute PHP Code", true);
    if ($_REQUEST['__debug']) {
      unset($_SESSION['__v:store']['shipping']);
    }
    $out = _verb_interpret_verbml($verbml);
    if ((strlen($_VERB['debug']) || $_REQUEST['__force']) && $_REQUEST['__debug']) _verb_error("Debugging Traces Available");
    if (isset($_VERB['run_hooks'])) {
      _verb_update_feed(false);
      foreach ($_VERB['run_hooks'] as $to_run) {
        _verb_run_hooks($to_run[0], $to_run[1]);
      }
    }
    if (isset($_VERB['ticks'])) return _verb_render_timer();
    if (($_SERVER['HTTPS'] || $_REQUEST['__verb_ssl_router']) && !$_VERB['ssl_required'] && !$_REQUEST['__verb_local'] && !$_REQUEST['__xhr']) {
      $_VERB['force_redirect'] = "http://" . ($_SESSION['__v:pre_ssl_host'] ? $_SESSION['__v:pre_ssl_host'] : $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
    }
    if (isset($_VERB['force_redirect']) && $_SESSION['__v:flash']['redirected']) {
      if (isset($_SESSION['__v:flash']) && isset($_SESSION['__v:flash']['messages']) && count($_SESSION['__v:flash']['messages'])) {
        foreach ($_SESSION['__v:flash']['messages'] as $m) {
          $_SESSION['__v:flash_new']['messages'][] = $m;
        }
      }
    } elseif (isset($_SESSION['__v:flash_new']) && isset($_SESSION['__v:flash_new']['messages']) && count($_SESSION['__v:flash_new']['messages'])) {
      if (!isset($_VERB['force_redirect'])) $_VERB['force_redirect'] = $_SERVER['PHP_SELF'];
      if (_verb_is_xhr() && ($_VERB['force_redirect'] == $_SERVER['PHP_SELF'])) {
        foreach ($_SESSION['__v:flash_new']['messages'] as $m) {
          if ($m['type'] == "err") {
            return "__err=" . strip_tags(str_replace("<li>", "\\n - ", $m['msg']));
          }
        }
      }
    }
    if (isset($_VERB['force_redirect'])) $_SESSION['__v:flash_new']['redirected'] = 1;
    if (count($_POST) && !$_VERB['trash_post_data']) $_SESSION['__v:flash_new']['post'] = $_POST;
    if (isset($_SESSION['__v:flash_new'])) {
      $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    } else {
      unset($_SESSION['__v:flash']);
    }
    unset($_SESSION['__v:error_handling']);
    if (isset($_VERB['session_cookies'])) {
      foreach ($_VERB['session_cookies'] as $k => $v) {
        $_SESSION[$k] = $v;
      }
    }
    unset($_SESSION['__v:flash_new']);
    if (isset($_VERB['final'])) return $_VERB['final'];
    if (isset($_VERB['force_redirect'])) {
      $url = $_VERB['force_redirect'];
      if (_verb_is_xhr()) $url .= (strstr($url, "?") ? "&" : "?") . "__xhr=1";
      if ($_REQUEST['__debug']) $url .= (strstr($url, "?") ? "&" : "?") . "__debug=" . $_REQUEST['__debug'];
      if ($_REQUEST['__host']) $url .= (strstr($url, "?") ? "&" : "?") . "__host=" . $_REQUEST['__host'];
      if (strstr($url, "<script>")) $url = "/";
      if (_verb_is_xhr() && strstr($url, "www.paypal.com")) {
        return "<script type='text/javascript'>window.location.href='" . $url . "'; window.vRedirected = true;</script>";
      }
      @header("Location: " . $url);
      return "Redirecting to " . _verb_h($url);
    }
    if (strtolower(substr($_SERVER['SCRIPT_FILENAME'], -4)) == ".xml") @header("Content-Type: application/xml");
    elseif (strtolower(substr($_SERVER['SCRIPT_FILENAME'], -4)) == ".rss" || $_VERB['serve_rss']) @header("Content-Type: application/rss+xml");
    if ($out == "__STREAM__") return file_get_contents($_VERB['stream']);
    $out = _verb_merge_session_data($out);
  } catch (Exception $e) {
    if (get_class($e) == "TException" && !isset($_SESSION['__v:error_handling']['recover_from_thrift_exception'])) {
      $_SESSION['__v:error_handling']['recover_from_thrift_exception'] = true;
      sleep(5);
      Header("Location: " . $_SERVER['PHP_SELF']);
      return "";
    }
    return _verb_render_error($e);
  }
  return $out;
}

function _verb_hide_dir($filename) {
  return str_replace($_SERVER['DOCUMENT_ROOT'], "", str_replace("/ebs/vhosts", "/var/www/vhosts", $filename));
}

function _verb_html2rgb($color) {
  if ($color[0] == '#') $color = substr($color, 1);
  if (strlen($color) == 6) list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
  elseif (strlen($color) == 3) list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
  else return array(0, 0, 0);
  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);
  return array($r, $g, $b);
}

function _verb_htmlarea($text, $a, $offsite = false) {
  global $_VERB;
  $text = _verb_urlize($text);
  $section = $a['section'];
  $width = $a['asset_width'];
  $height = $a['asset_height'];
  $quality = $a['asset_quality'];
	$preserve_filename = ($a['asset_filename'] ? '"' . $a['asset_filename'] . '"' : "false");
	if (strstr($text, "<v")) {
	  list($parse_tree, $render_context) = _verb_parse_verbml($text, "[Rich Text Structure]");
    $text = _verb_render_tags($parse_tree, null, $render_context);
	}
  if (strlen($section)) {
    if (strstr($section, "+")) {
      $section = str_replace("+", "", $section);
      if (is_numeric($section)) {
        $e = explode("<hr />", $text, $section + 1);
        $text = $e[$section];
      }
    } elseif (is_numeric($section)) {
      $e = explode("<hr />", $text);
      $text = $e[$section];
    }
  }
  if ($a['nohtml'] || $a['maxlength']) {
    $text = str_replace(array("\r", "\n"), "", $text);
    $text = str_replace(array("</p>", "<br />", "<br>", "<br/>"), "\n", $text);
    $text = preg_replace("/\n\n(\n*)/", "\n\n", $text);
    $text = strip_tags($text);
    if ($a['maxlength'] && (strlen($text) > $a['maxlength'])) $text = substr($text, 0, $a['maxlength']) . "...";
    $text = $a['before'] . $text . $a['after'];
    return $text;
  }
  if ($a['links_to_new_window'] == "external") {
    $text = preg_replace('/(<a([^>]*))(href=(|"|\')http(|s):)/', '$1target="_blank" $3', $text);
  } elseif ($a['links_to_new_window']) {
    $text = str_replace('<a ', '<a target="_blank" ', $text);
  }
  if (strlen($a['audio_player_vars'])) $audio_player_vars = htmlspecialchars("&" . str_replace("'", "", $a['audio_player_vars']));
  $text = str_replace("<hr />", "", $text);
  $size_video = (strlen($width) && strlen($height) && ($width < 400 || $height < 300));
  $player_width = ($size_video ? $width : 400);
  $player_height = ($size_video ? $height : 300);
  $text = preg_replace_callback("/<img([^>]*)\/VERB_HOSTED_AUDIO\/([0-9]*)([^>]*)>/", create_function(
    '$matches', ($offsite ? "return '';" :
    '$id = _verb_global_id();
     _verb_needs_javascript("audio-player");
     $file = "' . _verb_absolute_data_url() . '" . verb_asset($matches[2]);
     return \'<object type="application/x-shockwave-flash" data="' . $_VERB['config']['asset_url'] . 'audioplayer.swf" id="audioplayer\' . $id . \'" height="24" width="290">
      <param name="movie" value="' . $_VERB['config']['asset_url'] . 'audioplayer.swf">
      <param name="FlashVars" value="playerID=\' . $id . \'&amp;soundFile=\' . $file . \'' . $audio_player_vars . '">
      <param name="quality" value="high">
      <param name="menu" value="false">
      <param name="wmode" value="transparent">
      </object>\';')), $text);
  $text = preg_replace_callback("/<img([^>]*)src=(\"|'|)([^>]*)\/VERB_HOSTED_IMAGE\/([0-9]*)(\"|'|)/", create_function(
    '$matches',
    'return "<img" . $matches[1] . "src=\"' . _verb_absolute_data_url() . '" . verb_asset($matches[4], "' . $width . '","' . $height . '", "' . $quality . '", ' . $preserve_filename . ') . "\"";'), $text);
  $text = preg_replace_callback("/<img([^>]*)\/VERB_HOSTED_VIDEO\/([0-9]*)([^>]*)>/", create_function(
    '$matches', ($offsite ? "return '';" : 
    '$id = _verb_global_id();
     $file = verb_asset($matches[2]);
     if ($file == "tryagain.flv") $file = "' . $_VERB['config']['backlot_url'] . '/videos/" . $file;
     else $file = "' . _verb_absolute_data_url() . '" . $file; 
     _verb_needs_javascript("jwplayer");
     return \'<div id="\' . $id . \'_container">You need to <a href="http://www.macromedia.com/go/getflashplayer">get the Flash Player</a> to see this video.</div>
     <script type="text/javascript">
       jwplayer("\' . $id . \'_container").setup({
         flashplayer: "' . $_VERB['config']['asset_url'] . 'player.swf",
         file: "\' . $file . \'",
         height: ' . $player_height . ',
         width: ' . $player_width . '
       });
     </script>\';')), $text);
  $text = $a['before'] . $text . $a['after'];
  return $text;
}

function _verb_humanize($a) {
  return ucwords(str_replace("_", " ", $a));
}

function _verb_in_ob() {
  if ($_ENV['TEST']) return true;
  $handlers = ob_list_handlers();
  return !(!count($handlers) || (count($handlers) == 1 && $handlers[0] == "default output handler"));
}

function _verb_inject_assets($out) {
  global $_VERB;
  $html = array();
  if (isset($_VERB['javascripts']['jquery']) && !preg_match('/<script[^>]*src=[^\n]*jquery([-0-9a-z.])*(.min.js|.js)/', $out) && !_verb_is_xhr()) {
    $bottom .= '<script type="text/javascript" src="' . $_VERB['config']['asset_url'] . 'jquery.js"></script>';
  }
  if (is_array($_VERB['javascripts']) && (count($_VERB['javascripts']) > 0)) {
    foreach ($_VERB['javascripts'] as $s => $garbage) {
      if ($s == "jquery") continue;
      if (strlen($s)) $bottom .= '<script type="text/javascript" src="' . $_VERB['config']['asset_url'] . $s . '.js"></script>';
    }
  }
  if (count($_VERB['assets'])) {    
    _verb_load_cache();
    foreach ($_VERB['assets'] as $group => $assets) {
      $iden = "";
      foreach ($assets as $asset) {
        $md5 = @md5_file($_SERVER['DOCUMENT_ROOT'] . "/" . $asset);
        _verb_dependency_add($asset, $md5);
        $iden .= $md5;
      }
      $iden = "asset" . md5($iden);
      if (isset($_VERB['file_cache'][$iden])) {
        $html[$group] = _verb_asset_html($_VERB['asset_types'][$group], _verb_absolute_data_url() . $_VERB['file_cache'][$iden]);
      } else {
        $raw = "";
        foreach ($assets as $asset) {
          $content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/" . $asset);
          if (strstr($asset, ".sass")) {
            require_once(dirname(__FILE__)."/haml.php");
            $content = _verb_sass($content, false, dirname($_SERVER['DOCUMENT_ROOT'] . "/" . $asset));
          }
          if ($_VERB['asset_types'][$group] != "js") {
            $_VERB['assets_css_callback'] = (substr($asset, 0, 1) == "/" ? "" : "/") . dirname($asset);
            $content = preg_replace_callback("/url\\((\"|'|)([^\"')]*)(\"|'|)\\)/", "_verb_inject_assets_css_callback", $content);
          }
          $raw .= $content . "\n";
        }
        if ($_VERB['asset_types'][$group] == "js") {
          require_once(dirname(__FILE__) . "/../vendor/jsmin.php");
          $raw = JSMin::minify($raw);
        } else {
          require_once(dirname(__FILE__) . "/../vendor/csstidy/csstidy.php");
          $css = new csstidy();
          $css->parse($raw);
          $raw = $css->print->plain();
        }
        $html[$group] = _verb_asset_html($_VERB['asset_types'][$group], _verb_absolute_data_url() . _verb_store_file($iden, $raw, ($_VERB['asset_types'][$group] == "js" ? "js" : "css")));
      }
    }
  }
  if (isset($_VERB['on_dom_ready'])) {
    if (_verb_is_xhr()) {
      $out .= _verb_script_tag(implode("\n", $_VERB['on_dom_ready']));
    } else {
      $bottom .= _verb_script_tag('jQuery(function() { ' . implode("\n", $_VERB['on_dom_ready']) . ' });');
    }
  }
  if (isset($_VERB['asset_inject_points'])) {
    foreach ($_VERB['asset_inject_points'] as $group => $points) {
      for ($i = 1; $i < $points; $i++) {
        $out = str_replace("<_VERB_ASSET_" . $group . $i . ">", "", $out);
      }
      $out = str_replace("<_VERB_ASSET_" . $group . $i . ">", $html[$group], $out);
    }
  }
  $out = _verb_inject_at_bottom_of_head($out, $bottom);
  return $out;
}

function _verb_inject_at_bottom_of_head($out, $html) {
  if (strstr($out, "</head>")) {
    return str_replace("</head>", $html . "</head>", $out);
  } else {
    return $html . $out;
  }
}

function _verb_inject_assets_css_callback($a) {
  global $_VERB;
  $url = $a[2];
  if ((substr($url, 0, 1) != "/") && !strstr($url, "://")) $url = $_VERB['assets_css_callback'] . "/" . $url;
  $url = _verb_cdn_timestamp_url($url);
  return "url(" . $a[1] . $url . $a[3] . ")";
}

function _verb_inject_cdn($out) {
  $out = preg_replace_callback("/(\"|'|url\\()http:\\/\\/(www\\.|)" . preg_replace("/^www\\./", "", $_SERVER['HTTP_HOST']) . "\\/([^\"')]*\\/|)wp-(content|photos)\\/([^\"')]*)(\"|'|\\))/", "_verb_inject_cdn_callback", $out);
  $out = preg_replace('/verbsite\.com\.lg1([a-z0-9]*)\.simplecdn\.net/', "verbsite.net", $out);
  return $out;
}

function _verb_inject_cdn_callback($a) {
  if (strstr($a[0], "wp-content/plugins")) return $a[0];
  $url = $a[3] . "wp-" . $a[4] . "/" . $a[5];
  $url = _verb_cdn_timestamp_url("/" . $url);
  return $a[1] . verb_cdn_url() . substr($url, 1) . $a[6]; 
}

function _verb_interpret_verbml($verbml) {
  global $_VERB;
  $out = "";
  $callbacks = array();
  $_VERB['callback_stack'] = array();
  $old_session = $_SESSION;
  if (!strstr($verbml, "<v") && !strstr($_VERB['filename'], ".haml")) return _verb_post_process($verbml);
  $cache_key = $_VERB['cache_key'] . "3" . md5($verbml);
  if (count($_VERB['callbacks'])) {
    foreach ($_VERB['callbacks'] as $name => $func) {
      if (isset($_REQUEST['__v:' . $name])) {
        $callbacks[] = $name;
      }
    }
  }
  if (count($callbacks)) {
    _verb_tick("can't use cached version because there are callbacks");
  } elseif (isset($_SESSION['__v:flash'])) {
    _verb_tick("can't use cached version because there is data in the flash bucket");
  } elseif (count($_COOKIE)) {
    _verb_tick("can't use cached version because there's a cookie");
  } elseif (!isset($_REQUEST['__verb_local'])) {
    $cached = memcache_get($_VERB['memcached'], $cache_key);
    if (is_array($cached) && $cached[0] == "c") {
      $out = $cached[1];
      if (is_array($cached[2]) && count($cached[2])) {
        foreach ($cached[2] as $filename => $hash) {
          if ($hash == "s") {
            if (isset($_SESSION[$filename])) {
              _verb_tick("can't use cached version because $filename is in my session");
              unset($out); 
              break;
            }
          } else {
            if (@md5_file($_SERVER['DOCUMENT_ROOT'] . "/" . $filename) != $hash) {
              _verb_tick("can't use cached version because $filename has changed");
              unset($out); 
              break;
            }
          }
        }
      }
    } else {
      _verb_tick("no cached version");
    }
  }
  if (strlen($out) && !$_REQUEST['__debug']) {
    $from_cache = true;
    $_VERB['session_cookies'] = $cached[3];
    _verb_tick("read HTML from cache");
  } else {  
    _verb_set_initial_context();
    _verb_tick("set initial context");
    list($parse_tree, $render_context) = _verb_parse_verbml($verbml, $_VERB['filename']);
    _verb_tick("parse VerbML");
    try {
      $out = _verb_render_tags($parse_tree, $_VERB['context'], $render_context);
    } catch (VerbFragment $e) {
      $out = $e->getMessage();
    }
    if (isset($_VERB['assets']) || isset($_VERB['javascripts'])) $out = _verb_inject_assets($out);
    if (isset($_VERB['prepend'])) $out = $_VERB['prepend'] . $out;
    $out = _verb_post_process($out);
    _verb_tick("render HTML (no cache)");
  }
  //if (!isset($_REQUEST['__debug']) && !isset($_REQUEST['__time'])) @file_put_contents("/usr/local/verb/logs/slow.txt", str_replace(array("/var/www/vhosts/", "/httpdocs", "/releases/current"), "", $_SERVER['DOCUMENT_ROOT']) . $_VERB['filename'] . "=" . ((microtime(true)-$_VERB['start_tick'])*1000) . "=" . (isset($from_cache) ? "1" : "0") . "\n", FILE_APPEND|LOCK_EX);
  foreach ($_VERB['callback_stack'] as $name => $tag) {
    if (_verb_run_hooks($name) != false) { 
      $func = $_VERB['callbacks'][$name];
      if (isset($func['filename'])) require_once(dirname(__FILE__)."/".$func['filename']);
      return call_user_func($func['callback'], $tag); 
    }
  }
  if ($_SESSION != $old_session) {
    _verb_tick("can't cache because the session changed");
  } elseif (isset($_SESSION['__v:flash'])) {
    _verb_tick("can't cache because some data got flashed");
  } elseif (headers_sent()) {
    _verb_tick("can't cache because headers have been sent");
  } elseif (isset($from_cache)) {
    _verb_tick("can't cache because this one already came from the cache");
  } elseif (isset($_VERB['cant_cache'])) {
    _verb_tick("can't cache because my var says so: " . $_VERB['cant_cache']);
  } elseif ($_SERVER['HTTPS'] || $_REQUEST['__verb_ssl_router']) {
    _verb_tick("can't cache because we be ssl");
  } elseif (($out != false) && !isset($_VERB['force_redirect'])) {
    $dependencies = (isset($_VERB['dependencies']) ? $_VERB['dependencies'] : "");
    memcache_set($_VERB['memcached'], $cache_key, array("c", $out, $_VERB['dependencies'], $_VERB['session_cookies']), 0, 1800);
    _verb_tick("cached page");
  }
  return $out;
}

function _verb_is_xhr() {
  return strstr($_SERVER['HTTP_X_REQUESTED_WITH'], "XML") || ($_REQUEST['__xhr']);
}

function _verb_jsesc($a) {
  return str_replace(array("\n", "\"", "'"), array("\\n", "\\\"", "&#39;"), trim($a));
}

function _verb_load_cache($reload = false) {
  global $_VERB;
  if (isset($_VERB['file_cache']) && !$reload) return;
  $cache = array();
  if (file_exists($_VERB['config']['data_path'] . "files.psz")) $cache = unserialize(_verb_read_file("files.psz"));
  $_VERB['file_cache'] = $cache;
}

function _verb_load_settings() {
  global $_VERB, $_VAE; // PATCH
  if (isset($_VERB['settings'])) return;
  if (!file_exists($_VERB['config']['data_path'] . "settings.php")) {
    _verb_update_settings_feed();
  }
  require_once($_VERB['config']['data_path'] . "settings.php");
  if (isset($_VAE['settings'])) $_VERB['settings'] = $_VAE['settings'];
  if (!$_VERB['config']['force_local_assets'] && !$_SERVER['HTTPS'] && !$_REQUEST['__verb_ssl_router']) {
    if (strlen($_VERB['settings']['cdn_host'])) {
      $_VERB['config']['cdn_url'] = "http://" . $_VERB['settings']['cdn_host'] . "/";
    } else {
      $domain = ($_VERB['settings']['domain_cdn'] ? $_VERB['settings']['domain_cdn'] : "verbsite.net");
      $_VERB['config']['cdn_url'] = "http://" . $_VERB['settings']['subdomain'] . "." . $domain . "/";
    }
    $_VERB['config']['data_url'] = $_VERB['config']['cdn_url'] . "__data/";
  }
}

function _verb_local($filename = "") {
  global $_VERB;
  $memcache_base_key = "__verb_local" . $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['__verb_local'];
  if ($_REQUEST['__local_username']) {
    echo _verb_local_authenticate($memcache_base_key);
    return _verb_die();
  }
  $authorized = memcache_get($_VERB['memcached'], $memcache_base_key . "auth");
  if ($authorized != "GOOD") {
    _verb_error("Your Local Development Session expired.  Please restart the Local Preview server and try again.");
  }
  $memcache_base_key .= "f";
  if (count($_REQUEST['__verb_local_files'])) {
    foreach ($_REQUEST['__verb_local_files'] as $fname => $file) {
      memcache_set($_VERB['memcached'], $memcache_base_key . $fname, $file);
    }
  }
  $_VERB['local'] = $memcache_base_key;
  if (!strlen($filename)) $filename = $_SERVER['SCRIPT_NAME'];
  list($filename, $script) = _verb_src($filename);
  _verb_set_cache_key();
  $_VERB['filename'] = $filename;
  $verb_php = memcache_get($_VERB['memcached'], $memcache_base_key . "/__verb.php");
  if (strlen($verb_php)) _verb_local_exec($verb_php);
  if (strstr($filename, ".sass")) {
    require_once(dirname(__FILE__)."/haml.php");
    echo _verb_sass($script, true, dirname($filename));
  } else {
    ob_start(_verb_handleob);
    _verb_local_exec($script);
  }
  _verb_die();
}

function _verb_local_authenticate($memcache_base_key) {
  global $_VERB;
  $out = _verb_rest(array(), "subversion/authorize?username=" . $_REQUEST['__local_username'] . "&password=" . $_REQUEST['__local_password'], "subversion");
  if ($out == "GOOD") {
    memcache_set($_VERB['memcached'], $memcache_base_key . "auth", $out);
    if ($_REQUEST['__local_version'] != $_VERB['local_newest_version']) return "MSG\n*****\nYour copy of the Verb Local Development Environment is out of date.\nPlease download a new copy at:\nhttp://docs.verbcms.com/verb_local\n*****\n\n";
    else return $out;
  }
  return "BAD";
}

function _verb_local_exec($script) {
  global $_VERB;
  preg_match_all("/\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)/", $script, $matches);
  if (is_array($matches) && is_array($matches[0])) {
    foreach ($matches[0] as $key) {
      $glbls .= (strlen($glbls) ? ", " : "") . $key;
    }
    if (strlen($glbls)) $script = "<?php global " . $glbls . "; ?>" . $script;
  }
  $temp = tempnam("/tmp", "VLOCAL");
  file_put_contents($temp, $script);
  require_once($temp);
  unlink($temp);
}

function _verb_local_needs($filename) {
  if (!$_REQUEST['__verb_local']) return;
  return _verb_render_final("__verb_local_needs=" . $filename);
}

function _verb_lock_acquire($load_cache = true, $which_lock = 'global', $only_one_winner = false) {
  global $_VERB;
  if (isset($_VERB[$which_lock . '_lock'])) return;
  if ($only_one_winner) {
    $waiting_lock = fopen($_VERB['config']['data_path'] .".verb." . $which_lock . ".2.lock", "w+");
    if (!flock($waiting_lock, LOCK_EX | LOCK_NB)) {
      die("Gave up on trying to get this lock because someone else is already waiting for it.");
    }
  }
  $_VERB[$which_lock . '_lock'] = fopen($_VERB['config']['data_path'] .".verb." . $which_lock . ".lock", "w+");
  for ($i = 0; $i < 10; $i++) {
    if (flock($_VERB[$which_lock . '_lock'], LOCK_EX)) {
      if ($only_one_winner) fclose($waiting_lock);
      if ($load_cache) _verb_load_cache(true);
      return;
    }
    usleep(200000);
  }
  _verb_error("","Could not obtain Verb Lock.");
}

function _verb_lock_release($param = true, $which_lock = 'global') {
  global $_VERB;
  if (isset($_VERB[$which_lock . '_lock'])) {
    flock($_VERB[$which_lock . '_lock'], LOCK_UN);
    unset($_VERB[$which_lock . '_lock']);
  }
  return $param;
}

function _verb_log($msg) {
  global $_VERB;
  if (!is_string($msg)) $msg = serialize($msg);
  $_VERB['log'] .= $msg . "\n";
}

function _verb_mail($to, $subj, $body, $headers) {
  global $_VERB;
  if ($_ENV['TEST']) {
    $_VERB['mail_sent']++;
  } else {
    mail($to, $subj, $body, $headers);
  }
}

function _verb_make_filename($ext, $filename = null) {
  global $_VERB;
  if ($filename) {
    $filename = substr(preg_replace("/[^a-z0-9_\-]/", "", preg_replace('/\s/', "-", preg_replace('/\s\s+/', ' ', strtolower($filename)))), 0, 55);
    $i = 0;
  }
  do {
    if ($filename) {
      $newname = $filename . ($i == 0 ? "" : "." . $i) . "." . $ext;
      $i++;
    } else {
      $newname = md5(mt_rand()) . "." . $ext;
    }
  } while (file_exists($_VERB['config']['data_path'] . $newname));
  return $newname;
}

function _verb_merge_data_from_tags(&$tag, &$data, &$errors, $nested = false) {
	global $_VERB;
  if (_verb_akismet($tag['attrs'])) {
    $errors[] = "This post looks spammy. Please do something to make it not hit our spam filters.";
    return;
  }
  if ($tag['attrs']['nested']) $nested = true;
  if ($nested) {
    foreach ($_POST as $k => $v) {
      if (!in_array($k, array("VerbSession", "id", "locale", "page", "recaptcha_challenge_field", "recaptcha_response_field")) && (substr($k, 0, 3) != "__v") && (substr($k, 0, 3) != "utm")) {
        if (is_array($v)) $v = implode(", ", $v);
        $data[$k] = $v;
      }
    }
  }
	$tags = $tag['tags'];
	if (count($tags) && is_array($tags)) {
  	foreach ($tags as $itag) {
      $err = "";
  	  if (isset($_VERB['form_items'][$itag['type']]) && isset($itag['callback']['_form_prepared'])) {
  	    $name = str_replace("[]", "", $itag['attrs']['name']);
  	    $value = "";
  	    if (!strlen($name)) $name = $itag['attrs']['path'];
  	    if ($itag['type'] == "captcha") {
  	      if (isset($_VERB['recaptcha']['private'])) {
            $resp = recaptcha_check_answer($_VERB['recaptcha']['private'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
            if (!$resp->is_valid) $errors[] = "You entered the wrong word(s) in the reCAPTCHA window.  Please try again.";
            unset($_VERB['recaptcha']['private']);
          }
          continue;
  	    } elseif ($itag['type'] == "date_select") {
  	      $time = strtotime(_verb_request_param($name . "_month") . "/" . _verb_request_param($name . "_day") . _verb_request_param($name . "_year")); 
  	      $value = ($time > 0 ? strftime("%Y-%m-%d", $time) : "");
  	    } elseif ($itag['type'] == "file_field") {
  	      if ($_FILES[$name] && $_FILES[$name]['name']) {
  	        $sep = explode(".", $_FILES[$name]['name']);
  	        $ext = array_pop($sep);
  	        $value = verb_data_url() . _verb_store_file(null, $_FILES[$name]['tmp_name'], $ext, "upload_" . implode("_", $sep), "uploaded");
  	      }
  	    } else {
  	      $value = _verb_request_param($name);
  	      if (is_array($value)) {
  	        $value = implode(", ", $value);
  	      }
  	    }
  	    if ($itag['attrs']['required'] && !$nested) {
  	      if ($itag['attrs']['required'] == "creditcard") {
  	        if (!_verb_valid_creditcard($value)) $err = "must be a valid credit card number.";
  	      } elseif ($itag['attrs']['required'] == "date") {
  	        if (!_verb_valid_date($value)) $err = "must be a valid date.";
  	      } elseif ($itag['attrs']['required'] == "digits") {
  	        if (!_verb_valid_digits($value)) $err = "must only contain numeric digits.";
  	      } elseif ($itag['attrs']['required'] == "email") {
  	        if (!_verb_valid_email($value)) $err = "must be a valid E-Mail address.";
  	      } elseif ($itag['attrs']['required'] == "name") {
  	        if ((strlen($value) < 3)  || !strstr($value, " ")) $err = "must contain a first and last name.";
  	      } elseif ($itag['attrs']['required'] == "number") {
  	        if (!is_numeric($value)) $err = "must be a valid number.";
  	      } elseif ($itag['attrs']['required'] == "url") {
  	        if (!_verb_valid_url($value)) $err = "must be a valid URL.";
          } elseif (!strlen(trim($value))) {
            $country = $_REQUEST[str_replace(array("state","zip"), "country", $name)];
            if ($itag['attrs']['required'] == "uscanada") $itag['attrs']['required'] = "state";
            if ($itag['attrs']['required'] != "state" || (isset($_VERB['states'][$country]))) {
              $err = "can't be blank.";
            }
  	      }
  	    }
  	    if (!strlen($err) && (substr($name, 0, 8) == "confirm_") && !$nested) {
  	      if ($value != $_REQUEST[substr($name, 8)]) {
  	        $name = substr($name, 8);
  	        $err = "doesn't match confirmation.";
  	      }
  	    }
  	    if (strlen($err)) {
  	      $errors[] = _verb_humanize($name) . " " . $err;
  	    } else {
  	      if (substr($name, 0, 8) != "confirm_") $data[$name] = $value;
  	    }
  	  } elseif (is_array($itag['tags'])) {
  	    _verb_merge_data_from_tags($itag, $data, $errors, $nested);
  	  }
  	}
	}
}

function _verb_merge_divider($data, $divider, $rendered, $context, $render_context, $reverse = false) {
  if (($rendered % $divider['every']) == 0) {
    if ($divider['to_merge']) {
      $outer = $divider['to_merge'];
      $inner = array('tags' => array(array('innerhtml' => $data)));
      _verb_merge_yield($outer, $inner, $render_context);
      $data = _verb_render_tags($outer, $context, $render_context);
    } elseif ($rendered > 0) {
      if ($reverse) $data .= $divider['out'];
      else $data = $divider['out'] . $data;
    }
  }
  return $data;
}

function _verb_merge_dividers($data, $dividers, $rendered, $context, $render_context, $reverse = false, $type = "divider") {
  foreach ($dividers as $divider) {
    if ($divider['type'] == $type) {
      $data = _verb_merge_divider($data, $divider, $rendered, $context, $render_context, $reverse);
    }
  }
  return $data;
}

function _verb_merge_session_data($out) {
  while (preg_match("/<__VERB_SESSION_DUMP=([^>]*)>/", $out, $matches)) {
    $out = str_replace($matches[0], $_SESSION[$matches[1]], $out);
  }
  return $out;
}

function _verb_minify_js($js) {
  return trim(str_replace(array("\r", "\n"), "", $js));
}

function _verb_multipart_mail($from, $to, $subject, $text, $html) {
  $headers  = 'From: ' . $from . "\n";
  $headers .= 'Return-Path: ' . $from . "\n";
  if (!strlen($html)) {
    return _verb_mail($to, $subject, $text, $headers);
  }
  $boundary = md5(uniqid(time()));
  $headers .= 'MIME-Version: 1.0' ."\n";
  $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\n\n";
  $headers .= $text . "\n";
  $headers .= '--' . $boundary . "\n";
  $headers .= 'Content-Type: text/plain; charset=ISO-8859-1' ."\n";
  $headers .= 'Content-Transfer-Encoding: 8bit'. "\n\n";
  $headers .= $text . "\n";
  $headers .= '--' . $boundary . "\n";
  $headers .= 'Content-Type: text/HTML; charset=ISO-8859-1' ."\n";
  $headers .= 'Content-Transfer-Encoding: 8bit'. "\n\n";
  $headers .= $html . "\n";
  $headers .= '--' . $boundary . "--\n";
  return _verb_mail($to, $subject,'', $headers);
}

function _verb_natural_time($time) {
  $diff = time() - $time;
  if ($diff < 45) return "less than a minute ago";
  if ($diff < 90) return "about a minute ago";
  $diff /= 60;
  if ($diff < 45) return ceil($diff) . " minutes ago";
  $diff /= 60;
  if ($diff < 22) return ceil($diff) . " hours ago";
  $diff /= 24;
  if ($diff < 2) return "1 day ago";
  return ceil($diff) . " days ago";
}

function _verb_needs_javascript() {
  global $_VERB;
  if (!is_array($_VERB['javascripts'])) $_VERB['javascripts'] = array();
  foreach (func_get_args() as $arg) {
    $_VERB['javascripts'][$arg] = true;
  }
}

function _verb_needs_jquery() {
  global $_VERB;
  if (!is_array($_VERB['javascripts'])) $_VERB['javascripts'] = array();
  $_VERB['javascripts']['jquery'] = true;
  foreach (func_get_args() as $arg) {
    if (strlen($arg)) $_VERB['javascripts']['jquery.' . $arg] = true;
  }
}

function _verb_newsletter_subscribe($code, $email) {
  return _verb_simple_rest('http://newsletter-agent.com/' . $code, "email=" . $email . "&customer_id=" . $_SESSION['__v:store']['customer_id']);
}

function _verb_on_dom_ready($js) {
  global $_VERB;
  _verb_needs_jquery();
  if (!isset($_VERB['on_dom_ready'])) $_VERB['on_dom_ready'] = array();
  $_VERB['on_dom_ready'][] = $js;
}

function _verb_oneline($a, $context, $attribute_type = false) {
  global $_VERB;
  if (preg_match('/SIZE\(([^)]*)\)/i', $a, $regs)) {
    $a = $regs[1];
    $getsize = true;
  }
  if (preg_match('/JOIN\(([^)]*)\)/i', $a, $regs)) {
    $out = array();
    $values = _verb_fetch($regs[1], $context, array('assume_numbers' => true));
    if (is_object($values)) {
      foreach ($values as $value) {
        $out[] = _verb_oneline_get($value, $getsize, $params);
      }
      return implode(",", $out);
    }
    return $values;
  } elseif (preg_match('/PARAM\(([^)]*)\)/i', $a, $regs)) {
    $out = $_REQUEST[$regs[1]];
  } else {
    $params = explode(",", $a);
    $last_paren = 0;
    for ($i = 0; $i < count($params); $i++) {
      if (strstr($params[$i], "(") || strstr($params[$i], ")")) $last_paren = $i;
    }
    if ($last_paren > 0) {
      for ($i = 0; $i <= $last_paren; $i++) {
        if ($i > 0) $query .= ",";
        $query .= array_shift($params);
      }
      array_unshift($params, $query);
    }
    $query = $params[0];
    $value = _verb_fetch($query, $context, array('assume_numbers' => true));
    $out = _verb_oneline_get($value, $getsize, $params);
  }
  if ($attribute_type == "href") $out = urlencode($out);
  if ($attribute_type == "path") $out = htmlspecialchars($out, ENT_QUOTES);
  return $out;
}

function _verb_oneline_get($value, $getsize, $params) {
  global $_VERB;
  $type = $value->type;
  if ($type == "ImageItem" || ($type == "VideoItem" && strlen($params[2]))) {
    if (strlen($params[1]) && !is_numeric($params[1])) {
      $src = verb_sizedimage($value, $params[1], $params[2]);
    } else {
      $src = verb_image($value, $params[1], $params[2], $params[3], $params[4], $params[5]);
    }
    if (strstr($params[count($params)-1], ".png")) $src = verb_watermark($src, $params[count($params)-1]);
    return _verb_oneline_size($src, $getsize);
  } elseif ($type == "VideoItem") {
    $src = verb_video($value, $params[1]);
    if ($src == "tryagain.flv") return $_VERB['config']['backlot_url'] . "/videos/" . $src;
    return _verb_oneline_size($src, $getsize);
  } elseif ($type == "FileItem") {
    $preserve_filename = ($_VERB['settings']['preserve_filenames'] ? true : false);
    $src = verb_file($value, $preserve_filename);
    return _verb_oneline_size($src, $getsize);
  } elseif ($type == "DateItem") {
    return strftime("%B %d, %Y", (string)$value);
  } elseif ($type == "OptionsItem") {
    return str_replace("=", ": ", str_replace(",", ", ", (string)$value));
  } elseif ($type == "Collection" || $type == "NestedCollection") {
    return $value->id();
  } else {
    return trim((string)$value);
  }
}

function _verb_oneline_size($src, $getsize = false) {
  global $_VERB;
  if ($getsize) return filesize($_VERB['config']['data_path'] . $src);
  return _verb_absolute_data_url() . $src;
}

function _verb_oneline_url($a, $context) {
  global $_VERB;
  if (strlen($a)) $context = _verb_fetch($a, $context);
  return (is_object($context) ? $context->permalink() : "");
}

function _verb_parse_path() {
  $uri = explode("?", $_SERVER['REQUEST_URI']);
  _verb_page_find(substr($uri[0], 1));
  $prev = "id";
  foreach (explode("-", $_REQUEST['path']) as $part) {
    if (is_numeric($part)) {
      $_REQUEST[$prev] = $part;
    }
    $prev = $part;
  }
}

function _verb_php($code, $context) {
  if (substr($code, 0, 1) == "=") $code = "return " . substr($code, 1) . ";";
  preg_match_all("/\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)/", $code, $matches);
  if (is_array($matches) && is_array($matches[0])) {
    foreach ($matches[0] as $key) {
      if ($key != '$context' && $key != '$id') $glbls .= (strlen($glbls) ? ", " : "") . $key;
    }
    if (strlen($glbls)) $code = "global " . $glbls . "; " . $code;
  }
  $pfunc = create_function('$context,$id', $code);
  if (!$pfunc) return _verb_error("Invalid PHP Code.");
  return $pfunc($context, ($context ? $context->id() : null));
}

function _verb_placeholder($which) {
  global $_VERB;
  if ($_VERB['from_proxy']) return "%" . strtoupper($which) . "%";
  $which = strtolower($which);
  if ($which == "id") return "1234";
  if ($which == "shipment_company") return "UPS";
  if ($which == "shipment_tracking_number") return "1Z8A5E940342201962";
  return "(" . $which . ")";
}

function _verb_post_process($out) {
  global $_VERB;
  if (isset($_VERB['config']['cdn_url'])) $out = _verb_inject_cdn($out);
  return $out;
}

function _verb_proto() {
  return (($_REQUEST['__verb_ssl_router'] || $_SERVER['HTTPS']) ? "https" : "http") . "://";
}

function _verb_qs($out = "", $keep_current = true, $append_to_end = "") {
  global $_VERB;
  if (!is_array($out)) {
    $nvps = explode("&", $out);
    $out = array();
    foreach ($nvps as $nvp) {
      $a = explode("=", $nvp);
      $out[$a[0]] = $a[1];
    }
  }
  $new = ($keep_current ? array_merge($_GET, $out) : $out);
  if (!$keep_current && $_REQUEST['locale']) $new['locale'] = $_REQUEST['locale'];
  $out = $path = "";
  if (count($new)) {
    foreach ($new as $k => $v) {
      if ($k != "__verb_local" && $k != "__verb_ssl_router" && $k != "__page" && strlen($v)) {
        if ((preg_match("/([a-z0-9]*_)?page/", $k) && preg_match("/^([0-9]*|all)$/", $v) && !isset($_VERB['settings']['query_string_pagination'])) || ($k == "locale")) {
          if (($v != 1) && ($v != "en")) $path .= "/" . $k . "/" . $v;
        } else {
          $out .= "&" . $k . "=" . urlencode($v);
        }
      }
    }
  }
  if ($append_to_end) $out .= "&" . $append_to_end;
  return $path . ($out ? "?" . substr($out, 1) : "");
}

function _verb_read_file($name) {
  global $_VERB;
  return @file_get_contents($_VERB['config']['data_path'] . $name);
}

function _verb_register_hook($name, $a) {
  global $_VERB;
  $name = str_replace(":", "_", $name);
  if (!isset($_VERB['hook'][$name])) $_VERB['hook'][$name] = array();
  $_VERB['hook'][$name][] = $a;
  return true;
}

function _verb_register_tag($name, $a) {
  global $_VERB;
  if ($a['callback'] && !$a['html']) $a['html'] = 'form';
  if ($a['html']) {
    $form = array('input','select','textarea');
    if (in_array($a['html'], $form)) $_VERB['form_items'][$name] = 1;
  }
  $_VERB['tags'][$name] = $a;
  if ($a['callback']) {
    $_VERB['callbacks'][$name] =  array('callback' => $a['callback']);
    if (strlen($a['filename'])) $_VERB['callbacks'][$name]['filename'] = $a['filename'];
  }
  return true;
}

function _verb_remote() {
  global $_VERB;
  if ($_REQUEST['secret_key'] == $_VERB['config']['secret_key']) {
    if ($_REQUEST['version']) {
      echo "201 Version " . $_VERB['version'];
    } elseif ($_REQUEST['update_feed'] || $_REQUEST['hook']) {
      sleep(2);
      if ($_REQUEST['update_feed']) {
        if ($_REQUEST['hook'] == "settings:updated") {
          _verb_update_settings_feed();
        } else {
          _verb_update_feed(true);
        }
      }
      if ($_REQUEST['hook']) {
        if (strstr($_REQUEST['hook_param'], ",")) {
          foreach (explode(",", $_REQUEST['hook_param']) as $id) {
            _verb_run_hooks($_REQUEST['hook'], $id);
          }
        } else {
          _verb_run_hooks($_REQUEST['hook'], $_REQUEST['hook_param']);
        }
      }
    } else {
      _verb_error("","No action specified");
    }
  } else {
    _verb_error("","Secret Key Mismatch");
  }
  _verb_die("Verb Remote Done");
}

function _verb_remove_file($name) {
  global $_VERB;
  if (strlen($name)) @unlink($_VERB['config']['data_path'] . $name);
}

function _verb_render_backtrace($backtrace, $plaintext = false) {
  $calls = array();
  foreach ($backtrace as $bt) {
    $bt['file']  = (isset($bt['file'])) ? $bt['file'] : 'Unknown';
    $bt['line']  = (isset($bt['line'])) ? $bt['line'] : 0;
    $bt['class'] = (isset($bt['class'])) ? $bt['class'] : '';
    $bt['type']  = (isset($bt['type'])) ? $bt['type'] : '';
    $bt['args']  = (isset($bt['args'])) ? $bt['args'] : '';
    $args = '';
    if ($bt['args']) {
      foreach ($bt['args'] as $arg) {
        if (!empty($args)) {
          $args .= ', ';
        }
        switch (gettype($arg)) {
          case 'integer':
          case 'double':
            $args .= $arg;
            break;
          case 'string':
            $arg = str_replace("\n", "", $arg);
            if ($bt['function'] != "get") $arg = substr($arg, 0, 50) . ((strlen($arg) > 50) ? '...' : '');
            $args .= '"' . $arg . '"';
            break;
          case 'array':
            $args .= 'array(size ' . count($arg) . ')';
            break;
          case 'object':
            $args .= 'object(' . get_class($arg) . ')';
            break;
          case 'resource':
            $args .= 'resource(' . strstr($arg, '#') . ')';
            break;
          case 'boolean':
            $args .= $arg ? 'true' : 'false';
            break;
          case 'NULL':
            $args .= 'null';
            break;
          default:
            $args .= 'unknown';
        }
      }
    }
    $calls[] = array(
      'file'  => _verb_hide_dir($bt['file']),
      'line'  => $bt['line'],
      'class' => $bt['class'],
      'type'  => $bt['type'],
      'func'  => $bt['function'],
      'args'  => $args
    );
  }
  $htmlmessage = "<pre><ul>";
  foreach ($calls as $call) {
    $textmessage .= "    * {$call['class']}{$call['type']}{$call['func']}({$call['args']}) at {$call['file']}:{$call['line']}\n";
    $htmlmessage .= '<li><span class="bt1">' . htmlspecialchars($call['class'], ENT_COMPAT, 'UTF-8') . '</span><span class="bt2">'
        . htmlspecialchars($call['type'], ENT_COMPAT, 'UTF-8') . '</span><span class="bt3">' . htmlspecialchars($call['func'], ENT_COMPAT, 'UTF-8')
        . '</span><span class="bt4">(</span><span class="bt5">' . htmlspecialchars($call['args'], ENT_COMPAT, 'UTF-8') . '</span><span class="bt4">)</span> at ' . htmlspecialchars($call['file'], ENT_COMPAT, 'UTF-8') . ':' . $call['line'] . '</li>';
  }
  $htmlmessage .= "</pre>\n";
  if ($plaintext) return $textmessage;
  return $htmlmessage;
}

function _verb_render_error($e) {
  global $_VERB;
  @header("HTTP/1.1 500 Internal Server Error");
  @header("Status: 500 Internal Server Error");
  if (strstr($e->getFile(), "/www/verb_thrift") || strstr($e->getFile(), "/usr/local") || (strstr(get_class($e), "Verb"))) {
    $error_type = "Verb Error";
    if (get_class($e) == "VerbException" || get_class($e) == "VerbSyntaxError" || $_REQUEST['__debug']) $msg = $e->getMessage();
  } else {
    $error_type = "Exception Thrown";
    $msg = get_class($e) . ($e->getFile() ? " thrown in <span class='c'>" . _verb_hide_dir($e->getFile()) . "</span>" : "") . ($e->getLine() ? " at line <span class='c'>" . $e->getLine() . "</span>" : "") . ": " . $e->getMessage();
  }
  $out = "<h2>" . $error_type . (($e->filename && !strstr($e->filename, "/verb")) ? " in " . $e->filename : "") . "</h2>";
  if (!strlen($msg)) $msg = "An error has occured on our servers.  Please try again in a few minutes.";
  $out .= "<div class='b'>" . $msg . "</div>";
  if ($_REQUEST['__debug']) {
    if (strlen($e->debugging_info)) $out .= "<h3>Debugging Info:</h3><div class='b'>" . $e->debugging_info . "</div>";
    if (strlen($_VERB['debug'])) $out .= "<h3>Debugging Traces:</h3><div class='b'><pre>" . htmlentities($_VERB['debug']) . "</pre></div>";
  }
  foreach (array("_SERVER" => $_SERVER, "_REQUEST" => $_REQUEST) as $name => $r) {
    $log_details .= "  $" . $name . ":\n";
    if ($_REQUEST['__debug'] == "verb") $out .= "<h3>$" . $name . ":</h3><div class='b'><pre>";
    foreach ($r as $k => $v) {
      if ($_REQUEST['__debug'] == "verb") $out .= $k . " => " . $v . "\n";
      $log_details .= "    " . $k . " => " . $v . "\n";
    }
    if ($_REQUEST['__debug'] == "verb") $out .= "</pre></div>";
  }
  if ($e->backtrace) {
    $backtrace = $e->backtrace;
  } else {
    $backtrace = $e->getTrace();
  }
  $log_msg = "[" . $_VERB['settings']['subdomain'] . "] " . get_class($e) . "\n" . ($e->debugging_info ? "  " . $e->debugging_info . "\n" : "") . ($e->getMessage() ? "  " . $e->getMessage() . "\n" : "") . $log_details;
  if ($backtrace && (count($backtrace) > 1)) {
    if ($_REQUEST['__debug'] || !strstr(get_class($e), "Verb")) $out .= "<h3>Call stack (most recent first):</h3><div class='b'>" . _verb_render_backtrace($backtrace) . "</div>";
    $log_msg .= "  Call Stack:\n" . _verb_render_backtrace($backtrace, true);
  }
  //if (!$_ENV['TEST'] && !$_REQUEST['__debug']) {
  //  @file_put_contents("/usr/local/verb/logs/errors.txt", $log_msg . "\n\n", FILE_APPEND|LOCK_EX);
  //}
  return _verb_render_message($error_type, $out);
}

function _verb_render_final($txt) {
  global $_VERB;
  if (!_verb_in_ob()) die($txt);
  $_VERB['final'] = $txt;
  return "";
}

function _verb_render_message($title, $msg) {
  global $_VERB;
  $out = "<html><head><title>" . $title . "</title>";
  $out .= "<style type='text/css'>";
  if (!_verb_is_xhr()) $out .= "body { background: #333; margin: 0px; font: 14px \"Lucida Grande\", \"Myriad\", \"Lucida Sans Unicode\", Arial, Helvetica, sans-serif; }\nh1 { margin-top: 0px; }\n#main { background: #fff url(" . $_VERB['config']['asset_url'] . "images/grad-bottom.png) bottom repeat-x; padding: 15px 15px 100px 15px; min-height: 400px; }\n#footer { font-size: 0.75em; text-align: center; color: #fff; height: 50px; padding-top: 15px; background: url(" . $_VERB['config']['asset_url'] . "images/grad-footer.png) top repeat-x; }\n";
  $out .= "a { color: #fff; }\n.c, pre { font-family: Monaco, \"Bitstream Vera Sans Mono\", \"Lucida Console\", \"Courier New\", serif; }\n.c { font-size: 1.1em; color: #faec31; }\n.b { overflow: auto; margin: 15px 0px; color: #fff; background: #666; padding: 15px; }\n.fail { color: red; }\n.bt3 { color: #faec31; }\n.bt4 { color: #ee4423; }\n.bt5 { color: #ccc; }\n</style>";
  $out .= "</head><body><div id='main'><h1><img src='" . $_VERB['config']['asset_url'] . "images/verb.png' alt='Verb' /></h1>";
  if ($msg != false) $out .= $msg . _verb_render_message_footer();
  return $out;
}

function _verb_render_message_footer() {
  return "</div>" . (_verb_is_xhr() ? "" : "<div id='footer'>Copyright &copy;2007-" . strftime("%Y") . " Action Verb, LLC.</div>") . "</body></html>";
}

function _verb_render_timer() {
  global $_VERB;
  foreach ($_VERB['ticks'] as $r) {
    $sum += $r[1];
  }
  foreach ($_VERB['ticks'] as $r) {
    $ticks .= "<tr style='color: #fff;'><td>" . $r[0] . "</td><td align='right'>" . number_format($r[1]*100/$sum, 3) . "%</td><td align='right'>" . number_format($r[1], 3) . "ms</td></tr>\n";
  }
  return _verb_render_message("Verb Timer", "<h2>Verb Timer</h2><div class='b'><table style='width: 100%;'>" . $ticks . "</table></div>");
}

function _verb_report_error($subject, $message, $urgent = true) {
  $body = "------------------------------------\nMessage:\n$message\n------------------------------------\nEnvironment:\n";
  foreach ($_SERVER as $k => $v) {
    $body .= $k . " => " . $v . "\n";
  }
  $bad = array('cc_number','cc_month','cc_year','cc_start_month','cc_cvv','cc_start_year','cc_issue_number');
  $body .= "\n------------------------------------\nRequest:\n";
  foreach ($_REQUEST as $k => $v) {
    if (!in_array($k, $bad)) $body .= $k . " => " . $v . "\n";
  }
  _verb_mail("support@actionverb.com", "Verb Remote Error : " . $subject, $body, "From: verberrors@actionverb.com");
  if ($urgent) _verb_mail("2563376464@vtext.com", "REST ERROR", substr($message, 0, 120), "From: kevin@bombino.org");
  return $body;
}

function _verb_request_param($name, $flash = false) {
  $name = str_replace(" ", "_", $name);
  if ($flash) return $_SESSION['__v:flash']['post'][$name];
  return $_REQUEST[$name];
}

function _verb_require_ssl() {
  global $_VERB;
  $_VERB['ssl_required'] = true;
  if (!$_SERVER['HTTPS'] && !$_REQUEST['__verb_ssl_router'] && !$_REQUEST['__verb_local']) {
    $_SESSION['__v:pre_ssl_host'] = $_SERVER['HTTP_HOST'];
    if ($_VERB['settings']['domain_ssl'] && strstr($_SERVER['DOCUMENT_ROOT'], ".verb/releases/")) {
      $domain = $_VERB['settings']['subdomain'] . "." . $_VERB['settings']['domain_ssl'];
    } elseif ($_VERB['settings']['domain_ssl']) {
      $domain = $_VERB['settings']['subdomain'] . "-staging." . $_VERB['settings']['domain_ssl'];
    } elseif (strstr($_SERVER['DOCUMENT_ROOT'], ".verb/releases/")) {
      $domain = $_VERB['settings']['subdomain'] . "-secure.verbsite.com";
    } else {
      $domain = $_VERB['settings']['subdomain'] . ".verbsite.com";
    }
    return _verb_render_redirect("https://" . $domain . $_SERVER['REQUEST_URI']);
  }
  return false;
}

function _verb_round_significant_digits($value, $sigFigs) {
  if ($sigFigs < 1) $sigFigs = 1;
  $exponent = floor(log10($value) + 1);
  $significand = $value / pow(10, $exponent);
  $significand = round($significand * pow(10, $sigFigs)) / pow(10, $sigFigs);
  $value = $significand * pow(10, $exponent);
  return (string)$value;
}

function _verb_run_hooks($name, $params = null) {
  global $_VERB;
  $name = str_replace(":", "_", $name);
  if (isset($_VERB['hook'][$name])) {
    foreach ($_VERB['hook'][$name] as $a) {
      try {
        $retval = call_user_func($a['callback'], $params);
        if ($retval == false) return $retval;
      } catch (Exception $e) {
        if (strstr($e->message, "TSocket")) {
        } else {
          _verb_report_error("Callback Hook Error: $name", serialize($e), false);
        }
      }
    }
  }
  return true;
}

function _verb_script_tag($a) {
  return "<script type='text/javascript'>" . $a . "</script>";
}

function _verb_session_deps_add($key, $from = "unknown") {
  global $_VERB;
  if (!isset($_VERB['dependencies'])) $_VERB['dependencies'] = array();
  $_VERB['dependencies'][$key] = "s";
  if (isset($_SESSION[$key])) $_VERB['cant_cache'] = $key . " - " . $from;
}

function _verb_set_cache_key() {
  global $_VERB;
  $key = "p" . $_VERB['global_cache_key'];
  $key .= filemtime($_SERVER['DOCUMENT_ROOT'] . $_VERB['filename']) . "-";
  $verb_php = $_SERVER['DOCUMENT_ROOT'] . '/__verb.php';
  if (file_exists($verb_php)) $key .= filemtime($verb_php);
  $key = md5($key.$_VERB['filename'].$_SERVER['HTTP_HOST'].$_SERVER['QUERY_STRING'].(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "").serialize($_POST));
  $_VERB['cache_key'] = $key;
}

function _verb_session_cookie($name, $val) {
  global $_VERB;
  if (!isset($_VERB['session_cookies'])) $_VERB['session_cookies'] = array();
  $_VERB['session_cookies'][$name] = $val;
}

function _verb_set_default_config() {
  global $_VERB, $BACKLOTCONFIG;
  if (file_exists(dirname(__FILE__) . "/config.php")) include_once(dirname(__FILE__) . "/config.php");
  if (isset($BACKLOTCONFIG)) $_VERB['config'] = $BACKLOTCONFIG;
  if (!isset($_VERB['config']['data_path'])) $_VERB['config']['data_path'] = dirname(__FILE__) . "/data/";
  if (!isset($_VERB['config']['data_url'])) $_VERB['config']['data_url'] = substr($_VERB['config']['data_path'], 1 + strlen(dirname($_SERVER['SCRIPT_FILENAME'])));
  if (!isset($_VERB['config']['asset_url'])) $_VERB['config']['asset_url'] = $_VERB['config']['data_url'] . "../";
  $key = @filemtime($_VERB['config']['data_path'] . 'feed.xml');
  $verb_yml = $_SERVER['DOCUMENT_ROOT'] . '/__verb.yml';
  if (file_exists($verb_yml)) $key .= filemtime($verb_yml);
  $_VERB['global_cache_key'] = $key;
}

function _verb_set_initial_context() {
  global $_VERB;
  if (!isset($_VERB['context'])) {
    if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && !strstr($_REQUEST['id'], ".")) {
      $id = $_REQUEST['id'];
    } else {
      foreach ($_GET as $k => $v) {
        if (is_numeric($v) && substr($k, 0, 1) != "_" && !strstr($v, ".")) $id = $v;
      }
    }
    $_VERB['context'] =  (isset($id) ? _verb_fetch($id) : null);
  } elseif (!is_object($_VERB['context']) && is_numeric($_VERB['context'])) {
    $_VERB['context'] =  _verb_fetch($_VERB['context']);
  }
}

function _verb_set_login() {
  global $_VERB;
  $res = _verb_simple_rest("/feed/authenticate?secret_key=" . $_VERB['config']['secret_key'] . "&remote_access_key=" . $_REQUEST['remote_access_key']);
  if (preg_match('/601 Authorized\. user_id=([0-9]*)/', $res, $output)) {
    foreach ($_SESSION as $k => $v) {
      unset($_SESSION[$k]);
    }
    $_SESSION['__v:user_id'] = $output[1];
    if ($_REQUEST['customer_id']) {
      if ($raw = _verb_rest(array(), "customers/show/" . $_REQUEST['customer_id'], "customer", $tag, null, true)) {
        _verb_store_load_customer($raw);
      }
    }
    if (strlen($_REQUEST['redirect'])) {
      @header("Location: " . $_REQUEST['redirect']);
    } else {
      @header("Location: /");
    }
  } else {
    _verb_error("","Bad key.");
  }
  _verb_die();
}

function _verb_sql_ar() {
  return mysql_affected_rows();
}

function _verb_sql_connect() {
  global $_VERB;
  if (!isset($_VERB['shared_sql'])) {
    $_VERB['shared_sql'] = mysql_connect("localhost", "verbshared", "DataData");
    mysql_select_db("av_verbshared");
  }
}

function _verb_sql_e($q) {
  return mysql_escape_string($q);
}

function _verb_sql_iid() {
  return mysql_insert_id();
}

function _verb_sql_n($q) {
  return mysql_num_rows($q);
}

function _verb_sql_q($q) {
  $ret = mysql_query($q) or die("Error running $q: " . mysql_error());
  return $ret;
}

function _verb_sql_r($q) {
  return mysql_fetch_assoc($q);
}

function _verb_src($filename) {
  global $_VERB;
  if (substr($filename, 0, 1) != "/") $filename = "/" . $filename;
  if ($filename == "/") $filename = "/index";
  foreach (array("", ".html", ".haml", ".php", ".sass", ".xml", ".rss", ".pdf.html", ".pdf.haml", ".pdf.haml.php", ".haml.php") as $ext) {
    if ($_VERB['local']) {
      $verbml = memcache_get($_VERB['memcached'], $_VERB['local'] . $filename . $ext);
      if (strlen($verbml)) {
        $filename = $filename . $ext;
        break;
      }     
    } else {
      if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $filename . $ext)) {
        $verbml = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/" . $filename . $ext);
        $filename = $filename . $ext;
        break;
      }
    }
  }
  if ($_VERB['local'] && !strlen($verbml)) {
    return _verb_local_needs($filename);
  }
  _verb_dependency_add($filename, md5($verbml));
  return array($filename, $verbml);
}

function _verb_start_ob() {
  global $_VERB;
  $avoid = array("load-styles.php", "load-scripts.php", "wp-tinymce.php");
  foreach ($avoid as $a) {
    if (strstr($_VERB['filename'], $a)) return;
  }
  if (strstr($_VERB['filename'], "/p.php") && file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . dirname($_VERB['filename']) . "/config/conf.php")) {
    return;
  }
  ob_start('_verb_handleob');
}

function _verb_store_feed($feed, $message = false) {
  _verb_write_file("feed.xml", $feed);
  if($message) echo "200 Success";
}

function _verb_store_file($iden, $file, $ext, $filename = null, $gd_or_uploaded = false) {
  global $_VERB;
  $newname = _verb_make_filename($ext, $filename);
  if ($gd_or_uploaded == "uploaded") {
    move_uploaded_file($file, $_VERB['config']['data_path'] . $newname);
    if ($_ENV['TEST']) $_VERB['files_written'][] = $newname;
  } elseif ($gd_or_uploaded) {
    if ($gd == "jpeg") imagejpeg($file, $_VERB['config']['data_path'] . $newname, 100);
    else imagepng($file, $_VERB['config']['data_path'] . $newname, 9);
    if ($_ENV['TEST']) $_VERB['files_written'][] = $newname;
  } else {
    _verb_write_file($newname, $file);
  }
  if ($iden) _verb_store_files($iden, $newname);
  return $newname;
}

function _verb_store_files($key, $value) {
  global $_VERB;
  _verb_lock_acquire();
  if ($value == null) {
    unset($_VERB['file_cache'][$key]);
  } else {
    $_VERB['file_cache'][$key] = $value;
  }
  _verb_write_file("files.psz", serialize($_VERB['file_cache']));
  _verb_lock_release();
}

function _verb_stringify_array($array) {
  foreach ($array as $k => $v) {
    if (is_object($v)) $array[$k] = (string)$v;
  }
  return $array;
}

function _verb_tag_unique_id(&$tag, $context) {
  if (!isset($tag['unique_id'])) {
    $tag['unique_id'] = md5(serialize($tag['attrs']) . $tag['type'] . serialize($tag['callback']) . count($tag['tags']) . (($context && $context->id) ? $context->id : 0));
  }
  return $tag['unique_id'];
}

function _verb_tick($desc, $userland = false) {
  global $_VERB;
  if (!isset($_REQUEST['__time'])) return;
  if ($_REQUEST['__time'] != "verb" && !$userland) return;
  $now = microtime(true);
  $_VERB['ticks'][] = array($desc, ($now - $_VERB['tick'])*1000, $userland);
  $_VERB['tick'] = $now;
}

function _verb_update_feed($message = false) {
  global $_VERB;
  _verb_lock_acquire(false, "update", true);
  $retry = 0;
  do {
    $retry++;
    $feed_data = _verb_simple_rest("/feed?secret_key=" . $_VERB['config']['secret_key']);
  } while (!strstr($feed_data, "</website>") && $retry < 3);
  if (strstr($feed_data, "</website>")) {
    _verb_store_feed($feed_data, $message);
    _verb_reset_site();
  }
  _verb_lock_release(null, "update");
}

function _verb_update_settings_feed() {
  global $_VERB;
  _verb_lock_acquire(false, "update", true);
  $retry = 0;
  do {
    $retry++;
    $feed_data = _verb_simple_rest("/feed/settings?secret_key=" . $_VERB['config']['secret_key']);
  } while (!strstr($feed_data, "?>") && $retry < 3);
  if (strstr($feed_data, "?>")) {
    _verb_write_file("settings.php", $feed_data);
  }
  _verb_lock_release(null, "update");
}

function _verb_urlize($r) {
  $r = preg_replace('/(^| )[a-zA-Z]+:\/\/([-]*[.]?[a-zA-Z0-9_\/\?&%=+])*/', '<a href="$0" target="_blank">$0</a>', str_replace("\n", "\n ", $r));
  $r = preg_replace('/(^| |>)www[.](([a-zA-Z0-9.])*[.](com|net|org|au|jp|us|uk)([a-zA-Z0-9_\/\?&%=+])*)/', "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $r);
  $r = preg_replace('/(^| |>)(([a-zA-Z0-9_.])*@([a-zA-Z0-9_\/\?&.%=+])*.(com|net|org|au|jp|us|uk))/', "\\1<a href=\"mailto:\\2\">\\2</a>", $r);
  return $r;
}

function _verb_valid_creditcard($creditcard) {
  if (!is_numeric($creditcard)) return false;
  $cardlength = strlen($creditcard);
  $parity = $cardlength % 2;
  $sum = 0;
  for ($i = 0; $i < $cardlength; $i++) {
    $digit = $creditcard[$i];
    if ($i % 2 == $parity) $digit = $digit * 2;
    if ($digit > 9) $digit = $digit - 9;
    $sum = $sum + $digit;
  }
  return ($sum % 10 == 0);
}

function _verb_valid_date($date) {
  return (strtotime($date) > 0);
}

function _verb_valid_digits($input) {
  return (!preg_match('/[^0-9]/', $input));
}

function _verb_valid_email($email) {
  return preg_match('/^[-+_.[:alnum:]]+@(?:(?:(?:[[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+(?:[a-z]+)$|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i', $email);
}

function _verb_valid_url($url) {
  return preg_match('/^(https?|ftp|telnet):\/\/((?:[a-z0-9@:.-]|%[0-9A-F]{2}){3,})(?::(\d+))?((?:\/(?:[a-z0-9-._~!$&\'()*+,;=:@]|%[0-9A-F]{2})*)*)(?:\?((?:[a-z0-9-._~!$&\'()*+,;=:\/?@]|%[0-9A-F]{2})*))?(?:#((?:[a-z0-9-._~!$&\'()*+,;=:\/?@]|%[0-9A-F]{2})*))?$/i', $url);
}

function _verb_write_file($name, $data) {
  global $_VERB;
  $f = fopen($_VERB['config']['data_path'] . $name, "wb");
  //if (!$f) { _verb_debug("Couldn't write local cache file " . _verb_h($_VERB['config']['data_path'] . $name)); return; }
  if (!$f) { _verb_error("","Couldn't write local cache file " . _verb_h($name)); _verb_die(); }
  fwrite($f, $data);
  fclose($f);
  if ($_ENV['TEST']) $_VERB['files_written'][] = $name;
}

?>
