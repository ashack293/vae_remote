<?php

function _verb_users_callback_forgot(&$tag) {
  global $_VERB;
  _verb_load_cache();
  if ($_REQUEST['__v:users_forgot_code']) {
    $u = $_VERB['file_cache']["users:forgot-".$_REQUEST['__v:users_forgot_code']];
    if (isset($u)) {
      _verb_store_files("users:forgot-".$_REQUEST['__v:users_forgot_code'], null);
      $_SESSION['__v:logged_in'] = $u;
      _verb_flash("You have been logged in.  Please take this time to change your password to something memorable.");
      if (strlen($tag['attrs']['redirect'])) return _verb_callback_redirect($tag['attrs']['redirect']);
    }
  } else {
    $user = _verb_users_find($tag);
    if ($user) {
      $code = strtoupper(substr(base_convert(md5(rand() . time()), 16, 36), 0, 6));
      _verb_store_files($_VERB['file_cache']["users:forgot-".$code], array('path' => $tag['attrs']['path'], 'id' => $user->id()));
      $domain = $_SERVER['HTTP_HOST'];
      $msg = "You are receiving this E-Mail because a request was submitted to reset your password for $domain.  If you submitted this request, please go to the following URL to login and reset your password:\n\nhttp://$domain" . $_SERVER['PHP_SELF'] . _verb_qs("__v:users_forgot_code=$code&__v:users_forgot=" . _verb_tag_unique_id($tag, $context)) . "\n\nThanks,\n$domain Password Recovery";
      _verb_mail(_verb_fetch($tag['attrs']['email_field'], $user), "$domain Password Recovery", $msg, "From: $domain Password Recovery <noreply@$domain>");
      _verb_flash('We have sent you an E-Mail containing a link that will let you reset your password.','msg');
      if (strlen($tag['attrs']['redirect'])) return _verb_callback_redirect($tag['attrs']['redirect']);
    } else {
      _verb_flash("Your entry didn't match any user stored in our records.",'err');
    }
  }
  return _verb_callback_redirect($_SERVER['PHP_SELF']);
}

function _verb_users_callback_login(&$tag) {
  global $_VERB;
  $user = _verb_users_find($tag);
  if ($user) {
    $_SESSION['__v:logged_in'] = array('path' => $tag['attrs']['path'], 'id' => $user->id());
    return _verb_callback_redirect($tag['attrs']['redirect']);
  }
  _verb_flash(($tag['attrs']['invalid'] ? $tag['attrs']['invalid'] : 'Login information incorrect.'),'err');
  return _verb_callback_redirect($_SERVER['PHP_SELF']);
}

function _verb_users_callback_logout(&$tag) {
  global $_VERB;
  $_SESSION['__v:logged_in'] = null;
  if (strlen($tag['attrs']['redirect'])) return _verb_callback_redirect($tag['attrs']['redirect']);
  return _verb_callback_redirect($_SERVER['PHP_SELF']);
}

function _verb_users_callback_register(&$tag) {
  $ret = _verb_rest(array(), "content/create/" . $tag['callback']['structure_id'] . ($tag['callback']['row_id'] > 0 ? "/" . $tag['callback']['row_id'] : ""), "content", $tag);
  if ($ret) {
    $data = _verb_array_from_rails_xml(simplexml_load_string($ret));
    $_SESSION['__v:logged_in'] = array('path' => $tag['attrs']['path'], 'id' => $data['id']);
    _verb_flash('Your account has been created.');
    if ($tag['attrs']['formmail']) {
      $tag['attrs']['to'] = $tag['attrs']['formmail'];
      return _verb_callback_formmail($tag);
    }
    if (strlen($tag['attrs']['redirect'])) return _verb_callback_redirect($tag['attrs']['redirect']);
  }
  return _verb_callback_redirect($_SERVER['PHP_SELF']);
}

function _verb_users_current_user() {
  _verb_session_deps_add('__v:logged_in');
  if (!$_SESSION['__v:logged_in']['id']) return null;
  return _verb_fetch($_SESSION['__v:logged_in']['id']);
}

function _verb_users_find(&$tag) {
  $look_for = explode(",", $tag['attrs']['required']);
  if (!count($look_for)) return "";
  $c = _verb_fetch($tag['attrs']['path']);
  foreach ($c as $r) {
    $gotit = true;
    foreach ($look_for as $k) {
      if ($r->$k->type == "PasswordItem") {
        $sep = explode(":", $r->$k);
        if (sha1($_REQUEST[$k] . $sep[0]) != $sep[1]) $gotit = false;
      } elseif ($_REQUEST[$k] != $r->$k) {
        $gotit = false;
      }
    }
    if ($gotit) return $r;
  }
  return null;
}

function _verb_users_render_forgot($a, &$tag, $context, &$callback, $render_context) {
  _verb_session_deps_add('__v:logged_in');
  if ($_SESSION['__v:logged_in']) return _verb_render_redirect("/");
 return _verb_render_callback("users_forgot", $a, $tag, $context, $callback, $render_context, $a['path']);
}

function _verb_users_render_if_logged_in($a, &$tag, $context, &$callback, $render_context) {
  _verb_session_deps_add('__v:logged_in');
  if (!$_SESSION['__v:logged_in'] && $a['redirect']) return _verb_render_redirect($a['redirect']);
  return _verb_render_tags($tag, $context, $render_context, $_SESSION['__v:logged_in']);
}

function _verb_users_render_login($a, &$tag, $context, &$callback, $render_context) {
 return _verb_render_callback("users_login", $a, $tag, $context, $callback, $render_context);
}

function _verb_users_render_logout($a, &$tag, $context, &$callback, $render_context) {
 return _verb_render_callback_link("users_logout", $a, $tag, $context, $callback, $render_context);
}

function _verb_users_render_register($a, &$tag, $context, &$callback, $render_context) {
  $createInfo = _verb_fetch_for_creating($a['path'], $context);
  $callback['structure_id'] = $createInfo->structure_id;
  $callback['row_id'] = $createInfo->row_id;
  if (!$callback['structure_id']) return _verb_error("Could not find users collection in <span class='c'>&lt;v:users:register&gt;</span>.", "", $tag['filename']);
 return _verb_render_callback("users_register", $a, $tag, $context, $callback, $render_context);
}

?>