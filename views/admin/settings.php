<?php
$profile = [
    'first_name' => 'Swornim',
    'last_name' => 'Sanjel',
    'email' => '',
    'phone' => '+977 9841222890',
    'role' => 'System Admin',
    'photo' => '/inventra-ui/public/images/me.jpg',
];
?>

<section class="settings-page">
    <div class="settings-page__top">
        <h1 class="page-title settings-page__title">Settings</h1>

        <div class="settings-tabs" role="tablist" aria-label="Settings tabs">
            <button class="settings-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="settings-profile-panel" id="settings-profile-tab" data-settings-tab="profile">Profile</button>
            <button class="settings-tab" type="button" role="tab" aria-selected="false" aria-controls="settings-security-panel" id="settings-security-tab" data-settings-tab="security">Security</button>
            <button class="settings-tab" type="button" role="tab" aria-selected="false" aria-controls="settings-notifications-panel" id="settings-notifications-tab" data-settings-tab="notifications">Notifications</button>
        </div>
    </div>

    <div class="settings-panels">
        <form class="settings-panel is-active" id="settings-profile-panel" data-settings-panel="profile" novalidate>
            <div class="settings-card">
                <h2 class="settings-card__title">Personal Information</h2>

                <div class="settings-photo-row">
                    <div class="settings-photo">
                        <img src="<?= htmlspecialchars($profile['photo']) ?>" alt="Profile photo">
                    </div>

                    <div class="settings-photo-copy">
                        <button class="btn-outline settings-upload-btn" type="button">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 5 17 10"></polyline>
                                <line x1="12" y1="5" x2="12" y2="16"></line>
                            </svg>
                            Upload Photo
                        </button>
                        <p class="settings-upload-help">JPG, PNG or GIF — max 5MB</p>
                    </div>
                </div>

                <div class="settings-form-grid">
                    <div class="settings-field">
                        <label class="settings-label" for="settingsFirstName">First Name</label>
                        <input class="settings-input" id="settingsFirstName" name="first_name" type="text" value="<?= htmlspecialchars($profile['first_name']) ?>" required data-label="First name">
                        <p class="settings-error" data-error-for="settingsFirstName"></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsLastName">Last Name</label>
                        <input class="settings-input" id="settingsLastName" name="last_name" type="text" value="<?= htmlspecialchars($profile['last_name']) ?>" required data-label="Last name">
                        <p class="settings-error" data-error-for="settingsLastName"></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsEmail">Email Address</label>
                        <input class="settings-input" id="settingsEmail" name="email" type="email" value="<?= htmlspecialchars($profile['email']) ?>" required data-label="Email address" data-validate="email">
                        <p class="settings-error" data-error-for="settingsEmail"></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsPhone">Phone Number</label>
                        <input class="settings-input" id="settingsPhone" name="phone" type="tel" value="<?= htmlspecialchars($profile['phone']) ?>" required data-label="Phone number">
                        <p class="settings-error" data-error-for="settingsPhone"></p>
                    </div>

                    <div class="settings-field settings-field--full">
                        <label class="settings-label" for="settingsRole">Role</label>
                        <div class="settings-input-wrap settings-input-wrap--icon settings-input-wrap--readonly">
                            <input class="settings-input settings-input--readonly" id="settingsRole" name="role" type="text" value="<?= htmlspecialchars($profile['role']) ?>" readonly>
                            <span class="settings-field-icon settings-field-icon--right" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button class="btn-outline settings-btn-secondary" type="reset">Cancel</button>
                    <button class="btn-primary settings-btn-primary" type="submit">Save Changes</button>
                </div>
            </div>
        </form>

        <form class="settings-panel" id="settings-security-panel" data-settings-panel="security" novalidate>
            <div class="settings-card">
                <h2 class="settings-card__title">Password &amp; Security</h2>

                <div class="settings-form-grid settings-form-grid--security">
                    <div class="settings-field settings-field--full">
                        <label class="settings-label" for="settingsCurrentPassword">Current Password</label>
                        <div class="settings-input-wrap settings-input-wrap--icon">
                            <span class="settings-field-icon settings-field-icon--left" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                                </svg>
                            </span>
                            <input class="settings-input settings-input--with-left-icon settings-input--with-right-icon" id="settingsCurrentPassword" name="current_password" type="password" placeholder="Your current password" data-password-field data-label="Current password">
                            <button class="settings-password-toggle" type="button" aria-label="Show password">
                                <svg class="icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="icon-eye-off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M16.68 16.67A10.94 10.94 0 0 1 12 18C5 18 1 12 1 12a21.76 21.76 0 0 1 5.08-5.75"></path>
                                    <path d="M19 19 5 5"></path>
                                    <path d="M9.9 4.24A10.93 10.93 0 0 1 12 4c7 0 11 8 11 8a21.09 21.09 0 0 1-2.17 3.19"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="settings-error" data-error-for="settingsCurrentPassword"></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsNewPassword">New Password</label>
                        <div class="settings-input-wrap settings-input-wrap--icon">
                            <span class="settings-field-icon settings-field-icon--left" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                                </svg>
                            </span>
                            <input class="settings-input settings-input--with-left-icon settings-input--with-right-icon" id="settingsNewPassword" name="new_password" type="password" placeholder="use at least one !@#$_" data-password-field data-password-new data-label="New password">
                            <button class="settings-password-toggle" type="button" aria-label="Show password">
                                <svg class="icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="icon-eye-off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M16.68 16.67A10.94 10.94 0 0 1 12 18C5 18 1 12 1 12a21.76 21.76 0 0 1 5.08-5.75"></path>
                                    <path d="M19 19 5 5"></path>
                                    <path d="M9.9 4.24A10.93 10.93 0 0 1 12 4c7 0 11 8 11 8a21.09 21.09 0 0 1-2.17 3.19"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="settings-error" data-error-for="settingsNewPassword"></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsConfirmPassword">Confirm New Password</label>
                        <div class="settings-input-wrap settings-input-wrap--icon">
                            <input class="settings-input settings-input--with-right-icon" id="settingsConfirmPassword" name="confirm_password" type="password" placeholder="Repeat new password" data-password-field data-password-confirm data-label="Confirm new password">
                            <button class="settings-password-toggle" type="button" aria-label="Show password">
                                <svg class="icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="icon-eye-off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M16.68 16.67A10.94 10.94 0 0 1 12 18C5 18 1 12 1 12a21.76 21.76 0 0 1 5.08-5.75"></path>
                                    <path d="M19 19 5 5"></path>
                                    <path d="M9.9 4.24A10.93 10.93 0 0 1 12 4c7 0 11 8 11 8a21.09 21.09 0 0 1-2.17 3.19"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="settings-error" data-error-for="settingsConfirmPassword"></p>
                    </div>
                </div>

                <div class="settings-actions settings-actions--single">
                    <button class="btn-primary settings-btn-primary" type="submit">Update Password</button>
                </div>
            </div>
        </form>

        <form class="settings-panel" id="settings-notifications-panel" data-settings-panel="notifications" novalidate>
            <div class="settings-card">
                <h2 class="settings-card__title">Notification Preferences</h2>

                <div class="settings-notification-list">
                    <label class="settings-notification-row">
                        <span class="settings-notification-copy">
                            <span class="settings-notification-title">Email alerts for low stock</span>
                            <span class="settings-notification-text">Get notified immediately when item counts drop below threshold.</span>
                        </span>
                        <span class="settings-switch">
                            <input type="checkbox" name="low_stock_alerts" checked>
                            <span class="settings-switch__track"></span>
                        </span>
                    </label>

                    <label class="settings-notification-row">
                        <span class="settings-notification-copy">
                            <span class="settings-notification-title">Weekly summary reports</span>
                            <span class="settings-notification-text">A condensed report of all inventory movement delivered Mondays.</span>
                        </span>
                        <span class="settings-switch">
                            <input type="checkbox" name="weekly_summary_reports" checked>
                            <span class="settings-switch__track"></span>
                        </span>
                    </label>
                </div>

                <div class="settings-actions">
                    <button class="btn-outline settings-btn-secondary" type="reset">Cancel</button>
                    <button class="btn-primary settings-btn-primary" type="submit">Save Preferences</button>
                </div>
            </div>
        </form>
    </div>
</section>
