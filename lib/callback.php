<?php

function _vae_callback_create($tag) {
  if ($tag['callback']['structure_id'] && (_vae_rest(array(), "content/create/" . $tag['callback']['structure_id'] . ($tag['callback']['row_id'] > 0 ? "/" . $tag['callback']['row_id'] : ""), "content", $tag))) {
    $email_field = ($tag['attrs']['newsletter_email_field'] ? $tag['attrs']['newsletter_email_field'] : 'e_mail_address');
    if ($tag['attrs']['newsletter']) _vae_newsletter_subscribe($tag['attrs']['newsletter'], $data[$email_field], $tag['attrs']['newsletter_confirm']);
    unset($tag['attrs']['newsletter']);
    if ($tag['attrs']['formmail']) {
      $tag['attrs']['to'] = $tag['attrs']['formmail'];
      return _vae_callback_formmail($tag);
    }
    if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect'], true);
    return _vae_callback_redirect($_SERVER['PHP_SELF'], true);
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_callback_file($tag) {
  global $_VAE;
  $file = $_VAE['config']['data_path'] . $tag['callback']['src'];
  $sep = explode(".", $file);
  $filename = ($tag['callback']['filename'] ? $tag['callback']['filename'] : "file") . "." . $sep[count($sep)-1];
  $filename = str_replace("/", "", $filename);
  @header('Content-Description: File Transfer');
  @header('Content-Type: application/octet-stream');
  @header('Content-Length: ' . filesize($file));
  if (strstr($filename, " ")) $filename = '"' . $filename . '"';
  @header('Content-Disposition: attachment; filename=' . $filename);
  $_VAE['stream'] = $file;
  return "__STREAM__";
}

function _vae_callback_formmail($tag) {
  $data = array();
  $errors = array();
  _vae_merge_data_from_tags($tag, $data, $errors);
  if (!_vae_flash_errors($errors)) {
    foreach ($data as $k => $v) {
      $_SESSION['__v:formmail']['recent'][$k] = $v;
      if ($k != "__v:to" && strlen($v)) {
        $k = str_replace("_", " ", $k);
        $text1 .= $k . ": " . $v . "\n";
        $html1 .= "<tr><td style='padding-right: 25px; vertical-align: top;'>" . htmlentities($k) . ":</td><td style='padding-bottom: 5px'>" . nl2br(htmlentities($v)) . "</td></tr>\n";
      }
    }
    if (strlen($text1)) {
      $html = "<div id=\"vmail\" style='font-size: 0.95em; font-family: Arial, \"Lucida Grande\", \"Myriad\", \"Lucida Sans Unicode\", \"Bitstream Vera Sans\", Helvetica, Arial, sans-serif;'>";
      $text = "On " . strftime("%B %d, %Y at %H:%M") . ", the following form was submitted to your website at http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . ":\n\n";
      $html .= "<p style='color: #333; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #ddd;'>On <strong>" . strftime("%B %d, %Y</strong> at <strong>%H:%M") . "</strong>, the following form was submitted to your website at <strong>http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "</strong></p><table border='0' style='font-size: 1.0em; font-family: Arial, \"Lucida Grande\", \"Myriad\", \"Lucida Sans Unicode\", \"Bitstream Vera Sans\", Helvetica, Arial, sans-serif;' cellspacing='0' cellpadding='0'>";
      $html .= $html1 . "</table>";
      $text .= $text1 . "\n\n";
      if ($tag['attrs']['email_template']) {
        if (($html_template = _vae_find_source($tag['attrs']['email_template'])) && ($text_template = _vae_find_source($tag['attrs']['email_template'] . ".txt"))) {
          if (($html = _vae_proxy($html_template, "", false, $html1)) == false) return _vae_error("Unable to build Formmail Template E-Mail (HTML version) file from <span class='c'>" . _vae_h($tag['attrs']['email_template']) . "</span>.  You can debug this by loading that file directly in your browser.");
          if (($text = _vae_proxy($text_template, "", false, $text1)) == false) return _vae_error("Unable to build Formmail Template E-Mail (text version) file from <span class='c'>" . _vae_h($tag['attrs']['email_template']) . "</span>.  You can debug this by loading that file directly in your browser.");
        }
      }
      $from = "Form Mailer <no-reply@newsletter-agent.com>";
      if ($tag['attrs']['from']) $from = $tag['attrs']['from'];
      if ($tag['attrs']['from_field']) $from = $data[$tag['attrs']['from_field']];
      if (strstr($_POST['__v:to'], $tag['attrs']['to'])) $tag['attrs']['to'] = $_POST['__v:to'];
      $subject = ($tag['attrs']['subject'] ? $tag['attrs']['subject'] :  $_SERVER['HTTP_HOST'] . " Website Form Submission");
      _vae_multipart_mail($from, $tag['attrs']['to'], $subject, $text, $html);
    }
    $email_field = ($tag['attrs']['newsletter_email_field'] ? $tag['attrs']['newsletter_email_field'] : 'e_mail_address');
    if ($tag['attrs']['newsletter']) _vae_newsletter_subscribe($tag['attrs']['newsletter'], $data[$email_field], $tag['attrs']['newsletter_confirm']);
    if ($tag['attrs']['redirect']) return _vae_callback_redirect($tag['attrs']['redirect']);
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_callback_newsletter($tag) {
  $a = $tag['attrs'];
  if ($_REQUEST['e_mail_address']) {
    $res = _vae_newsletter_subscribe($tag['attrs']['code'], $_REQUEST['e_mail_address']);
    if (strstr($res, "Welcome to")) {
      if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
    } elseif (strstr($res, "That E-Mail Address already on this list!")) {
      _vae_flash("You are already subscribed!", 'err', $a['flash']);
    } else {
      _vae_flash("There was an error in creating the subscription.", 'err', $a['flash']);
    }
  } else {
    _vae_flash("You did not enter an E-Mail address.", 'err', $a['flash']);
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_callback_update($tag) {
  if (_vae_rest(array(), "content/update/" . $tag['callback']['row_id'], "content", $tag)) {
    _vae_flash('Saved.', 'msg', $tag['attrs']['flash']);
    if (strlen($tag['attrs']['redirect'])) return _vae_callback_redirect($tag['attrs']['redirect']);
  }
  return _vae_callback_redirect($_SERVER['PHP_SELF']);
}

function _vae_callback_zip($tag) {
  global $_VAE;
  require_once(dirname(__FILE__)."/../vendor/zipstream.php");
  $name = ($tag['callback']['filename'] ? $tag['callback']['filename'] : "Archive");
  $zip = new ZipStream($name.'.zip');
  foreach ($tag['callback']['files'] as $file) {
    $sep = explode(".", $file['src']);
    if (!$file['filename']) $file['filename'] = $sep[0];
    $zip->add_file(str_replace("/", "", $file['filename']) . "." . $sep[count($sep)-1], file_get_contents($_VAE['config']['data_path'] . $file['src']));
  }
  $zip->finish();
  return $zip->out;
}

?>