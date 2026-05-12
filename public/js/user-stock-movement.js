(function () {
  if (typeof initStockPage === 'function') {
    initStockPage({
      productsUrl: 'api/products/get_user_products.php',
      historyUrl: 'api/stock/user_list.php',
      updateStatusUrl: 'api/stock/user_update_status.php'
    });
  }
})();
