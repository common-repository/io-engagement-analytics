<?php


/*
Plugin Name: Plugin for Google Analytics by IO technologies
Plugin URI: https://iotechnologies.com/
description: Understand how your readers engage into content. Analyze in real time. No external scripts or storages used, fully wordpress-powered tracking and database.
Version: 1.1
Author: engagementanalytics
License: GPL2
*/



if ( ! defined( 'ABSPATH' ) ) exit;



/*
  Plugin setup
*/

# Activate plugin:  create tables for tracking
register_activation_hook( __FILE__,  function() {
  global $table_prefix, $wpdb;

  require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
  
  $wp_table = $table_prefix . 'io_stats_rt';
  $sql = "CREATE TABLE {$wp_table} (
    action smallint, post_id int, ts int,
    key (action, post_id, ts)
  )";
  dbDelta($sql);
  
  $wp_table = $table_prefix . 'io_stats_daily';
  $sql = "CREATE TABLE {$wp_table} (
    action smallint, post_id int, date date, val int,
    primary key (action, post_id, date),
    key(action, date)
  )";
  dbDelta($sql);
});



/*
  Insert GA into footer, if it's configured
*/

add_action( 'wp_footer', function() {
  $id = htmlspecialchars(get_option('io-ga-id'));
  if ( !$id ) {
    return;
  }
  
  echo
   '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag(\'js\', new Date());
      gtag(\'config\', \'' . $id . '\');
    </script>';
} );



/*
  Admin area menus and dashboards
*/

# Admin menu
add_action( 'admin_menu', function() {
  add_menu_page(
    'IO',
    'Engagement',
    'manage_options',
    plugin_dir_path(__FILE__) . 'dashboard.php',
    null,
    'dashicons-chart-area',
    80
  );
  
  add_menu_page(
    'IO',
    'Google Analytics',
    'manage_options',
    plugin_dir_path(__FILE__) . 'ga.php',
    null,
    'dashicons-analytics',
    80
  );
} );

# Add admin scripts and styles
add_action('admin_enqueue_scripts', function() {
  wp_register_style( 'io-styles',  plugin_dir_url( __FILE__ ) . 'styles.css' );
  wp_enqueue_style( 'io-styles' );
  
  wp_register_script( 'io-admin',  plugin_dir_url( __FILE__ ) . 'admin.js' );
  wp_enqueue_script( 'io-admin' );
});

# Add admin context menu to posts list
add_filter( 'post_row_actions', function($actions, $post) {
  $actions['io'] =
    '<a href="' . menu_page_url( 'io/dashboard.php', false ). '&p=' . $post->ID . '">' .
      'Engagement' .
    '</a>';
    
  return $actions;
}, 10, 2 );



/*
  Tracking
*/

# Register client tracking
add_action( 'wp_enqueue_scripts', function() {
  global $post;
  
  wp_register_script( 
    'io_track_handle', 
    plugins_url('track.js', __FILE__), 
    array('jquery'),
    false, 
    true 
  );
  
  wp_enqueue_script( 'io_track_handle' );
  
  wp_localize_script( 
    'io_track_handle', 
    'ajax_object', 
    [
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'post_id' => is_singular() ? $post->ID : 0
    ]
  );
});

add_action( "wp_ajax_io_track_depth", "io_wp_ajax_function" );
add_action( "wp_ajax_nopriv_io_track_depth", "io_wp_ajax_function" );

# Tracking AJAX handler
function io_wp_ajax_function(){
  global $table_prefix, $wpdb;

  $rt_table = $table_prefix . 'io_stats_rt';
  $daily_table = $table_prefix . 'io_stats_daily';
  
  $action = 10 + (int)$_POST['depth'];
  $post_id = (int)$_POST['post_id'];
  
  if ( !$post_id ) {
    return;
  }
  
  $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO {$rt_table} SET post_id = %d, ts = unix_timestamp(), action = {$action}",
      $post_id
    )
  );
  
  $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO {$daily_table}
       SET post_id = %d, date = date(now()), action = {$action}, val = 1
       ON DUPLICATE KEY UPDATE val = val + 1",
      $post_id
    )
  );
  
  echo 'ok';
  wp_die();
}
