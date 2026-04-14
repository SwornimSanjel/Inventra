document.addEventListener('DOMContentLoaded', function () {
  var modal = document.getElementById('productModal');
  var productPage = document.querySelector('.products-page');
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

  function openModal(mode, product) {
    form.reset();
    document.getElementById('productId').value = '';
    imageName.textContent = 'No file chosen';
    syncProductsSelect(document.getElementById('productCategorySelect'), '', 'Select category');
    toggleNewCategoryFields(false);
    setMessage('', '');

    if (mode === 'edit' && product) {
      title.textContent = 'Edit Product';
      submitBtn.textContent = 'Save Changes';
      document.getElementById('productId').value = product.id || '';
      document.getElementById('productName').value = product.name || '';
      syncProductsSelect(document.getElementById('productCategorySelect'), product.category_id || '', 'Select category');
      document.getElementById('productQty').value = product.qty || 0;
      document.getElementById('productPrice').value = product.unit_price || 0;
      document.getElementById('productLower').value = product.lower_limit || 0;
      document.getElementById('productUpper').value = product.upper_limit || 0;
      document.getElementById('productDescription').value = product.description || '';
      var addCategoryOption = document.querySelector('#productCategorySelect [data-products-select-option][data-value="new"]');
      if (addCategoryOption) {
        addCategoryOption.hidden = true;
      }
    } else {
      title.textContent = 'Add Product';
      submitBtn.textContent = 'Save Product';
      var createCategoryOption = document.querySelector('#productCategorySelect [data-products-select-option][data-value="new"]');
      if (createCategoryOption) {
        createCategoryOption.hidden = false;
      }
    }

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

  openBtn.addEventListener('click', function () {
    openModal('create');
  });

  imageTrigger.addEventListener('click', function () {
    imageInput.click();
  });

  imageInput.addEventListener('change', function () {
    imageName.textContent = imageInput.files && imageInput.files.length
      ? imageInput.files[0].name
      : 'No file chosen';
  });

  closeBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);

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

        selectRoot.querySelectorAll('[data-products-select-option]').forEach(function (item) {
          item.classList.toggle('is-active', item === option);
        });

        closeFilterSelect(selectRoot);
      });
    });
  });

  document.querySelectorAll('.js-edit-product').forEach(function (button) {
    button.addEventListener('click', function () {
      var raw = button.getAttribute('data-product');
      if (!raw) {
        return;
      }

      openModal('edit', JSON.parse(raw));
    });
  });

  document.querySelectorAll('.js-delete-product').forEach(function (button) {
    button.addEventListener('click', function () {
      var id = button.getAttribute('data-id');
      var name = button.getAttribute('data-name') || 'this product';

      if (!window.confirm('Delete ' + name + '?')) {
        return;
      }

      var body = new FormData();
      body.append('id', id);

      fetch('api/products/delete_product.php', {
        method: 'POST',
        body: body
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.message || 'Delete failed');
          }

          window.location.reload();
        })
        .catch(function (error) {
          window.alert(error.message || 'Unable to delete product.');
        });
    });
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    submitBtn.disabled = true;
    setMessage('Saving product...', '');

    var formData = new FormData(form);
    if (formData.get('category_id') === 'new' && !String(formData.get('new_category') || '').trim()) {
      submitBtn.disabled = false;
      setMessage('Please enter a name for the new category.', 'is-error');
      return;
    }
    var endpoint = formData.get('id') ? 'api/products/update_product.php' : 'api/products/add_product.php';

    fetch(endpoint, {
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
    }
  });
});
