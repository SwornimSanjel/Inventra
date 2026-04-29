(function () {
  var page = document.querySelector('.settings-page');
  var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-settings-tab]'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('[data-settings-panel]'));

  if (!tabs.length || !panels.length) {
    return;
  }

  var activeTabName = page ? page.getAttribute('data-active-tab') || 'profile' : 'profile';

  function activateTab(name) {
    activeTabName = name;

    tabs.forEach(function (tab) {
      var active = tab.getAttribute('data-settings-tab') === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-settings-panel') === name);
    });
  }

  function setError(field, message) {
    if (!field || !field.id) {
      return;
    }

    field.classList.toggle('is-invalid', Boolean(message));

    var node = document.querySelector('[data-error-for="' + field.id + '"]');
    if (node) {
      node.textContent = message || '';
    }
  }

  function validateTextField(field) {
    if (!field || field.readOnly) {
      return true;
    }

    var value = (field.value || '').trim();
    var label = field.getAttribute('data-label') || 'This field';

    if (field.hasAttribute('required') && value === '') {
      setError(field, label + ' is required.');
      return false;
    }

    if (field.getAttribute('data-validate') === 'email' && value !== '') {
      var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(value)) {
        setError(field, 'Please enter a valid email address.');
        return false;
      }
    }

    setError(field, '');
    return true;
  }

  function validateSecurityForm(form) {
    var current = form.querySelector('[name="current_password"]');
    var next = form.querySelector('[name="new_password"]');
    var confirm = form.querySelector('[name="confirm_password"]');
    var currentValue = current ? current.value.trim() : '';
    var nextValue = next ? next.value : '';
    var confirmValue = confirm ? confirm.value : '';
    var hasValue = currentValue !== '' || nextValue !== '' || confirmValue !== '';
    var valid = true;

    if (!hasValue) {
      setError(current, '');
      setError(next, '');
      setError(confirm, '');
      return true;
    }

    if (current && currentValue === '') {
      setError(current, 'Old password is required.');
      valid = false;
    } else {
      setError(current, '');
    }

    if (next && nextValue.trim() === '') {
      setError(next, 'New password is required.');
      valid = false;
    } else if (next && nextValue.length < 8) {
      setError(next, 'Use at least 8 characters.');
      valid = false;
    } else if (next && !/[^A-Za-z0-9]/.test(nextValue)) {
      setError(next, 'Use at least one special character.');
      valid = false;
    } else {
      setError(next, '');
    }

    if (confirm && confirmValue.trim() === '') {
      setError(confirm, 'Please confirm your new password.');
      valid = false;
    } else if (nextValue !== confirmValue) {
      setError(confirm, 'Passwords do not match.');
      valid = false;
    } else {
      setError(confirm, '');
    }

    return valid;
  }

  function togglePasswordFields(form, forceOpen) {
    var fields = form.querySelector('[data-password-fields]');
    var trigger = form.querySelector('[data-password-expand]');
    var nextOpen = typeof forceOpen === 'boolean' ? forceOpen : !(fields && fields.classList.contains('is-open'));

    if (fields) {
      fields.classList.toggle('is-open', nextOpen);
    }

    if (trigger) {
      trigger.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
      trigger.textContent = nextOpen ? 'Hide Password Form' : 'Change Password';
    }
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      activateTab(tab.getAttribute('data-settings-tab'));
    });
  });

  activateTab(activeTabName);

  Array.prototype.slice.call(document.querySelectorAll('.settings-password-toggle')).forEach(function (button) {
    button.addEventListener('click', function () {
      var input = button.parentElement.querySelector('input');

      if (!input) {
        return;
      }

      var visible = input.type === 'password';
      input.type = visible ? 'text' : 'password';
      button.classList.toggle('is-visible', visible);
      button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    });
  });

  Array.prototype.slice.call(document.querySelectorAll('[data-password-expand]')).forEach(function (button) {
    var form = button.closest('form');
    if (!form) {
      return;
    }

    button.addEventListener('click', function () {
      togglePasswordFields(form);
    });

    togglePasswordFields(form, form.getAttribute('data-security-expanded') === 'true');
  });

  var avatarInput = document.getElementById('userSettingsAvatarInput');
  var avatarPreview = document.getElementById('userSettingsAvatarPreview');
  var avatarFileName = document.getElementById('userSettingsAvatarFileName');
  var avatarFallback = document.getElementById('userSettingsAvatarFallback');

  Array.prototype.slice.call(document.querySelectorAll('[data-upload-trigger]')).forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.getAttribute('data-upload-trigger');
      var input = targetId ? document.getElementById(targetId) : null;
      if (input) {
        input.click();
      }
    });
  });

  if (avatarInput) {
    avatarInput.addEventListener('change', function () {
      var file = avatarInput.files && avatarInput.files[0] ? avatarInput.files[0] : null;

      if (!file) {
        if (avatarFileName) {
          avatarFileName.textContent = '';
        }
        return;
      }

      if (avatarFileName) {
        avatarFileName.textContent = file.name;
      }

      if (avatarPreview && window.URL && typeof window.URL.createObjectURL === 'function') {
        avatarPreview.hidden = false;
        avatarPreview.src = window.URL.createObjectURL(file);
        if (avatarFallback) {
          avatarFallback.hidden = true;
        }
      }
    });
  }

  Array.prototype.slice.call(document.querySelectorAll('.settings-panel')).forEach(function (form) {
    if (form.tagName !== 'FORM') {
      return;
    }

    Array.prototype.slice.call(form.querySelectorAll('.settings-input')).forEach(function (field) {
      if (field.readOnly) {
        return;
      }

      field.addEventListener('blur', function () {
        validateTextField(field);
        if (form.getAttribute('data-settings-panel') === 'security') {
          validateSecurityForm(form);
        }
      });

      field.addEventListener('input', function () {
        if (field.classList.contains('is-invalid')) {
          validateTextField(field);
        }

        if (form.getAttribute('data-settings-panel') === 'security') {
          validateSecurityForm(form);
        }
      });
    });

    form.addEventListener('reset', function () {
      window.setTimeout(function () {
        Array.prototype.slice.call(form.querySelectorAll('.settings-input')).forEach(function (field) {
          setError(field, '');
          if (field.hasAttribute('data-password-field')) {
            field.type = 'password';
          }
        });

        Array.prototype.slice.call(form.querySelectorAll('.settings-password-toggle')).forEach(function (button) {
          button.classList.remove('is-visible');
          button.setAttribute('aria-label', 'Show password');
        });

        if (form.getAttribute('data-settings-panel') === 'security') {
          togglePasswordFields(form, false);
        }
      }, 0);
    });

    form.addEventListener('submit', function (event) {
      var valid = true;

      if (form.getAttribute('data-settings-panel') === 'security') {
        togglePasswordFields(form, true);
      }

      Array.prototype.slice.call(form.querySelectorAll('.settings-input')).forEach(function (field) {
        if (!validateTextField(field)) {
          valid = false;
        }
      });

      if (form.getAttribute('data-settings-panel') === 'security' && !validateSecurityForm(form)) {
        valid = false;
      }

      if (!valid) {
        event.preventDefault();
        var firstInvalid = form.querySelector('.settings-input.is-invalid');
        if (firstInvalid) {
          firstInvalid.focus();
        }
        return;
      }

      activateTab(form.getAttribute('data-settings-panel'));
    });
  });
})();
