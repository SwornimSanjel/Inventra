/**
 * Admin User Management Logic
 * Fully AJAX-driven — all data flows through user_api.php
 */

// ─── Load users on page load ───
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    setupFormHandlers();
    setupSearch();
});

// ─── FETCH & RENDER USERS ───
function loadUsers() {
    const role   = document.getElementById('filterRole').value;
    const status = document.getElementById('filterStatus').value;
    const tbody  = document.getElementById('usersTableBody');

    // Show loading state
    tbody.innerHTML = `
        <tr class="loading-row">
            <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 20px; margin-bottom: 10px; display: block;"></i>
                Loading users...
            </td>
        </tr>`;

    fetch(`user_api.php?action=fetch&role=${role}&status=${status}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:30px; color:#e11d48;">${data.message}</td></tr>`;
                return;
            }

            if (data.users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fa-solid fa-users-slash" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            No users found
                        </td>
                    </tr>`;
                updateResultCounter(0);
                return;
            }

            tbody.innerHTML = data.users.map(user => renderUserRow(user)).join('');
            updateResultCounter(data.users.length);
        })
        .catch(err => {
            console.error('Failed to load users:', err);
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:30px; color:#e11d48;">Failed to load users. Please try again.</td></tr>`;
        });
}

// ─── RENDER A SINGLE TABLE ROW ───
function renderUserRow(user) {
    const roleClass   = user.display_role === 'Admin' ? 'role-admin' : 'role-user';
    const statusClass = user.status === 'active' ? 'status-active' : 'status-inactive';
    const statusLabel = user.status === 'active' ? 'Active' : 'Inactive';
    const toggleIcon  = user.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off';
    const toggleColor = user.status === 'active' ? 'color: #10b981;' : 'color: #94a3b8;';
    
    // Format date
    const date = new Date(user.created_at);
    const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

    return `
        <tr data-user-id="${user.id}">
            <td class="user-cell">${escapeHtml(user.full_name)}</td>
            <td>${escapeHtml(user.username)}</td>
            <td><span class="badge-role ${roleClass}">${user.display_role}</span></td>
            <td>
                <span class="badge-status ${statusClass}" style="cursor: pointer;" onclick="toggleStatus(${user.id})" title="Click to toggle status">
                    ${statusLabel}
                </span>
            </td>
            <td>${formattedDate}</td>
            <td class="actions">
                <button class="btn-icon btn-toggle" title="Toggle Status" onclick="toggleStatus(${user.id})" style="${toggleColor}">
                    <i class="fa-solid ${toggleIcon}"></i>
                </button>
                <button class="btn-icon" title="Edit User" onclick="openEditModal(${user.id}, '${escapeAttr(user.full_name)}', '${escapeAttr(user.email)}', '${escapeAttr(user.username)}', '${user.display_role}')">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon btn-delete" title="Delete User" onclick="deleteUser(${user.id}, '${escapeAttr(user.full_name)}')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>`;
}

// ─── FORM HANDLERS ───
function setupFormHandlers() {
    // Create User Form
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createUser();
        });
    }

    // Edit User Form
    const editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateUser();
        });
    }

    // Delete Confirm
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', executeDelete);
    }
}

// ─── CREATE USER ───
function createUser() {
    const btnText    = document.querySelector('.btn-save-text');
    const btnLoading = document.querySelector('.btn-save-loading');
    const btnSave    = document.getElementById('btnSave');

    // Show loading state
    btnText.style.display    = 'none';
    btnLoading.style.display = 'inline-flex';
    btnSave.disabled         = true;
    btnSave.style.opacity    = '0.7';

    const formData = new FormData();
    formData.append('action',    'create');
    formData.append('full_name', document.getElementById('addFullName').value);
    formData.append('email',     document.getElementById('addEmail').value);
    formData.append('username',  document.getElementById('addUsername').value);
    formData.append('role',      document.getElementById('addRole').value);
    formData.append('password',  document.getElementById('initialPwd').value);

    fetch('user_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            // Reset button state
            btnText.style.display    = 'inline';
            btnLoading.style.display = 'none';
            btnSave.disabled         = false;
            btnSave.style.opacity    = '1';

            if (data.success) {
                if (data.email_sent) {
                    showToast('Credentials Sent Successfully to Email', 'success');
                } else {
                    showToast(data.message, 'success');
                }
                closeModal();
                document.getElementById('addUserForm').reset();
                loadUsers();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            // Reset button state
            btnText.style.display    = 'inline';
            btnLoading.style.display = 'none';
            btnSave.disabled         = false;
            btnSave.style.opacity    = '1';

            console.error('Create user error:', err);
            showToast('Failed to create user. Please try again.', 'error');
        });
}

// ─── TOGGLE USER STATUS ───
function toggleStatus(userId) {
    const formData = new FormData();
    formData.append('action',  'toggle_status');
    formData.append('user_id', userId);

    fetch('user_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                loadUsers();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error('Toggle status error:', err);
            showToast('Failed to update status', 'error');
        });
}

// ─── DELETE USER ───
let userToDeleteId = null;

function deleteUser(userId, userName) {
    userToDeleteId = userId;
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    userToDeleteId = null;
}

function executeDelete() {
    if (!userToDeleteId) return;

    const btn = document.getElementById('confirmDeleteBtn');
    const originalText = btn.textContent;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action',  'delete');
    formData.append('user_id', userToDeleteId);

    fetch('user_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            btn.textContent = originalText;
            btn.disabled = false;
            closeDeleteModal();
            
            if (data.success) {
                showToast(data.message, 'success');
                loadUsers();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            btn.textContent = originalText;
            btn.disabled = false;
            closeDeleteModal();
            console.error('Delete user error:', err);
            showToast('Failed to delete user', 'error');
        });
}

// ─── UPDATE USER ───
function updateUser() {
    const formData = new FormData();
    formData.append('action',    'update');
    formData.append('user_id',   document.getElementById('editUserId').value);
    formData.append('full_name', document.getElementById('editFullName').value);
    formData.append('email',     document.getElementById('editEmail').value);
    formData.append('username',  document.getElementById('editUsername').value);
    formData.append('role',      document.getElementById('editRole').value);

    fetch('user_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeEditModal();
                loadUsers();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error('Update user error:', err);
            showToast('Failed to update user', 'error');
        });
}

// ─── MODAL CONTROLS ───
function openModal() {
    document.getElementById('userModal').classList.add('active');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('active');
}

function openEditModal(id, fullName, email, username, role) {
    document.getElementById('editUserId').value   = id;
    document.getElementById('editFullName').value  = fullName;
    document.getElementById('editEmail').value     = email;
    document.getElementById('editUsername').value   = username;
    document.getElementById('editRole').value      = role;
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Close modals on outside click
window.addEventListener('click', function(event) {
    if (event.target === document.getElementById('userModal'))  closeModal();
    if (event.target === document.getElementById('editModal')) closeEditModal();
    if (event.target === document.getElementById('deleteModal')) closeDeleteModal();
});

// ─── PASSWORD TOOLS ───
function togglePassword() {
    const pwdField = document.getElementById('initialPwd');
    const eyeIcon  = document.getElementById('toggleEye');

    if (pwdField.type === "password") {
        pwdField.type = "text";
        eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwdField.type = "password";
        eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function generatePassword() {
    const length  = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let retVal    = "";

    for (let i = 0; i < length; i++) {
        retVal += charset.charAt(Math.floor(Math.random() * charset.length));
    }

    const pwdField = document.getElementById('initialPwd');
    pwdField.value = retVal;

    // Auto-reveal for 1.5 seconds
    if (pwdField.type === "password") {
        togglePassword();
        setTimeout(() => {
            if (pwdField.type === "text") togglePassword();
        }, 1500);
    }
}

// ─── SEARCH ───
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterTableBySearch(this.value.toLowerCase());
            }, 300);
        });
    }
}

function filterTableBySearch(query) {
    const rows = document.querySelectorAll('#usersTableBody tr[data-user-id]');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateResultCounter(visibleCount);
}

// ─── TOAST NOTIFICATIONS ───
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast     = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';

    toast.innerHTML = `
        <i class="fa-solid ${icon}"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    container.appendChild(toast);

    // Trigger animation
    requestAnimationFrame(() => toast.classList.add('show'));

    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 5000);
}

// ─── UTILITIES ───
function updateResultCounter(count) {
    const counter = document.getElementById('resultCounter');
    if (counter) {
        counter.textContent = `Showing ${count} result${count !== 1 ? 's' : ''}`;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeAttr(text) {
    return text.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}