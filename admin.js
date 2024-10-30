// Chart previewer - hover data
function io_chart_preview(el) {
  document.querySelector('.figure .total_views b').innerText = el.dataset.views || '?';
  document.querySelector('.figure .total_article_views b').innerText = el.dataset.article_views || '?';
  document.querySelector('.figure .started_reading b').innerText = (el.dataset.started_reading || '?') + '%';
  document.querySelector('.figure .finished_reading b').innerText = (el.dataset.finished_reading || '?') + '%';
}

// Chart previewer - unhover data
function io_chart_unpreview(el) {
  document.querySelector('.figure .total_views b').innerText =
    document.querySelector('.figure .total_views').dataset.value;
  document.querySelector('.figure .total_article_views b').innerText =
    document.querySelector('.figure .total_article_views').dataset.value;
  document.querySelector('.figure .started_reading b').innerText =
    document.querySelector('.figure .started_reading').dataset.value + '%';
  document.querySelector('.figure .finished_reading b').innerText =
    document.querySelector('.figure .finished_reading').dataset.value + '%';
}



// General load handler
window.onload = function() {
  
  // Scale post content for funnel page
  if ( document.querySelector('.io-content') ) {
    var content_container = 500;
    var content = document.querySelector('.io-content .in').offsetHeight;
    var scale = content_container/content;
 
    document.querySelector('.io-content .in').style.transform = 'scaleY(' + scale + ')';
    document.querySelector('.io-content .in').style.height = '1px';
  }
}