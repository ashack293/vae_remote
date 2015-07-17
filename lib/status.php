<?php

function _vae_status() {
  $pages = array();
  $domains = array();
  if ($f = @fopen("/usr/local/vae/logs/slow.txt", "r")) {
    while (!feof($f)) {
      $buffer = fgets($f, 4096);
      if (!strlen($buffer)) continue;
      $a = explode("=", $buffer);
      $b = explode("/", $a[0]);
      $domain = $b[0];
      if (isset($pages[$a[0]])) {
        $new = array($pages[$a[0]][0] + $a[1], $pages[$a[0]][1] + 1, $pages[$a[0]][2] + $a[2]);
      } else {
        $new = array($a[1], 1, $a[2]);
      }
      $pages[$a[0]] = $new;
      if (isset($domains[$domain])) {
        $new = array($domains[$domain][0] + $a[1], $domains[$domain][1] + 1, $domains[$domain][2] + $a[2]);
      } else {
        $new = array($a[1], 1, $a[2]);
      }
      $domains[$domain] = $new;
    }
  }
  uasort($pages, "_vae_status_cmp");
  $out = "<h2>Most intense pages</h2><div class='b' style='height: 300px; padding: 15px;'><table width='100%'><thead><tr style='color: #fff; font-weight: bold;'><td>Page</td><td align='right'>Total Time</td><td align='right'>Pageviews</td><td align='right'>Avg Time</td><td align='right'>Cache Hit %</td></tr></thead>";
  foreach ($pages as $page => $r) {
    $out .= "<tr style='color: #fff;'><td>$page</td><td align='right'>" . number_format($r[0], 0) . "</td><td align='right'>" . number_format($r[1]) . "</td><td align='right'>" . number_format($r[0]/$r[1], 0) . "</td><td align='right'>" . number_format($r[2]*100/$r[1], 1) . "%</td></tr>";
  }
  $out .= "</table></div>";
  uasort($domains, "_vae_status_cmp");
  $out .= "<h2>Most intense domains</h2><div class='b' style='height: 300px; padding: 15px;'><table width='100%'><thead><tr style='color: #fff; font-weight: bold;'><td>Domain</td><td align='right'>Total Time</td><td align='right'>Pageviews</td><td align='right'>Avg Pageview Time</td><td align='right'>Cache Hit %</td></tr></thead>";
  foreach ($domains as $domain => $r) {
    $out .= "<tr style='color: #fff;'><td>$domain</td><td align='right'>" . number_format($r[0], 0) . "</td><td align='right'>" . number_format($r[1]) . "</td><td align='right'>" . number_format($r[0]/$r[1]) . "</td><td align='right'>" . number_format($r[2]*100/$r[1], 1) . "%</td></tr>";
  }
  $out .= "</table></div>";
  if (!$_ENV['TEST']) echo _vae_render_message("Vae Status", $out);
  _vae_die();
}

function _vae_status_cmp($a, $b) {
  return ((int)$a[0] < (int)$b[0] ? 1 : -1);
}

?>
