<?php

header('Content-Type: text/plain; charset=utf-8');

$key = 'image';
$saveDir = __DIR__.'/uploads';

// Create save directory if not exists
if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true);
}

try {
    $uploadedFile = $_FILES[$key];
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

    if ($uploadedFile['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
        $finfo->file($uploadedFile['tmp_name']),
        [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ],
        true
    )) {
        throw new RuntimeException('Invalid file format.');
    }

    $filename = sprintf('%s/%s.%s',
                    $saveDir,
                    sha1_file($uploadedFile['tmp_name']),
                    $ext
                );

    if (file_exists($filename)) {
        echo 'File already exists.';

        exit;
    }

    if (!move_uploaded_file(
        $uploadedFile['tmp_name'],
        sprintf('%s/%s.%s',
            $saveDir,
            sha1_file($uploadedFile['tmp_name']),
            $ext
        )
    )) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    echo 'File is uploaded successfully.';
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
