<?php

// set this to false to disable slow tests
$_ENV['slow_tests'] = false;
$_VAE['vaedbd_port'] = 9091;
$_VAE['vaedbd_backends'] = array('127.0.0.1');

$_ENV['TEST'] = true;
require_once(dirname(__FILE__) . '/../vendor/simpletest/autorun.php');
require_once(dirname(__FILE__) . '/_vae_unit_test_case.php');
require_once(dirname(__FILE__) . '/../lib/index.php');

if ($argv[1] == "cov") {
  require_once 'PHP/CodeCoverage.php';
  require_once 'PHP/CodeCoverage/Report/HTML.php';
  $coverage = new PHP_CodeCoverage;
  $coverage->start('Vae Remote');
}

class AllTests extends TestSuite {
  
  function AllTests() {
    $this->TestSuite('Vae Remote Tests');
    $this->addFile(dirname(__FILE__) . '/callback_test.php');
    $this->addFile(dirname(__FILE__) . '/constants_test.php');
    $this->addFile(dirname(__FILE__) . '/context_test.php');
    $this->addFile(dirname(__FILE__) . '/func_test.php');
    $this->addFile(dirname(__FILE__) . '/general_test.php');
    $this->addFile(dirname(__FILE__) . '/haml_test.php');
    $this->addFile(dirname(__FILE__) . '/pages_test.php');
    $this->addFile(dirname(__FILE__) . '/parse_test.php');
    $this->addFile(dirname(__FILE__) . '/pdf_test.php');
    $this->addFile(dirname(__FILE__) . '/phpapi_test.php');
    $this->addFile(dirname(__FILE__) . '/rest_test.php');
    $this->addFile(dirname(__FILE__) . '/render_test.php');
    $this->addFile(dirname(__FILE__) . '/status_test.php');
    $this->addFile(dirname(__FILE__) . '/store_test.php');
    $this->addFile(dirname(__FILE__) . '/store.oscommerce_test.php');
    $this->addFile(dirname(__FILE__) . '/users_test.php');
    $this->addFile(dirname(__FILE__) . '/vae_exception_test.php');
    $this->addFile(dirname(__FILE__) . '/vaedata_test.php');
  }
  
}

?>
