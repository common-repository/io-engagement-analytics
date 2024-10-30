<?php



if ( ! defined( 'ABSPATH' ) ) exit;



# utility library
include 'lib.php';



# save GA ID
if ( isset($_POST['ga_id']) && preg_match('/^UA[0-9\-]+$/', $_POST['ga_id']) ) {
  update_option('io-ga-id', $_POST['ga_id']);
  $updated = true;
}



include 'ga.phtml';