/**
 * Admin User Management Logic
 */

// Toggle Modal visibility
function openModal() {
    const modal = document.getElementById('userModal');
    modal.classList.add('active');
}

function closeModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('active');
}

// Password Eye Toggle
function togglePassword() {
    const pwdField = document.getElementById('initialPwd');
    const eyeIcon = document.getElementById('toggleEye');

    if (pwdField.type === "password") {
        pwdField.type = "text";
        eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwdField.type = "password";
        eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Generate Random Password
function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let retVal = "";

    for (let i = 0, n = charset.length; i < length; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }

    const pwdField = document.getElementById('initialPwd');
    pwdField.value = retVal;

    // Auto-reveal for 1.5 seconds so admin can see what was generated
    if (pwdField.type === "password") {
        togglePassword();
        setTimeout(() => {
            if (pwdField.type === "text") togglePassword();
        }, 1500);
    }
}

// Optional: Close modal when clicking outside of the box
window.onclick = function (event) {
    const modal = document.getElementById('userModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Add User Form Submission
document.addEventListener('DOMContentLoaded', () => {
    const addUserForm = document.getElementById('addUserForm');

    if (addUserForm) {
        addUserForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const fullName = document.getElementById('addFullName').value;
            const username = document.getElementById('addUsername').value;
            const roleSelect = document.getElementById('addRole');
            const role = roleSelect.options[roleSelect.selectedIndex].text;

            const tbody = document.querySelector('tbody');
            const tr = document.createElement('tr');

            const date = new Date();
            const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

            let roleClass = role.toLowerCase() === 'admin' ? 'role-admin' : 'role-user';

            tr.innerHTML = `
                <td class="user-cell">${fullName}</td>
                <td>${username}</td>
                <td><span class="badge-role ${roleClass}">${role}</span></td>
                <td><span class="badge-status status-active">Active</span></td>
                <td>${formattedDate}</td>
                <td class="actions">
                    <button class="btn-icon" title="Edit User" onclick="editUser(this)"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon btn-delete" title="Delete User" onclick="deleteUser(this)"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;

            tbody.appendChild(tr);
            updateResultCounter();

            closeModal();
            this.reset();
        });
    }
});

// Delete User
function deleteUser(btn) {
    if (confirm('Are you sure you want to delete this user?')) {
        const tr = btn.closest('tr');
        tr.remove();
        updateResultCounter();
    }
}

// Edit User Placeholder
function editUser(btn) {
    alert("Edit user popup coming soon!");
}

// Update Result Counter
function updateResultCounter() {
    const counterSpan = document.querySelector('.pagination span');
    if (counterSpan) {
        const currentCount = document.querySelectorAll('tbody tr').length;
        counterSpan.textContent = `Showing ${currentCount} results`;
    }
}