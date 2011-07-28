// Cufon
/*
$.ajaxSetup({async: false});
$.getScript('js/font/MyriadRegular_400.font.js');
$.getScript('js/font/MyriadItalic_italic_400.font.js');
$.getScript('js/font/MyriadCond_400.font.js');
$.getScript('js/font/MyriadSemibold_600.font.js');
$.getScript('js/font/MyriadBold_700.font.js');
$.ajaxSetup({async: true});

Cufon.replace(".cufon-mr", { fontFamily: "MyriadRegular" });
Cufon.replace(".cufon-mi", { fontFamily: "MyriadItalic" });
Cufon.replace(".cufon-mc", { fontFamily: "MyriadCond" });
Cufon.replace(".cufon-ms", { fontFamily: "MyriadSemibold" });
Cufon.replace(".cufon-mb", { fontFamily: "MyriadBold" });
Cufon.now();
*/

// Var
var IE = (navigator.appVersion.indexOf("MSIE") == -1) ? false : true;

// Object
if (!Imagepush) var Imagepush = {};

// Public
function XXX(){
// code
}

/*
 * Src: https://github.com/kvz/phpjs/raw/master/functions/url/rawurlencode.js
 **/
function rawurlencode (str) {
  str = (str + '').toString();
  return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A');
}

// Share
Imagepush.Share = function() {
  /*
  var verticalShare = jQuery("#verticalShare");
  var horisontalShare = jQuery("#horisontalShare");
  var bodyContainer = jQuery("#body");

  if (bodyContainer.offset().left > verticalShare.width()) {
    verticalShare.css("left", bodyContainer.offset().left).show();
    horisontalShare.hide();
  }
  else {
    verticalShare.hide();
    horisontalShare.show();
  }
  jQuery(document).scrollTop(jQuery(document).scrollTop() + 1);
  jQuery(document).scrollTop(jQuery(document).scrollTop() - 1);
  */
}

// Close box icon
Imagepush.Close = function(selector) {
  var handler = jQuery(selector);
  handler.live("click", function() {
    var tempHeight = jQuery(this).parent().height() + 30;
    jQuery(this).parent().slideUp("slow");
    jQuery(this).parents(".pp_pic_holder").find(".pp_content").animate({
      "height": "-=" + tempHeight + "px"
    }, "slow");
  });
}

// Like
Imagepush.Like = function(image_id) {
  //alert(image_id);

  $.ajax({
    url: "/vote",
    type: "POST",
    data: "vote=up&id="+image_id,
    context: document.body,
    success: function(msg){
     //alert( "Data Saved: " + msg );
   }
 });

}

// Close box icon
Imagepush.Dislike = function(image_id) {

  $.ajax({
    url: "/vote",
    type: "POST",
    data: "vote=down&id="+image_id,
    context: document.body,
    success: function(msg){
     $("#item-"+image_id).slideUp("fast");
   }
 });
}

// Flag image as inappropriate
Imagepush.Flag = function(image_id) {

  $.ajax({
    url: "/flag",
    type: "POST",
    data: "flag=1&id="+image_id,
    context: document.body,
    success: function(msg){
     $("#item-"+image_id).slideUp("fast");
   }
 });
}

// Recalculate height after new object arrives
Imagepush.RecalculateHomepageHeight = function() {

  var left_extra_height = 40;   // share buttons
  var right_extra_height = 0;  // "view more images" button
  var right_thumb_height = 155; // thumb box

  var main_image_height = $('#main_image_img').height();

  var left_height = left_extra_height + main_image_height;
  var right_height = right_extra_height;
  var prev_items = $('#prev_images li').length; //:visible

  //alert(prev_items);
  var last_i = 0;

  for(i=0;i<prev_items;i++) {
    right_height += right_thumb_height;
    //alert("i="+i+"; left: "+left_height+"; right: "+right_height);
    if (left_height < right_height) {
      $("#prev_images li:gt("+(i-1)+")").hide();
      last_i = i;
      break;
    }
  }

  // show all above hidden
  if (last_i == 0) { // left_height > right_height
    var left_thumbs = Math.floor((left_height - right_extra_height) / right_thumb_height);
    var prev_items_to_show = (left_thumbs < prev_items ? left_thumbs : prev_items);
    //alert("last_i="+last_i+"; left: "+left_thumbs+"; right: "+prev_items+"; prev_items_to_show: "+prev_items_to_show);

    $("#prev_images li:lt("+(prev_items_to_show)+")").show();
    $("#prev_images li:gt("+(prev_items_to_show-1)+")").hide();
  } else {
    $("#prev_images li:lt("+(last_i)+")").show();
  }

//alert("last_i="+last_i+"; left: "+left_height+"; right: "+right_height);
//alert(right_height+" == "+main_image_height);

}

jQuery(function() {
  //new Imagepush.Popup("a[rel^='prettyPhoto']");
  new Imagepush.Share();
  new Imagepush.RecalculateHomepageHeight();
  new Imagepush.Close(".close");
  //new Imagepush.DislikeClose(".close");
  //new Imagepush.Like();
  jQuery(window).resize(function(){
    new Imagepush.Share();
  });
  $('#main_image_img').load(function () {
    new Imagepush.RecalculateHomepageHeight();
  });

  $("img").lazyload({
    effect      : "fadeIn"
  });

});

function recordOutboundLink(link, category, action) {
  _gat._getTrackerByName()._trackEvent(category, action);
  setTimeout('document.location = "' + link.href + '"', 100);
}
