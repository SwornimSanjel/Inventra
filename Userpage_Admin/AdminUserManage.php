<?php
    //php logic goes here.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventra | Admin User Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="AdminUserManage.css">
</head>
<body>

    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="brand">
                <img src=" " alt="" class="brand-logo">
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
                <button class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
            </div>
        </aside>

        <main class="main-content">
            
            <header class="top-header">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8;"></i>
                    <input type="text" placeholder="Search anything...">
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
                        <select><option>All Roles</option><option>Admin</option><option>User</option></select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select><option>All Statuses</option><option>Active</option><option>Inactive</option></select>
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
                        <tbody>
                            <tr>
                                <td class="user-cell">Swornim Sanjel</td>
                                <td>swornim12</td>
                                <td><span class="badge-role role-admin">Admin</span></td>
                                <td><span class="badge-status status-active">Active</span></td>
                                <td>Oct 12, 2026</td>
                                <td class="actions">
                                    <button class="btn-icon" title="Edit User" onclick="editUser(this)"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon btn-delete" title="Delete User" onclick="deleteUser(this)"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="user-cell">Dipana Shrestha</td>
                                <td>dipana_xtha</td>
                                <td><span class="badge-role role-admin">Admin</span></td>
                                <td><span class="badge-status status-active">Active</span></td>
                                <td>Feb 02, 2026</td>
                                <td class="actions">
                                    <button class="btn-icon" title="Edit User" onclick="editUser(this)"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon btn-delete" title="Delete User" onclick="deleteUser(this)"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <span>Showing 2 results</span>
                        <div class="page-btns">
                            <button>Previous</button>
                            <button>Next</button>
                        </div>
                    </div>
                </div>

                <p class="footer-text">© 2026 INVENTRA. EDITORIAL INVENTORY MANAGEMENT.</p>
            </div>
        </main>

        <div class="modal-overlay" id="userModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h2>Create New User</h2>
                    <button class="btn-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form id="addUserForm" action="javascript:void(0);">
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
                                <option>User</option>
                                <option>Admin</option>
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
                        <button type="submit" class="btn-save">Save & Send Credentials</button>
                    </div>
                </form>

                <div class="modal-footer">
                    <i class="fa-solid fa-circle-info"></i>
                    User will be notified via email with their login credentials.
                </div>
            </div>
        </div>

    </div>

    <script src="AdminUserManage.js"></script>
</body>
</html>