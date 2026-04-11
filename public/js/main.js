var notifBtn = document.getElementById('notifBtn');
var notifDrop = document.getElementById('notifDropdown');
var notifMarkAll = document.getElementById('notifMarkAll');
var notifDot = document.getElementById('notifDot');

if (notifBtn && notifDrop) {
  function closeNotifications() {
    notifDrop.classList.remove('open');
    notifBtn.setAttribute('aria-expanded', 'false');
  }

  function openNotifications() {
    notifDrop.classList.add('open');
    notifBtn.setAttribute('aria-expanded', 'true');
  }

  notifBtn.addEventListener('click', function(e) {
    e.stopPropagation();

    if (notifDrop.classList.contains('open')) {
      closeNotifications();
      return;
    }

    openNotifications();
  });

  document.addEventListener('click', function(event) {
    if (!notifDrop.contains(event.target) && !notifBtn.contains(event.target)) {
      closeNotifications();
    }
  });

  notifDrop.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeNotifications();
    }
  });

  if (notifMarkAll) {
    notifMarkAll.addEventListener('click', function () {
      var url = notifDrop.getAttribute('data-mark-read-url');

      if (!url || notifMarkAll.disabled) {
        return;
      }

      notifMarkAll.disabled = true;

      fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (!data || !data.success) {
            notifMarkAll.disabled = false;
            return;
          }

          document.querySelectorAll('.notif-popover__item').forEach(function (item) {
            item.classList.remove('is-unread');
            item.classList.add('is-read');
          });

          if (notifDot) {
            notifDot.classList.add('is-hidden');
          }
        })
        .catch(function () {
          notifMarkAll.disabled = false;
        });
    });
  }
}


var gSearch = document.getElementById('globalSearch');

if (gSearch) {
  gSearch.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.data-table tbody tr').forEach(function(row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

}

