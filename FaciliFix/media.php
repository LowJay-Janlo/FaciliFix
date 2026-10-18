<?php
// Equipment photos: checking, saving and deleting the files.
// Photos are stored in uploads/equipment/ and only the file name goes in the database.

const IMAGE_DIR   = __DIR__ . '/uploads/equipment';
const IMAGE_URL   = 'uploads/equipment/';
const IMAGE_MAX   = 3145728; // 3 MB
const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

function image_dir() {
    if (!is_dir(IMAGE_DIR)) {
        @mkdir(IMAGE_DIR, 0755, true);
    }
    // Nothing in this folder may ever run as a script or be listed
    $guard = IMAGE_DIR . '/.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n    Require all denied\n</FilesMatch>\n");
    }
    return IMAGE_DIR;
}

function valid_image_name($name) {
    return is_string($name) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) === 1;
}

function image_url($name) {
    return valid_image_name($name) ? IMAGE_URL . $name : '';
}

// Returns [saved file name or null, error message or null]. No file chosen = [null, null].
function save_image($file) {
    if (!is_array($file) || !isset($file['error']) || is_array($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > IMAGE_MAX) {
        return [null, 'The image is too large. Choose one under 3 MB.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, 'The image could not be uploaded. Please try again.'];
    }

    // Check the real file contents, not the name the browser reports
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    $info = @getimagesize($file['tmp_name']);
    if (!isset(IMAGE_TYPES[$mime]) || !$info || $info['mime'] !== $mime || $info[0] > 10000 || $info[1] > 10000) {
        return [null, 'Please choose a JPG, PNG or WebP image.'];
    }

    $name = bin2hex(random_bytes(16)) . '.' . IMAGE_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], image_dir() . '/' . $name)) {
        return [null, 'The image could not be saved. Please try again.'];
    }
    @chmod(IMAGE_DIR . '/' . $name, 0644);

    return [$name, null];
}

function delete_image($name) {
    if (valid_image_name($name)) {
        @unlink(IMAGE_DIR . '/' . $name);
    }
}
