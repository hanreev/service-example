<?php

// Set default Content-Type header
header('Content-Type: text/plain; charset=utf-8');

// Set file key
$key = 'image';

// Set maximum file size to 10MB
$maxSize = 10 * 1024 * 1024;

// Set upload folder
$uploadDirectory = __DIR__.'/uploads';

// Create upload folder if not exists
if (!file_exists($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

try {
    // Get temporary uploaded file
    $uploadedFile = $_FILES[$key];

    // Check file errors
    if (
        !isset($uploadedFile['error']) ||
        is_array($uploadedFile['error'])
    ) {
        throw new RuntimeException('Invalid parameters.');
    }

    switch ($uploadedFile['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    // Check file size
    if ($uploadedFile['size'] > $maxSize) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    // Check file mime type and extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
        $finfo->file($uploadedFile['tmp_name']),
        [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ],
        true
    )) {
        throw new RuntimeException('Invalid file format.');
    }

    // Set new filename
    $filename = sprintf('%s/%s.%s',
                    $uploadDirectory,
                    sha1_file($uploadedFile['tmp_name']),
                    $ext
                );

    // Check for duplicate
    if (file_exists($filename)) {
        echo 'File already exists.';

        exit;
    }

    // Save temporary uploaded file
    if (!move_uploaded_file(
        $uploadedFile['tmp_name'],
        $filename
    )) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    header('Location: /');
    echo 'File is uploaded successfully. '.$filename;
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
