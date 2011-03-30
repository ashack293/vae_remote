<?php

function _verb_render(&$tag, $context, $render_context) {
  global $_VERB;
  if (isset($tag['type'])) {
    $a = $tag['attrs'];
    if (is_array($tag['attrs']) && count($tag['attrs'])) {
      foreach ($tag['attrs'] as $k => $v) {
        if (strpos($v, "<v") !== false) {
          $a[$k] = (string)_verb_render_oneline($v, $context, $k);
        }
      }
    }
    if (!is_array($a)) $a = array();
    $tag['callback'] = array();
    unset($tag['unique_id']);
    if ($_VERB['tags'][$tag['type']]['handler']) {
      $func_name = $_VERB['tags'][$tag['type']]['handler'];
      if (isset($_VERB['tags'][$tag['type']]['filename'])) require_once(dirname(__FILE__)."/".$_VERB['tags'][$tag['type']]['filename']);
      $return_value = $func_name($a, $tag, $context, $tag['callback'], $render_context);
    } else {
      $func_type = preg_replace("/_+/", "_", preg_replace("/[^a-z0-9_]/", "_", $tag['type']));
      if (function_exists($func_type)) {
        $return_value = call_user_func($func_type);
      } elseif (function_exists("verb_" . $func_type)) {
        $return_value = call_user_func("verb_" . $func_type);
      }
    }
    if ((!isset($return_value) || ($return_value === true)) && $_VERB['tags'][$tag['type']]['callback']) {
      $return_value = _verb_render_callback($tag['type'], $a, $tag, $context, $tag['callback'], $render_context);
    }
    if (is_object($render_context) && $render_context->get("else2")) {
      $render_context->unset_in_place("else2");
    } elseif (is_object($render_context) && $render_context->get("else")) {
      $return_value = $return_value . $render_context->get("else_message");
      $render_context->unset_in_place("else");
      $render_context->unset_in_place("else_message");
    }
    if (isset($_REQUEST['__v:' . $tag['type']]) && ($_REQUEST['__v:' . $tag['type']] == _verb_tag_unique_id($tag, $context))) {
      $t = $tag;
      $t['attrs'] = $a;
      $_VERB['callback_stack'][$tag['type']] = $t;
    }
    if (isset($return_value)) return $return_value;
  }
  return ((strpos($tag['innerhtml'], "<v") === false) ? $tag['innerhtml'] : _verb_render_oneline($tag['innerhtml'], $context));;
}

function _verb_render_a($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ($a['ajax']) _verb_needs_jquery('');
  $old_context = $context;
  if (isset($a['path'])) {
    if ($a['path'] != "/") {
      $new_context = _verb_fetch($a['path'], $context);
      if (strlen((string)$new_context)) {
        if ($new_context->type == "FileItem" || $new_context->type == "VideoItem" || $new_context->type == "ImageItem") {          
          $preserve_filename = ($_VERB['settings']['preserve_filenames'] ? true : false);
          $href = verb_data_url() . verb_file($new_context, $preserve_filename);
        } else {
          $href = $new_context;
          if (substr($href, 0, 1) == "/") $href = _verb_proto() . $_SERVER['HTTP_HOST'] . $href;
        }
      } else {
        $context = $new_context;
      }
    } else {
      $context = null;
    }
  }
  if (strlen($a['href'])) {
    $d = explode("#", $a['href']);
    $anchor = $d[1];
    if (strlen($d[0])) {
      $e = explode("?", $d[0]);
      if ($e[0] == "/") {
        $href = $e[0];
      } elseif (substr($e[0], 0, 1) == "/" && $context && strlen($url = $context->permalink())) {
        $href = $url . (($e[0] == ("/" . $context->structure()->permalink)) ? "" : $e[0]) . _verb_qs($e[1], false);
      } else {
        $href = $e[0] . (($context != null && ($idfc = $context->id())) ? (substr($e[0], -1, 1) == "/" ? "" : "/") . $idfc : "") . _verb_qs($e[1], false);
      }
    }
  }
  if (!strlen($href) && $context) {
    if (strlen($url = $context->permalink())) $href = $url;
  }
  if ($a['autofollow'] && $render_context->get("total_items") == 1) _verb_render_redirect($href);
  if ($_VERB['hrefs'][$a['id']]) $href = $_VERB['hrefs'][$a['id']];
  if ($href == "...") $href = "";
  if ($a['ajax']) {
    if ($a['loading']) {
      $imgid = _verb_global_id();
      $a['onclick'] = _verb_append_js($a['onclick'], "jQuery('#" . $imgid . "').show();");
      $loader = '<img id="' . $imgid . '" src="' . $a['loading'] . '" alt="Loading ..." class="loading-indicator" style="display: none; vertical-align: middle;" />';
      if ($a['loadingposition'] == "before") $before = $loader;
      else $after = $loader;
      $a['oncomplete'] = _verb_append_js($a['oncomplete'], "jQuery('#" . $imgid . "').hide();");
    }
    if ($a['animate']) $a['oncomplete'] .= _verb_append_js($a['oncomplete'], "jQuery('#" . $a['ajax'] . "')." . $a['animate'] . "('slow');");
    $a['onclick'] = _verb_append_js($a['onclick'], "jQuery.get('" . $href . "', function(d){  jQuery('#" . $a['ajax'] . "').html(d); " . $a['oncomplete'] . " }); " . ($a['jump'] ? "" : "return false;"));
  }
  if ($_REQUEST['__host']) $a['href'] .= (strstr($a['href'], "?") ? "&" : "?") . "__host=" . $_REQUEST['__host'];
  $a['href'] = ($a['jump'] ? "#" . $a['jump'] : $href . ($anchor ? "#" . $anchor : ""));
  return ($href ? ($before . _verb_render_tag("a", $a, $tag, $old_context, $render_context) . $after) : "");
}

function _verb_render_a_if($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $true = false;
  if ($a['path']) {
    $true = _verb_fetch($a['path'], $context);
    if (is_object($true) && $true->type != "TextItem") {
      unset($a['path']);
    }
  } elseif ($a['total_items']) {
    $true = ($render_context->get("total_items") == $a['total_items']);
  }
  if ($_VERB['hrefs'][$a['id']]) $true = true;
  if (strlen($a['href'])) unset($a['path']);
  return ($true ? _verb_render_a($a, $tag, $context, $callback, $render_context) : _verb_render_tags($tag, $context, $render_context));
}

function _verb_render_asset($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if (!strlen($a['src']) && strlen($a['href'])) $a['src'] = $a['href'];
  $b = explode(".", $a['src']);
  $type = $a['media'];
  if ($b[count($b)-1] == "js") $type = "js";
  if (!strlen($type)) $type = "all";
  if (strlen($a['framework'])) {
    $split = explode("/", $a['framework']);
    if (!isset($split[1])) $split[] = (($split[0] == "yui" || $split[0] == "swfobject") ? "2" : "1");
    if (!isset($split[2])) {
      $file = $split[0];
      if ($split[0] == "dojo") $file .= "/dojo.xd";
      if ($split[0] == "jquery") $file .= ".min";
      if ($split[0] == "jqueryui") $file = "jquery-ui.min";
      if ($split[0] == "mootools") $file .= "-yui-compressed";
      if ($split[0] == "yui") $file = "build/yuiloader/yuiloader-min";
      $split[] = $file;
    }
    $framework = implode("/", $split);
    if (!strstr($framework, ".js")) $framework .= ".js";
    $proto = _verb_proto();
    return _verb_asset_html("js", $proto . 'ajax.googleapis.com/ajax/libs/'. $framework);
  }
  if ($a['debug'] || $_VERB['local']) {
    if (substr($a['src'], 0, 1) != "/") $a['src'] = "/" . $a['src'];
    return _verb_asset_html($type, $a['src']);
  } else {
    if (!isset($_VERB['assets'])) $_VERB['assets'] = array();
    $group = $type;
    if ($a['group']) $group = $a['group'];
    if (isset($_VERB['asset_types'][$group]) && ($_VERB['asset_types'][$group] != $type)) {
      return _verb_error("When using the <span class='c'>group</span> attribute in the <span class='c'>&lt;v:asset&gt;</span> tag, you must ensure that all assets in the same group are of the same media type.");
    }
    $_VERB['assets'][$group][] = $a['src'];
    $_VERB['asset_types'][$group] = $type;
    $_VERB['asset_inject_points'][$group]++;
    return "<_VERB_ASSET_" . $group . $_VERB['asset_inject_points'][$group] . ">";
  }
}

function _verb_render_callback($name, $a, &$tag, $context, &$callback, $render_context, $qs = "") {
  global $_VERB;
  $a['action'] = $_SERVER['PHP_SELF'] . _verb_qs("__v:" . $name . "=" . _verb_tag_unique_id($tag, $context) . (strlen($qs) ? "&" . $qs : ""));
  $out = _verb_render_form($a, $tag, $context, $callback, $render_context);
  return $out;
}

function _verb_render_callback_link($name, $a, &$tag, $context, &$callback, $render_context) {
  $a['href'] = $_SERVER['PHP_SELF'] . _verb_qs("__v:" . $name . "=" . _verb_tag_unique_id($tag, $context));
  return _verb_render_tag("a", $a, $tag, $context, $render_context);
}

function _verb_render_captcha($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $tag['callback']['_form_prepared'] = true;
  require_once(dirname(__FILE__) . "/../vendor/recaptchalib.php");
  return recaptcha_get_html($_VERB['recaptcha']['public'], null, ($_SERVER['HTTPS'] || $_REQUEST['__verb_ssl_router']));
}

function _verb_render_cdn($a, &$tag, $context, &$callback, $render_context) {
  if (strlen($a['href'])) $a['src'] = $a['href'];
  if (strlen($a['src'])) {
    $url = $a['src'];
    if (substr($url, 0, 1) != "/")  $url = "/" . dirname($_VERB['filename']) . $url;
    $url = _verb_cdn_timestamp_url($url);
    return verb_cdn_url() . substr($url, 1);
  }
  return verb_cdn_url();
}

function _verb_render_checkbox($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  if ($a['value'] && !strlen($a['checked'])) $a['checked'] = "checked";
  if (!strlen($a['value'])) $a['value'] = "1";
  if ($a['checked'] == "false" || $a['checked'] == "0") unset($a['checked']);
  $a['type'] = "checkbox";
  return  '<input' . _verb_attrs($a, "input") . ' />';
}

function _verb_render_collection($a, &$tag, $context, &$callback, $render_context, $contexts = "__") {
  global $_VERB;
  $options = array();
  if ($a['filter_input']) {
    if (strlen($_REQUEST[$a['filter_input']])) {
      $options['filter'] = $_REQUEST[$a['filter_input']];
    } else {
      return _verb_get_else($tag, $context, $render_context, "You did not enter a search query.");
    }
  }
  foreach(array('order','skip','paginate','limit','groups','filter','unique') as $opt) {
    if ($a[$opt]) $options[$opt] = $a[$opt];
  }
  if ($a['paginate']) {
    if (!isset($_VERB['main_pagination']) && !isset($_VERB['settings']['query_string_pagination'])) {
      $_VERB['main_pagination'] = $page_request_variable = "page";
    } elseif (isset($a['id'])) {
      if (isset($_VERB['pagination'][$a['id']])) {
        return _verb_error("You cannot define a <span class='c'>id=&quot;" . _verb_h($a['id']) . "&quot;</span> on a <span class='c'>&lt;v:collection&gt; tag that will be rendered more than once on the page (i.e. inside another collection).", "", $tag['filename']);
      }
      $page_request_variable = $a['id'] . "_page";
    } else {
      $page_request_variable = '_page_' . _verb_tag_unique_id($tag, $context);
    }
  }
  if ($a['paginate'] && is_numeric($_REQUEST[$page_request_variable]) || $_REQUEST[$page_request_variable] == "all") {
    $page = $_REQUEST[$page_request_variable];
  } elseif ($a['default_page']) {
    $page = $a['default_page'];
    $set_page_to_last = true;
  } elseif ($a['groups']) {
    $page = ($_VERB['render']['collection']['groups'][($context ? $context->id() : "").$a['path']]++) + 1;
  } else {
    $page = 1;
  }
  $options['page'] = $page;
  if ($contexts == "__") $contexts = _verb_fetch($a['path'], $context, $options);
  $dividers = _verb_find_dividers($tag);
  if ($a['store_in_session']) {
    $mycontexts = $contexts;
    if ($a['skip'] || $a['paginate']) {
      unset($options['skip']);
      unset($options['paginate']);
      $mycontexts = _verb_fetch($a['path'], $context, $options);
    }
    $cookie = "";
    if (is_object($mycontexts) && $mycontexts->count) {
      foreach ($mycontexts as $context) {
        $cookie .= "," . $context->permalinkOrId();
      }
    }
    _verb_session_cookie($a['store_in_session'], substr($cookie, 1));
  }
  if (is_object($contexts) && $contexts->count) {
    $tr_open = false;
    $rendered = 0;
    if ($a['paginate']) {
      $last_page = ceil($contexts->totalMatches() / $a['paginate']);
      if (strlen($a['max_pages']) && ($a['max_pages'] < $last_page)) $last_page = $a['max_pages'];
      if ($set_page_to_last) $page = $last_page;
      if ($a['previous']) $_VERB['hrefs'][$a['previous']] = (((($page > 1) || $a['wrap']) && $_REQUEST[$page_request_variable] != "all") ? $_SERVER['PHP_SELF'] . _verb_qs(array($page_request_variable => ($page == 1 ? $last_page : ($page - 1)))) : "...");
      if ($a['next']) $_VERB['hrefs'][$a['next']] = (((($page < $last_page) || $a['wrap']) && $_REQUEST[$page_request_variable] != "all") ? $_SERVER['PHP_SELF'] . _verb_qs(array($page_request_variable => ($page == $last_page ? 1 : ($page + 1)))) : "...");
      if ($a['all']) $_VERB['hrefs'][$a['all']] =  $_SERVER['PHP_SELF'] . _verb_qs(array($page_request_variable => "all"));
    }
    if ($a['page_select']) $_VERB['page_select'][$a['page_select']] = array($last_page, $page, $_SERVER['PHP_SELF'] . _verb_qs($page_request_variable . "=", true, $page_request_variable . "="), $a['default_page'] == "last()");
    if ($a['id']) $_VERB['pagination'][$a['id']] = array('page' => $page, 'last_page' => $last_page, 'page_request_variable' => $page_request_variable);  
    $render_context = $render_context->set("total_items", $contexts->totalMatches());
    $reverse = stristr($a['output_order'], "reverse");
    if ($a['per-row']) $a['per_row'] = $a['per-row'];
    foreach ($contexts as $context) {
      if ($a['per_row'] && $tr_open == false) {
        if ($a['per_row'] >= 1) {
          $out .= "<tr>";
          $tr_open = true;
        }
      }
      $this_tag = _verb_render_tags($tag, $context, $render_context);
      $this_tag = _verb_merge_dividers($this_tag, $dividers, $rendered, $context, $render_context);
      if ($reverse) $out = $this_tag . $out;
      else $out .= $this_tag;
      $rendered++;
      if (($a['per_row'] && ($a['per_row'] >= 1)) && (($rendered % $a['per_row']) == 0) && $tr_open == true) {
        $out .= "</tr>";
        $tr_open = false;
      }
    }
    if ($a['per_row'] && $tr_open && ($a['per_row'] >= 1)) {
      while (($rendered % $a['per_row']) != 0) {
        $out .= "<td></td>";
        $rendered++;
      }
      $out .= "</tr>";
    }
  } else {
    return _verb_get_else($tag, $context, $render_context);
  }
  if (_verb_is_xhr() && strlen($_REQUEST['__xhr_paginate']) && ($_REQUEST['__xhr_paginate'] == $page_request_variable)) {
    _verb_final($out);
  }
  return $out;
}

function _verb_render_country_select($a, &$tag, $context, &$callback, $render_context) {
  _verb_needs_jquery();
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  if (!strlen($a['default'])) $a['default'] = "US";
  $a['options'] = _verb_list_countries();
  $state_select_id = str_replace("country", "state", $a['id']);
  $a['onchange'] = _verb_append_js($a['onchange'], "
    jQuery('#" . $state_select_id . "').trigger('change');
    if (jQuery('#" . $state_select_id . "_'+this.value).length > 0) {
      jQuery('.$state_select_id').each(function() { jQuery(this).attr('name',jQuery(this).attr('id')) }).hide();
      jQuery('#" . $state_select_id . "_'+this.value).show().attr('name','$state_select_id');
      jQuery('#" . $state_select_id . "').val(jQuery('#" . $state_select_id . "_'+this.value).val());
    } else {
      jQuery('.$state_select_id').each(function() { jQuery(this).attr('name',jQuery(this).attr('id')) }).hide();
      jQuery('#" . $state_select_id . "_txt').show().attr('name','$state_select_id');
      jQuery('#" . $state_select_id . "').val(jQuery('#" . $state_select_id . "_txt').val());
    }
  ");
  _verb_on_dom_ready("jQuery('#" . $a['id'] . "').trigger('change');");
  return _verb_render_select($a, $tag, $context, $callback, $render_context);
}

function _verb_render_create($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $createInfo = _verb_fetch_for_creating($a['path'], $context);
  $callback['structure_id'] = $createInfo->structure_id;
  $callback['row_id'] = $createInfo->row_id;
  return _verb_render_callback("create", $a, $tag, $context, $callback, $render_context->set("form_create_mode"), $a['path']);
}

function _verb_render_date_select($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  $date = strtotime(_verb_request_param($a['name'] . "_month") . "/" . _verb_request_param($a['name'] . "_day") . _verb_request_param($a['name'] . "_year"));
  if (($date < 1) && strlen($a['value'])) $date = strtotime($a['value']);
  $orig = $a;
  $a['options'] = array('' => '', 1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
  $a['value'] = ($date ? strftime("%m", $date) : "");
  $a['name'] = $orig['name'] . "_month";
  $a['id'] = $orig['id'] . "_month";
  $out = _verb_render_select($a, $tag, $context, $callback, $render_context);
  $a['options'] = array('' => '');
  for ($i = 1; $i <= 31; $i++) { $a['options'][$i] = $i; }
  $a['value'] = ($date ? strftime("%d", $date) : "");
  $a['name'] = $orig['name'] . "_day";
  $a['id'] = $orig['id'] . "_day";
  $out .= " " . _verb_render_select($a, $tag, $context, $callback, $render_context);
  $a['options'] = array('' => '');
  for ($i = strftime("%Y"); $i > 1900; $i--) { $a['options'][$i] = $i; }
  $a['value'] = ($date ? strftime("%Y", $date) : "");
  $a['name'] = $orig['name'] . "_year";
  $a['id'] = $orig['id'] . "_year";
  $out .= " " . _verb_render_select($a, $tag, $context, $callback, $render_context);
  return $out;
}

function _verb_render_date_selection($a, &$tag, $context, &$callback, $render_context) {
  $out = $last = "";
  $rendered = $count = 0;
  $options = array("order" => "DESC(" . $a['date_field'] . ")");
  foreach(array('skip','paginate','limit','groups','filter','unique') as $opt) {
    if ($a[$opt]) $options[$opt] = $a[$opt];
  }
  $contexts = _verb_fetch($a['path'], $context, $options);
  if (!strlen($a['href'])) $a['href'] = $_SERVER['PHP_SELF'];
  if (is_object($contexts)) {
    $param = ($a['param'] ? $a['param'] : $a['date_field']);
    $format = ($a['strftime'] ? $a['strftime'] : "%B %Y");
    $dividers = _verb_find_dividers($tag);
    foreach ($contexts as $ctxt) {
      if ($date = _verb_fetch($a['date_field'], $ctxt)) {
        $formatted = strftime($format, (string)$date);
        if ($formatted != $last) {
          $out = str_replace("%N", $count, $out);
          $count = 0;
          if (strstr($format, "%a") || strstr($format, "%A") || strstr($format, "%d")  || strstr($format, "%e") || strstr($format, "%j") || strstr($format, "%u") || strstr($format, "%w")) {
            $link = strftime("%Y-%m-%d", (string)$date);
          } elseif (strstr($format, "%b") || strstr($format, "%B") || strstr($format, "%h")  || strstr($format, "%m")) {
            $link = strftime("%Y-%m", (string)$date);
          } else {
            $link = strftime("%Y", (string)$date);
          }
          $data = '<a ' . (($_REQUEST[$param] == $i) ? 'class="current" ' : '') . 'href="' . $a['href'] . _verb_qs(array($param => $link)) . '">' . $formatted . '</a> ';
          $out .= _verb_merge_dividers($data, $dividers, $rendered, $context, $render_context);
          $rendered++;
        }
        $count++;
        $last = $formatted;
      }
    }
  }
  $out = str_replace("%N", $count, $out);
  return $out;
}

function _verb_render_debug($a, &$tag, $context, &$callback, $render_context) {
  $out = "<div style='background: #333; color: #fff; padding: 15px; font: 14px \"Lucida Grande\", sans-serif;'><p style='font-weight: bold; font-size: 1.2em;'>Verb Debugging Information</p><p>Current Context:</p><ul>";
  if ($context) {
    foreach ($context->data() as $key => $value) {
      if (strlen($value) > 50) $value = substr($value, 0, 50) . "...";
      if ($value != "(collection)") {
        $value = "<span style='font-family: Monaco, \"Courier New\"; color: #ccc;'>" . $value . "</span>";
      }
      $out .= "<li><span style='font-family: Monaco, \"Courier New\"; color: #faec31;'>$key</span> => " . $value . "</li>";
    }
  }
  return $out . "</ul></div>";
}

function _verb_render_disqus($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ($_VERB['disqus_rendered']) {
    return _verb_error("You may only include one <span class='code'>&lt;v:disqus /&gt;</span> tag per page.");
  }
  $xid = ($context ? $context->id : null);
  $js = '<div id="disqus_thread"></div>
    <script type="text/javascript">'. (($context && $context->id) ? '
      var disqus_identifier = ' . $xid . ';' : '') . ($_REQUEST['__verb_local'] ? '
      var disqus_developer = true;' : '') . ($a['css'] ? '
      var disqus_iframe_css = ' . $a['css'] . ';' : '') . '
      (function() {
       var dsq = document.createElement("script"); dsq.type = "text/javascript"; dsq.async = true;
       dsq.src = "http://' . $a['shortname'] . '.disqus.com/embed.js";
       (document.getElementsByTagName("head")[0] || document.getElementsByTagName("body")[0]).appendChild(dsq);
      })();
    </script>
    <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript=' . $a['shortname'] . '">comments powered by Disqus.</a></noscript>
    <a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>';
  return $js;
}

function _verb_render_divider($a, &$tag, $context, &$callback, $render_context) {
  return "";
}

function _verb_render_else($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ((!isset($_VERB['settings']['child_v_else'])) && is_object($render_context) && $render_context->get("else")) {
    $render_context->unset_in_place("else");
    return _verb_render_tags($tag, $context, $render_context);
  } else {
    return "";
  }
}

function _verb_render_elseif($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ((!isset($_VERB['settings']['child_v_else'])) && is_object($render_context) && $render_context->get("else")){
    $render_context->unset_in_place("else");
    return _verb_render_if($a, $tag, $context, $callback, $render_context);
  } else {
    return "";
  }
}

function _verb_render_facebook_comments($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if (!is_numeric($a['paginate'])) $a['paginate'] = 10;
  if (!is_numeric($a['width'])) $a['width'] = 450;
  if ($a['path'] != "/") {
    if ($a['path']) $context = _verb_fetch($a['path'], $context);
    if (is_object($context)) $xid = $context->id;
    elseif (strlen($context)) $xid = $context;
  }
  if (!$xid) $xid = $_SERVER['PHP_SELF'];
  if ($_VERB['facebook_js_rendered']) {
    $js = "";
  } else {
    $js = '<div id="fb-root"></div>
      <script>
        window.fbAsyncInit = function() {
          FB.init({appId: "' . $a['appid'] . '", status: true, cookie: true, xfbml: true});
        };
        (function() {
          var e = document.createElement("script"); e.async = true;
          e.src = document.location.protocol +
            "//connect.facebook.net/en_US/all.js";
          document.getElementById("fb-root").appendChild(e);
        }());
      </script>';
  }
  return $js . '<fb:comments xid="' . $xid . '" numposts="' . $a['paginate'] . '" width="' . $a['width'] . '"></fb:comments>';
}

function _verb_render_facebook_like($a, &$tag, $context, &$callback, $render_context) {
  if (!is_numeric($a['width'])) $a['width'] = 450;
  if ($a['colorscheme'] != "dark") $a['colorscheme'] = "light";
  if ($a['layout'] != "button_count") $a['layout'] = "standard";
  if ($a['action'] != "recommend") $a['action'] = "like";
  if ($a['path'] != "/") {
    if ($a['path']) $context = _verb_fetch($a['path'], $context);
    if (is_object($context)) $url = "http://" . $_SERVER['HTTP_HOST'] . "/" . $context->permalink(false);
  }
  if ($a['url']) $url = $a['url'];
  if (!$url) $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  return '<iframe src="' . _verb_proto() . 'www.facebook.com/plugins/like.php?href=' . urlencode($url) . '&amp;layout=' . $a['layout'] . '&amp;show_faces=true&amp;width=' . $a['width'] . '&amp;action=' . $a['action'] . '&amp;colorscheme=' . $a['colorscheme'] . '" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:' . $a['width'] . 'px; height: 60px"></iframe>';
}

function _verb_render_file($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $file = (string)_verb_fetch($a['path'], $context);
  $callback['src'] = (preg_match("/^([0-9]*)(-|$)/", $file) ? verb_file($file) : $file);
  $callback['filename'] = $a['filename'];
  if (!isset($_VERB['zip_files'])) $_VERB['zip_files'] = array();
  $_VERB['zip_files'][] = $callback;
  return _verb_render_callback_link("file", $a, $tag, $context, $callback, $render_context);
}

function _verb_render_file_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  $a['type'] = "file";
  return  '<input' . _verb_attrs($a, "input") . ' />';
}

function _verb_render_fileurl($a, &$tag, $context, &$callback, $render_context) {
  $file = (string)_verb_fetch($a['path'], $context);
  $e = explode("-", $file);
  return (is_numeric($e[0]) ? verb_file($file) : $file);
}

function _verb_render_flash($a, &$tag, $context, &$callback = null, $render_context) {
  return _verb_render_flash_inside($a['flash'], $render_context, true);
}

function _verb_render_flash_inside($which = "", $render_context, $is_flash_tag = false) {
  global $_VERB;
  $shown = array();
  if ($_SESSION['__v:flash']['messages'] && !$_VERB['flash_rendered'][$which]) {
    foreach ($_SESSION['__v:flash']['messages'] as $f) {
      if (!strlen($which) || ($f['which'] == $which)) {
        if ((strlen($which) || $is_flash_tag || !$render_context->get("has_flash_tag" . $f['which'])) && !$_VERB['flash_rendered'][$f['which']]) {
          if (!$shown[$f['msg']]) {
            $out .= '<div class="flash ' . $f['type'] . '">' . $f['msg'] . "</div>";
            $shown[$f['msg']] = true;
          }
        }
      }
    }
  }
  if (strlen($out)) $_VERB['flash_rendered'][$which] = true;
  return $out;
}

function _verb_render_form($a, &$tag, $context, &$callback = null, $render_context) {
  if ($render_context->get("form")) _verb_error("You cannot nest <span class='c'>&lt;form&gt;</span> tags.  Watch out for any VerbML tags you have that generate <span class='c'>&lt;form&gt;</span> tags.", "", $tag['filename']);
  if (!strlen($a['id']) && ($a['ajax'] || $a['validateinline'] || $a['loading'])) $a['id'] = _verb_global_id();
  if (!strlen($a['method'])) $a['method'] = 'post';
  $out = _verb_render_flash_inside($a['flash'], $render_context);
  $out .= _verb_render_tag("form", $a, $tag, $context, $render_context->set("form_context", $context)->set_in_place("form", true));
  if ($a['ajax']) {   
    if ($a['loading']) {
      $loader = '<img id="' . $a['id'] . '_loading" src="' . $a['loading'] . '" alt="Loading ..." class="loading-indicator" style="display: none; vertical-align: middle;" />';
      if ($a['loadingposition'] == "before") $out = $loader . $out;
      else $out .= $loader;
    }
    $script = "jQuery('#" . $a['id'] . "').ajaxForm({ success: function(data,status) { jQuery('#" . $a['id'] . "_loading').hide(); if (match = /^__err=(.*)/.exec(data)) {" . $a['ajaxfailure'] . " alert(match[1].replace(/\\\\n/g, \"\\n\")); } else { jQuery('#" . $a['ajax'] . "').html(data); if (!window.vRedirected) { " . $a['ajaxsuccess'] . " } " . ($a['animate'] ? "jQuery('#" . $a['ajax'] . "')." . $a['animate'] . "('slow');" : "") . "} }";
    if ($a['validateinline']) {
      _verb_needs_jquery('form','validate');
      _verb_on_dom_ready($script . ", beforeSubmit: function() {" . $a['ajaxbefore'] . " var t = jQuery('#" . $a['id'] . "').valid(); if (t) { jQuery('#" . $a['id'] . "_loading').show(); } else { " . $a['ajaxfailure'] . " } return t; } }); jQuery('#" . $a['id'] . "').validate();");
    } else {
      _verb_needs_jquery('form');
      _verb_on_dom_ready($script . ", beforeSubmit: function() {" . $a['ajaxbefore'] . " jQuery('#" . $a['id'] . "_loading').show(); } });");
    }
  } elseif ($a['validateinline']) {
    _verb_needs_jquery('validate');
    _verb_on_dom_ready("jQuery('#" . $a['id'] . "').validate();");
  }
  return $out;
}

function _verb_render_formmail($a, &$tag, $context, &$callback, $render_context) {
  return _verb_render_callback("formmail", $a, $tag, $context, $callback, $render_context);
}

function _verb_render_fragment($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if (!$a['cache']) return "";
  if (!$_SERVER['HTTPS'] && !$_REQUEST['__verb_ssl_router'] && !$_REQUEST['__verb_local']) {
    $key = $_VERB['global_cache_key'] . $a['cache'];
    $cached = memcache_get($_VERB['memcached'], $key);
    if (is_array($cached) && $cached[0] == "chks") {
      return $cached[1];
    }
  }
  $out = _verb_render_tags($tag, $context, $render_context);
  if ($key) {
    memcache_set($_VERB['memcached'], $key, array("chks", $out), 0, 3600);
  }
  return $out;
}

function _verb_render_gravatar($a, &$tag, $context, &$callback, $render_context) {
  if (!strlen($a['size'])) $a['size'] = "80";
  if (!strlen($a['default'])) $a['default'] = "wavatar";
  if (!strlen($a['rating'])) $a['rating'] = "g";
  $a['src'] =  "http://www.gravatar.com/avatar/" . md5(strtolower($a['email'])) . "?default=" . urlencode($a['default']) . "&rating=" . $a['rating'] . "&size=" . $a['size']; 
  return '<img' . _verb_attrs($a, "img") . ' />';
}

function _verb_render_hidden_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  $a['type'] = "hidden";
  return  '<input' . _verb_attrs($a, "input") . ' />';
}

function _verb_render_if($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $true = false;
  if ($a['path']) {
    $true = _verb_fetch_without_errors($a['path'], $context);
  } elseif ($a['total_items']) {
    $true = ($render_context->get("total_items") == $a['total_items']);
  } elseif ($a['param']) {
    $true = ($_REQUEST[$a['param']]);
  } elseif ($a['id']) {
    $q1 = (string)_verb_fetch_without_errors($a['id'], $context);
    if (!is_numeric($q1)) {
      $new_context = _verb_fetch_without_errors($a['id']);
      if (is_object($new_context)) {
        $q1 = (string)$new_context->id();
      } else {
        $q1 = $new_context;
      }
    }
    $q2 = (string)$context->id();
    $true = ($q1 == $q2);
  }
  if ($true === "0.0" || (string)$true == "0") $true = 0;
  if ($a['is'] && ($true != $a['is'])) $true = false; 
  if (is_object($true) && !$true->collection() && (string)$true == "") {
    $true = false;
  }
  return _verb_render_tags($tag, $context, $render_context, $true);
}

function _verb_render_if_backstage($a, &$tag, $context, &$callback, $render_context) {
  _verb_session_deps_add('__v:user_id');
  if (!isset($_SESSION['__v:user_id']) && $a['redirect']) return _verb_render_redirect($a['redirect']);
  return _verb_render_tags($tag, $context, $render_context, isset($_SESSION['__v:user_id']));
}

function _verb_render_if_paginate($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ($a['collection']) {
    $b = $_VERB['pagination'][$a['collection']];
    $true = ($b['last_page'] > 1);
  } else {
    $items = _verb_fetch($a['path'], $context);
    $true = (is_object($items) && (count($items) > $a['paginate']));
  }
  return _verb_render_tags($tag, $context, $render_context, $true);
}

function _verb_render_if_time($a, &$tag, $context, &$callback, $render_context) {
  $true = true;
  if ($a['before'] && (time() > strtotime($a['before']))) $true = false;
  if ($a['after'] && (time() < strtotime($a['after']))) $true = false;
  return _verb_render_tags($tag, $context, $render_context, $true);
}

function _verb_render_img($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if (!isset($a['alt'])) $a['alt'] = "Image";
  if ($a['path']) {
    if ($a['filename']) {
      $preserve_filename = $a['filename'];
    } else {
      $preserve_filename = ($_VERB['settings']['preserve_filenames'] ? true : false);
    }
    $a['src'] = (($a['image_size'] && !$a['width']) ? verb_sizedimage(_verb_fetch($a['path'], $context), $a['image_size'], $preserve_filename) : verb_image(_verb_fetch($a['path'], $context), $a['width'], $a['height'], $a['image_size'], $a['grow'], $a['quality'], $preserve_filename));
    if (!$a['src']) return "";
    if ($a['watermark']) $a['src'] = verb_watermark($a['src'], $a['watermark'], $a['watermark_vertical_align'], $a['watermark_align'], $a['watermark_vertical_padding'], $a['watermark_horizontal_padding']);
    if (substr($a['filter'], 0, 7) == "reflect") {
      $params = explode(",", str_replace(array("(", ")"), "", substr($a['filter'], 7)));
      $a['src'] = verb_image_reflect($a['src'], (strlen($params[0]) ? $params[0] : 30), (strlen($params[1]) ? $params[1] : 35), true);
    } elseif ($a['filter'] == "grey") {
      $a['src'] = verb_image_grey($a['src'], true);    
    }
    $size = _verb_imagesize($a['src']);
    if ($size) {
      $a['width'] = $size[0];
      $a['height'] = $size[1];
    } else {
      unset($a['width']);
      unset($a['height']);
    }
    $a['src'] = _verb_absolute_data_url() . $a['src'];
  } elseif ($a['src']) {
    if (!strstr($a['src'], "://")) $a['src'] = _verb_render_cdn($a, $tag, $context, $callback, $render_context);
  } else {
    return _verb_error("You need to provide a value for either the <span class='c'>path</span> or <span class='c'>src</span> attribute of the <span class='c'>&lt;v:img&gt;</span> tag.", "", $tag['filename']);
  }
  if ($tag['type'] == "img") {
    if ($a['protect']) {
      $a['style'] = ($a['style'] ? $a['style'] . " " : "") . "background-image: url(" . $a['src'] . "); height: " . $a['height'] . "px; width: " . $a['width'] . "px;";
      return '<div' . _verb_attrs($a, "div") . '><img src="' . $_VERB['config']['asset_url'] . 'spacer.png" height="' . $a['height'] . '" width="' . $a['width'] . '" /></div>';
    }
    return '<img' . _verb_attrs($a, "img") . ' />';
  } else {
    return $a['src'];
  }
}

function _verb_render_nested_collection($a, &$tag, $context, &$callback, $render_context) {
  $options = array();
  foreach (array('filter','order','unique') as $opt) {
    if ($a[$opt]) $options[$opt] = $a[$opt];
  }
  if ($a['filter_input']) {
    if (strlen($_REQUEST[$a['filter_input']])) {
      $options['filter'] = $_REQUEST[$a['filter_input']];
    } else {
      return _verb_get_else($tag, $context, $render_context, "You did not enter a search query.");
    }
  }
  if ($a['path'] == ".." && !$render_context->get("nestedRendering")) {
    $contexts = $context;
  } else {
    $contexts = _verb_fetch($a['path'], $context, $options);
  }
  if (is_object($contexts) && ($contexts->count > 0)) {
    while (substr($a['path'], 0, 1) == "@" || substr($a['path'], 0, 1) == "/") $a['path'] = substr($a['path'], 1);
    $dividers = _verb_find_dividers($tag);
    $rendered = 0;
    $nested_render_context = $render_context->set("total_items", $contexts->totalMatches())->set_in_place("nestedRendering", "1");
    foreach ($contexts as $context) {
      $children = _verb_render_nested_collection($a, $tag, $context, $callback, $nested_render_context);
      $parent = _verb_render_tags($tag, $context, $render_context);
      if ($a['output_order'] == "reverse") $parent = $children . $parent;
      else $parent .= $children;
      $out .= _verb_merge_dividers($parent, $dividers, $rendered, $context, $nested_render_context);
      $rendered++;
    }
    if (strlen($out)) {
      $out = _verb_merge_dividers($out, $dividers, $render_context->get("nestedRendering"), $context, $render_context, ($a['output_order'] == "reverse"), "nested_divider");
    }
  }
  return $out;
}

function _verb_render_newsletter($a, &$tag, $context, &$callback, $render_context) {
  return _verb_render_callback("newsletter", $a, $tag, $context, $callback, $render_context);
}

function _verb_render_nowidows($a, &$tag, $context, &$callback, $render_context) {
  $out = trim(_verb_render_tags($tag, $context, $render_context));
  $words = preg_split('/\s\s*/', $out);
  $end = array_pop($words);
  $next = array_pop($words);
  array_push($words, $next . "&nbsp;" . $end);
  return implode(" ", $words);
}

function _verb_render_oneline($out, $context, $attribute_type = false) {
  global $_VERB;
  preg_match_all('/<v=([^>]*)>/', $out, $matches, PREG_SET_ORDER);
  foreach ($matches as $regs) {
    $out = str_replace($regs[0], _verb_oneline($regs[1], $context, $attribute_type), $out);
  }
  preg_match_all('/<v~([^>]*)>/', $out, $matches, PREG_SET_ORDER);
  foreach ($matches as $regs) {
    $out = str_replace($regs[0], _verb_oneline_url($regs[1], $context), $out);
  }
  preg_match_all('/<v\\?(.*)\\?>/', $out, $matches, PREG_SET_ORDER);
  foreach ($matches as $regs) {
    $out = str_replace($regs[0], _verb_php($regs[1], $context), $out);
  }
  return str_replace("<v->", ($context ? $context->formId() : ""), $out);
}

function _verb_render_option_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $blank = $set_fn = $out = "";
  $options = $option_ids = array();
  $fields = array();
  $price_field = $render_context->attr("price_field", $a);
  $inventory_field = $render_context->attr("inventory_field", $a);
  $disable_inventory_check = $render_context->attr("disable_inventory_check", $a);
  $all_entries = _verb_fetch($a['path'], $context);
  if ($all_entries == false) return "";
  $entries = array();
  foreach ($all_entries as $r) {
    if ($inventory_field && !$disable_inventory_check && (string)$r->get($inventory_field) === "0") continue;
    $entries[] = $r;
  }
  _verb_needs_jquery();
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  $old_a = $a;
  $glob_id = _verb_global_id();
  $script = "\n";
  $name = $glob_id . "_list";
  foreach (explode(",", $a['fields']) as $field) {
    $values = array();
    foreach ($entries as $r) {
      $val = _verb_fetch($field, $r);
      if ($val->type == "OptionsItem") {
        foreach (explode(",", (string)$val) as $option) {
          $e = explode("=", $option);
          $options[$e[0]][$e[1]] = 1;
        }
      } elseif (strlen($val)) {
        $e = explode("/", $field);
        $options[(string)$all_entries->get($e[0])->structure->name][(string)$val] = 1;
      }
      if (($val->type == "OptionsItem" || strlen($val)) && !in_array($field, $fields)) {
        $fields[] = $field;
      }
    }
  }
  if (count($options)) {
    foreach ($options as $option_name => $values) {
      $option_ids[$option_name] = _verb_global_id();
    }
    $i = 1;
    foreach ($options as $option_name => $values) {
      $a['options'] = ($a['default'] ? '<option value="" class="' . $glob_id . '_default">' . $a['default'] . '</option>' : "");
      foreach ($values as $r => $v) {
        $a['options'] .= '<option value="' . str_replace('"', "", $r) . '">' . str_replace('"', "", $r) . '</option>';
      }
      unset($a['name']);
      $a['id'] = $option_ids[$option_name];
      if (strlen($set_fn)) $set_fn .= " && ";
      $set_fn .= $name . "[item_][" . $i . "] == jQuery('#" . $a['id'] . "').val()";
      $j = 1;
      $change = $check_fn = "";
      foreach ($options as $f => $stuff) {
        if ($option_name != $f) {
          $check_fn .= " && " . $name . "[sel][" . $j . "] == jQuery('#" . $option_ids[$f] . "').val()";
          $change .= "    selected = jQuery('#" . $option_ids[$f] . "').val();\n    jQuery('#" . $option_ids[$f]. "').empty();\n    for (item_ in " . $name . ") {\n      if (" . $name . "[item_][$i] == jQuery(this).val()) {\n        if (jQuery('#" . $option_ids[$f] . "').find('option[value=\"' + " . $name . "[item_][" . $j . "] + '\"]').length < 1) {\n          jQuery('#" . $option_ids[$f]. "').append('<option' + (selected == " . $name . "[item_][$j] ? ' selected=\"selected\"' : '') + ' value=\"' + " . $name . "[item_][$j] + '\">' + " . $name . "[item_][$j] + '</option>');\n        }\n      }\n    }\n";
        }
        $j++;
      }
      $price_hints .= "        if (" . $name . "[sel][" . $i . "] == jQuery('#" . $option_ids[$f] . "').val()" . $check_fn . ") {\n          t = jQuery('#" . $a['id'] . "').find('option[value=\"' + " . $name . "[item_][" . $i . "] + '\"]'); t.html(t.val() + upcharge);\n        }\n";
      $script .= "  jQuery('#" . $a['id'] . "').change(function() {\n    jQuery('." . $glob_id . "_default').remove();\n" . $change . "    " . $glob_id . "_set();\n  });\n";
      $out .= '<div><label>' . $option_name . ":</label> " . _verb_render_select($a, $tag, $context, $callback, $render_context) . '</div>';
      $i++;
    }
  }
  if ($price_field) $main_price = _verb_fetch($price_field, $context);
  $out .= _verb_render_tag("input", array('type' => 'hidden', 'value' => (($old_a['default'] && strlen($set_fn)) ? "" : $all_entries->id()), 'name' => $old_a['name'], 'id' => $glob_id), $blank, $context, $render_context);
  if (strlen($set_fn)) {
    $script .= "  function " . $glob_id . "_set() {\n    for (var item_ in " . $name . ") {\n      if (" . $set_fn . ") {\n        jQuery('#" . $glob_id . "').val(item_);\n" . ($a['price_display'] ? "        jQuery('#" . $a['price_display'] . "').html(" . $name . "[item_][0]);\n" : "") . "      }\n    };\n";
    if ($a['price_display']) $script .= "    sel = jQuery('#" . $glob_id . "').val();\n    if (sel) {\n      for (var item_ in " . $name . ") {\n        price_diff = (" . $name . "[item_][0] - " . $name . "[sel][0]).toFixed(2);\n        if (price_diff > 0) { upcharge = ' [+\$' + price_diff + ']'; } else if (price_diff < 0) { upcharge = ' [-\$' + (price_diff*-1).toFixed(2) + ']'; } else { upcharge = ''; } \n" . $price_hints . "      }\n    }\n";
    $script .= "  }\n";
    $script .= "  var " . $name . " = {};\n";
    foreach ($entries as $r) {
      $script .= "  " . $name . "['" . $r->id() . "'] = new Array(";
      $line = '"' . ($price_field ? (($p = (string)$r->get($price_field)) ? $p : $main_price) : "0.00") . '"';
      foreach ($fields as $field) {
        $val = _verb_fetch($field, $r);
        if ($val->type == "OptionsItem") {
          foreach (explode(",", (string)$val) as $option) {
            $e = explode("=", $option);
            $val = $e[1];
            if (strlen($line)) $line .= ",";
            $line .= '"' . str_replace('"', "", $val) . '"';
          }
        } else {
          if (strlen($line)) $line .= ",";
          $line .= '"' . str_replace('"', "", $val) . '"';
        }
      }
      $script .= $line . ");\n";
    }
    $script .= $glob_id . "_set();";
    _verb_on_dom_ready($script);
  }
  return $out;
}

function _verb_render_pagination($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $b = $_VERB['pagination'][$a['collection']];
  $dividers = _verb_find_dividers($tag);
  $out = $class = "";
  if ($a['ajax']) {
    $class = _verb_global_id($b['page_request_variable']);
    _verb_on_dom_ready('jQuery(".' . $class . '").click(function(e) { ' . $a['ajaxbefore'] . ' jQuery("#' . $class . '_loading").show(); jQuery.get(jQuery(this).attr("href")+"&__xhr_paginate=' . $b['page_request_variable'] . '", function(d){ ' . $a['ajaxsuccess'] . ' ' . $a['oncomplete'] . ' jQuery("#' . $a['ajax'] . '").html(d); jQuery("#' . $class . '_loading").hide(); }); e.preventDefault(); return false; });');
  }
  for ($i = 1; $i <= $b['last_page']; $i++) {
    $data = '<a class="' . $class . (($b['page'] == $i) ? ' current' : '') . '" href="' . $_SERVER['PHP_SELF'] . _verb_qs(array($b['page_request_variable'] => $i)) . '">' . $i . '</a>'; 
    $out .= _verb_merge_dividers($data, $dividers, $i - 1, $context, $render_context) . ' ';
  }
  if ($a['loading']) {
    $loader = '<img id="' . $class . '_loading" src="' . $a['loading'] . '" alt="Loading ..." class="loading-indicator" style="display: none; vertical-align: middle;" />';
    if ($a['loadingposition'] == "before") $out = $loader . $out;
    else $out .= $loader;
  }
  return $out;
}

function _verb_render_password_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  unset($a['value']);
  $a['type'] = 'password';
  return  '<input' . _verb_attrs($a, "input") . ' />';
}

function _verb_render_pdf($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $_VERB['prepend'] .= "<!--PDF--" . $a['filename'] . ";" . $a['orientation'] . ";" . $a['paper'] . "-->";
  return _verb_render_tags($tag, $context, $render_context);
}

function _verb_render_php($a, &$tag, $context, &$callback, $render_context) {
  return _verb_php(_verb_render_tags($tag, $context, $render_context), $context);
}

function _verb_render_radio($a, &$tag, $context, &$callback, $render_context) {
  $value = $a['value'];
  unset($a['value']);
  $a2 = _verb_form_prepare($a, $tag, $context, $render_context);
  $a2['type'] = "radio";
  if ($a2['value'] == $value) $a2['checked'] = "checked";
  $a2['value'] = $value;
  return  '<input' . _verb_attrs($a2, "input") . ' />';
}

function _verb_render_redirect($to, $trash_post_data = false) {
  global $_VERB;
  if (!strlen($_VERB['force_redirect'])) {
    if (!_verb_is_xhr() && isset($_SESSION['__v:pre_ssl_host']) && !strstr($to, "://") && ($_SERVER['PHP_SELF'] != $to)) {
      $to = "http://" . $_SESSION['__v:pre_ssl_host'] . (substr($to, 0, 1) == "/" ? "" : "/") . $to;
      unset($_SESSION['__v:pre_ssl_host']);
    } elseif (strstr($to, "://") && !strstr($to, "://" . $_SERVER['HTTP_HOST'])) {
      $router = strstr($to, $_VERB['settings']['subdomain'] . ".verbsite.com") || strstr($to, $_VERB['settings']['subdomain'] . "-secure.verbsite.com");
      if ($_VERB['settings']['domain_ssl'] && strstr($to, $_VERB['settings']['subdomain'] . "." . $_VERB['settings']['domain_ssl'])) $router = true;
      if ($_VERB['settings']['domain_ssl'] && strstr($to, $_VERB['settings']['subdomain'] . "-staging." . $_VERB['settings']['domain_ssl'])) $router = true;
      foreach ($_VERB['settings']['domains'] as $domain => $garbage) {
        if (strstr($to, "://" . $domain) || strstr($to, "://www." . $domain)) {
          $router = true;
        }
      }
      if ($router) $to .= (strstr($to, "?") ? "&" : "?") . "__router=" . session_id();
    }
    $_VERB['force_redirect'] = $to;
    $_VERB['trash_post_data'] = $trash_post_data;
  }
  return "";
}

function _verb_render_repeat($a, &$tag, $context, &$callback, $render_context) {
  if (!is_numeric($a['times']) || ($a['times'] < 1)) {
    return _verb_error("You did not specify a valid numeric value for the <span class='c'>times</span> attribute of the <span class='c'>&lt;v:repeat&gt;</span> tag.", "", $tag['filename']);
  }
  $out = "";
  for ($i = 0; $i < $a['times']; $i++) {
    $out .= _verb_render_tags($tag, $context, $render_context);
  }
  return $out;
}

function _verb_render_require_permalink($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if (!isset($_VERB['context'])) _verb_render_redirect("/");
  return _verb_render_tags($tag, $context, $render_context);
}

function _verb_render_require_ssl($a, &$tag, $context, &$callback, $render_context) {
  _verb_require_ssl();
  return _verb_render_tags($tag, $context, $render_context);
}

function _verb_render_rss($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $_VERB['serve_rss'] = true;
  $items = "";
  if (!$a['limit']) $a['limit'] = 25;
  foreach (_verb_fetch($a['path'], $context, $a) as $ctxt) {
    $inside = _verb_render_tags($tag, $ctxt, $render_context);
    $items .= '  <item>' . "\n";
    if (!strstr($inside, "<title>")) $items .= '   <title>' . _verb_format_for_rss(_verb_fetch_multiple($a['title_field'], $ctxt, $a)) . '</title>' . "\n";
    if ($a['author_field'] && !strstr($inside, "<author>")) $items .= '   <author>' . _verb_format_for_rss(_verb_fetch_multiple($a['author_field'], $ctxt, $a)) . '</author>' . "\n";
    if (!strstr($inside, "<description>")) $items .= '   <description>' . _verb_format_for_rss(_verb_fetch_multiple($a['description_field'], $ctxt, $a)) . '</description>' . "\n";
    $id_or_permalink = $ctxt->permalinkOrId();
    if (is_numeric($id_or_permalink)) { 
      if (!strstr($inside, "<guid>")) $items .= '   <guid>http://' . $_SERVER['HTTP_HOST'] . "/?_guid=" . $id_or_permalink . '</guid>' . "\n";
    } else {
      if (!strstr($inside, "<link>")) $items .= '   <link>http://' . $_SERVER['HTTP_HOST'] . $id_or_permalink. '</link>' . "\n";
      if (!strstr($inside, "<guid>")) $items .= '   <guid>http://' . $_SERVER['HTTP_HOST'] . $id_or_permalink . '</guid>' . "\n";
    }
    $items .= $inside;
    $items .= '  </item>' . "\n";
  }
  $out  = '<?xml version="1.0"?>' . "\n";
  $out .= '<rss version="2.0"' . (strstr($items, "<g:") ? ' xmlns:g="http://base.google.com/ns/1.0"' : "") . '>' . "\n";
  $out .= ' <channel>' . "\n";
  $out .= '  <title>' . $a['title'] . '</title>' . "\n";
  $out .= '  <link>http://' . $_SERVER['HTTP_HOST'] . '/</link>' . "\n";
  $out .= '  <description>' . $a['description'] . '</description>' . "\n";
  $out .= '  <language>en-us</language>' . "\n";
  $out .= '  <generator>Verb</generator>' . "\n";
  $out .= $items;
  $out .= ' </channel>' . "\n";
  $out .= '</rss>';
  return $out;
}

function _verb_render_section($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ($a['path'] == "/") {
    $new_context = null;
  } else {
    $new_context = _verb_fetch($a['path'], $context);
  }
  if ($new_context == false || !is_object($new_context)) return _verb_render_tags($tag, $context, $render_context);
  return _verb_render_tags($tag, $new_context, $render_context->set("total_items", 1));
}

function _verb_render_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  if (!is_array($a['options']) && !strstr($a['options'], "<option")) {
    if (strlen($a['options'])) {
      $ex = explode(",", $a['options']);
      $a['options'] = array();
      foreach ($ex as $e1) {
        if (strstr($e1, "=")) $ex2 = explode("=", $e1);
        else $ex2 = array($e1, $e1);
        $a['options'][$ex2[0]] = $ex2[1];
      }
    } elseif (is_array($_VERB['page_select'][$a['id']])) {
      $num_pages = $_VERB['page_select'][$a['id']][0];
      $a['options'] = array();
      if ($_VERB['page_select'][$a['id']][3]) for ($i = $num_pages; $i >= 1; $i--) { $a['options'][$i] = $i; }
      else for ($i = 1; $i <= $num_pages; $i++) { $a['options'][$i] = $i; }
      $a['onchange'] = _verb_append_js($a['onchange'], "window.location.href='" . $_VERB['page_select'][$a['id']][2] . "'+this.value");
      $a['value'] = $_VERB['page_select'][$a['id']][1];
    }
  }
  if (!strlen($a['value'])) $a['value'] = $a['default'];
  $out = _verb_render_tags($tag, $context, $render_context);
  if ((is_string($a['options']) && strstr($a['options'], "<option")) || count($a['options']) || strlen($out)) {
    if (is_string($a['options'])) {
      $out = $a['options'] . $out;
    } else {
      foreach ($a['options'] as $k => $v) {
        if (is_array($v)) {
          $k = $v[0];
          $v = $v[1];
        }
        $o .= '<option value="' . $k . '"' . ($a['value'] == $k ? ' selected="selected"' : '') . '>' . $v . '</option>';
      }
      $out = $o . $out;
    }
    unset($a['options']);
    return _verb_render_tag("select", $a, $out, $context, $render_context);
  }
  return "";
}


function _verb_render_session_dump($a, &$tag, $context, &$callback, $render_context) {
  return "<__VERB_SESSION_DUMP=" . $a['key'] . ">";
}

function _verb_render_set($a, &$tag, $context, &$callback, $render_context) {
  if (!strlen($a['value'])) $a['value'] = 1;
  $_REQUEST[$a['name']] = $a['value'];
  return "";
}

function _verb_render_set_default($a, &$tag, $context, &$callback, $render_context) {
  if (!strlen($_REQUEST[$a['name']])) {
    if (!strlen($a['value'])) $a['value'] = 1;
    $_REQUEST[$a['name']] = $a['value'];
  }
  return "";
}

function _verb_render_site_seal($a, &$tag, $context, &$callback, $render_context) {
  return '<script type="text/javascript" src="https://seal.godaddy.com/getSeal?sealID=zdJUgdXGseI5YSkqNAmusZPAraVRI08a8crQbnB0tkyeqKnICJT8dv7NOs"></script>';
}

function _verb_render_state_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  $a['class'] = $a['id'];
  $a['onchange'] = _verb_append_js($a['onchange'], "jQuery('#" . $a['id'] . "').val(jQuery(this).val());");
  $a2 = $a;
  $out = "";
  $a['style'] = "display: none";
  foreach ($_VERB['states'] as $country => $states) {
    $a['options'] = $states;
    $a['id'] = $a['name'] = $a2['id'] . "_" . $country;
    $out .= _verb_render_select($a, $tag, $context, $callback, $render_context);
  }
  $a3 = array("type" => "hidden", "value" => $a2['value'], "name" => $a2['name'], "id" => $a2['id'], "onchange" => _verb_append_js("", "jQuery('." . $a['class'] . "').val(jQuery(this).val());"));
  $out .= "<input" . _verb_attrs($a3, "input") . " />";
  $a2['name'] .= "_txt";
  $a2['id'] .= "_txt";
  return $out . _verb_render_text_field($a2, $tag, $context, $callback, $render_context);
}

function _verb_render_tag($tagname, $a, &$tag, $context = null, $render_context = null) {
  $inside = (is_array($tag) ? _verb_render_tags($tag, $context, $render_context, true) : $tag);
  if (!strlen($inside) && (!in_array($tagname, array("form", "script", "textarea")))) return '<' . $tagname . _verb_attrs($a, $tagname) . ' />';
  return '<' . $tagname . _verb_attrs($a, $tagname) . '>' . $inside . '</' . $tagname . '>';
}

function _verb_render_tags(&$parent_tag, $context = null, $render_context = null, $true = true) {
  global $_VERB;
  $out = "";
  if (!$true) {
    return _verb_get_else($parent_tag, $context, $render_context);
  }  
  if (is_object($render_context)) $render_context = $render_context->unsett("else");
  if (count($parent_tag['tags'])) {
    for ($i = 0; $i < count($parent_tag['tags']); $i++) {
      $out .= _verb_render($parent_tag['tags'][$i], $context, $render_context);      
    }
  }
  if (is_object($render_context) && $render_context->get("else")) {
    $out .= $render_context->get("else_message");
  }
  return $out;
}

function _verb_render_template($a, &$tag, $context, &$callback, $render_context) {
  list($filename, $verbml) = _verb_src($a['filename']);
  if (!strlen($verbml)) return _verb_render_tags($tag, $context, $render_context);
  foreach ($a as $k => $v) {
    if ($k != "filename") $_REQUEST[$k] = $v;
  }
  list($parse_tree, $render_context) = _verb_parse_verbml($verbml, $filename, $tag, $render_context);
  return _verb_render_tags($parse_tree, $context, $render_context);
}

function _verb_render_text($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ($a['param']) {
    $text = $_REQUEST[$a['param']];
  } elseif ($a['text']) {
    $text = $a['text'];
  } elseif ($a['placeholder']) {
    $text = _verb_placeholder($a['placeholder']);
  } else {
    if (strlen($a['path'])) {
      $text = _verb_fetch($a['path'], $context);
    }
    if ($text->type == "DateItem" || strlen($a['strftime'])) { 
      if (!strlen($a['path'])) $text = time();
      if (!strlen($a['strftime'])) $a['strftime'] = "%B %d, %Y";
      $time = (string)$text;
      if (!is_numeric($time)) $time = strtotime($time);
      if (strstr($a['strftime'], "%N")) {
        $a['strftime'] = str_replace("%N", _verb_natural_time($time), $a['strftime']);
      }
      $text = strftime($a['strftime'], $time);
    }
  }
  $html = (is_object($text) && $text->type == "HtmlAreaItem");
  if ($html) {
    $render = _verb_htmlarea($text, $a);
  } else {
    if ($a['maxlength'] && strlen($text) > $a['maxlength']) $text = substr($text, 0, $a['maxlength']) . "...";
    $text = $a['before'] . $text . $a['after'];
    if ($a['transform']) {
      if (function_exists($a['transform'])) {
        $text = call_user_func($a['transform'], $text);
      } elseif (function_exists("verb_" . $a['transform'])) {
        $text = call_user_func("verb_" . $a['transform'], $text);
      } else {
        $text = "[FUNCTION NOT FOUND: " . $a['transform'] . "]";
      }
    }
    if (strlen($a['number_format'])) $text = number_format($text, $a['number_format']);
    if ($a['font'] && strlen($text)) {
      $render = verb_text($text, $a['font'], $a['font-size'], $a['color'], $a['kerning'], $a['padding'], $a['max-width']);
    } else {
      $render = verb_style($text);
    }
  }
  if ($a['escape']) $render = addslashes($render);
  return $render;
}

function _verb_render_text_area($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  return _verb_render_tag("textarea", $a, $a['value'], $context, $render_context);
}

function _verb_render_text_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _verb_form_prepare($a, $tag, $context, $render_context);
  $a['type'] = "text";
  return  '<input' . _verb_attrs($a, "input") . ' />';
}

function _verb_render_unsubscribe($a, &$tag, $context, &$callback, $render_context) {
  return '<v:unsubscribe />';
}

function _verb_render_update($a, &$tag, $context, &$callback, $render_context) {
  if ($a['path']) $context = _verb_fetch($a['path'], $context);
  if ($context) {
    if (!is_object($context) && is_numeric($context)) $context = _verb_fetch($context);
    $callback['row_id'] = $context->id();
    return _verb_render_callback("update", $a, $tag, $context, $callback, $render_context);
  } else {
    return "";
  }
}

function _verb_render_video($a, &$tag, $context, &$callback, $render_context) { 
  global $_VERB;
  $video = _verb_fetch($a['path'], $context);
  $src = verb_video($video, $a['size']);
  $id = $a['id'];
  if (!$id) $id = _verb_global_id();
  if ($src == "tryagain.flv") $src = $_VERB['config']['backlot_url'] . "/videos/" . $src;
  else $src = _verb_absolute_data_url() . $src;
  $player_width = $a['width'];
  $player_height = $a['height'];
  if (!is_numeric($player_width)) $player_width = 400;
  if (!is_numeric($player_height)) $player_height = 300;
  $extra_params = "";
  foreach ($a as $k => $v) {
    if (in_array($k, array("controlbar","autostart"))) {
      $extra_params .= ",\n" . $k . ": '" . $v . "'";
    }
  }
  _verb_needs_javascript("jwplayer");
  return '<div id="' . $id . '_container">You need to <a href="http://www.macromedia.com/go/getflashplayer">get the Flash Player</a> to see this video.</div>
  <script type="text/javascript">
    jwplayer("' . $id . '_container").setup({
      flashplayer: "' . $_VERB['config']['asset_url'] . 'player.swf",
      file: "' . $src . '",
      height: ' . $player_height . ',
      width: ' . $player_width . $extra_params . '
    });
  </script>';
}

function _verb_render_yield($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if ($y = $_VERB['yield']) {
    unset($_VERB['yield']);
    return $y;
  }
  if ($context && (!$render_context->get("nestedRendering")) && ($body = _verb_fetch_without_errors("yield", $context))) {
    return _verb_htmlarea($body, $a);
  }
  return _verb_render_tags($tag, $context, $render_context);
}

function _verb_render_zip($a, &$tag, $context, &$callback, $render_context) {
  global $_VERB;
  if (count($tag['tags'])) {
    foreach ($tag['tags'] as $itag) {
      if (!$itag['type']) $out .= $itag['innerhtml'];
    }
  }
  unset($_VERB['zip_files']);
  _verb_render_tags($tag, $context, $render_context);
  $callback['files'] = $_VERB['zip_files'];
  $callback['filename'] = $a['filename'];
  if ($a['direct']) $_REQUEST['__v:zip'] = _verb_tag_unique_id($tag, $context);
  $a['href'] = $_SERVER['PHP_SELF'] . _verb_qs("__v:zip=" . _verb_tag_unique_id($tag, $context));
  return '<a' . _verb_attrs($a, "a") . '>' . $out .'</a>';
}

?>