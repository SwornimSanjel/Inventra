<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventra Dashboard</title>

<link rel="stylesheet" href="style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>

<div class="dashboard">

  <!-- SIDEBAR -->
  <aside class="sidebar">

    <div class="sidebar-top">
      <div class="logo">
        <h2>Inventra</h2>
        <p>Inventory Management</p>
      </div>

      <nav class="menu">
        <a class="active"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <a><i class="fa-solid fa-users"></i>Users</a>
        <a><i class="fa-solid fa-box"></i>Products</a>
        <a><i class="fa-solid fa-arrows-rotate"></i>Stock Update</a>
        <a><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
    </div>

    <div class="sidebar-bottom">
      <button class="logout">
        <i class="fa-solid fa-right-from-bracket"></i>
        <a href="../login/logout.php" class="btn-logout">Logout</a>
      </button>
    </div>

  </aside>

  <!-- MAIN -->
  <div class="main">

    <!-- HEADER -->
    <header class="header">
      <div class="header-inner">

        <div class="search-wrapper">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search anything...">
        </div>

        <div class="header-right">
          <i class="fa-regular fa-bell notification"></i>

          <div class="profile">
            <div>
              <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
              <span>System Admin</span>
            </div>
            <img src="https://i.pravatar.cc/40" alt="">
          </div>
        </div>

      </div>
    </header>

    <!-- CONTENT -->
    <main class="content">
      <div class="container">

        <h1>Overview</h1>

        <!-- STATS -->
        <div class="stats">
          <div class="card">
            <p>Total Products</p>
            <h2>12,842</h2>
          </div>

          <div class="card">
            <p>Categories</p>
            <h2>84</h2>
            <span>+ Added this month</span>
          </div>

          <div class="card">
            <p>Active Users</p>
            <h2>11</h2>
          </div>

          <div class="card alert">
            <p>Low Stock Items</p>
            <h2>24</h2>
            <span>Review Now →</span>
          </div>
        </div>

        <!-- ALERTS -->
        <div class="panel">

          <div class="panel-header">
            <div class="panel-title">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <div>
                <h3>System-wide Low Stock Alerts</h3>
                <p>Immediate action required for critical thresholds.</p>
              </div>
            </div>
            <a href="#">View All</a>
          </div>

          <table>
            <thead>
              <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Stock</th>
                <th>Threshold</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>1</td>
                <td><i class="fa-solid fa-laptop"></i> MacBook Pro 14"</td>
                <td class="low">4</td>
                <td>10/40</td>
                <td><span class="badge low">LOW</span></td>
              </tr>

              <tr>
                <td>2</td>
                <td><i class="fa-solid fa-print"></i> HP LaserJet</td>
                <td class="low">8</td>
                <td>15/50</td>
                <td><span class="badge low">LOW</span></td>
              </tr>

              <tr>
                <td>3</td>
                <td><i class="fa-solid fa-computer-mouse"></i> Logitech MX</td>
                <td class="low">2</td>
                <td>20/60</td>
                <td><span class="badge low">LOW</span></td>
              </tr>

              <tr>
                <td>4</td>
                <td><i class="fa-solid fa-tv"></i> Dell Monitor</td>
                <td class="medium">28</td>
                <td>25/80</td>
                <td><span class="badge medium">MEDIUM</span></td>
              </tr>
            </tbody>
          </table>

        </div>

      </div>
    </main>

  </div>
</div>

</body>
</html>
