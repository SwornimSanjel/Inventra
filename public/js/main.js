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

  function unreadItems() {
    return Array.prototype.slice.call(notifDrop.querySelectorAll('.notif-popover__item.is-unread'));
  }

  // Keep the red dot, aria-label, and "mark all" button in sync after reads.
  function syncNotificationState() {
    var unreadCount = unreadItems().length;

    notifBtn.setAttribute(
      'aria-label',
      unreadCount > 0 ? unreadCount + ' unread notifications' : 'Notifications'
    );

    if (notifDot) {
      notifDot.classList.toggle('is-hidden', unreadCount === 0);
    }

    if (notifMarkAll) {
      notifMarkAll.disabled = unreadCount === 0;
    }
  }

  function markItemRead(item) {
    if (!item) {
      return;
    }

    item.classList.remove('is-unread', 'is-clickable');
    item.classList.add('is-read');
    item.removeAttribute('role');
    item.removeAttribute('tabindex');
    item.removeAttribute('aria-label');
    item.removeAttribute('data-loading');
  }

  function postNotification(url, payload) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new URLSearchParams(payload).toString()
    }).then(function (response) {
      return response.json();
    });
  }

  function markSingleNotification(item) {
    var url = notifDrop.getAttribute('data-mark-one-url');
    var notificationId = item ? item.getAttribute('data-notification-id') : '';

    if (!item || !url || !notificationId || item.classList.contains('is-read') || item.getAttribute('data-loading') === 'true') {
      return;
    }

    item.setAttribute('data-loading', 'true');

    postNotification(url, { notification_id: notificationId })
      .then(function (data) {
        if (!data || !data.success) {
          item.removeAttribute('data-loading');
          return;
        }

        markItemRead(item);
        syncNotificationState();
      })
      .catch(function () {
        item.removeAttribute('data-loading');
      });
  }

  notifBtn.addEventListener('click', function (e) {
    e.stopPropagation();

    if (notifDrop.classList.contains('open')) {
      closeNotifications();
      return;
    }

    openNotifications();
  });

  document.addEventListener('click', function (event) {
    if (!notifDrop.contains(event.target) && !notifBtn.contains(event.target)) {
      closeNotifications();
    }
  });

  notifDrop.addEventListener('click', function (e) {
    e.stopPropagation();

    var item = e.target.closest('.notif-popover__item.is-unread');
    if (item) {
      markSingleNotification(item);
    }
  });

  notifDrop.addEventListener('keydown', function (event) {
    var item = event.target.closest('.notif-popover__item.is-unread');

    if (!item) {
      return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      markSingleNotification(item);
    }
  });

  document.addEventListener('keydown', function (event) {
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

      postNotification(url, {})
        .then(function (data) {
          if (!data || !data.success) {
            syncNotificationState();
            return;
          }

          document.querySelectorAll('.notif-popover__item').forEach(function (item) {
            markItemRead(item);
          });

          syncNotificationState();
        })
        .catch(function () {
          syncNotificationState();
        });
    });
  }

  syncNotificationState();
}

var gSearch = document.getElementById('globalSearch');

if (gSearch) {
  gSearch.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.data-table tbody tr').forEach(function (row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
