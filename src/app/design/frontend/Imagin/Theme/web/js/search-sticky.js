define(['jquery'], function ($) {
  'use strict';
  return function (config) {
    var $bar = $(config.barSelector || '.finder');
    var $anchor = $(config.anchorSelector || '.home-hero');
    var offsetTop = parseInt(config.offset || 16, 10);
    if (!$bar.length || !$anchor.length) return;

    function threshold() {
      var rect = $anchor[0].getBoundingClientRect();
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      return rect.top + scrollTop + $anchor.outerHeight();
    }
    var stickPoint = 0;
    function recalc(){ stickPoint = threshold(); toggle(); }
    function toggle(){
      var y = window.pageYOffset || document.documentElement.scrollTop;
      if (y >= stickPoint - offsetTop) $bar.addClass('is-sticky');
      else $bar.removeClass('is-sticky');
    }
    recalc(); $(window).on('scroll', toggle); $(window).on('resize', recalc);
  };
});
