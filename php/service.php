<?php

require 'functions.php';

header('Content-Type: text/plain; charset=utf-8');

$action = $_REQUEST['action'];

if ($action == 'getGeojson') {
    header('Content-Type: application/json');
    $geojsonTable = $_POST['geojsonTable'];
    $geojson = getGeoJson($geojsonTable);
    if ($geojson) {
        echo $geojson;
    } else {
        header('HTTP/1.0 400 Bad Request');
        echo json_encode(['error' => 'Invalid table name.']);
    }
} elseif ($action == 'addFeature') {
    $tableName = $_POST['tableName'];
    $keys = $_POST['keys'];
    $values = $_POST['values'];

    $attributes = [];
    for ($i = 0; $i < count($keys); $i++) {
        $attributes[$keys[$i]] = $values[$i];
    }

    $result = addFeature($tableName, $attributes);
    if ($result->errorCode()) {
        var_dump($result->errorInfo());
    } else {
        echo 'Feature has been added successfully.';
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid action.';
}
