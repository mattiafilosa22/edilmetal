/* ============================================================
   EDILMETAL — interazioni wireframe (vanilla, accessibili, DRY)
   Referenziato IDENTICO da tutte le pagine con <script defer>.
   - theme toggle chiaro/scuro (persistente)
   - overlay menu full-screen
   - reveal allo scroll (IntersectionObserver) + prefers-reduced-motion
   - Esc per chiudere overlay / drawer
   - drawer filtri (realizzazioni)
   - tab ARIA (scheda progetto)
   - galleria: thumbnail -> immagine grande (demo lightbox)
   ============================================================ */
(function () {
  'use strict';

  /* ---- Theme toggle ---- */
  document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var d = document.documentElement;
      var t = d.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      d.setAttribute('data-theme', t);
      try { localStorage.setItem('theme', t); } catch (e) {}
    });
  });

  /* ---- Overlay menu full-screen ---- */
  var open = document.querySelector('[data-menu-open]');
  var close = document.querySelector('[data-menu-close]');
  if (open) open.addEventListener('click', function () { document.body.classList.add('menu-open'); });
  if (close) close.addEventListener('click', function () { document.body.classList.remove('menu-open'); });
  document.querySelectorAll('.overlay-nav__list a').forEach(function (a) {
    a.addEventListener('click', function () { document.body.classList.remove('menu-open'); });
  });

  /* ---- Drawer filtri (mobile) ---- */
  var fOpen = document.querySelector('[data-filters-open]');
  var fClose = document.querySelector('[data-filters-close]');
  var fBackdrop = document.querySelector('[data-filters-backdrop]');
  function openFilters() { document.body.classList.add('filters-open'); if (fOpen) fOpen.setAttribute('aria-expanded', 'true'); }
  function closeFilters() { document.body.classList.remove('filters-open'); if (fOpen) { fOpen.setAttribute('aria-expanded', 'false'); fOpen.focus(); } }
  if (fOpen) fOpen.addEventListener('click', openFilters);
  if (fClose) fClose.addEventListener('click', closeFilters);
  if (fBackdrop) fBackdrop.addEventListener('click', closeFilters);

  /* ---- Esc: chiude overlay e drawer ---- */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.body.classList.remove('menu-open');
      closeFilters();
    }
  });

  /* ---- Chip filtri: toggle stato attivo (demo) ---- */
  document.querySelectorAll('[data-chip]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var group = chip.getAttribute('data-chip');
      if (group) {
        document.querySelectorAll('[data-chip="' + group + '"]').forEach(function (c) {
          c.setAttribute('aria-pressed', 'false');
        });
      }
      chip.setAttribute('aria-pressed', 'true');
    });
  });

  /* ---- Tab ARIA ---- */
  document.querySelectorAll('[data-tabs]').forEach(function (group) {
    var btns = Array.prototype.slice.call(group.querySelectorAll('[role="tab"]'));
    function select(btn) {
      btns.forEach(function (b) {
        var sel = b === btn;
        b.setAttribute('aria-selected', sel ? 'true' : 'false');
        b.tabIndex = sel ? 0 : -1;
        var panel = document.getElementById(b.getAttribute('aria-controls'));
        if (panel) panel.hidden = !sel;
      });
    }
    btns.forEach(function (btn, i) {
      btn.addEventListener('click', function () { select(btn); });
      btn.addEventListener('keydown', function (e) {
        var idx = i;
        if (e.key === 'ArrowRight') idx = (i + 1) % btns.length;
        else if (e.key === 'ArrowLeft') idx = (i - 1 + btns.length) % btns.length;
        else if (e.key === 'Home') idx = 0;
        else if (e.key === 'End') idx = btns.length - 1;
        else return;
        e.preventDefault();
        btns[idx].focus();
        select(btns[idx]);
      });
    });
  });

  /* ---- Galleria: thumbnail -> immagine grande (demo) ---- */
  var gMain = document.querySelector('[data-gallery-main]');
  if (gMain) {
    document.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        document.querySelectorAll('[data-gallery-thumb]').forEach(function (t) {
          t.setAttribute('aria-current', 'false');
        });
        thumb.setAttribute('aria-current', 'true');
        var label = thumb.getAttribute('data-label') || 'Foto';
        var lab = gMain.querySelector('[data-gallery-label]');
        if (lab) lab.textContent = label;
      });
    });
  }

  /* ---- Rail orizzontali: drag-to-scroll (mouse) + frecce prev/next ----
     Progressive enhancement DRY: vale per ogni .rail (home + correlati scheda).
     Il touch/trackpad già scorre nativamente; qui rendiamo scorrevole e
     scopribile anche con il mouse su desktop. */
  var ARROW =
    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>';
  var reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('.rail').forEach(function (rail) {
    /* drag-to-scroll col puntatore (no touch: già nativo) */
    var down = false, startX = 0, startScroll = 0, moved = false;
    rail.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'touch') return;
      down = true; moved = false; startX = e.clientX; startScroll = rail.scrollLeft;
      rail.classList.add('is-grabbing');
    });
    window.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 4) moved = true;
      rail.scrollLeft = startScroll - dx;
    });
    window.addEventListener('pointerup', function () {
      down = false; rail.classList.remove('is-grabbing');
    });
    /* se ho trascinato, non seguo il link della card */
    rail.addEventListener('click', function (e) {
      if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
    }, true);

    /* frecce di navigazione (iniettate: HTML resta pulito) */
    var nav = document.createElement('div');
    nav.className = 'rail-nav';
    nav.innerHTML =
      '<button type="button" class="rail-arrow rail-arrow--prev" data-dir="-1" aria-label="Scorri indietro">' + ARROW + '</button>' +
      '<button type="button" class="rail-arrow" data-dir="1" aria-label="Scorri avanti">' + ARROW + '</button>';
    rail.parentNode.insertBefore(nav, rail.nextSibling);
    var prev = nav.querySelector('[data-dir="-1"]');
    var next = nav.querySelector('[data-dir="1"]');
    function step() {
      var card = rail.querySelector('.proj');
      return card ? card.getBoundingClientRect().width + 24 : rail.clientWidth * 0.85;
    }
    nav.querySelectorAll('.rail-arrow').forEach(function (b) {
      b.addEventListener('click', function () {
        rail.scrollBy({ left: step() * parseInt(b.getAttribute('data-dir'), 10), behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    });
    function update() {
      var max = rail.scrollWidth - rail.clientWidth - 1;
      nav.hidden = max <= 0;                 /* nasconde se non c'è nulla da scorrere */
      prev.disabled = rail.scrollLeft <= 0;
      next.disabled = rail.scrollLeft >= max;
    }
    rail.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
  });

  /* ---- Reveal allo scroll ---- */
  var els = document.querySelectorAll('.reveal');
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
    els.forEach(function (e) { e.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (en) {
      en.forEach(function (x) {
        if (x.isIntersecting) { x.target.classList.add('in'); io.unobserve(x.target); }
      });
    }, { threshold: .12 });
    els.forEach(function (e) { io.observe(e); });
  }
})();
