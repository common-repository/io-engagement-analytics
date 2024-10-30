jQuery(document).ready( function($){
  
  /* Initially track view */
  $.ajax({
    url: ajax_object.ajaxurl,
    type: 'POST',
    data:{ 
      action: 'io_track_depth',
      depth: 0,
      post_id: ajax_object.post_id
    }
  });
  
  
  /* Now track scroll progress */
  var tracked = {};
  $(document).scroll(function() {
    
    if ( $(document).scrollTop() > 50 ) {
      var depth = Math.round(5 * ($(document).scrollTop() + $(window).height())/$(document).height());
      
      if ( !tracked[depth] ) {
        tracked[depth] = true;
        
        $.ajax({
          url: ajax_object.ajaxurl,
          type: 'POST',
          data:{ 
            action: 'io_track_depth',
            depth: depth,
            post_id: ajax_object.post_id
          }
        });
      }
    }
  });
});