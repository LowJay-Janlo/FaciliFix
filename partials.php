<?php

$icons = [
    'menu'       => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
    'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'arrow-left' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
    'arrow-right'=> '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    'search'     => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'plus'       => '<path d="M5 12h14"/><path d="M12 5v14"/>',
    'check'      => '<path d="M20 6 9 17l-5-5"/>',
    'briefcase'  => '<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    'users'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'graduation' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/><path d="M22 10v6"/>',
    'monitor'    => '<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>',
    'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
    'x-circle'     => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
    'help-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
];

$floor_tags = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];

$room_icon_options = [
    'office'       => ['label' => 'Office',       'icon' => 'briefcase'],
    'staff'        => ['label' => 'Staff Room',   'icon' => 'users'],
    'student'      => ['label' => 'Student Room', 'icon' => 'graduation'],
    'computer_lab' => ['label' => 'Computer Lab', 'icon' => 'monitor'],
];

$admin_nav = [
    'dashboard'  => ['admin_dashboard.php',  'Dashboard'],
    'facilities' => ['facilities.php',       'Facilities'],
    'staff'      => ['manage_staff.php',     'Manage Staff'],
];

function icon($paths) {
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';
}

function render_topbar($active = null, $logged_in = true) {
    global $icons, $admin_nav;

    $home = $logged_in ? 'admin_dashboard.php' : 'landingpage.php';

    echo '<header class="topbar">';
    echo '<a href="' . $home . '" class="topbar-logo" aria-label="FaciliFix home">';
    echo '<img src="Assets/Images/logo.png" alt="FaciliFix" draggable="false">';
    echo '</a>';

    if ($logged_in) {
        echo '<nav class="topbar-nav" id="topbarNav" aria-label="Main navigation">';
        foreach ($admin_nav as $key => $link) {
            $is_active = ($key === $active);
            echo '<a href="' . $link[0] . '"'
               . ($is_active ? ' class="active" aria-current="page"' : '') . '>'
               . htmlspecialchars($link[1]) . '</a>';
        }
        echo '</nav>';

        echo '<div class="topbar-actions">';
        echo '<a href="logout.php" class="logout-button">' . icon($icons['logout']) . '<span class="logout-text">Log Out</span></a>';
        echo '<button type="button" class="icon-button" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="topbarNav">' . icon($icons['menu']) . '</button>';
        echo '</div>';
    }

    echo '</header>';
}

function render_back($url, $label = 'Go back') {
    global $icons;
    echo '<a href="' . htmlspecialchars($url) . '" class="back-button" aria-label="' . htmlspecialchars($label) . '" title="' . htmlspecialchars($label) . '">'
       . icon($icons['arrow-left']) . '</a>';
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valid() {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}
