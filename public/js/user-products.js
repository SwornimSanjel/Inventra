document.addEventListener('DOMContentLoaded', function () {
  var productPage = document.querySelector('.products-page');
  var modal = document.getElementById('productModal');
  var form = document.getElementById('productForm');
  var message = document.getElementById('productFormMessage');
  var title = document.getElementById('productModalTitle');
  var submitBtn = document.getElementById('productSubmitBtn');
  var openBtn = document.getElementById('openProductModal');
  var closeBtn = document.getElementById('closeProductModal');
  var cancelBtn = document.getElementById('productModalCancel');
  var imageInput = document.getElementById('productImage');
  var imageTrigger = document.getElementById('productImageTrigger');
  var imageName = document.getElementById('productImageName');
  var newCategoryWrap = document.getElementById('productNewCategoryWrap');
  var newCategoryInput = document.getElementById('productNewCategory');
  var newCategoryDescription = document.getElementById('productNewCategoryDescription');
  var filterSelects = Array.prototype.slice.call(document.querySelectorAll('[data-products-select]'));
  var addEndpoint = (productPage && productPage.getAttribute('data-add-product-endpoint')) || 'api/products/add_product.php';

  if (!productPage || !modal || !form || !message || !title || !submitBtn) {
    return;
  }

  function setMessage(text, state) {
    message.textContent = text || '';
    message.classList.remove('is-error', 'is-success');
    if (state) {
      message.classList.add(state);
    }
  }

  function syncProductsSelect(selectRoot, value, fallbackLabel) {
    if (!selectRoot) {
      return;
    }

    var hiddenInput = selectRoot.querySelector('[data-products-select-input]');
    var label = selectRoot.querySelector('[data-products-select-label]');
    var matchedOption = null;

    if (hiddenInput) {
      hiddenInput.value = value == null ? '' : String(value);
    }

    selectRoot.querySelectorAll('[data-products-select-option]').forEach(function (option) {
      var isMatch = option.getAttribute('data-value') === String(value);
      option.classList.toggle('is-active', isMatch);
      if (isMatch) {
        matchedOption = option;
      }
    });

    if (label) {
      label.textContent = matchedOption ? matchedOption.textContent.trim() : (fallbackLabel || 'Select option');
    }
  }

  function toggleNewCategoryFields(show) {
    if (!newCategoryWrap) {
      return;
    }

    newCategoryWrap.hidden = !show;

    if (!show) {
      newCategoryInput.value = '';
      newCategoryDescription.value = '';
    }
  }

  function setFieldInvalid(field, invalid) {
    if (field) {
      field.classList.toggle('is-invalid', Boolean(invalid));
    }
  }

  function setCategoryInvalid(invalid) {
    var trigger = document.querySelector('#productCategorySelect [data-products-select-trigger]');
    setFieldInvalid(trigger, invalid);
  }

  function validateRequiredField(id, messageText) {
    var field = document.getElementById(id);
    var isValid = field && String(field.value || '').trim() !== '';
    setFieldInvalid(field, !isValid);

    if (!isValid && messageText) {
      setMessage(messageText, 'is-error');
    }

    return isValid;
  }

  function validateNumberField(id, label, allowZero) {
    var field = document.getElementById(id);
    var rawValue = field ? String(field.value || '').trim() : '';
    var value = Number(rawValue);
    var isValid = rawValue !== '' && Number.isFinite(value) && value >= (allowZero ? 0 : 0.01);
    var requiresWholeNumber = id === 'productQty' || id === 'productLower' || id === 'productUpper';

    if (isValid && requiresWholeNumber && !Number.isInteger(value)) {
      isValid = false;
    }

    setFieldInvalid(field, !isValid);

    if (!isValid) {
      setMessage(label + ' must be a valid ' + (requiresWholeNumber ? 'whole number.' : 'number' + (allowZero ? '.' : ' greater than 0.')), 'is-error');
    }

    return isValid;
  }

  function validateProductForm(formData) {
    setMessage('', '');
    setCategoryInvalid(false);
    if (newCategoryInput) {
      setFieldInvalid(newCategoryInput, false);
    }

    if (!validateRequiredField('productName', 'Product name is required.')) {
      return false;
    }

    var categoryValue = String(formData.get('category_id') || '').trim();
    if (categoryValue === '') {
      setCategoryInvalid(true);
      setMessage('Category is required.', 'is-error');
      return false;
    }

    if (categoryValue === 'new' && !String(formData.get('new_category') || '').trim()) {
      setFieldInvalid(newCategoryInput, true);
      setMessage('Please enter a name for the new category.', 'is-error');
      return false;
    }

    if (
      !validateNumberField('productQty', 'Quantity', true) ||
      !validateNumberField('productPrice', 'Unit price', true) ||
      !validateNumberField('productLower', 'Lower limit', true) ||
      !validateNumberField('productUpper', 'Upper limit', true)
    ) {
      return false;
    }

    var lower = Number(document.getElementById('productLower').value);
    var upper = Number(document.getElementById('productUpper').value);
    if (upper < lower) {
      setFieldInvalid(document.getElementById('productUpper'), true);
      setMessage('Upper limit must be greater than or equal to lower limit.', 'is-error');
      return false;
    }

    if (!validateRequiredField('productDescription', 'Description is required.')) {
      return false;
    }

    return true;
  }

  // The user page only supports product creation, so the modal always resets
  // to the Inventra1 create flow when it opens.
  function openModal() {
    form.reset();
    document.getElementById('productId').value = '';
    title.textContent = 'Add Product';
    submitBtn.textContent = 'Save Product';
    imageName.textContent = 'No file chosen';
    syncProductsSelect(document.getElementById('productCategorySelect'), '', 'Select category');
    toggleNewCategoryFields(false);
    setMessage('', '');
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  function closeFilterSelect(selectRoot) {
    if (!selectRoot) {
      return;
    }

    selectRoot.classList.remove('is-open');
    var trigger = selectRoot.querySelector('[data-products-select-trigger]');
    var menu = selectRoot.querySelector('[data-products-select-menu]');

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    if (menu) {
      menu.hidden = true;
    }
  }

  function closeAllFilterSelects() {
    filterSelects.forEach(closeFilterSelect);
  }

  if (openBtn) {
    openBtn.addEventListener('click', openModal);
  }

  if (imageTrigger && imageInput) {
    imageTrigger.addEventListener('click', function () {
      imageInput.click();
    });

    imageInput.addEventListener('change', function () {
      imageName.textContent = imageInput.files && imageInput.files.length
        ? imageInput.files[0].name
        : 'No file chosen';
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }

  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('click', function (event) {
    filterSelects.forEach(function (selectRoot) {
      if (!selectRoot.contains(event.target)) {
        closeFilterSelect(selectRoot);
      }
    });
  });

  filterSelects.forEach(function (selectRoot) {
    var trigger = selectRoot.querySelector('[data-products-select-trigger]');
    var hiddenInput = selectRoot.querySelector('[data-products-select-input]');
    var label = selectRoot.querySelector('[data-products-select-label]');
    var menu = selectRoot.querySelector('[data-products-select-menu]');

    if (!trigger || !hiddenInput || !label || !menu) {
      return;
    }

    trigger.addEventListener('click', function (event) {
      event.stopPropagation();
      var isOpen = selectRoot.classList.contains('is-open');
      closeAllFilterSelects();

      if (!isOpen) {
        selectRoot.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
      }
    });

    selectRoot.querySelectorAll('[data-products-select-option]').forEach(function (option) {
      option.addEventListener('click', function () {
        hiddenInput.value = option.getAttribute('data-value') || '';
        label.textContent = option.textContent.trim();
        toggleNewCategoryFields(hiddenInput.value === 'new');
        setFieldInvalid(trigger, false);

        selectRoot.querySelectorAll('[data-products-select-option]').forEach(function (item) {
          item.classList.toggle('is-active', item === option);
        });

        closeFilterSelect(selectRoot);

        if (selectRoot.hasAttribute('data-products-select-submit')) {
          var parentForm = selectRoot.closest('form');
          if (parentForm) {
            parentForm.submit();
          }
        }
      });
    });
  });

  Array.prototype.slice.call(form.querySelectorAll('input, textarea')).forEach(function (field) {
    field.addEventListener('input', function () {
      setFieldInvalid(field, false);
    });
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    submitBtn.disabled = true;
    setMessage('Saving product...', '');

    var formData = new FormData(form);
    if (!validateProductForm(formData)) {
      submitBtn.disabled = false;
      return;
    }

    fetch(addEndpoint, {
      method: 'POST',
      body: formData
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'Save failed');
        }

        setMessage(data.message || 'Product saved successfully.', 'is-success');
        window.setTimeout(function () {
          window.location.reload();
        }, 500);
      })
      .catch(function (error) {
        setMessage(error.message || 'Unable to save product.', 'is-error');
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeAllFilterSelects();
      closeModal();
    }
  });
});
