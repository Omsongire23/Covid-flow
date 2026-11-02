/* Editorial interaction layer: restrained motion, count-up figures, and accessible navigation state. */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var header = document.getElementById('siteHeader');

  function updateHeader() {
    if (header) header.classList.toggle('scrolled', window.scrollY > 12);
  }
  window.addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();

  var revealItems = document.querySelectorAll('.dash-section-header, .dash-resource-card, .dash-table-wrap, .dash-callout-card, .page-title-block');
  if (!reduced && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal', 'is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px' });
    revealItems.forEach(function (item, index) {
      item.classList.add('reveal');
      item.style.transitionDelay = Math.min(index * 28, 180) + 'ms';
      observer.observe(item);
    });
  } else {
    revealItems.forEach(function (item) { item.classList.add('is-visible'); });
  }

  document.querySelectorAll('.dash-stat-value').forEach(function (el) {
    var raw = el.textContent.trim().replace(/,/g, '');
    var target = Number(raw);
    if (reduced || !Number.isFinite(target) || target <= 0) return;
    var start = 0;
    var started = false;
    var animate = function () {
      if (started) return;
      started = true;
      var duration = 900;
      var startTime = performance.now();
      var step = function (now) {
        var progress = Math.min((now - startTime) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + (target - start) * eased).toLocaleString();
        if (progress < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    if ('IntersectionObserver' in window) {
      var counterObserver = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) { animate(); counterObserver.disconnect(); }
      }, { threshold: 0.7 });
      counterObserver.observe(el);
    } else animate();
  });

  document.querySelectorAll('.dash-resource-card').forEach(function (card) {
    card.addEventListener('mouseenter', function () { card.setAttribute('data-hovered', 'true'); });
    card.addEventListener('mouseleave', function () { card.removeAttribute('data-hovered'); });
  });
})();
