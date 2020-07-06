<?php

/**
 * PostgreSQL connection.
 */
$host = '127.0.0.1';
$port = 5432;
$user = 'postgres';
$password = '1';
$dbname = 'kmp_gisweb'; // nama database
$dsn = "pgsql:dbname=$dbname;host=$host;port=$port";

// Membuat koneksi ke database
try {
    $connection = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    die('Connection failed: '.$e->getMessage());
}

/**
 * Prepare and execute SQL command.
 *
 * @param string $sql        SQL command
 * @param array  $parameters Placeholder parameters
 *
 * @return \PDOStatement|bool
 */
function executeSQL(string $sql, array $parameters = [])
{
    global $connection;
    $statement = $connection->prepare($sql);
    $statement->execute($parameters);

    return $statement;
}

/**
 * Get GeoJSON output from the spatial table.
 *
 * @param string $table Spatial table name
 *
 * @return string|null
 */
function getGeoJson(string $table)
{
    $sql = "SELECT row_to_json(fc) AS geojson FROM (SELECT 'FeatureCollection' AS type, array_to_json(array_agg(ft.f::json)) AS features FROM (SELECT ST_AsGeoJSON(t.*) AS f FROM $table AS t) AS ft) AS fc";

    // Call executeSQL function defined above
    $statement = executeSQL($sql);

    if ($statement) {
        return $statement->fetch()['geojson'];
    }
}

/**
 * Add a feature into the spatial table.
 *
 * @param string $table      Spatial table name
 * @param array  $attributes Feature attributes in associated array format
 *
 * @return \PDOStatement|bool
 */
function addFeature(string $table, array $attributes)
{
    $keys = array_keys($attributes);
    $placeholders = array_map(function ($k) {
        return ":$k";
    }, $keys);

    $sql = "INSERT INTO $table (".implode(', ', $keys).') VALUES ('.implode(', ', $placeholders).')';

    $parameters = [];
    foreach ($attributes as $k => $v) {
        $parameters[":$k"] = $v;
    }

    return executeSQL($sql, $parameters);
}

/**
 * Edit a feature in the spatial table.
 *
 * @param string $table      Spatial table name
 * @param int    $gid        Feature GID
 * @param array  $attributes Feature attributes in associated array format
 *
 * @return \PDOStatement|bool
 */
function editFeature(string $table, int $gid, array $attributes = [])
{
    if ($gid < 1 || count($attributes) < 1) {
        return;
    }

    $keys = array_map(function ($key) {
        return "$key = :$key";
    }, array_keys($attributes));

    $sql = "UPDATE $table SET ".implode(', ', $keys).' WHERE gid = :gid;';

    $parameters = [];
    foreach ($attributes as $k => $v) {
        $parameters[":$k"] = $v;
    }
    $parameters[':gid'] = $gid;

    return executeSQL($sql, $parameters);
}
