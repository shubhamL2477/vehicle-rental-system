<?php

function normalize_files_array($files)
{
    $list = [];

    if (!isset($files['name'])) {
        return $list;
    }

    if (!is_array($files['name'])) {
        $list[] = $files;
        return $list;
    }

    foreach ($files['name'] as $index => $name) {
        $list[] = [
            'name' => $name,
            'type' => isset($files['type'][$index]) ? $files['type'][$index] : '',
            'tmp_name' => isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '',
            'error' => isset($files['error'][$index]) ? $files['error'][$index] : UPLOAD_ERR_NO_FILE,
            'size' => isset($files['size'][$index]) ? $files['size'][$index] : 0,
        ];
    }

    return $list;
}

function ensure_upload_directories()
{
    $folders = [
        app_path(VEHICLE_UPLOAD_DIR),
        app_path(DOCUMENT_UPLOAD_DIR),
    ];

    foreach ($folders as $folder) {
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
    }
}

function save_uploaded_files($files, $relativeDirectory, $allowedExtensions, $maxSize = MAX_UPLOAD_SIZE)
{
    $savedFiles = [];
    $folder = app_path($relativeDirectory);

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $fileList = normalize_files_array($files);

    foreach ($fileList as $file) {
        $error = isset($file['error']) ? $file['error'] : UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('One of the uploaded files could not be processed.');
        }

        $size = isset($file['size']) ? $file['size'] : 0;

        if ($size > $maxSize) {
            throw new RuntimeException('Uploaded file exceeds the 5MB size limit.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Invalid file type uploaded.');
        }

        $generatedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $savePath = $folder . '/' . $generatedName;

        if (!move_uploaded_file($file['tmp_name'], $savePath)) {
            throw new RuntimeException('Unable to save uploaded file.');
        }

        $savedFiles[] = [
            'generated_name' => $generatedName,
            'original_name' => $file['name'],
            'file_size' => (int) $size,
        ];
    }

    return $savedFiles;
}

function delete_uploaded_file($relativeDirectory, $fileName)
{
    $path = app_path(trim($relativeDirectory, '/') . '/' . $fileName);

    if (is_file($path)) {
        unlink($path);
    }
}
