<?php

$dsn = 'pgsql:dbname=kmp_gisweb;host=localhost;port=5432';
$user = 'postgres';
$password = '1';

try {
    $connection = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    die('Connection failed: '.$e->getMessage());
}

function getGeoJson(string $table)
{
    $sql = "SELECT row_to_json(fc) AS geojson FROM (SELECT 'FeatureCollection' AS type, array_to_json(array_agg(ft.f::json)) AS features FROM (SELECT ST_AsGeoJSON(t.*) AS f FROM $table AS t) AS ft) AS fc";
    global $connection;
    $statement = $connection->prepare($sql);
    if ($statement->execute()) {
        return $statement->fetch()['geojson'];
    }
}

function editFeature(int $gid, array $attributes = [])
{
    if ($gid < 1 || count($attributes) < 1) {
        return;
    }
    $keys = array_map(function ($key) {
        return "$key = :$key";
    }, array_keys($attributes));
    $sql = 'UPDATE layers.patok_ippkh_1 SET '.implode(', ', $keys).' WHERE gid = :gid;';
    global $connection;
    $statement = $connection->prepare($sql);
    $parameters = [];
    foreach ($attributes as $k => $v) {
        $parameters[":$k"] = $v;
    }
    $parameters[':gid'] = $gid;

    return $statement->execute($parameters);
}
