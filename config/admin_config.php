<?php
// Change this password! Generate a new hash by running this once locally:
//   php -r "echo password_hash('yourNewPassword', PASSWORD_BCRYPT);"
// then paste the result below and delete the old one.
define('ADMIN_PASSWORD_HASH', password_hash('changeme123', PASSWORD_BCRYPT));

// Random-ish string used to sign the admin session token.
// Change this to any random string of your choosing too.
define('ADMIN_SECRET', 'bb-admin-secret-swap-this-out');

function getAdminToken() {
    return hash_hmac('sha256', 'admin-session', ADMIN_SECRET);
}
