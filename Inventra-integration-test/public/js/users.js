(function () {
  var page = document.querySelector('.users-page');

  if (!page) {
    return;
  }

  var apiBase = page.getAttribute('data-users-api-base') || 'index.php?url=admin/users';
  var tableBody = document.getElementById('usersTableBody');
  var resultCounter = document.getElementById('usersResultCounter');
  var searchInput = document.getElementById('usersSearchInput');
  var roleFilter = document.getElementById('usersRoleFilter');
  var statusFilter = document.getElementById('usersStatusFilter');
  var modalBackdrop = document.getElementById('usersModalBackdrop');
  var createModal = document.getElementById('createUserModal');
  var editModal = document.getElementById('editUserModal');
  var deleteModal = document.getElementById('deleteUserModal');
  var createForm = document.getElementById('createUserForm');
  var editForm = document.getElementById('editUserForm');
  var deleteLabel = document.getElementById('deleteUserLabel');
  var confirmDelete = document.getElementById('confirmDeleteUser');
  var toastWrap = document.getElementById('usersToastWrap');
  var openCreateButton = document.getElementById('openCreateUserModal');
  var generatePasswordButton = document.getElementById('generateUserPassword');
  var createPasswordInput = document.getElementById('createUserPassword');
  var users = [];
  var deleteUserId = null;
  var allModals = [createModal, editModal, deleteModal];
  var activeModal = null;

  if (
    !modalBackdrop ||
    !createModal ||
    !editModal ||
    !deleteModal ||
    !createForm ||
    !editForm ||
    !deleteLabel ||
    !confirmDelete ||
    !toastWrap ||
    !openCreateButton ||
    !generatePasswordButton ||
    !createPasswordInput
  ) {
    return;
  }

  function setLoading() {
    tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">Loading users...</td></tr>';
  }

  function buildUrl(path, params) {
    var url = apiBase + path;
    if (params) {
      url += (url.indexOf('?') === -1 ? '?' : '&') + params.toString();
    }
    return url;
  }

  function loadUsers() {
    setLoading();

    var params = new URLSearchParams();
    params.set('role', roleFilter.value);
    params.set('status', statusFilter.value);

    fetch(buildUrl('/data', params), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data || !data.success) {
          throw new Error(data && data.message ? data.message : 'Unable to load users.');
        }

        users = Array.isArray(data.users) ? data.users : [];
        renderUsers();
      })
      .catch(function (error) {
        tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">' + escapeHtml(error.message) + '</td></tr>';
        updateCounter(0);
      });
  }

  function renderUsers() {
    var query = (searchInput.value || '').trim().toLowerCase();
    var filtered = users.filter(function (user) {
      if (!query) {
        return true;
      }

      var haystack = [user.full_name, user.username, user.email, user.display_role, user.status].join(' ').toLowerCase();
      return haystack.indexOf(query) !== -1;
    });

    if (!filtered.length) {
      tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No users found.</td></tr>';
      updateCounter(0);
      return;
    }

    tableBody.innerHTML = filtered.map(function (user) {
      var created = user.created_at ? new Date(user.created_at) : null;
      var formattedDate = created && !isNaN(created.getTime())
        ? created.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
        : '-';

      return '' +
        '<tr data-user-id="' + user.id + '">' +
          '<td><strong>' + escapeHtml(user.full_name) + '</strong><div class="muted">' + escapeHtml(user.email) + '</div></td>' +
          '<td>' + escapeHtml(user.username) + '</td>' +
          '<td><span class="users-chip ' + (user.role === 'admin' ? 'users-chip-role-admin' : 'users-chip-role-user') + '">' + escapeHtml(user.display_role) + '</span></td>' +
          '<td><span class="users-chip ' + (user.status === 'active' ? 'users-chip-status-active' : 'users-chip-status-inactive') + '">' + escapeHtml(capitalize(user.status)) + '</span></td>' +
          '<td>' + escapeHtml(formattedDate) + '</td>' +
          '<td class="users-actions-col">' +
            '<div class="users-row-actions">' +
              '<button type="button" class="icon-btn" data-action="toggle" data-user-id="' + user.id + '" title="Toggle Status">Toggle</button>' +
              '<button type="button" class="icon-btn" data-action="edit" data-user-id="' + user.id + '" title="Edit User">Edit</button>' +
              '<button type="button" class="icon-btn icon-btn-danger" data-action="delete" data-user-id="' + user.id + '" title="Delete User">Delete</button>' +
            '</div>' +
          '</td>' +
        '</tr>';
    }).join('');

    updateCounter(filtered.length);
  }

  function updateCounter(count) {
    resultCounter.textContent = 'Showing ' + count + ' result' + (count === 1 ? '' : 's');
  }

  function resetModalState(modal) {
    if (modal === createModal) {
      createForm.reset();
      createPasswordInput.type = 'password';
    }

    if (modal === editModal) {
      editForm.reset();
    }

    if (modal === deleteModal) {
      deleteUserId = null;
      deleteLabel.textContent = '';
    }
  }

  function closeModal(modal) {
    if (!modal) {
      return;
    }

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    resetModalState(modal);

    if (activeModal === modal) {
      activeModal = null;
    }

    if (allModals.every(function (item) { return item.hidden; })) {
      modalBackdrop.hidden = true;
    }
  }

  function closeAllModals() {
    allModals.forEach(function (modal) {
      closeModal(modal);
    });
  }

  function openModal(modal) {
    if (!modal) {
      return;
    }

    closeAllModals();
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    modalBackdrop.hidden = false;
    activeModal = modal;
  }

  function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 'users-toast users-toast-' + type;
    toast.textContent = message;
    toastWrap.appendChild(toast);

    window.setTimeout(function () {
      toast.remove();
    }, 3500);
  }

  function postForm(path, formData) {
    return fetch(buildUrl(path), {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).then(function (response) {
      return response.json();
    });
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function capitalize(value) {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
  }

  openCreateButton.addEventListener('click', function () {
    openModal(createModal);
  });

  modalBackdrop.addEventListener('click', closeAllModals);

  document.querySelectorAll('[data-close-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
      var modalId = button.getAttribute('data-close-modal');
      var modal = document.getElementById(modalId);
      if (modal) {
        closeModal(modal);
      }
    });
  });

  roleFilter.addEventListener('change', loadUsers);
  statusFilter.addEventListener('change', loadUsers);
  searchInput.addEventListener('input', renderUsers);

  generatePasswordButton.addEventListener('click', function () {
    var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    var password = '';

    for (var i = 0; i < 12; i += 1) {
      password += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    createPasswordInput.value = password;
    createPasswordInput.type = 'text';
    window.setTimeout(function () {
      createPasswordInput.type = 'password';
    }, 1200);
  });

  createForm.addEventListener('submit', function (event) {
    event.preventDefault();
    var formData = new FormData(createForm);

    postForm('/create', formData)
      .then(function (data) {
        if (!data || !data.success) {
          throw new Error(data && data.message ? data.message : 'Unable to create user.');
        }

        closeModal(createModal);
        showToast(data.message || 'User created successfully.', 'success');
        loadUsers();
      })
      .catch(function (error) {
        showToast(error.message, 'error');
      });
  });

  editForm.addEventListener('submit', function (event) {
    event.preventDefault();
    var formData = new FormData(editForm);

    postForm('/update', formData)
      .then(function (data) {
        if (!data || !data.success) {
          throw new Error(data && data.message ? data.message : 'Unable to update user.');
        }

        closeModal(editModal);
        showToast(data.message || 'User updated successfully.', 'success');
        loadUsers();
      })
      .catch(function (error) {
        showToast(error.message, 'error');
      });
  });

  confirmDelete.addEventListener('click', function () {
    if (!deleteUserId) {
      return;
    }

    var formData = new FormData();
    formData.append('user_id', deleteUserId);

    postForm('/delete', formData)
      .then(function (data) {
        if (!data || !data.success) {
          throw new Error(data && data.message ? data.message : 'Unable to delete user.');
        }

        deleteUserId = null;
        closeModal(deleteModal);
        showToast(data.message || 'User deleted successfully.', 'success');
        loadUsers();
      })
      .catch(function (error) {
        showToast(error.message, 'error');
      });
  });

  tableBody.addEventListener('click', function (event) {
    var button = event.target.closest('[data-action]');

    if (!button) {
      return;
    }

    var action = button.getAttribute('data-action');
    var userId = parseInt(button.getAttribute('data-user-id') || '0', 10);
    var user = users.find(function (item) { return item.id === userId; });

    if (!user) {
      return;
    }

    if (action === 'edit') {
      editForm.elements.user_id.value = user.id;
      editForm.elements.full_name.value = user.full_name;
      editForm.elements.email.value = user.email;
      editForm.elements.username.value = user.username;
      editForm.elements.role.value = user.role === 'admin' ? 'Admin' : 'User';
      openModal(editModal);
      return;
    }

    if (action === 'delete') {
      deleteUserId = user.id;
      deleteLabel.textContent = user.full_name;
      openModal(deleteModal);
      return;
    }

    if (action === 'toggle') {
      var formData = new FormData();
      formData.append('user_id', user.id);

      postForm('/toggle-status', formData)
        .then(function (data) {
          if (!data || !data.success) {
            throw new Error(data && data.message ? data.message : 'Unable to update status.');
          }

          showToast(data.message || 'User status updated.', 'success');
          loadUsers();
        })
        .catch(function (error) {
          showToast(error.message, 'error');
        });
    }
  });

  allModals.forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && activeModal) {
      closeModal(activeModal);
    }
  });

  closeAllModals();
  loadUsers();
})();
