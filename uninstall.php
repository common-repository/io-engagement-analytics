<?php


if ( ! defined( 'ABSPATH' ) ) exit;


/* Clean/remove plugin */



# make sire this is called by WP
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
  die('Forbidden');
}



# Delete tables
global $wpdb;

$table_name = $wpdb->prefix . 'io_stats_daily';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

$table_name = $wpdb->prefix . 'io_stats_rt';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");