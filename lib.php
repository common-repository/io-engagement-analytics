<?php



if ( ! defined( 'ABSPATH' ) ) exit;



/* Stats retrievers */

# returns summary figures for the main dashboard
function io_get_summary($period, $post_id = null) {
  global $table_prefix, $wpdb;
  
  $table = $table_prefix . 'io_stats_daily';
  $selector = 'sum(val) total';
  $date_filter = io_get_date_filter($period);
  $post_filter = $post_id ? ' post_id = ' . intval($post_id)  : ' post_id > 0 ';
  
  $total_views = $wpdb->get_results(
    "SELECT {$selector} FROM {$table}
     WHERE action = 10 AND {$date_filter}"
  )[0]->total;
  
  $total_article_views = $wpdb->get_results(
    "SELECT {$selector} FROM {$table}
     WHERE action = 10 AND {$post_filter} AND {$date_filter}"
  )[0]->total;
  
  $started_reading = $wpdb->get_results(
    "SELECT {$selector} FROM {$table}
     WHERE action = 11 AND {$post_filter} AND {$date_filter}"
  )[0]->total;
  
  $finished_reading = $wpdb->get_results(
    "SELECT {$selector} FROM {$table}
     WHERE action = 15 AND {$post_filter} AND {$date_filter}"
  )[0]->total;
  
  return [
    'total_views' => $total_views ? : 0,
    'total_article_views' => $total_article_views ? : 0,
    'started_reading' => round(100*$started_reading/($total_article_views?:1) ? : 0),
    'finished_reading' => round(100*$finished_reading/($total_article_views?:1) ? : 0),
  ];
}

# return article funnel
function io_get_article_funnel($post_id, $period) {
  global $table_prefix, $wpdb;
  
  $table = $table_prefix . 'io_stats_daily';
  $date_filter = io_get_date_filter($period);
  
  $row = $wpdb->get_results( $wpdb->prepare(
    "SELECT SUM(IF(action = 10, val, 0)) views,
            SUM(IF(action = 11, val, 0)) views_start,
            SUM(IF(action = 12, val, 0)) views_quarter,
            SUM(IF(action = 13, val, 0)) views_half,
            SUM(IF(action = 14, val, 0)) views_tquarter,
            SUM(IF(action = 15, val, 0)) views_end
     FROM {$table}
     WHERE post_id = %d AND {$date_filter}",
     $post_id
  ))[0];
  
  return [
    'views' => $row->views,
    'views_start' => $row->views_start,
    'views_quarter' => $row->views_quarter,
    'views_half' => $row->views_half,
    'views_tquarter' => $row->views_tquarter,
    'views_end' => $row->views_end
  ];
}

# return articles list
function io_get_articles($period) {
  global $table_prefix, $wpdb;
  
  $table = $table_prefix . 'io_stats_daily';
  $date_filter = io_get_date_filter($period);
  
  $result = $wpdb->get_results(
    "SELECT post_id,
            SUM(IF(action = 10, val, 0)) views,
            SUM(IF(action = 11, val, 0)) views_start,
            SUM(IF(action = 12, val, 0)) views_quarter,
            SUM(IF(action = 13, val, 0)) views_half,
            SUM(IF(action = 14, val, 0)) views_tquarter,
            SUM(IF(action = 15, val, 0)) views_end
     FROM {$table}
     WHERE post_id > 0 AND {$date_filter}
     GROUP BY post_id
     ORDER BY views DESC
     LIMIT 100"
  );
  
  $list = [];
  
  foreach ( $result as $row ) {
    $list[] = [
      'views' => $row->views,
      'views_start' => $row->views_start,
      'views_quarter' => $row->views_quarter,
      'views_half' => $row->views_half,
      'views_tquarter' => $row->views_tquarter,
      'views_end' => $row->views_end,
      'title' => get_post($row->post_id)->post_title,
      'post_id' => $row->post_id
    ];
  }
  
  return $list;
}

# get readability trends
function io_get_chart($period) {
  global $table_prefix, $wpdb;
  
  if ( $period == 'today' ) {
    $table = $table_prefix . 'io_stats_rt';
    
    $wpdb->query("DELETE FROM {$table} WHERE ts < UNIX_TIMESTAMP() - 60*60*24 - 1");
    
    $result = $wpdb->get_results(
      "SELECT from_unixtime(round(ts/3600)*3600) as date,
              SUM(IF(action = 10, 1, 0)) views,
              SUM(IF(post_id > 0 AND action = 10, 1, 0)) article_views,
              SUM(IF(post_id > 0 AND action = 11, 1, 0)) views_start,
              SUM(IF(post_id > 0 AND action = 15, 1, 0)) views_end
       FROM {$table}
       WHERE date(from_unixtime(ts)) = date(now())
       GROUP BY date
       ORDER BY date DESC"
    );
    
    $chart = [];
  
    foreach ( $result as $row ) {
      $chart[$row->date] = [
        'date' => $row->date,
        'views' => $row->views,
        'article_views' => $row->article_views,
        'views_start' => $row->views_start,
        'views_end' => $row->views_end
      ];
      
      $max = max($row->article_views, $max);
    }
    
    foreach ( $chart as $date => &$figures ) {
      $figures['total'] = round(100*$figures['article_views']/$max);
      $figures['started'] = round(100*$figures['views_start']/($figures['article_views']?:1));
      $figures['finished'] = round(100*$figures['views_end']/($figures['article_views']?:1));
    }
    
    
    $begin = new DateTime( 'today' );
    $end   = new DateTime( '23:59:59' );
    
    $normalized = [];
    for($i = $begin; $i <= $end; $i->modify('+1 hour')){
        $normalized[$i->format('Y-m-d H:i:s')] = $chart[$i->format('Y-m-d H:i:s')];
        $normalized[$i->format('Y-m-d H:i:s')]['date'] = $i->format('H:i');
        $normalized[$i->format('Y-m-d H:i:s')]['future'] = $i->format('H') > date('H');
        $normalized[$i->format('Y-m-d H:i:s')]['now'] = $i->format('H') == date('H');
    }
  }
  else {
    $table = $table_prefix . 'io_stats_daily';
    $date_filter = io_get_date_filter($period);
    
    $result = $wpdb->get_results(
      "SELECT date,
              SUM(IF(action = 10, val, 0)) views,
              SUM(IF(post_id > 0 AND action = 10, val, 0)) article_views,
              SUM(IF(post_id > 0 AND action = 11, val, 0)) views_start,
              SUM(IF(post_id > 0 AND action = 15, val, 0)) views_end
       FROM {$table}
       WHERE {$date_filter}
       GROUP BY date
       ORDER BY date DESC"
    );
    
    $chart = [];
  
    foreach ( $result as $row ) {
      $chart[$row->date] = [
        'date' => $row->date,
        'views' => $row->views,
        'article_views' => $row->article_views,
        'views_start' => $row->views_start,
        'views_end' => $row->views_end,
      ];
      
      $max = max($row->views, $max);
    }
    
    foreach ( $chart as $date => &$figures ) {
      $figures['total'] = round(100*$figures['views']/$max);
      $figures['started'] = round(100*$figures['views_start']/($figures['views']?:1));
      $figures['finished'] = round(100*$figures['views_end']/($figures['views']?:1));
    }
    
    
    $begin = new DateTime( $period == 'week' ? '1 week ago' : '1 month ago' );
    $end   = new DateTime();
    
    $normalized = [];
    for($i = $begin; $i <= $end; $i->modify('+1 day')){
        $normalized[$i->format('Y-m-d')] = $chart[$i->format('Y-m-d')];
        $normalized[$i->format('Y-m-d')]['date'] = $i->format('m/d');
    }
  }
  
  return $normalized;
}



/* Utilities */

# returns time filter for rt or daily table
function io_get_date_filter($period) {
  if ( $period == 'today' ) {
    return 'date = DATE(NOW())';
  }
  else if ( $period == 'week' ) {
    return 'date >= DATE_SUB(DATE(NOW()), INTERVAL 1 WEEK)';
  }
  else {
    return 'date >= DATE_SUB(DATE(NOW()), INTERVAL 1 MONTH)';
  }
}

# IO funnel step class HTML helper
function io_funnel_step_class($value, $step) {
  $goods = [
    'start' => 70,
    'quarter' => 60,
    'half' => 50,
    'tquarter' => 40,
    'end' => 30,
  ];
  
  $class = '';
  
  if ( $value < $goods[$step] ) $class = 'norm';
  if ( $value < $goods[$step] * 0.8 ) $class = 'avg';
  if ( $value < $goods[$step] * 0.5 ) $class = 'bad';
  
  return $class;
}

# IO funnel step comment for human
function io_funnel_step_comment($value, $step) {
  $goods = [
    'start' => 70,
    'quarter' => 60,
    'half' => 50,
    'tquarter' => 40,
    'end' => 30,
  ];
  
  $comment = 'Good';
  
  if ( $value < $goods[$step] ) $comment = 'Almost good';
  if ( $value < $goods[$step] * 0.8 ) $comment = 'Average';
  if ( $value < $goods[$step] * 0.5 ) $comment = 'Poor';
  
  return $comment;
}