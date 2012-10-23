<?php
 
require_once(dirname(__FILE__) . "/../vendor/dompdf-0.6.0/dompdf_config.inc.php");

function _vae_pdf() {
  $html = _vae_proxy($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING'] . "&__skip_pdf=1", true);
  if (strstr($html, '<html><head><title>Vae Error</title>')) {
    echo $html;
    die();
  }
  $dompdf = new DOMPDF();
  $name = explode(".", basename($_SERVER['SCRIPT_FILENAME']));
  $filename = $name[0];
  if (preg_match("/^<!--PDF--([^;]*);([^;]*);([^;]*)-->/", $html, $matches)) {
    if (strlen($matches[1])) $filename = $matches[1];
    if (strlen($matches[3])) {
      $dompdf->set_paper($matches[3], ($matches[2] == "landscape" ? "landscape" : "portrait"));
    } elseif ($matches[2] == "landscape") {
      $dompdf->set_paper("letter", "landscape");
    }
  }
  $dompdf->set_base_path(dirname(str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME'])));
  $dompdf->set_host($_SERVER['HTTP_HOST']);
  $dompdf->set_protocol("http://");
  $dompdf->load_html($html);
  try {
    @$dompdf->render();
  } catch(Exception $e) {
    _vae_error("Couldn't render PDF.  Please double check that your HTML is valid.  Make sure that it passes the <a href='http://validator.w3.org/'>HTML Validation test</a>.");
  }
  $dompdf->stream($filename . ".pdf");
  die();
}

?>