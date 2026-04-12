<?php
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventra | Admin User Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="AdminUserManage.css?v=<?= time() ?>">
</head>
<body>

    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="brand">
                 <img src="../public/images/inventra-logo.png" alt="logo" class="brand-logo">
                <div class="brand-text">
                    <h2>Inventra</h2>
                    <p>INVENTORY MANAGEMENT</p>
                </div>
            </div>

            <nav class="nav-menu">
                <a href="#" class="nav-link"><i class="fa-solid fa-border-all"></i> Dashboard</a>
                <a href="#" class="nav-link active"><i class="fa-solid fa-users"></i> Users</a>
                <a href="#" class="nav-link"><i class="fa-solid fa-box"></i> Products</a>
                <a href="#" class="nav-link"><i class="fa-solid fa-arrow-right-arrow-left"></i> Stock Update</a>
                <a href="#" class="nav-link"><i class="fa-solid fa-gear"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <button class="btn-logout" onclick="window.location.href='../login/logout.php'"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
            </div>
        </aside>

        <main class="main-content">
            
            <header class="top-header">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8;"></i>
                    <input type="text" placeholder="Search anything..." id="searchInput">
                </div>

                <div class="user-info">
                    <i class="fa-regular fa-bell" style="color: #64748b; font-size: 18px; cursor: pointer;" onclick="alert('You have 3 new notifications!\n\n- Swornim Sanjel requested an account\n- Stock alert: Laptop inventory low\n- System update successful')"></i>
                    <div class="admin-details">
                        <span class="admin-name">Admin</span>
                        <span class="admin-role">Admin</span>
                    </div>
                    <div class="avatar">A</div>
                </div>
            </header>

            <div class="page-body">
                <div class="page-header">
                    <h1>User Management</h1>
                    <button class="btn-primary" onclick="openModal()"><i class="fa-solid fa-user-plus"></i> Create User</button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>Role</label>
                        <select id="filterRole" onchange="loadUsers()">
                            <option value="all">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select id="filterStatus" onchange="loadUsers()">
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Populated dynamically via AJAX -->
                            <tr class="loading-row">
                                <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 20px; margin-bottom: 10px; display: block;"></i>
                                    Loading users...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <span id="resultCounter">Showing 0 results</span>
                        <div class="page-btns">
                            <button>Previous</button>
                            <button>Next</button>
                        </div>
                    </div>
                </div>

                <p class="footer-text">© 2026 INVENTRA. EDITORIAL INVENTORY MANAGEMENT.</p>
            </div>
        </main>

        <!-- CREATE USER MODAL -->
        <div class="modal-overlay" id="userModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h2>Create New User</h2>
                    <button class="btn-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form id="addUserForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="addFullName" placeholder="e.g. Swornim Sanjel" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="addEmail" placeholder="e.g. swornim@example.com" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="addUsername" placeholder="swornim_123" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select id="addRole">
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            Initial Password 
                            <span class="gen-pass" onclick="generatePassword()">Generate Random</span>
                        </label>
                        <div class="input-icon-wrap">
                            <input type="password" id="initialPwd" placeholder="••••••••••••" required>
                            <i class="fa-regular fa-eye" id="toggleEye" onclick="togglePassword()"></i>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn-save" id="btnSave">
                            <span class="btn-save-text">Save & Send Credentials</span>
                            <span class="btn-save-loading" style="display:none;">
                                <i class="fa-solid fa-spinner fa-spin"></i> Sending...
                            </span>
                        </button>
                    </div>
                </form>

                <div class="modal-footer">
                    <i class="fa-solid fa-circle-info"></i>
                    User will be notified via email with their login credentials.
                </div>
            </div>
        </div>

        <!-- EDIT USER MODAL -->
        <div class="modal-overlay" id="editModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h2>Edit User</h2>
                    <button class="btn-close" onclick="closeEditModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form id="editUserForm">
                    <input type="hidden" id="editUserId">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="editFullName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="editEmail" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="editUsername" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select id="editRole">
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update User</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE CONFIRM MODAL -->
        <div class="modal-overlay" id="deleteModal">
            <div class="modal-box" style="width: 400px; text-align: center; padding: 40px 30px;">
                <div style="width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 10px;">Delete User?</h2>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 30px;">Are you sure you want to delete <strong id="deleteUserName" style="color: #0f172a;"></strong>? This action cannot be undone.</p>
                <div class="modal-actions" style="margin-top: 0; display: flex; gap: 10px;">
                    <button class="btn-cancel" onclick="closeDeleteModal()" style="flex: 1;">No, Cancel</button>
                    <button class="btn-save" style="background: #ef4444; flex: 1;" id="confirmDeleteBtn">Yes, Delete</button>
                </div>
            </div>
        </div>

        <!-- TOAST NOTIFICATION -->
        <div class="toast-container" id="toastContainer"></div>

    </div>

    <script src="AdminUserManage.js?v=<?= time() ?>"></script>
</body>
</html>