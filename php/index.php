<?php

/**
 * Implementation example using HTML.
 */
require 'functions.php';

$action = $_POST['action'];

if ($action == 'getGeojson') {
    header('Content-Type: application/json');
    $geojsonTable = $_POST['geojsonTable'];
    $geojson = getGeoJson($geojsonTable);
    if ($geojson) {
        echo $geojson;
    } else {
        http_response_code(400);
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
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Example</title>

    <style>
        body {
            margin: 0;
            font-family: sans-serif;
        }

        li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div style="margin: 16px;">
        <h1>Service Example</h1>

        <ul>
            <li>
                <p>Get GeoJSON</p>
                <form action="" method="post">
                    <input type="hidden" name="action" value="getGeojson">
                    <input id="geojsonTable" type="text" name="geojsonTable" placeholder="Table name" >
                    <button type="submit">Get GeoJSON</button>
                </form>
            </li>
            <li>
                <p>Add Feature</p>
                <form action="" method="post">
                    <input type="hidden" name="action" value="addFeature">
                    <p>
                        <input type="text" name="tableName" placeholder="Table name">
                    </p>
                    <div id="attributes">
                        Attributes
                        <br>
                        <div class="attribute">
                            <input type="text" placeholder="Key" name="keys[]">
                            <input type="text" placeholder="Value" name="values[]">
                            <button type="button" onclick="this.parentElement.remove();">Delete</button>
                        </div>
                    </div>
                    <button type="button" onclick="addAttribute()">Add Attribute</button>
                    <p>
                        <button type="submit">Submit</button>
                    </p>
                </form>
            </li>
            <li>
                <p>Upload Image</p>
                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <input id="image" type="file" name="image" accept="image/png,image/jpeg" >
                    <button type="submit">Upload</button>
                </form>
            </li>
        </ul>
    </div>

    <script type="text/javascript">
        function addAttribute() {
            const attributeEl = document.createElement('div');
            attributeEl.className = 'attribute';

            const keyEl = document.createElement('input');
            keyEl.type = 'text';
            keyEl.name = 'keys[]';
            keyEl.placeholder = 'Key';

            const valueEl = document.createElement('input');
            valueEl.type = 'text';
            valueEl.name = 'values[]';
            valueEl.placeholder = 'Value';

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.innerText = 'Delete';
            deleteButton.addEventListener('click', () => attributeEl.remove());

            attributeEl.appendChild(keyEl);
            attributeEl.appendChild(valueEl);
            attributeEl.appendChild(deleteButton);

            document.getElementById('attributes').appendChild(attributeEl);
        }

        function deleteAttribute(buttonEl) {
            buttonEl.parentElement.remove();
        }
    </script>
</body>
</html>

<?php
}
?>
