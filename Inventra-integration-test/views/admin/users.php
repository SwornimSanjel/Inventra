<?php
$usersApiBase = $usersPageState['users_api_base'] ?? 'index.php?url=admin/users';
?>

<div class="page-header users-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="section-subtitle">Create, update, and manage staff access from the main admin panel.</p>
    </div>
    <button class="btn-primary" type="button" id="openCreateUserModal">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Create User
    </button>
</div>

<section class="section-card users-page" data-users-api-base="<?= htmlspecialchars($usersApiBase) ?>">
    <div class="users-toolbar">
        <div class="users-search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="usersSearchInput" placeholder="Search users...">
        </div>

        <div class="users-filters">
            <label class="users-filter">
                <span>Role</span>
                <select id="usersRoleFilter">
                    <option value="all">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
            </label>

            <label class="users-filter">
                <span>Status</span>
                <select id="usersStatusFilter">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
        </div>
    </div>

    <div class="users-table-wrap">
        <table class="data-table users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Date Created</th>
                    <th class="users-actions-col">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="6" class="empty-state">Loading users...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="users-footer">
        <span class="muted" id="usersResultCounter">Showing 0 results</span>
    </div>
</section>

<div class="users-modal-backdrop" id="usersModalBackdrop" hidden></div>

<section class="users-modal" id="createUserModal" hidden aria-hidden="true">
    <div class="users-modal-card">
        <div class="users-modal-header">
            <h2>Create New User</h2>
            <button type="button" class="users-modal-close" data-close-modal="createUserModal">&times;</button>
        </div>

        <form id="createUserForm" class="users-form">
            <label>
                <span>Full Name</span>
                <input type="text" name="full_name" required>
            </label>
            <label>
                <span>Email Address</span>
                <input type="email" name="email" required>
            </label>
            <div class="users-form-row">
                <label>
                    <span>Username</span>
                    <input type="text" name="username" required>
                </label>
                <label>
                    <span>Role</span>
                    <select name="role">
                        <option value="User">User</option>
                        <option value="Admin">Admin</option>
                    </select>
                </label>
            </div>
            <label>
                <span>Initial Password</span>
                <div class="users-password-field">
                    <input type="password" name="password" id="createUserPassword" required>
                    <button type="button" class="users-link-btn" id="generateUserPassword">Generate</button>
                </div>
            </label>
            <div class="users-modal-actions">
                <button type="button" class="btn-outline" data-close-modal="createUserModal">Cancel</button>
                <button type="submit" class="btn-primary">Save User</button>
            </div>
        </form>
    </div>
</section>

<section class="users-modal" id="editUserModal" hidden aria-hidden="true">
    <div class="users-modal-card">
        <div class="users-modal-header">
            <h2>Edit User</h2>
            <button type="button" class="users-modal-close" data-close-modal="editUserModal">&times;</button>
        </div>

        <form id="editUserForm" class="users-form">
            <input type="hidden" name="user_id">
            <label>
                <span>Full Name</span>
                <input type="text" name="full_name" required>
            </label>
            <label>
                <span>Email Address</span>
                <input type="email" name="email" required>
            </label>
            <div class="users-form-row">
                <label>
                    <span>Username</span>
                    <input type="text" name="username" required>
                </label>
                <label>
                    <span>Role</span>
                    <select name="role">
                        <option value="User">User</option>
                        <option value="Admin">Admin</option>
                    </select>
                </label>
            </div>
            <div class="users-modal-actions">
                <button type="button" class="btn-outline" data-close-modal="editUserModal">Cancel</button>
                <button type="submit" class="btn-primary">Update User</button>
            </div>
        </form>
    </div>
</section>

<section class="users-modal" id="deleteUserModal" hidden aria-hidden="true">
    <div class="users-modal-card users-modal-card-small">
        <div class="users-modal-header">
            <h2>Delete User</h2>
            <button type="button" class="users-modal-close" data-close-modal="deleteUserModal">&times;</button>
        </div>
        <p class="users-delete-copy">Are you sure you want to delete <strong id="deleteUserLabel"></strong>?</p>
        <div class="users-modal-actions">
            <button type="button" class="btn-outline" data-close-modal="deleteUserModal">Cancel</button>
            <button type="button" class="btn-danger" id="confirmDeleteUser">Delete</button>
        </div>
    </div>
</section>

<div class="users-toast-wrap" id="usersToastWrap"></div>
