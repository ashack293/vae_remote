<?php

function _vae_thrift($port = 9090) {
  global $_VAE;
  if ($_VAE['vaerubyd']) return $_VAE['vaerubyd'];
  $_VAE['vaerubyd'] = _vae_thrift_open("VaeRubydClient", $port);
  return $_VAE['vaerubyd'];
};

function _vae_dbd($port = 9091) {
  global $_VAE;
  if ($_VAE['vaedbd_port']) $port = $_VAE['vaedbd_port'];
  if ($_VAE['vaedbd']) return $_VAE['vaedbd'];
  $_VAE['vaedbd'] = _vae_thrift_open("VaeDbClient", $port);
  return $_VAE['vaedbd'];
};

function _vae_thrift_open($client_class, $port) {
  global $_VAE;
  $GLOBALS['THRIFT_ROOT'] = '/www/vae_thrift/current/php/vendor/thrift';
  require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/../../../gen-php/vae/VaeDb.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/../../../gen-php/vae/VaeRubyd.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/../../../gen-php/vae/vae_types.php';
  try {
    $host = 'localhost';
    $hash = base_convert(substr(md5($_SERVER['DOCUMENT_ROOT']), 0, 2), 16, 10) % 4;
    if (($_VAE['config']['vaedb'] == "vaedb0" || $hash == 0) && $port == 9091) {
      $host = '10.38.9.44';
    }
    $socket = new TSocket($host, $port);
    $socket->setRecvTimeout(30000);
    $transport = new TBufferedTransport($socket, 1024, 1024);
    $protocol = new TBinaryProtocol($transport);
    $client = new $client_class($protocol);
    $transport->open();
    return $client;
  } catch (TException $tx) {
    throw new VaeException("", $tx->getMessage());
  }
}