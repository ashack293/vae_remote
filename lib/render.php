<?php

function _vae_render(&$tag, $context, $render_context) {
  global $_VAE;
  if (isset($tag['type'])) {
    $a = $tag['attrs'];
    if (is_array($tag['attrs']) && count($tag['attrs'])) {
      foreach ($tag['attrs'] as $k => $v) {
        if (strpos($v, "<v") !== false) {
          $a[$k] = (string)_vae_render_oneline($v, $context, $k);
        }
      }
    }
    if (!is_array($a)) $a = array();
    $tag['callback'] = array();
    unset($tag['unique_id']);
    if ($_VAE['tags'][$tag['type']]['handler']) {
      $func_name = $_VAE['tags'][$tag['type']]['handler'];
      if (isset($_VAE['tags'][$tag['type']]['filename'])) require_once(dirname(__FILE__)."/".$_VAE['tags'][$tag['type']]['filename']);
      $return_value = $func_name($a, $tag, $context, $tag['callback'], $render_context);
    } else {
      $func_type = preg_replace("/_+/", "_", preg_replace("/[^a-z0-9_]/", "_", $tag['type']));
      if (function_exists($func_type)) {
        $return_value = call_user_func($func_type);
      } elseif (function_exists("vae_" . $func_type)) {
        $return_value = call_user_func("vae_" . $func_type);
      }
    }
    if ((!isset($return_value) || ($return_value === true)) && $_VAE['tags'][$tag['type']]['callback']) {
      $return_value = _vae_render_callback($tag['type'], $a, $tag, $context, $tag['callback'], $render_context);
    }
    if (is_object($render_context) && $render_context->get("else2")) {
      $render_context->unset_in_place("else2");
    } elseif (is_object($render_context) && $render_context->get("else")) {
      $return_value = $return_value . $render_context->get("else_message");
      $render_context->unset_in_place("else");
      $render_context->unset_in_place("else_message");
    }
    if (isset($_REQUEST['__v:' . $tag['type']]) && ($_REQUEST['__v:' . $tag['type']] == _vae_tag_unique_id($tag, $context))) {
      $t = $tag;
      $t['attrs'] = $a;
      $_VAE['callback_stack'][$tag['type']] = $t;
    }
    if (isset($return_value)) return $return_value;
  }
  return ((strpos($tag['innerhtml'], "<v") === false) ? $tag['innerhtml'] : _vae_render_oneline($tag['innerhtml'], $context, false));;
}

function _vae_render_a($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($a['ajax']) _vae_needs_jquery('');
  $old_context = $context;
  if (isset($a['path'])) {
    if ($a['path'] != "/") {
      $new_context = _vae_fetch($a['path'], $context);
      if (strlen((string)$new_context)) {
        if ($new_context->type == "FileItem" || $new_context->type == "VideoItem" || $new_context->type == "ImageItem") {
          $preserve_filename = ($_VAE['settings']['preserve_filenames'] ? true : false);
          $href = vae_data_url() . vae_file($new_context, $preserve_filename);
        } else {
          $href = $new_context;
          if (substr($href, 0, 1) == "/" && !$_REQUEST['__vae_local'] && !$_VAE['local_full_stack']) $href = _vae_proto() . $_SERVER['HTTP_HOST'] . $href;
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
        $href = $url . (($e[0] == ("/" . $context->structure()->permalink)) ? "" : $e[0]) . _vae_qs($e[1], false);
      } else {
        $href = $e[0] . (($context != null && ($idfc = $context->id())) ? (substr($e[0], -1, 1) == "/" ? "" : "/") . $idfc : "") . _vae_qs($e[1], false);
      }
    }
  }
  if (!strlen($href) && $context) {
    if (strlen($url = $context->permalink())) $href = (_vae_generate_relative_links() ? "" : (_vae_proto() . $_SERVER['HTTP_HOST'])) . $url;
  }
  if ($a['autofollow'] && $render_context->get("total_items") == 1) vae_redirect($href);
  if ($_VAE['hrefs'][$a['id']]) $href = $_VAE['hrefs'][$a['id']];
  if ($href == "...") $href = "";
  if ($a['ajax']) {
    if ($a['loading']) {
      $imgid = _vae_global_id();
      $a['onclick'] = _vae_append_js($a['onclick'], "jQuery('#" . $imgid . "').show();");
      $loader = '<img id="' . $imgid . '" src="' . $a['loading'] . '" alt="Loading ..." class="loading-indicator" style="display: none; vertical-align: middle;" />';
      if ($a['loadingposition'] == "before") $before = $loader;
      else $after = $loader;
      $a['oncomplete'] = _vae_append_js($a['oncomplete'], "jQuery('#" . $imgid . "').hide();");
    }
    if ($a['animate']) $a['oncomplete'] .= _vae_append_js($a['oncomplete'], "jQuery('#" . $a['ajax'] . "')." . $a['animate'] . "('slow');");
    $a['onclick'] = _vae_append_js($a['onclick'], "jQuery.get('" . $href . "', function(d){  jQuery('#" . $a['ajax'] . "').html(d); " . $a['oncomplete'] . " }); " . ($a['jump'] ? "" : "return false;"));
  }
  if ($_REQUEST['__host']) $a['href'] .= (strstr($a['href'], "?") ? "&" : "?") . "__host=" . $_REQUEST['__host'];
  $a['href'] = ($a['jump'] ? "#" . $a['jump'] : $href . ($anchor ? "#" . $anchor : ""));
  return ($href ? ($before . _vae_render_tag("a", $a, $tag, $old_context, $render_context) . $after) : "");
}

function _vae_render_a_if($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $true = false;
  if ($a['path']) {
    $true = _vae_fetch($a['path'], $context);
    if (is_object($true) && $true->type != "TextItem") {
      unset($a['path']);
    }
  } elseif ($a['total_items']) {
    $true = ($render_context->get("total_items") == $a['total_items']);
  }
  if ($_VAE['hrefs'][$a['id']]) $true = true;
  if (strlen($a['href'])) unset($a['path']);
  return ($true ? _vae_render_a($a, $tag, $context, $callback, $render_context) : _vae_render_tags($tag, $context, $render_context));
}

function _vae_render_asset($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
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
    $proto = _vae_proto();
    return _vae_asset_html("js", $proto . 'ajax.googleapis.com/ajax/libs/'. $framework);
  }
  if ($a['debug'] || $_VAE['local']) {
    if (substr($a['src'], 0, 1) != "/") $a['src'] = "/" . $a['src'];
    return _vae_asset_html($type, $a['src']);
  } else {
    if (!isset($_VAE['assets'])) $_VAE['assets'] = array();
    $group = $type;
    if ($a['group']) $group = $a['group'];
    if (isset($_VAE['asset_types'][$group]) && ($_VAE['asset_types'][$group] != $type)) {
      return _vae_error("When using the <span class='c'>group</span> attribute in the <span class='c'>&lt;v:asset&gt;</span> tag, you must ensure that all assets in the same group are of the same media type.");
    }
    $_VAE['assets'][$group][] = $a['src'];
    $_VAE['asset_types'][$group] = $type;
    $_VAE['asset_inject_points'][$group]++;
    return "<_VAE_ASSET_" . $group . $_VAE['asset_inject_points'][$group] . ">";
  }
}

function _vae_render_callback($name, $a, &$tag, $context, &$callback, $render_context, $qs = "") {
  global $_VAE;
  $a['action'] = $_SERVER['PHP_SELF'] . _vae_qs("__v:" . $name . "=" . _vae_tag_unique_id($tag, $context) . (strlen($qs) ? "&" . $qs : ""));
  $out = _vae_render_form($a, $tag, $context, $callback, $render_context);
  return $out;
}

function _vae_render_callback_link($name, $a, &$tag, $context, &$callback, $render_context) {
  $a['href'] = $_SERVER['PHP_SELF'] . _vae_qs("__v:" . $name . "=" . _vae_tag_unique_id($tag, $context));
  return _vae_render_tag("a", $a, $tag, $context, $render_context);
}

function _vae_render_captcha($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $tag['callback']['_form_prepared'] = true;
  require_once(dirname(__FILE__) . "/../vendor_old/recaptchalib.php");
  return recaptcha_get_html($_VAE['recaptcha']['public'], null, _vae_ssl());
}

function _vae_render_cdn($a, &$tag, $context, &$callback, $render_context) {
  if (strlen($a['href'])) $a['src'] = $a['href'];
  if (strlen($a['src'])) {
    $url = $a['src'];
    if (substr($url, 0, 1) != "/")  $url = "/" . dirname($_VAE['filename']) . $url;
    $url = _vae_cdn_timestamp_url($url);
    return vae_cdn_url() . substr($url, 1);
  }
  return vae_cdn_url();
}

function _vae_render_checkbox($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  if ($a['value'] && !strlen($a['checked'])) $a['checked'] = "checked";
  if (!strlen($a['value'])) $a['value'] = "1";
  if ($a['checked'] == "false" || $a['checked'] == "0") unset($a['checked']);
  $a['type'] = "checkbox";
  return  '<input' . _vae_attrs($a, "input") . ' />';
}

function _vae_render_collection($a, &$tag, $context, &$callback, $render_context, $contexts = "__") {
  global $_VAE;
  $options = array();
  if ($a['filter_input']) {
    if (strlen($_REQUEST[$a['filter_input']])) {
      $options['filter'] = $_REQUEST[$a['filter_input']];
    } else {
      return _vae_get_else($tag, $context, $render_context, "You did not enter a search query.");
    }
  }
  foreach(array('order','skip','paginate','limit','groups','filter','unique') as $opt) {
    if ($a[$opt]) $options[$opt] = $a[$opt];
  }
  if ($a['paginate']) {
    if (!isset($_VAE['main_pagination']) && !isset($_VAE['settings']['query_string_pagination'])) {
      $_VAE['main_pagination'] = $page_request_variable = "page";
    } elseif (isset($a['id'])) {
      if (isset($_VAE['pagination'][$a['id']])) {
        return _vae_error("You cannot define a <span class='c'>id=&quot;" . _vae_h($a['id']) . "&quot;</span> on a <span class='c'>&lt;v:collection&gt; tag that will be rendered more than once on the page (i.e. inside another collection).", "", $tag['filename']);
      }
      $page_request_variable = $a['id'] . "_page";
    } else {
      $page_request_variable = '_page_' . _vae_tag_unique_id($tag, $context);
    }
  }
  if ($a['paginate'] && is_numeric($_REQUEST[$page_request_variable]) || $_REQUEST[$page_request_variable] == "all") {
    $page = $_REQUEST[$page_request_variable];
  } elseif ($a['default_page']) {
    $page = $a['default_page'];
    $set_page_to_last = true;
  } elseif ($a['groups']) {
    $page = ($_VAE['render']['collection']['groups'][($context ? $context->id() : "").$a['path']]++) + 1;
  } else {
    $page = 1;
  }
  $options['page'] = $page;
  if ($contexts == "__") {
    $contexts = _vae_fetch($a['path'], $context, $options);
  }
  $dividers = _vae_find_dividers($tag);
  if ($a['store_in_session']) {
    $mycontexts = $contexts;
    if ($a['skip'] || $a['paginate']) {
      unset($options['skip']);
      unset($options['paginate']);
      $mycontexts = _vae_fetch($a['path'], $context, $options);
    }
    $cookie = "";
    if (is_object($mycontexts) && $mycontexts->count) {
      foreach ($mycontexts as $context) {
        $cookie .= "," . $context->permalinkOrId();
      }
    }
    _vae_session_cookie($a['store_in_session'], substr($cookie, 1));
  }
  if (is_object($contexts) && $contexts->count) {
    $tr_open = false;
    $rendered = 0;
    if ($a['paginate']) {
      $last_page = ceil($contexts->totalMatches() / $a['paginate']);
      if (strlen($a['max_pages']) && ($a['max_pages'] < $last_page)) $last_page = $a['max_pages'];
      if ($set_page_to_last) $page = $last_page;
      if ($a['previous']) $_VAE['hrefs'][$a['previous']] = (((($page > 1) || $a['wrap']) && $_REQUEST[$page_request_variable] != "all") ? $_SERVER['PHP_SELF'] . _vae_qs(array($page_request_variable => ($page == 1 ? $last_page : ($page - 1)))) : "...");
      if ($a['next']) $_VAE['hrefs'][$a['next']] = (((($page < $last_page) || $a['wrap']) && $_REQUEST[$page_request_variable] != "all") ? $_SERVER['PHP_SELF'] . _vae_qs(array($page_request_variable => ($page == $last_page ? 1 : ($page + 1)))) : "...");
      if ($a['all']) $_VAE['hrefs'][$a['all']] =  $_SERVER['PHP_SELF'] . _vae_qs(array($page_request_variable => "all"));
    }
    if ($a['page_select']) $_VAE['page_select'][$a['page_select']] = array($last_page, $page, $_SERVER['PHP_SELF'] . _vae_qs($page_request_variable . "=", true, $page_request_variable . "="), $a['default_page'] == "last()");
    if ($a['id']) $_VAE['pagination'][$a['id']] = array('page' => $page, 'last_page' => $last_page, 'page_request_variable' => $page_request_variable);
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
      if ($a['counter']) $_REQUEST[$a['counter']] = $rendered + 1;
      $this_tag = _vae_render_tags($tag, $context, $render_context);
      $this_tag = _vae_merge_dividers($this_tag, $dividers, $rendered, $context, $render_context);
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
    return _vae_get_else($tag, $context, $render_context);
  }
  if (_vae_is_xhr() && strlen($_REQUEST['__xhr_paginate']) && ($_REQUEST['__xhr_paginate'] == $page_request_variable)) {
    _vae_final($out);
  }
  return $out;
}

function _vae_render_country_select($a, &$tag, $context, &$callback, $render_context) {
  _vae_needs_jquery();
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  if (!strlen($a['default'])) $a['default'] = "US";
  if (!$a['options']) $a['options'] = _vae_list_countries();
  $state_select_id = str_replace("country", "state", $a['id']);
  $a['onchange'] = _vae_append_js($a['onchange'], "
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
  _vae_on_dom_ready("jQuery('#" . $a['id'] . "').trigger('change');");
  return _vae_render_select($a, $tag, $context, $callback, $render_context);
}

function _vae_render_create($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $createInfo = _vae_fetch_for_creating($a['path'], $context);
  $callback['structure_id'] = $createInfo->structure_id;
  $callback['row_id'] = $createInfo->row_id;
  $callback['unpublished'] = ($a['unpublished']);
  return _vae_render_callback("create", $a, $tag, $context, $callback, $render_context->set("form_create_mode"), $a['path']);
}

function _vae_render_date_select($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $date = strtotime(_vae_request_param($a['name'] . "_month") . "/" . _vae_request_param($a['name'] . "_day") . _vae_request_param($a['name'] . "_year"));
  if (($date < 1) && strlen($a['value'])) $date = strtotime($a['value']);
  $orig = $a;
  $a['options'] = array('' => '', 1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
  $a['value'] = ($date ? strftime("%m", $date) : "");
  $a['name'] = $orig['name'] . "_month";
  $a['id'] = $orig['id'] . "_month";
  $out = _vae_render_select($a, $tag, $context, $callback, $render_context);
  $a['options'] = array('' => '');
  for ($i = 1; $i <= 31; $i++) { $a['options'][$i] = $i; }
  $a['value'] = ($date ? strftime("%d", $date) : "");
  $a['name'] = $orig['name'] . "_day";
  $a['id'] = $orig['id'] . "_day";
  $out .= " " . _vae_render_select($a, $tag, $context, $callback, $render_context);
  $a['options'] = array('' => '');
  for ($i = strftime("%Y"); $i > 1900; $i--) { $a['options'][$i] = $i; }
  $a['value'] = ($date ? strftime("%Y", $date) : "");
  $a['name'] = $orig['name'] . "_year";
  $a['id'] = $orig['id'] . "_year";
  $out .= " " . _vae_render_select($a, $tag, $context, $callback, $render_context);
  return $out;
}

function _vae_render_date_selection($a, &$tag, $context, &$callback, $render_context) {
  $out = $last = "";
  $rendered = $count = 0;
  $options = array("order" => "DESC(" . $a['date_field'] . ")");
  foreach(array('skip','paginate','limit','groups','filter','unique') as $opt) {
    if ($a[$opt]) $options[$opt] = $a[$opt];
  }
  $contexts = _vae_fetch($a['path'], $context, $options);
  if (!strlen($a['href'])) $a['href'] = $_SERVER['PHP_SELF'];
  if (is_object($contexts)) {
    $param = ($a['param'] ? $a['param'] : $a['date_field']);
    $format = ($a['strftime'] ? $a['strftime'] : "%B %Y");
    $dividers = _vae_find_dividers($tag);
    foreach ($contexts as $ctxt) {
      if ($date = _vae_fetch($a['date_field'], $ctxt)) {
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
          $data = '<a ' . (($_REQUEST[$param] == $link) ? 'class="current" ' : '') . 'href="' . $a['href'] . _vae_qs(array($param => $link)) . '">' . $formatted . '</a> ';
          $out .= _vae_merge_dividers($data, $dividers, $rendered, $context, $render_context);
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

function _vae_render_debug($a, &$tag, $context, &$callback, $render_context) {
  $out = "<div style='background: #333; color: #fff; padding: 15px; font: 14px \"Lucida Grande\", sans-serif;'><p style='font-weight: bold; font-size: 1.2em;'>Vae Debugging Information</p><p>Current Context:</p><ul>";
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

function _vae_render_disqus($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($_VAE['disqus_rendered']) {
    return _vae_error("You may only include one <span class='code'>&lt;v:disqus /&gt;</span> tag per page.");
  }
  $xid = ($context ? $context->id : null);
  $js = '<div id="disqus_thread"></div>
    <script type="text/javascript">'. (($context && $context->id) ? '
      var disqus_identifier = ' . $xid . ';' : '') . (($_VAE['local_full_stack'] || $_REQUEST['__vae_local'] || $_REQUEST['__verb_local']) ? '
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

function _vae_render_divider($a, &$tag, $context, &$callback, $render_context) {
  return "";
}

function _vae_render_else($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ((!isset($_VAE['settings']['child_v_else'])) && is_object($render_context) && $render_context->get("else")) {
    $render_context->unset_in_place("else");
    return _vae_render_tags($tag, $context, $render_context);
  } else {
    return "";
  }
}

function _vae_render_elseif($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ((!isset($_VAE['settings']['child_v_else'])) && is_object($render_context) && $render_context->get("else")){
    $render_context->unset_in_place("else");
    return _vae_render_if($a, $tag, $context, $callback, $render_context);
  } else {
    return "";
  }
}

function _vae_render_facebook_comments($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if (!is_numeric($a['paginate'])) $a['paginate'] = 10;
  if (!is_numeric($a['width'])) $a['width'] = 450;
  if ($a['path'] != "/") {
    if ($a['path']) $context = _vae_fetch($a['path'], $context);
    if (is_object($context)) $xid = $context->id;
    elseif (strlen($context)) $xid = $context;
  }
  if (!$xid) $xid = $_SERVER['PHP_SELF'];
  if ($_VAE['facebook_js_rendered']) {
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
  $a['xid'] = $xid;
  $a['numposts'] = $a['paginate'];
  $out = "";
  unset($a['path']);
  unset($a['paginate']);
  return $js . _vae_render_tag("fb:comments", $a, $out, $context, $render_context);
}

function _vae_render_facebook_like($a, &$tag, $context, &$callback, $render_context) {
  if (!is_numeric($a['width'])) $a['width'] = 450;
  if ($a['colorscheme'] != "dark") $a['colorscheme'] = "light";
  if ($a['layout'] != "button_count") $a['layout'] = "standard";
  if ($a['action'] != "recommend") $a['action'] = "like";
  if ($a['path'] != "/") {
    if ($a['path']) $context = _vae_fetch($a['path'], $context);
    if (is_object($context)) $url = "http://" . $_SERVER['HTTP_HOST'] . "/" . $context->permalink(false);
  }
  if ($a['url']) $url = $a['url'];
  if (!$url) $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  return '<iframe src="' . _vae_proto() . 'www.facebook.com/plugins/like.php?href=' . urlencode($url) . '&amp;layout=' . $a['layout'] . '&amp;show_faces=true&amp;width=' . $a['width'] . '&amp;action=' . $a['action'] . '&amp;colorscheme=' . $a['colorscheme'] . '" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:' . $a['width'] . 'px; height: 60px"></iframe>';
}

function _vae_render_file($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $file = (string)_vae_fetch($a['path'], $context);
  $callback['src'] = (preg_match("/^([0-9]*)(-|$)/", $file) ? vae_file($file) : $file);
  $callback['filename'] = $a['filename'];
  if (!isset($_VAE['zip_files'])) $_VAE['zip_files'] = array();
  $_VAE['zip_files'][] = $callback;
  return _vae_render_callback_link("file", $a, $tag, $context, $callback, $render_context);
}

function _vae_render_file_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $a['type'] = "file";
  return  '<input' . _vae_attrs($a, "input") . ' />';
}

function _vae_render_fileurl($a, &$tag, $context, &$callback, $render_context) {
  $file = (string)_vae_fetch($a['path'], $context);
  $e = explode("-", $file);
  return (is_numeric($e[0]) ? _vae_absolute_data_url() . vae_file($file) : $file);
}

function _vae_render_flash($a, &$tag, $context, &$callback = null, $render_context) {
  return _vae_render_flash_inside($a['flash'], $render_context, true);
}

function _vae_render_flash_inside($which = "", $render_context, $is_flash_tag = false) {
  global $_VAE;
  $shown = array();
  if ($_SESSION['__v:flash']['messages'] && !$_VAE['flash_rendered'][$which]) {
    foreach ($_SESSION['__v:flash']['messages'] as $f) {
      if (!strlen($which) || ($f['which'] == $which)) {
        if ((strlen($which) || $is_flash_tag || !$render_context->get("has_flash_tag" . $f['which'])) && !$_VAE['flash_rendered'][$f['which']]) {
          if (!$shown[$f['msg']]) {
            $out .= '<div class="flash ' . $f['type'] . '">' . $f['msg'] . "</div>";
            $shown[$f['msg']] = true;
          }
        }
      }
    }
  }
  if (strlen($out)) $_VAE['flash_rendered'][$which] = true;
  return $out;
}

function _vae_render_form($a, &$tag, $context, &$callback = null, $render_context) {
  if ($render_context->get("form")) _vae_error("You cannot nest <span class='c'>&lt;form&gt;</span> tags.  Watch out for any VaeML tags you have that generate <span class='c'>&lt;form&gt;</span> tags.", "", $tag['filename']);
  if (!strlen($a['id']) && ($a['ajax'] || $a['validateinline'] || $a['loading'])) $a['id'] = _vae_global_id();
  if (!strlen($a['method'])) $a['method'] = 'post';
  $out = _vae_render_flash_inside($a['flash'], $render_context);
  $out .= _vae_render_tag("form", $a, $tag, $context, $render_context->set("form_context", $context)->set_in_place("form", true));
  if ($a['ajax']) {
    if ($a['loading']) {
      $loader = '<img id="' . $a['id'] . '_loading" src="' . $a['loading'] . '" alt="Loading ..." class="loading-indicator" style="display: none; vertical-align: middle;" />';
      if ($a['loadingposition'] == "before") $out = $loader . $out;
      else $out .= $loader;
    }
    $script = "jQuery('#" . $a['id'] . "').ajaxForm({ success: function(data,status) { jQuery('#" . $a['id'] . "_loading').hide(); if (match = /^__err=(.*)/.exec(data)) { var error = match[1]; " . $a['ajaxfailure'] . " alert(match[1].replace(/\\\\n/g, \"\\n\")); } else { jQuery('#" . $a['ajax'] . "').html(data); if (!window.vRedirected) { " . $a['ajaxsuccess'] . " } " . ($a['animate'] ? "jQuery('#" . $a['ajax'] . "')." . $a['animate'] . "('slow');" : "") . "} }";
    if ($a['validateinline']) {
      _vae_needs_jquery('form','validate');
      _vae_on_dom_ready($script . ", beforeSubmit: function() {" . $a['ajaxbefore'] . " var t = jQuery('#" . $a['id'] . "').valid(); if (t) { jQuery('#" . $a['id'] . "_loading').show(); } else { " . $a['ajaxfailure'] . " } return t; } }); jQuery('#" . $a['id'] . "').validate();");
    } else {
      _vae_needs_jquery('form');
      _vae_on_dom_ready($script . ", beforeSubmit: function() {" . $a['ajaxbefore'] . " jQuery('#" . $a['id'] . "_loading').show(); } });");
    }
  } elseif ($a['validateinline']) {
    _vae_needs_jquery('validate');
    _vae_on_dom_ready("jQuery('#" . $a['id'] . "').validate();");
  }
  return $out;
}

function _vae_render_formmail($a, &$tag, $context, &$callback, $render_context) {
  return _vae_render_callback("formmail", $a, $tag, $context, $callback, $render_context);
}

function _vae_render_fragment($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if (!$a['cache']) return "";
  if (!_vae_ssl() && !$_REQUEST['__vae_local'] && !$_REQUEST['__verb_local'] && !$_VAE['local_full_stack']) {
    $key = $_VAE['global_cache_key'] . $a['cache'];
    $cached = _vae_short_term_cache_get($key);
    if (is_array($cached) && $cached[0] == "chks") {
      return $cached[1];
    }
  }
  $out = _vae_render_tags($tag, $context, $render_context);
  if ($key) {
    _vae_short_term_cache_set($key, array("chks", $out), 0, 3600);
  }
  return $out;
}

function _vae_render_gravatar($a, &$tag, $context, &$callback, $render_context) {
  if (!strlen($a['size'])) $a['size'] = "80";
  if (!strlen($a['default'])) $a['default'] = "wavatar";
  if (!strlen($a['rating'])) $a['rating'] = "g";
  $a['src'] =  "http://www.gravatar.com/avatar/" . md5(strtolower($a['email'])) . "?default=" . urlencode($a['default']) . "&rating=" . $a['rating'] . "&size=" . $a['size'];
  return '<img' . _vae_attrs($a, "img") . ' />';
}

function _vae_render_hidden_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $a['type'] = "hidden";
  return  '<input' . _vae_attrs($a, "input") . ' />';
}

function _vae_render_if($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $true = false;
  if ($a['path']) {
    $true = _vae_fetch_without_errors($a['path'], $context);
  } elseif ($a['total_items']) {
    $true = ($render_context->get("total_items") == $a['total_items']);
  } elseif ($a['param']) {
    $true = ($_REQUEST[$a['param']]);
  } elseif ($a['id']) {
    $q1 = (string)_vae_fetch_without_errors($a['id'], $context);
    if (!is_numeric($q1)) {
      $new_context = _vae_fetch_without_errors($a['id']);
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
  if (strlen($a['is'])) $true = ($true == $a['is']);
  if (is_object($true) && !$true->collection() && (string)$true == "") {
    $true = false;
  }
  return _vae_render_tags($tag, $context, $render_context, $true);
}

function _vae_render_if_backstage($a, &$tag, $context, &$callback, $render_context) {
  _vae_session_deps_add('__v:user_id');
  $logged_in = isset($_SESSION['__v:user_id']) || $_REQUEST['__vae_local'] || $_VAE['local_full_stack'];
  if (!$logged_in && $a['redirect']) return vae_redirect($a['redirect']);
  return _vae_render_tags($tag, $context, $render_context, $logged_in);
}

function _vae_render_if_paginate($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($a['collection']) {
    $b = $_VAE['pagination'][$a['collection']];
    $true = ($b['last_page'] > 1);
  } else {
    $items = _vae_fetch($a['path'], $context);
    $true = (is_object($items) && (count($items) > $a['paginate']));
  }
  return _vae_render_tags($tag, $context, $render_context, $true);
}

function _vae_render_if_time($a, &$tag, $context, &$callback, $render_context) {
  $true = true;
  if ($a['before'] && (time() > strtotime($a['before']))) $true = false;
  if ($a['after'] && (time() < strtotime($a['after']))) $true = false;
  return _vae_render_tags($tag, $context, $render_context, $true);
}

function _vae_render_img($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if (!isset($a['alt'])) $a['alt'] = "Image";
  if ($a['path']) {
    if ($a['filename']) {
      $preserve_filename = $a['filename'];
    } else {
      $preserve_filename = ($_VAE['settings']['preserve_filenames'] ? true : false);
    }
    $a['src'] = (($a['image_size'] && !$a['width']) ? vae_sizedimage(_vae_fetch($a['path'], $context), $a['image_size'], $preserve_filename) : vae_image(_vae_fetch($a['path'], $context), $a['width'], $a['height'], $a['image_size'], $a['grow'], $a['quality'], $preserve_filename));
    if (!$a['src']) return "";
    if ($a['watermark']) $a['src'] = vae_watermark($a['src'], $a['watermark'], $a['watermark_vertical_align'], $a['watermark_align'], $a['watermark_vertical_padding'], $a['watermark_horizontal_padding']);
    if (substr($a['filter'], 0, 7) == "reflect") {
      $params = explode(",", str_replace(array("(", ")"), "", substr($a['filter'], 7)));
      $a['src'] = vae_image_reflect($a['src'], (strlen($params[0]) ? $params[0] : 30), (strlen($params[1]) ? $params[1] : 35), true);
    } elseif ($a['filter'] == "grey") {
      $a['src'] = vae_image_grey($a['src'], true);
    }
    if (!$a['nosize']) {
      $size = _vae_imagesize($a['src']);
      if ($size) {
        $a['width'] = $size[0];
        $a['height'] = $size[1];
      } else {
        unset($a['width']);
        unset($a['height']);
      }
    } else {
      unset($a['width']);
      unset($a['height']);
    }
    $a['src'] = _vae_absolute_data_url() . $a['src'];
  } elseif ($a['src']) {
    if (!strstr($a['src'], "://")) $a['src'] = _vae_render_cdn($a, $tag, $context, $callback, $render_context);
  } else {
    return _vae_error("You need to provide a value for either the <span class='c'>path</span> or <span class='c'>src</span> attribute of the <span class='c'>&lt;v:img&gt;</span> tag.", "", $tag['filename']);
  }
  if ($tag['type'] == "img") {
    if ($a['protect']) {
      $a['style'] = ($a['style'] ? $a['style'] . " " : "") . "background-image: url(" . $a['src'] . "); height: " . $a['height'] . "px; width: " . $a['width'] . "px;";
      return '<div' . _vae_attrs($a, "div") . '><img src="' . $_VAE['config']['asset_url'] . 'spacer.png" height="' . $a['height'] . '" width="' . $a['width'] . '" /></div>';
    }
    return '<img' . _vae_attrs($a, "img") . ' />';
  } else {
    return $a['src'];
  }
}

function _vae_render_nested_collection($a, &$tag, $context, &$callback, $render_context) {
  $options = array();
  foreach (array('filter','order','unique') as $opt) {
    if ($a[$opt]) $options[$opt] = $a[$opt];
  }
  if ($a['filter_input']) {
    if (strlen($_REQUEST[$a['filter_input']])) {
      $options['filter'] = $_REQUEST[$a['filter_input']];
    } else {
      return _vae_get_else($tag, $context, $render_context, "You did not enter a search query.");
    }
  }
  if ($a['path'] == ".." && !$render_context->get("nestedRendering")) {
    $contexts = $context;
  } else {
    $contexts = _vae_fetch($a['path'], $context, $options);
  }
  if (is_object($contexts) && ($contexts->count > 0)) {
    while (substr($a['path'], 0, 1) == "@" || substr($a['path'], 0, 1) == "/") $a['path'] = substr($a['path'], 1);
    $dividers = _vae_find_dividers($tag);
    $rendered = 0;
    $nested_render_context = $render_context->set("total_items", $contexts->totalMatches())->set_in_place("nestedRendering", "1");
    foreach ($contexts as $context) {
      $children = _vae_render_nested_collection($a, $tag, $context, $callback, $nested_render_context);
      $parent = _vae_render_tags($tag, $context, $render_context);
      if ($a['output_order'] == "reverse") $parent = $children . $parent;
      else $parent .= $children;
      $out .= _vae_merge_dividers($parent, $dividers, $rendered, $context, $nested_render_context);
      $rendered++;
    }
    if (strlen($out)) {
      $out = _vae_merge_dividers($out, $dividers, $render_context->get("nestedRendering"), $context, $render_context, ($a['output_order'] == "reverse"), "nested_divider");
    }
  }
  return $out;
}

function _vae_render_newsletter($a, &$tag, $context, &$callback, $render_context) {
  return _vae_render_callback("newsletter", $a, $tag, $context, $callback, $render_context);
}

function _vae_render_nowidows($a, &$tag, $context, &$callback, $render_context) {
  $out = trim(_vae_render_tags($tag, $context, $render_context));
  $words = preg_split('/\s\s*/', $out);
  $end = array_pop($words);
  $next = array_pop($words);
  array_push($words, $next . "&nbsp;" . $end);
  return implode(" ", $words);
}

function _vae_render_oneline($out, $context, $attribute_type = false) {
  global $_VAE;
  preg_match_all('/<v=([^>]*)>/', $out, $matches, PREG_SET_ORDER);
  foreach ($matches as $regs) {
    $out = str_replace($regs[0], _vae_oneline($regs[1], $context, $attribute_type), $out);
  }
  preg_match_all('/<v~([^>]*)>/', $out, $matches, PREG_SET_ORDER);
  foreach ($matches as $regs) {
    $out = str_replace($regs[0], _vae_oneline_url($regs[1], $context), $out);
  }
  preg_match_all('/<v\\?(.*)\\?>/', $out, $matches, PREG_SET_ORDER);
  foreach ($matches as $regs) {
    $out = str_replace($regs[0], _vae_php($regs[1], $context, " in one of your <code>&lt;v? ?&gt;</code> tags:<br /><br /><code>" . $regs[1] . "</code>"), $out);
  }
  return str_replace("<v->", ($context ? $context->formId() : ""), $out);
}

function _vae_render_option_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $blank = $set_fn = $out = "";
  $options = $option_ids = array();
  $fields = array();
  $price_field = $render_context->attr("price_field", $a);
  $inventory_field = $render_context->attr("inventory_field", $a);
  $disable_inventory_check = $render_context->attr("disable_inventory_check", $a);
  $all_entries = _vae_fetch($a['path'], $context);
  if ($all_entries == false) return "";
  $entries = array();
  foreach ($all_entries as $r) {
    if ($inventory_field && !$disable_inventory_check && (string)$r->get($inventory_field) === "0") continue;
    $entries[] = $r;
  }
  _vae_needs_jquery();
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $old_a = $a;
  $glob_id = _vae_global_id();
  $script = "\n";
  $name = $glob_id . "_list";
  foreach (explode(",", $a['fields']) as $field) {
    $values = array();
    foreach ($entries as $r) {
      $val = _vae_fetch($field, $r);
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
      $option_ids[$option_name] = _vae_global_id();
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
      $out .= '<div><label>' . $option_name . ":</label> " . _vae_render_select($a, $tag, $context, $callback, $render_context) . '</div>';
      $i++;
    }
  }
  if ($price_field) $main_price = _vae_fetch($price_field, $context);
  $out .= _vae_render_tag("input", array('type' => 'hidden', 'value' => (($old_a['default'] && strlen($set_fn)) ? "" : $all_entries->id()), 'name' => $old_a['name'], 'id' => $glob_id), $blank, $context, $render_context);
  if (strlen($set_fn)) {
    $script .= "  function " . $glob_id . "_set() {\n    for (var item_ in " . $name . ") {\n      if (" . $set_fn . ") {\n        jQuery('#" . $glob_id . "').val(item_);\n" . ($a['price_display'] ? "        jQuery('#" . $a['price_display'] . "').html(Number(" . $name . "[item_][0]).toFixed(2));\n" : "") . "      }\n    };\n";
    if ($a['price_display']) {
      $script .= "    sel = jQuery('#" . $glob_id . "').val();\n    if (sel) {\n      for (var item_ in " . $name . ") {\n";
      $script .= "        price_diff = (" . $name . "[item_][0] - " . $name . "[sel][0]).toFixed(2);\n";
      if ($a['price_display_changes'] == "full_price") {
        $script .= "        if (price_diff != 0) { upcharge = ' [$' + " . $name . "[item_][0] + ']'; } else { upcharge = ''; }\n";
      } else {
        $script .= "        if (price_diff > 0) { upcharge = ' [+\$' + price_diff + ']'; } else if (price_diff < 0) { upcharge = ' [-\$' + (price_diff*-1).toFixed(2) + ']'; } else { upcharge = ''; } \n";
      }
      $script .= $price_hints . "      }\n    }\n";
    }
    $script .= "  }\n";
    $script .= "  window." . $name . " = {};\n";
    foreach ($entries as $r) {
      $script .= "  " . $name . "['" . $r->id() . "'] = new Array(";
      $line = '"' . ($price_field ? (($p = (string)$r->get($price_field)) ? $p : $main_price) : "0.00") . '"';
      foreach ($fields as $field) {
        $val = _vae_fetch($field, $r);
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
    _vae_on_dom_ready($script);
  }
  return $out;
}

function _vae_render_pagination($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $b = $_VAE['pagination'][$a['collection']];
  $dividers = _vae_find_dividers($tag);
  $out = $class = "";
  if ($a['ajax']) {
    $class = _vae_global_id($b['page_request_variable']);
    _vae_on_dom_ready('jQuery(".' . $class . '").click(function(e) { ' . $a['ajaxbefore'] . ' jQuery("#' . $class . '_loading").show(); jQuery.get(jQuery(this).attr("href")+"&__xhr_paginate=' . $b['page_request_variable'] . '", function(d){ ' . $a['ajaxsuccess'] . ' ' . $a['oncomplete'] . ' jQuery("#' . $a['ajax'] . '").html(d); jQuery("#' . $class . '_loading").hide(); }); e.preventDefault(); return false; });');
  }
  $start_page = 1;
  $end_page = $b['last_page'];
  if (isset($a['max_to_show']) && ($b['last_page'] > $a['max_to_show'])) {
    $max_pages = $a['max_to_show'];
    if (($max_pages % 2) == 1) $max_pages -= 1;
    $start_page = max($b['page'] - ($max_pages / 2), 1);
    $end_page = min($b['last_page'], $start_page + $max_pages);
  }
  for ($i = $start_page; $i <= $end_page; $i++) {
    $data = '<a class="' . $class . (($b['page'] == $i) ? ' current' : '') . '" href="' . urlencode($_SERVER['PHP_SELF']) . _vae_qs(array($b['page_request_variable'] => $i)) . '">' . $i . '</a>';
    $out .= _vae_merge_dividers($data, $dividers, $i - 1, $context, $render_context) . ' ';
  }
  if ($a['loading']) {
    $loader = '<img id="' . $class . '_loading" src="' . $a['loading'] . '" alt="Loading ..." class="loading-indicator" style="display: none; vertical-align: middle;" />';
    if ($a['loadingposition'] == "before") $out = $loader . $out;
    else $out .= $loader;
  }
  return $out;
}

function _vae_render_password_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  unset($a['value']);
  $a['type'] = 'password';
  return  '<input' . _vae_attrs($a, "input") . ' />';
}

function _vae_render_pdf($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $_VAE['prepend'] .= "<!--PDF--" . $a['filename'] . ";" . $a['orientation'] . ";" . $a['paper'] . "-->";
  return _vae_render_tags($tag, $context, $render_context);
}

function _vae_render_php($a, &$tag, $context, &$callback, $render_context) {
  $php = _vae_render_tags($tag, $context, $render_context);
  return _vae_php($php, $context, " in <code>" . $tag['filename'] . "</code>:<br /><br /><code>" . $php . "</code>");
}

function _vae_render_radio($a, &$tag, $context, &$callback, $render_context) {
  $value = $a['value'];
  unset($a['value']);
  $a2 = _vae_form_prepare($a, $tag, $context, $render_context);
  $a2['type'] = "radio";
  if ($a2['value'] == $value) $a2['checked'] = "checked";
  $a2['value'] = $value;
  return  '<input' . _vae_attrs($a2, "input") . ' />';
}

function _vae_render_repeat($a, &$tag, $context, &$callback, $render_context) {
  if (!is_numeric($a['times']) || ($a['times'] < 1)) {
    return _vae_error("You did not specify a valid numeric value for the <span class='c'>times</span> attribute of the <span class='c'>&lt;v:repeat&gt;</span> tag.", "", $tag['filename']);
  }
  $out = "";
  for ($i = 0; $i < $a['times']; $i++) {
    $out .= _vae_render_tags($tag, $context, $render_context);
  }
  return $out;
}

function _vae_render_require_permalink($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if (!isset($_VAE['context'])) vae_redirect("/");
  return _vae_render_tags($tag, $context, $render_context);
}

function _vae_render_require_ssl($a, &$tag, $context, &$callback, $render_context) {
  _vae_require_ssl();
  return _vae_render_tags($tag, $context, $render_context);
}

function _vae_render_rss($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $_VAE['serve_rss'] = true;
  $items = "";
  if (!$a['limit']) $a['limit'] = 25;
  foreach (_vae_fetch($a['path'], $context, $a) as $ctxt) {
    $inside = _vae_render_tags($tag, $ctxt, $render_context);
    if (preg_match('/<item>(.*)<\/item>/s', $inside, $matches)) {
      $outside = preg_replace('/<item>(.*)<\/item>/s', '', $inside);
      $inside = $matches[1];
    }
    $items .= '  <item>' . "\n";
    if (!strstr($inside, "<title>")) $items .= '   <title>' . _vae_format_for_rss(_vae_fetch_multiple($a['title_field'], $ctxt, $a)) . '</title>' . "\n";
    if ($a['author_field'] && !strstr($inside, "<author>")) $items .= '   <author>' . _vae_format_for_rss(_vae_fetch_multiple($a['author_field'], $ctxt, $a)) . '</author>' . "\n";
    if (!strstr($inside, "<description>")) $items .= '   <description>' . _vae_format_for_rss(_vae_fetch_multiple($a['description_field'], $ctxt, $a)) . '</description>' . "\n";
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
  $out .= '<rss version="2.0"' . (strstr($items, "<g:") ? ' xmlns:g="http://base.google.com/ns/1.0"' : "") . (strstr($items, "<itunes:") ? ' xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"' : "") . '>' . "\n";
  $out .= ' <channel>' . "\n";
  if (!strstr($outside, "<title>")) $out .= '  <title>' . $a['title'] . '</title>' . "\n";
  if (!strstr($outside, "<link>")) $out .= '  <link>http://' . $_SERVER['HTTP_HOST'] . '/</link>' . "\n";
  if (!strstr($outside, "<description>")) $out .= '  <description>' . $a['description'] . '</description>' . "\n";
  if (!strstr($outside, "<language>")) $out .= '  <language>en-us</language>' . "\n";
  $out .= '  <generator>Vae</generator>' . "\n";
  $out .= $outside;
  $out .= $items;
  $out .= ' </channel>' . "\n";
  $out .= '</rss>';
  return $out;
}

function _vae_render_section($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($a['path'] == "/") {
    $new_context = null;
  } else {
    $new_context = _vae_fetch($a['path'], $context);
  }
  if ($new_context == false || !is_object($new_context)) return _vae_render_tags($tag, $context, $render_context);
  return _vae_render_tags($tag, $new_context, $render_context->set("total_items", 1));
}

function _vae_render_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  if (!is_array($a['options']) && !strstr($a['options'], "<option")) {
    if (strlen($a['options'])) {
      $ex = explode(",", $a['options']);
      $a['options'] = array();
      foreach ($ex as $e1) {
        if (strstr($e1, "=")) $ex2 = explode("=", $e1);
        else $ex2 = array($e1, $e1);
        $a['options'][$ex2[0]] = $ex2[1];
      }
    } elseif (is_array($_VAE['page_select'][$a['id']])) {
      $num_pages = $_VAE['page_select'][$a['id']][0];
      $a['options'] = array();
      if ($_VAE['page_select'][$a['id']][3]) for ($i = $num_pages; $i >= 1; $i--) { $a['options'][$i] = $i; }
      else for ($i = 1; $i <= $num_pages; $i++) { $a['options'][$i] = $i; }
      $a['onchange'] = _vae_append_js($a['onchange'], "window.location.href='" . $_VAE['page_select'][$a['id']][2] . "'+this.value");
      $a['value'] = $_VAE['page_select'][$a['id']][1];
    }
  }
  if (!strlen($a['value'])) $a['value'] = $a['default'];
  $out = _vae_render_tags($tag, $context, $render_context);
  if ((is_string($a['options']) && strstr($a['options'], "<option")) || count($a['options']) || strlen($out)) {
    if (is_string($a['options'])) {
      $out = $a['options'] . $out;
    } elseif (count($a['options'])) {
      foreach ($a['options'] as $k => $v) {
        if (is_array($v)) {
          $k = $v[0];
          $v = $v[1];
        }
        $o .= '<option value="' . $k . '"' . ((string)$a['value'] == $k ? ' selected="selected"' : '') . '>' . $v . '</option>';
      }
      $out = $o . $out;
    }
    unset($a['options']);
    return _vae_render_tag("select", $a, $out, $context, $render_context);
  }
  return "";
}


function _vae_render_session_dump($a, &$tag, $context, &$callback, $render_context) {
  return "<__VAE_SESSION_DUMP=" . $a['key'] . ">";
}

function _vae_render_set($a, &$tag, $context, &$callback, $render_context) {
  if (!strlen($a['value'])) $a['value'] = 1;
  $_REQUEST[$a['name']] = $a['value'];
  return "";
}

function _vae_render_set_default($a, &$tag, $context, &$callback, $render_context) {
  if (!strlen($_REQUEST[$a['name']])) {
    if (!strlen($a['value'])) $a['value'] = 1;
    $_REQUEST[$a['name']] = $a['value'];
  }
  return "";
}

function _vae_render_site_seal($a, &$tag, $context, &$callback, $render_context) {
  return '<script type="text/javascript" src="https://seal.godaddy.com/getSeal?sealID=tNtd4YGynD9R2H7FqDMOsQYQqYBAtTAJAuee36HrqYBLztygGlfANpCNcZ2o"></script>';
}

function _vae_render_state_select($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  $oldclass = $a['class'];
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $a['class'] = trim(str_replace("required", "", $a['class']) . " " . $a['id']);
  $a['onchange'] = _vae_append_js($a['onchange'], "jQuery('#" . $a['id'] . "').val(jQuery(this).val());");
  $a2 = $a;
  $out = "";
  $a['style'] = "display: none";
  foreach ($_VAE['states'] as $country => $states) {
    $a['options'] = $states;
    $a['id'] = $a['name'] = $a2['id'] . "_" . $country;
    $out .= _vae_render_select($a, $tag, $context, $callback, $render_context);
  }
  $a3 = array("type" => "hidden", "value" => $a2['value'], "name" => $a2['name'], "id" => $a2['id'], "class" => $oldclass, "onchange" => _vae_append_js("", "jQuery('." . $a2['id'] . "').val(jQuery(this).val());"));
  $out .= "<input" . _vae_attrs($a3, "input") . " />";
  $a2['name'] .= "_txt";
  $a2['id'] .= "_txt";
  return $out . _vae_render_text_field($a2, $tag, $context, $callback, $render_context);
}

function _vae_render_tag($tagname, $a, &$tag, $context = null, $render_context = null) {
  $inside = (is_array($tag) ? _vae_render_tags($tag, $context, $render_context, true) : $tag);
  if (!strlen($inside) && (!in_array($tagname, array("form", "script", "textarea")))) return '<' . $tagname . _vae_attrs($a, $tagname) . ' />';
  return '<' . $tagname . _vae_attrs($a, $tagname) . '>' . $inside . '</' . $tagname . '>';
}

function _vae_render_tags(&$parent_tag, $context = null, $render_context = null, $true = true) {
  global $_VAE;
  $out = "";
  if (!$true) {
    return _vae_get_else($parent_tag, $context, $render_context);
  }
  if (is_object($render_context)) $render_context = $render_context->unsett("else");
  if (count($parent_tag['tags'])) {
    for ($i = 0; $i < count($parent_tag['tags']); $i++) {
      $out .= _vae_render($parent_tag['tags'][$i], $context, $render_context);
    }
  }
  if (is_object($render_context) && $render_context->get("else")) {
    $out .= $render_context->get("else_message");
  }
  return $out;
}

function _vae_render_template($a, &$tag, $context, &$callback, $render_context) {
  list($filename, $vaeml) = _vae_src($a['filename']);
  if (!strlen($vaeml)) return _vae_render_tags($tag, $context, $render_context);
  foreach ($a as $k => $v) {
    if ($k != "filename" && !isset($_REQUEST[$k])) $_REQUEST[$k] = $v;
  }
  list($parse_tree, $render_context) = _vae_parse_vaeml($vaeml, $filename, $tag, $render_context);
  return _vae_render_tags($parse_tree, $context, $render_context);
}

function _vae_render_text($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($a['param']) {
    $text = $_REQUEST[$a['param']];
  } elseif ($a['text']) {
    $text = $a['text'];
  } elseif ($a['placeholder']) {
    $text = _vae_placeholder($a['placeholder']);
  } else {
    if (strlen($a['path'])) {
      $text = _vae_fetch($a['path'], $context);
    }
    if ($text->type == "DateItem" || strlen($a['strftime'])) {
      if (!strlen($a['path'])) $text = time();
      if (!strlen($a['strftime'])) $a['strftime'] = "%B %d, %Y";
      $time = (string)$text;
      if (!is_numeric($time)) $time = strtotime($time);
      if (strstr($a['strftime'], "%N")) {
        $a['strftime'] = str_replace("%N", _vae_natural_time($time), $a['strftime']);
      }
      $text = strftime($a['strftime'], $time);
    }
  }
  $html = (is_object($text) && $text->type == "HtmlAreaItem");
  if ($html) {
    $render = _vae_htmlarea($text, $a);
  } else {
    if ($a['maxlength'] && strlen($text) > $a['maxlength']) $text = substr($text, 0, $a['maxlength']) . "...";
    $text = $a['before'] . $text . $a['after'];
    if ($a['transform']) {
      if (function_exists($a['transform'])) {
        $text = call_user_func($a['transform'], $text);
      } elseif (function_exists("vae_" . $a['transform'])) {
        $text = call_user_func("vae_" . $a['transform'], $text);
      } else {
        $text = "[FUNCTION NOT FOUND: " . $a['transform'] . "]";
      }
    }
    if (strlen($a['number_format'])) $text = number_format($text, $a['number_format']);
    if ($a['font'] && strlen($text)) {
      $render = vae_text($text, $a['font'], $a['font-size'], $a['color'], $a['kerning'], $a['padding'], $a['max-width']);
    } else {
      $render = vae_style($text, ($a['nohtml'] ? false : true));
    }
  }
  if ($a['nolinebreak']) $render = str_replace("\n", " ", $render);
  if ($a['escape']) $render = addslashes($render);
  return $render;
}

function _vae_render_text_area($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  return _vae_render_tag("textarea", $a, $a['value'], $context, $render_context);
}

function _vae_render_text_field($a, &$tag, $context, &$callback, $render_context) {
  $a = _vae_form_prepare($a, $tag, $context, $render_context);
  $a['type'] = "text";
  return  '<input' . _vae_attrs($a, "input") . ' />';
}

function _vae_render_unsubscribe($a, &$tag, $context, &$callback, $render_context) {
  return '<v:unsubscribe />';
}

function _vae_render_update($a, &$tag, $context, &$callback, $render_context) {
  if ($a['path']) $context = _vae_fetch($a['path'], $context);
  if ($context) {
    if (!is_object($context) && is_numeric($context)) $context = _vae_fetch($context);
    $callback['row_id'] = $context->id();
    return _vae_render_callback("update", $a, $tag, $context, $callback, $render_context);
  } else {
    return "";
  }
}

function _vae_render_video($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($a['src']) {
    $url = $a['src'];
    if (substr($url, 0, 1) != "/")  $url = "/" . dirname($_VAE['filename']) . $url;
    $url = _vae_cdn_timestamp_url($url);
    $src = vae_cdn_url() . substr($url, 1);
  } else {
    $video = _vae_fetch($a['path'], $context);
    $src = vae_video($video, $a['size']);
    if ($src == "tryagain.flv") $src = $_VAE['config']['backlot_url'] . "/videos/" . $src;
    else $src = _vae_absolute_data_url() . $src;
  }
  $id = $a['id'];
  if (!$id) $id = _vae_global_id();
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
  _vae_needs_javascript("jwplayer");
  return '<div id="' . $id . '_container">You need to <a href="http://www.macromedia.com/go/getflashplayer">get the Flash Player</a> to see this video.</div>
  <script type="text/javascript">
    jwplayer("' . $id . '_container").setup({
      flashplayer: "' . $_VAE['config']['asset_url'] . 'player.swf",
      file: "' . $src . '",
      image: "' . $a['image'] . '",
      height: ' . $player_height . ',
      width: ' . $player_width . $extra_params . '
    });
  </script>';
}

function _vae_render_yield($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if ($y = $_VAE['yield']) {
    unset($_VAE['yield']);
    return $y;
  }
  if ($context && (!$render_context->get("nestedRendering")) && ($body = (string)_vae_fetch_without_errors("yield", $context))) {
    return _vae_render_newsletter_yield($body, $context, $a);
  }
  return _vae_render_tags($tag, $context, $render_context);
}

function _vae_render_newsletter_yield($body, $context, $a) {
  if (strpos($body, "&lt;v") !== false) {
    preg_match_all('/&lt;v=(.*)&gt;/U', $body, $matches, PREG_SET_ORDER);
    foreach ($matches as $regs) {
      $body = str_replace($regs[0], "<v=" . $regs[1] . ">", $body);
    }
    preg_match_all('/&lt;v\\?=(.*)\\?&gt;/U', $body, $matches, PREG_SET_ORDER);
    foreach ($matches as $regs) {
      $body = str_replace($regs[0], "<v?=" . $regs[1] . "?>", $body);
    }
    $body = _vae_render_oneline($body, $context, false);
  }
  return _vae_htmlarea($body, $a);
}

function _vae_render_zip($a, &$tag, $context, &$callback, $render_context) {
  global $_VAE;
  if (count($tag['tags'])) {
    foreach ($tag['tags'] as $itag) {
      if (!$itag['type']) $out .= $itag['innerhtml'];
    }
  }
  unset($_VAE['zip_files']);
  _vae_render_tags($tag, $context, $render_context);
  $callback['files'] = $_VAE['zip_files'];
  $callback['filename'] = $a['filename'];
  if ($a['direct']) $_REQUEST['__v:zip'] = _vae_tag_unique_id($tag, $context);
  $a['href'] = $_SERVER['PHP_SELF'] . _vae_qs("__v:zip=" . _vae_tag_unique_id($tag, $context));
  return '<a' . _vae_attrs($a, "a") . '>' . $out .'</a>';
}
