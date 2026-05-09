/* leones-redesign.js — replaces webflow.js for the redesign.
 *
 * Scope: only what the homepage needs.
 *   - mobile nav toggle (replaces .w-nav)
 *   - touch detection class on <body> (replaces .w-mod-touch shim)
 *   - Slick init for hero carousel (only if the carousel has multiple slides)
 *
 * Other-page behaviors (.w-dropdown, .w-tab, .w-lightbox) are added as those
 * pages get ported. Don't preempt — keep this small.
 */
(function ($) {
  'use strict';

  // ---- Touch detection ---------------------------------------------------
  if ('ontouchstart' in window || (window.DocumentTouch && document instanceof window.DocumentTouch)) {
    document.documentElement.classList.add('is-touch');
  }

  $(function () {
    // ---- Mobile nav toggle -----------------------------------------------
    var $navButton = $('.nav__menu-button');
    var $navMenu   = $('.nav__menu');
    if ($navButton.length && $navMenu.length) {
      $navButton.attr('aria-expanded', 'false').on('click', function () {
        var open = $navMenu.toggleClass('is-open').hasClass('is-open');
        $navButton.attr('aria-expanded', open ? 'true' : 'false')
                  .toggleClass('is-active', open);
      });
    }

    // ---- Hero carousel via Slick -----------------------------------------
    // Only init if the carousel has 2+ slides; a single slide doesn't need a
    // slider. The hero container uses .hero__carousel; per-slide is .hero__slide.
    var $hero = $('.hero__carousel');
    if ($hero.length && $hero.find('.hero__slide').length > 1 && typeof $.fn.slick === 'function') {
      $hero.slick({
        autoplay: true,
        autoplaySpeed: 5000,
        arrows: false,
        dots: true,
        fade: true,
        speed: 800
      });
    }
  });
})(window.jQuery);
