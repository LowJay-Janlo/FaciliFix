<?php
require 'auth.php';
ff_headers();

// Ends both sessions on this browser and deletes their cookies
foreach (array_keys(SESSION_NAMES) as $role) {
    ff_end($role);
}
// Cookie used by the old version of the system
setcookie('PHPSESSID', '', ff_cookie_options(time() - 3600));

header("Location: login.php");
exit();
