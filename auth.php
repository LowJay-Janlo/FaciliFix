<?php
// Sign-in handling for the whole system.
//
// Admin and Staff each get their OWN session (their own cookie), so being signed
// in as one never opens the other one's pages.
// Every protected page starts with:   require 'auth.php';  require_role('Admin');

require_once __DIR__ . '/db.php';

const SESSION_NAMES = ['Admin' => 'FF_ADMIN', 'Staff' => 'FF_STAFF'];
const IDLE_LIMIT    = 1800;   // sign out after 30 minutes without activity
const MAX_LIFETIME  = 28800;  // and never stay signed in for more than 8 hours

// ---------- Headers sent with every page ----------
function ff_headers() {
    // Stops the Back button from showing a protected page after logging out
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data: blob: https://images.unsplash.com; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

    // Make the page source one compact line (see ff_minify_html).
    // XAMPP already buffers output, so this adds its own buffer on top.
    ob_start('ff_minify_html');
}

// ---------- Sessions ----------
function ff_cookie_path() {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return ($dir === '/' || $dir === '.' || $dir === '') ? '/' : rtrim($dir, '/') . '/';
}

// Cookie settings. session_set_cookie_params() does not accept 'expires', setcookie() does.
function ff_cookie_options($expires = null) {
    $options = [
        'path'     => ff_cookie_path(),
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if ($expires !== null) {
        $options['expires'] = $expires;
    }
    return $options;
}

function ff_agent() {
    return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

// Opens the session that belongs to this role (and only that one)
function ff_open($role) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name(SESSION_NAMES[$role]);
    session_set_cookie_params(ff_cookie_options());
    session_start();
}

// Is the open session a real, current sign-in for this role?
function ff_valid($role) {
    global $conn;

    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role) {
        return false;
    }
    $now = time();
    if ($now - (int)($_SESSION['last'] ?? 0) > IDLE_LIMIT || $now - (int)($_SESSION['start'] ?? 0) > MAX_LIFETIME) {
        return false;
    }
    if (!hash_equals((string)($_SESSION['agent'] ?? ''), ff_agent())) {
        return false;
    }

    // The account must still exist with the same role (a deleted staff is signed out at once)
    $id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = ?");
    $stmt->bind_param("is", $id, $role);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (bool)$found;
}

// Ends the session of one role and deletes its cookie
function ff_end($role) {
    $name = SESSION_NAMES[$role];

    if (isset($_COOKIE[$name])) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ini_set('session.use_strict_mode', '1');
        session_name($name);
        session_id($_COOKIE[$name]);
        session_set_cookie_params(ff_cookie_options());
        session_start();
        $_SESSION = [];
        session_destroy();
    }

    setcookie($name, '', ff_cookie_options(time() - 3600));
    unset($_COOKIE[$name]);
}

// Put this at the top of every protected page
function require_role($role) {
    ff_headers();
    ff_open($role);

    if (!ff_valid($role)) {
        $expired = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === $role;
        ff_end($role);
        header('Location: login.php' . ($expired ? '?expired=1' : ''));
        exit();
    }

    $_SESSION['last'] = time();
}

// Called by login.php after the password was checked
function ff_login($id, $name, $role) {
    ff_open($role);
    session_regenerate_id(true);
    $_SESSION = [
        'user_id'   => (int)$id,
        'user_name' => $name,
        'role'      => $role,
        'start'     => time(),
        'last'      => time(),
        'agent'     => ff_agent(),
    ];
}

// Which role is signed in on this browser right now? (null = nobody)
function ff_signed_in_role() {
    foreach (SESSION_NAMES as $role => $name) {
        if (!isset($_COOKIE[$name])) {
            continue;
        }
        ff_open($role);
        if (ff_valid($role)) {
            return $role;
        }
        session_write_close();
    }
    return null;
}

function ff_home($role) {
    return $role === 'Admin' ? 'admin_dashboard.php' : 'staff_dashboard.php';
}

// ---------- Compact page source ----------
// Removes comments and layout whitespace so "View source" is one dense line.
// Text inside <pre>, <textarea>, <script> and <style> is left exactly as it is.
function ff_minify_html($html) {
    $parts = preg_split('#(<(?:pre|textarea|script|style)\b.*?</(?:pre|textarea|script|style)>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $out = '';

    foreach ($parts as $i => $part) {
        if ($i % 2 === 1) {
            $out .= $part;
            continue;
        }

        $part = preg_replace('/<!--(?!\[if).*?-->/s', '', $part);
        $tokens = preg_split('/(<(?:[^>"\']|"[^"]*"|\'[^\']*\')*>)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($tokens as $j => $token) {
            if ($j % 2 === 1) {
                // A tag: squeeze the gaps between attributes, never the values
                $out .= preg_replace_callback('/"[^"]*"|\'[^\']*\'|\s+/', function ($m) {
                    return ctype_space($m[0][0]) ? ' ' : $m[0];
                }, $token);
            } else {
                $out .= preg_replace('/\s+/', ' ', $token);
            }
        }
    }

    return trim($out);
}
