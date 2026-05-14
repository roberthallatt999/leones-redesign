/* leones-redesign.js — replaces webflow.js for the redesign.
 *
 * Scope: only what the homepage needs.
 *   - mobile nav toggle (replaces .w-nav)
 *   - touch detection class on <body> (replaces .w-mod-touch shim)
 *   - Slick init for hero carousel (only if the carousel has multiple slides)
 *   - FAQs accordion toggle (replaces Webflow IX2 timelines that were
 *     capped at 5 hardcoded data-w-id values)
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

    // ---- FAQs accordion --------------------------------------------------
    // Each .faqs-accordion contains a .faqs-question (button) and an
    // .extra_cta_answer panel. Animates the panel's actual height so the
    // transition duration matches the content — closing from a fixed
    // max-height (the earlier approach) created a perceptible delay at the
    // start of the close because the value was much larger than the visible
    // content. CSS only handles the transition + easing; JS sets the heights.
    document.querySelectorAll('.faqs-accordion .faqs-question').forEach(function (btn) {
      var accordion = btn.closest('.faqs-accordion');
      if (!accordion) return;
      var answer = accordion.querySelector('.extra_cta_answer');
      if (!answer) return;

      btn.addEventListener('click', function () {
        var willOpen = !accordion.classList.contains('is-open');
        var currentHeight = answer.scrollHeight;

        if (willOpen) {
          // open: 0 → measured content height, then drop the explicit
          // height after the transition so the panel can flex with later
          // content changes (responsive reflow, font loading, etc.).
          answer.style.height = '0px';
          // Force a reflow so the browser registers the start state before
          // we set the target height.
          answer.offsetHeight;  // eslint-disable-line no-unused-expressions
          accordion.classList.add('is-open');
          answer.style.height = currentHeight + 'px';
          answer.addEventListener('transitionend', function onEnd(e) {
            if (e.propertyName !== 'height') return;
            answer.style.height = '';
            answer.removeEventListener('transitionend', onEnd);
          });
        } else {
          // close: explicit current height → 0. Setting the start value
          // explicitly is required because the panel is at `height: auto`
          // when open; the browser can't transition from auto.
          answer.style.height = currentHeight + 'px';
          answer.offsetHeight;  // eslint-disable-line no-unused-expressions
          accordion.classList.remove('is-open');
          answer.style.height = '0px';
        }

        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        answer.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
      });
    });

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
