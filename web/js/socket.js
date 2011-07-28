$(document).ready(function() {
  //var socket = new io.Socket('localhost', {
  var socket = new io.Socket('85.17.138.150', {
    port: 3000,
    rememberTransport: false
  });

  socket.on('connect', function() {});

  socket.on('message', function(message){
    try {
      var obj = jQuery.parseJSON(message);

      /*
         * TODO: add parameter in message, which will keep channel name.
         * There is no other way to connect just to some channels (homepage, category)
         */
        
      if (obj._view_url !== undefined) {

        /*
         * Get current main image data
         */
        var m_title = $('#main_image_title').text();
        var m_src = $('#main_image_img').attr("src");
        var m_link = $('#main_image_link').attr("href");

        var t_src = m_src.replace(/(.*)\/m\/(.*)/, "$1/thumb/$2");

        //alert(m_src);
        //alert(t_src);

        /*
         * Update main image
         */
        var main_image = '<div id="main_image">\
        <header>\
        <!-- div class="category">\
          Category: <a href="#">comics</a>\
        </div -->\
        <ul>\
          <li><h1 id="main_image_title">'+obj.title+'</h1></li>\
        </ul>\
      </header>\
      <figure class="bigImg">\
        <a href="'+obj._view_url+'" id="main_image_link"><img src="'+obj._main_img+'" alt="'+obj.title+'" id="main_image_img" /></a>\
      </figure>';

        main_image += '<section id="horisontalShareSmall">\
  <ul>\
    <li style="width: 70px;">Share this</li>\
    <li>\
      <script type="text/javascript">\
      reddit_url = "'+obj._share_url+'";\
      reddit_title = "'+encodeURIComponent(obj.title)+'";\
      reddit_target=\'pics\';\
      reddit_newwindow=\'1\';\
      </script>\
      <script type="text/javascript" src="http://www.reddit.com/static/button/button1.js"></script>\
    </li>\
    <li>\
      <iframe src="http://www.stumbleupon.com/badge/embed/2/?url='+obj._share_url+'" style="border:none; overflow:hidden; width:74px; height:18px;"></iframe>\
    </li>\
    <li>\
      <a href="http://twitter.com/share" class="twitter-share-button" data-url="'+obj._share_url+'" data-text="'+obj.title+'" data-count="none" data-via="imagepush">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>\
    </li>\
    <li style="width: 55px;">\
      <iframe src="http://www.facebook.com/plugins/like.php?href='+rawurlencode(obj._share_url)+'&amp;layout=button_count&amp;show_faces=false&amp;width=60&amp;font=trebuchet+ms&amp;action=like&amp;colorscheme=light&amp;height=20" style="border:none; overflow:hidden; width:60px; height:20px;"></iframe>\
    </li>\
  </ul>\
</section>';

        main_image += '</div>';
        $('#main_image').replaceWith(main_image);
        $('#main_image_img').hide().fadeIn("slow");

        /*
         * Add small thumb
         */
        var prev_images = '<li>\
        <div class="thumbWrapper">\
          <details class="no_vote">\
            <span class="imgDescription">'+m_title+'</span>\
          </details>\
          <a href="'+m_link+'" title="'+m_title+'" class="imgWrapper">\
          <img src="'+t_src+'" width="140" alt="'+m_title+'" />\
          <span class="imgDescription"><span>'+m_title+'</span></span>\
          </a>\
        </div>\
        </li>';

        $('#prev_images').prepend(prev_images);

        /*
         * Hide/show some items, if main is smaller/larger than before
         */

        $('#main_image_img').load(function () {
          new Imagepush.RecalculateHomepageHeight();
        });

      }

    } catch (ex) {
      console.log(ex);
    }
  }) ;

  socket.on('disconnect', function() {
    //console.log('disconnected');
    $('#main_image').prepend("<b>Disconnected!</b>");
  });

  socket.connect();
});
