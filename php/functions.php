<?php

define('PUBLIC_DIR', __DIR__);
define('UPLOAD_DIR', PUBLIC_DIR.'/uploads');

/**
 * PostgreSQL connection.
 */
$host = '127.0.0.1';
$port = 5432;
$user = 'postgres';
$password = '1';
$dbname = 'db_mamberamo_tengah'; // nama database
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

function listData(string $table)
{
    $statement = executeSQL("SELECT * FROM $table");

    if ($statement) {
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    return [];
}

function inputData(string $table, array $attributes)
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

function getFiles(string $key)
{
    // Get temporary uploaded files
    $files = $_FILES[$key];

    // If files is null return empty array
    if (!$files) {
        return [];
    }

    // If files only contains one item return array of files
    if (!is_array($files['name'])) {
        return [$files];
    }

    // Remap files array
    $result = [];
    $count = count($files['name']);
    $keys = array_keys($files);
    for ($i = 0; $i < $count; $i++) {
        $result[$i] = [];
        foreach ($keys as $key) {
            $result[$i][$key] = $files[$key][$i];
        }
    }

    return $result;
}

function uploadImage($key = 'image')
{
    // Set maximum file size to 10MB
    $maxSize = 10 * 1024 * 1024;

    // Create upload folder if not exists
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

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
    $filename = sprintf(
                    '%s/%s.%s',
                    UPLOAD_DIR,
                    sha1_file($uploadedFile['tmp_name']),
                    $ext
                );

    // Check for duplicate
    if (file_exists($filename)) {
        return $filename;
    }

    // Save temporary uploaded file
    if (!move_uploaded_file(
        $uploadedFile['tmp_name'],
        $filename
    )) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return $filename;
}
