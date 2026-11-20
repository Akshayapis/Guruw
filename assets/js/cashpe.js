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


  // Turn the legacy statement tables into readable cards on phones.
  // Labels come from the table's own header row, so no PHP markup changes.
  function prepareTables() {
    document.querySelectorAll('table').forEach(function (table) {
      if (table.closest('.modal') || table.dataset.cpReady) return;
      var theads = table.querySelectorAll('thead');
      if (!theads.length) return;
      var labels = null, tools = null;
      for (var i = theads.length - 1; i >= 0; i--) {
        var head = theads[i];
        if (head.querySelector('input, select, textarea, button')) {
          if (!tools) tools = head;
        } else if (!labels) {
          labels = head;
        }
      }
      if (labels) labels.classList.add('cp-labels');
      var bar = null;
      if (tools) {
        bar = document.createElement('div');
        bar.className = 'cp-table-toolbar';
        tools.querySelectorAll('th, td').forEach(function (cell) {
          if (!cell.querySelector('input, select, textarea, button, a')) return;
          var field = document.createElement('div');
          field.className = 'cp-tool-field';
          if (cell.hasAttribute('colspan')) field.className += ' cp-tool-wide';
          if (cell.querySelector('.input-group')) field.className += ' cp-tool-search';
          field.innerHTML = cell.innerHTML;
          bar.appendChild(field);
        });
        tools.parentNode.removeChild(tools);
      }
      if (!labels) return;
      var names = Array.prototype.map.call(labels.querySelectorAll('th'), function (th) {
        return (th.textContent || '').replace(/\s+/g, ' ').trim();
      });
      table.querySelectorAll('tbody tr').forEach(function (row) {
        Array.prototype.forEach.call(row.children, function (cell, index) {
          if (cell.tagName !== 'TD') return;
          if (!cell.getAttribute('data-label') && names[index]) cell.setAttribute('data-label', names[index]);
          if (cell.querySelector('button, form, input, select, textarea, .btn')) cell.classList.add('cp-td-actions');
        });
      });
      var wrapper = table.closest('.table-responsive, .table-responsive-md, .cp-table-wrap');
      if (wrapper) {
        wrapper.classList.add('cp-table-wrap');
      } else {
        wrapper = document.createElement('div');
        wrapper.className = 'cp-table-wrap';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
      }
      var card = wrapper.closest('.cp-table-card');
      if (!card) {
        card = document.createElement('div');
        card.className = 'cp-table-card';
        wrapper.parentNode.insertBefore(card, wrapper);
        card.appendChild(wrapper);
      }
      if (bar) card.insertBefore(bar, card.firstChild);
      var body = table.tBodies && table.tBodies[0];
      if (body && !body.rows.length && !card.querySelector('.cp-empty')) {
        var empty = document.createElement('div');
        empty.className = 'cp-empty';
        empty.innerHTML = '<i class="fas fa-inbox"></i>No records to show yet. Payments will appear here automatically.';
        card.appendChild(empty);
      }
      table.dataset.cpReady = '1';
    });
  }
  prepareTables();
  if (document.readyState !== 'complete') window.addEventListener('load', prepareTables);
  if (window.MutationObserver) {
    new MutationObserver(function () { prepareTables(); }).observe(document.body, { childList: true, subtree: true });
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
