<?php
$settings = $settings ?? [];
$profile = $settings['profile'] ?? [];
$notifications = $settings['notifications'] ?? [];
$errors = $settings['errors'] ?? [];
$messages = $settings['messages'] ?? [];
$messageTypes = $settings['message_types'] ?? [];
$activeTab = $settings['active_tab'] ?? 'profile';

$profileErrors = $errors['profile'] ?? [];
$securityErrors = $errors['security'] ?? [];
$notificationErrors = $errors['notifications'] ?? [];
?>

<section class="settings-page" data-active-tab="<?= htmlspecialchars($activeTab) ?>">
    <div class="settings-page__top">
        <h1 class="page-title settings-page__title">Settings</h1>

        <div class="settings-tabs" role="tablist" aria-label="Settings tabs">
            <button class="settings-tab <?= $activeTab === 'profile' ? 'is-active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'profile' ? 'true' : 'false' ?>" aria-controls="settings-profile-panel" id="settings-profile-tab" data-settings-tab="profile">Profile</button>
            <button class="settings-tab <?= $activeTab === 'security' ? 'is-active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'security' ? 'true' : 'false' ?>" aria-controls="settings-security-panel" id="settings-security-tab" data-settings-tab="security">Security</button>
            <button class="settings-tab <?= $activeTab === 'notifications' ? 'is-active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'notifications' ? 'true' : 'false' ?>" aria-controls="settings-notifications-panel" id="settings-notifications-tab" data-settings-tab="notifications">Notifications</button>
        </div>
    </div>

    <div class="settings-panels">
        <form class="settings-panel <?= $activeTab === 'profile' ? 'is-active' : '' ?>" id="settings-profile-panel" data-settings-panel="profile" method="POST" action="index.php?url=admin/settings/profile" enctype="multipart/form-data" novalidate>
            <div class="settings-card">
                <h2 class="settings-card__title">Personal Information</h2>

                <?php if (!empty($messages['profile'])): ?>
                    <div class="settings-alert settings-alert--<?= htmlspecialchars((string) ($messageTypes['profile'] ?? 'success')) ?>"><?= htmlspecialchars((string) $messages['profile']) ?></div>
                <?php endif; ?>

                <div class="settings-photo-row">
                    <div class="settings-photo">
                        <img src="<?= htmlspecialchars((string) ($profile['photo'] ?? '')) ?>" alt="Profile photo" id="settingsAvatarPreview">
                    </div>

                    <div class="settings-photo-copy">
                        <input type="file" name="avatar" id="settingsAvatarInput" class="settings-file-input" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        <button class="btn-outline settings-upload-btn" type="button" data-upload-trigger="settingsAvatarInput">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 5 17 10"></polyline>
                                <line x1="12" y1="5" x2="12" y2="16"></line>
                            </svg>
                            Upload Photo
                        </button>
                        <p class="settings-upload-help">JPG, JPEG, or PNG only, max 5MB</p>
                        <p class="settings-upload-file" id="settingsAvatarFileName"></p>
                        <p class="settings-error"><?= htmlspecialchars((string) ($profileErrors['avatar'] ?? '')) ?></p>
                    </div>
                </div>

                <div class="settings-form-grid">
                    <div class="settings-field">
                        <label class="settings-label" for="settingsFirstName">First Name</label>
                        <input class="settings-input <?= !empty($profileErrors['first_name']) ? 'is-invalid' : '' ?>" id="settingsFirstName" name="first_name" type="text" value="<?= htmlspecialchars((string) ($profile['first_name'] ?? '')) ?>" required data-label="First name">
                        <p class="settings-error" data-error-for="settingsFirstName"><?= htmlspecialchars((string) ($profileErrors['first_name'] ?? '')) ?></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsLastName">Last Name</label>
                        <input class="settings-input <?= !empty($profileErrors['last_name']) ? 'is-invalid' : '' ?>" id="settingsLastName" name="last_name" type="text" value="<?= htmlspecialchars((string) ($profile['last_name'] ?? '')) ?>" required data-label="Last name">
                        <p class="settings-error" data-error-for="settingsLastName"><?= htmlspecialchars((string) ($profileErrors['last_name'] ?? '')) ?></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsEmail">Email Address</label>
                        <input class="settings-input <?= !empty($profileErrors['email']) ? 'is-invalid' : '' ?>" id="settingsEmail" name="email" type="email" value="<?= htmlspecialchars((string) ($profile['email'] ?? '')) ?>" required data-label="Email address" data-validate="email">
                        <p class="settings-error" data-error-for="settingsEmail"><?= htmlspecialchars((string) ($profileErrors['email'] ?? '')) ?></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsPhone">Phone Number</label>
                        <input class="settings-input <?= !empty($profileErrors['phone']) ? 'is-invalid' : '' ?>" id="settingsPhone" name="phone" type="tel" value="<?= htmlspecialchars((string) ($profile['phone'] ?? '')) ?>" required data-label="Phone number">
                        <p class="settings-error" data-error-for="settingsPhone"><?= htmlspecialchars((string) ($profileErrors['phone'] ?? '')) ?></p>
                    </div>

                    <div class="settings-field settings-field--full">
                        <label class="settings-label" for="settingsRole">Role</label>
                        <div class="settings-input-wrap settings-input-wrap--icon settings-input-wrap--readonly">
                            <input class="settings-input settings-input--readonly" id="settingsRole" name="role" type="text" value="<?= htmlspecialchars((string) ($profile['role'] ?? '')) ?>" readonly>
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

        <form class="settings-panel <?= $activeTab === 'security' ? 'is-active' : '' ?>" id="settings-security-panel" data-settings-panel="security" method="POST" action="index.php?url=admin/settings/password" novalidate>
            <div class="settings-card">
                <h2 class="settings-card__title">Password &amp; Security</h2>

                <?php if (!empty($messages['security'])): ?>
                    <div class="settings-alert settings-alert--<?= htmlspecialchars((string) ($messageTypes['security'] ?? 'success')) ?>"><?= htmlspecialchars((string) $messages['security']) ?></div>
                <?php endif; ?>

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
                            <input class="settings-input settings-input--with-left-icon settings-input--with-right-icon <?= !empty($securityErrors['current_password']) ? 'is-invalid' : '' ?>" id="settingsCurrentPassword" name="current_password" type="password" placeholder="Your current password" data-password-field data-label="Current password">
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
                        <p class="settings-error" data-error-for="settingsCurrentPassword"><?= htmlspecialchars((string) ($securityErrors['current_password'] ?? '')) ?></p>
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
                            <input class="settings-input settings-input--with-left-icon settings-input--with-right-icon <?= !empty($securityErrors['new_password']) ? 'is-invalid' : '' ?>" id="settingsNewPassword" name="new_password" type="password" placeholder="Use at least 8 chars and one of !, @, #" data-password-field data-password-new data-label="New password">
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
                        <p class="settings-error" data-error-for="settingsNewPassword"><?= htmlspecialchars((string) ($securityErrors['new_password'] ?? '')) ?></p>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label" for="settingsConfirmPassword">Confirm New Password</label>
                        <div class="settings-input-wrap settings-input-wrap--icon">
                            <input class="settings-input settings-input--with-right-icon <?= !empty($securityErrors['confirm_password']) ? 'is-invalid' : '' ?>" id="settingsConfirmPassword" name="confirm_password" type="password" placeholder="Repeat new password" data-password-field data-password-confirm data-label="Confirm new password">
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
                        <p class="settings-error" data-error-for="settingsConfirmPassword"><?= htmlspecialchars((string) ($securityErrors['confirm_password'] ?? '')) ?></p>
                    </div>
                </div>

                <div class="settings-actions settings-actions--single">
                    <button class="btn-primary settings-btn-primary" type="submit">Update Password</button>
                </div>
            </div>
        </form>

        <form class="settings-panel <?= $activeTab === 'notifications' ? 'is-active' : '' ?>" id="settings-notifications-panel" data-settings-panel="notifications" method="POST" action="index.php?url=admin/settings/notifications" novalidate>
            <div class="settings-card">
                <h2 class="settings-card__title">Notification Preferences</h2>

                <?php if (!empty($messages['notifications'])): ?>
                    <div class="settings-alert settings-alert--<?= htmlspecialchars((string) ($messageTypes['notifications'] ?? 'success')) ?>"><?= htmlspecialchars((string) $messages['notifications']) ?></div>
                <?php endif; ?>

                <?php if (!empty($notificationErrors['form'])): ?>
                    <div class="settings-alert settings-alert--error"><?= htmlspecialchars((string) $notificationErrors['form']) ?></div>
                <?php endif; ?>

                <div class="settings-notification-list">
                    <label class="settings-notification-row">
                        <span class="settings-notification-copy">
                            <span class="settings-notification-title">Email alerts for low stock</span>
                            <span class="settings-notification-text">Get notified immediately when item counts drop below threshold.</span>
                        </span>
                        <span class="settings-switch">
                            <input type="hidden" name="low_stock_alerts" value="0">
                            <input type="checkbox" name="low_stock_alerts" value="1" <?= !empty($notifications['low_stock_alerts']) ? 'checked' : '' ?>>
                            <span class="settings-switch__track"></span>
                        </span>
                    </label>

                    <label class="settings-notification-row">
                        <span class="settings-notification-copy">
                            <span class="settings-notification-title">Weekly summary reports</span>
                            <span class="settings-notification-text">A condensed report of all inventory movement delivered Mondays.</span>
                        </span>
                        <span class="settings-switch">
                            <input type="hidden" name="weekly_summary_reports" value="0">
                            <input type="checkbox" name="weekly_summary_reports" value="1" <?= !empty($notifications['weekly_summary_reports']) ? 'checked' : '' ?>>
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
