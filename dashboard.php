<?php



if ( ! defined( 'ABSPATH' ) ) exit;



# utility library
include 'lib.php';



# define and validate period
if ( !empty($_REQUEST['period']) && in_array($_REQUEST['period'], ['today', 'week', 'month']) ) {
  $period = $_REQUEST['period'];
}
else {
  $period = 'week';
}



# define and validate chosen post (if any)
if ( !empty($_GET['p']) && is_numeric($_GET['p']) ) {
  $post_id = (int)$_GET['p'];
}



# post funnel page
if ( $post_id ) {
  $post = get_post($post_id);
  if ( !$post ) {
    echo('Sorry, this post was not found');
    return;
  }
  
  $funnel = io_get_article_funnel($post_id, $period);
  $summary = io_get_summary($period, $post_id);
  
  include 'post.phtml';
}

# general dashboard
else {
  $summary = io_get_summary($period);
  $articles = io_get_articles($period);
  $chart = io_get_chart($period);
  
  include 'dashboard.phtml';
}