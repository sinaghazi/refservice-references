(function () {
  function initCarousel(wrapper) {
    var track = wrapper.querySelector('.refservice-carousel-track');
    var prevBtn = wrapper.querySelector('.refservice-carousel-prev');
    var nextBtn = wrapper.querySelector('.refservice-carousel-next');
    if (!track) return;

    var slides = track.querySelectorAll('.refservice-carousel-slide');
    if (slides.length === 0) return;

    var isRtl = window.getComputedStyle(track).direction === 'rtl';

    function getVisibleCount() {
      var slideWidth = slides[0].offsetWidth;
      if (slideWidth === 0) return 1;
      return Math.round(track.offsetWidth / slideWidth) || 1;
    }

    function getScrollAmount() {
      return slides[0].offsetWidth;
    }

    function updateButtons() {
      if (!prevBtn || !nextBtn) return;
      // Use the absolute value so boundary detection works on RTL pages,
      // where browsers report scrollLeft as negative or inverted.
      var scrollLeft = Math.abs(Math.round(track.scrollLeft));
      var maxScroll = track.scrollWidth - track.offsetWidth;
      prevBtn.disabled = scrollLeft <= 1;
      nextBtn.disabled = scrollLeft >= maxScroll - 1;
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        var amount = isRtl ? getScrollAmount() : -getScrollAmount();
        track.scrollBy({ left: amount, behavior: 'smooth' });
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        var amount = isRtl ? -getScrollAmount() : getScrollAmount();
        track.scrollBy({ left: amount, behavior: 'smooth' });
      });
    }

    track.addEventListener('scroll', updateButtons, { passive: true });
    updateButtons();

    // Re-check after fonts/images load
    window.addEventListener('load', updateButtons);

    // Recalculate button state when widths change on resize (debounced)
    var resizeTimer = null;
    window.addEventListener('resize', function () {
      if (resizeTimer !== null) {
        window.clearTimeout(resizeTimer);
      }
      resizeTimer = window.setTimeout(function () {
        resizeTimer = null;
        updateButtons();
      }, 150);
    });
  }

  function initAll() {
    var wrappers = document.querySelectorAll('.refservice-carousel-wrapper');
    for (var i = 0; i < wrappers.length; i++) {
      initCarousel(wrappers[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
