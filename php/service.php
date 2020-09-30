<?php

require 'functions.php';

header('Content-Type: text/plain; charset=utf-8');

$action = $_REQUEST['action'];

if ($action == 'getGeojson') {
    header('Content-Type: application/json');
    $geojsonTable = $_REQUEST['geojsonTable'];
    $geojson = getGeoJson($geojsonTable);
    if ($geojson) {
        echo $geojson;
    } else {
        header('HTTP/1.0 400 Bad Request');
        echo json_encode(['error' => 'Invalid table name.']);
    }
} elseif ($action == 'addFeature') {
    header('Content-Type: application/json');

    $tableName = $_POST['tableName'];
    $keys = $_POST['keys'];
    $values = $_POST['values'];

    $attributes = [];
    for ($i = 0; $i < count($keys); $i++) {
        $attributes[$keys[$i]] = $values[$i];
    }

    $result = addFeature($tableName, $attributes);

    if ($result->errorCode()) {
        header('HTTP/1.0 500 Server Error');
        echo json_encode(['error' => $result->errorInfo()]);
    } else {
        echo json_encode($attributes);
    }
} elseif ($action == 'indexKerusakan') {
    header('Content-Type: application/json');

    echo json_encode(listData('NAMA_TABEL'));
} elseif ($action == 'inputKerusakan') {
    header('Content-Type: application/json');

    try {
        // Handle image upload
        $filename = uploadImage('gambar');

        // Get relative path
        $filename = preg_replace('/^'.preg_quote(PUBLIC_DIR, '/').'\//', '', $filename);

        // Assign attributes
        $attributes = [
            'kondisi' => $_REQUEST['kondisi'],
            'keterangan' => $_REQUEST['keterangan'],
            'lat' => $_REQUEST['lat'],
            'long' => $_REQUEST['long'],
            'gambar' => $filename,
        ];

        // Cleanup empty attributes
        foreach ($attributes as $key => $value) {
            if (empty($value)) {
                unset($attributes[$key]);
            }
        }

        // Input data into the database
        if (inputData('NAMA_TABEL', $attributes)) {
            echo json_encode($attributes);
        } else {
            header('HTTP/1.0 500 Server Error');
            echo json_encode(['error' => 'Tidak dapat menginput data']);
        }
    } catch (\Throwable $th) {
        header('HTTP/1.0 500 Server Error');
        echo json_encode(['error' => $th->getMessage()]);
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid action.';
}
