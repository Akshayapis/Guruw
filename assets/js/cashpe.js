/* Cashpe UI enhancements — presentation only, no business logic. */
(function () {
  'use strict';
  var page = (location.pathname.split('/').pop() || 'index.php').replace(/\.php$/, '').toLowerCase();

  // Highlight the current section in the sidebar.
  document.querySelectorAll('.sidebar-nav-link').forEach(function (link) {
    link.classList.remove('active');
    var target = (link.getAttribute('href') || '').split('/').pop().replace(/\.php$/, '').toLowerCase();
    if (target === page || (page === 'usersupdate' && target === 'users')) {
      link.classList.add('active');
    }
  });

  // A separate control avoids legacy adminx.js toggling the sidebar twice.
  var toggle = document.querySelector('.cp-sidebar-toggle');
  var sidebar = document.querySelector('#cp-sidebar');
  if (toggle && sidebar) {
    function setMenu(open) {
      sidebar.classList.toggle('in', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    }
    toggle.addEventListener('click', function () { setMenu(!sidebar.classList.contains('in')); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setMenu(false); });
    document.addEventListener('click', function (event) {
      if (sidebar.classList.contains('in') && !sidebar.contains(event.target) && !toggle.contains(event.target)) setMenu(false);
    });
  }

  // Lightweight confirmation for dangerous links, if any are marked.
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      if (!window.confirm(el.getAttribute('data-confirm') || 'Are you sure?')) {
        event.preventDefault();
      }
    });
  });

  // Copy helper usable from inline handlers.
  window.cpCopy = function (text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).catch(function () {});
    }
  };
})();
