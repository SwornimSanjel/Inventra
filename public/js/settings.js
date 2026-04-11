(function () {
  var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-settings-tab]'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('[data-settings-panel]'));

  if (!tabs.length || !panels.length) {
    return;
  }

  function activateTab(name) {
    tabs.forEach(function (tab) {
      var active = tab.getAttribute('data-settings-tab') === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-settings-panel') === name);
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      activateTab(tab.getAttribute('data-settings-tab'));
    });
  });

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
      setError(current, 'Current password is required.');
      valid = false;
    } else {
      setError(current, '');
    }

    if (next && nextValue.trim() === '') {
      setError(next, 'New password is required.');
      valid = false;
    } else if (next && !/[!@#$^&_]/.test(nextValue)) {
      setError(next, 'Use at least one !@#$_');
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

  Array.prototype.slice.call(document.querySelectorAll('.settings-panel form, .settings-panel')).forEach(function (form) {
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
      }, 0);
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      var valid = true;

      Array.prototype.slice.call(form.querySelectorAll('.settings-input')).forEach(function (field) {
        if (!validateTextField(field)) {
          valid = false;
        }
      });

      if (form.getAttribute('data-settings-panel') === 'security' && !validateSecurityForm(form)) {
        valid = false;
      }

      if (!valid) {
        var firstInvalid = form.querySelector('.settings-input.is-invalid');
        if (firstInvalid) {
          firstInvalid.focus();
        }
      }
    });
  });
})();
