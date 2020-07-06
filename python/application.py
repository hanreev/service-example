# -*- coding: utf-8 -*-

import json
import os

import psycopg2
from flask import Flask, Response, abort, redirect, render_template, request, send_from_directory
from werkzeug.utils import secure_filename

# Define global variable
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
UPLOAD_FOLDER = os.path.join(BASE_DIR, 'uploads')
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg'}

MAX_UPLOAD_SIZE = 10 * 1024 * 1024

# Create database connection
connection = psycopg2.connect(
    database='kmp_gisweb',
    host='localhost',
    port='5432',
    user='postgres',
    password='1'
)

# Create new Flask application instance
app = Flask(__name__)

# Create upload folder if not exists
os.makedirs(UPLOAD_FOLDER, exist_ok=True)


def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


@app.route('/')
def index():
    return render_template('index.html', files=os.listdir(UPLOAD_FOLDER))


@app.route('/geojson', methods=['POST'])
def geojson():
    # Get geojson_table from client request
    geojson_table = request.form.get('geojson_table')

    # If geojson_table is empty, return HTTP error "400 Bad Request"
    if not geojson_table:
        return abort(400)

    # Create new database cursor instance
    cursor = connection.cursor()

    # Define SQL command
    sql = '''SELECT row_to_json(fc) AS geojson FROM (
        SELECT 'FeatureCollection' AS type, array_to_json(array_agg(ft.f::json)) AS features FROM (
            SELECT ST_AsGeoJSON(t.*) AS f FROM {} AS t
        ) AS ft
    ) AS fc'''.format(geojson_table)

    # Execure SQL command
    cursor.execute(sql)

    # Fetch single row result. Format: tuple of single GeoJSON dict
    result = cursor.fetchone()

    if result and len(result):
        geojson = result[0]

        # Convert GeoJSON dictionary to GeoJSON string
        if isinstance(geojson, (dict,)):
            geojson = json.dumps(geojson)

        return Response(geojson, status=200, headers={'Content-Type': 'application/json'})

    # If no result, return HTTP error "500 Internal Server Error"
    return abort(500)


@app.route('/add-feature', methods=['POST'])
def add_feature():
    # Get table_name from client request
    table_name = request.form.get('table_name')

    # If geojson_table is empty, return HTTP error "400 Bad Request"
    if not table_name:
        return abort(400)

    # Get attribute keys
    keys = request.form.getlist('keys')

    # Get attribute values
    values = request.form.getlist('values')

    # Define SQL command
    sql = '''INSERT INTO {} ({}) VALUES ({})'''.format(table_name, ', '.join(keys), ', '.join(['%s' for i in keys]))

    # Create new database cursor instance
    cursor = connection.cursor()

    # Execure SQL command
    cursor.execute(sql, values)

    # Commit database changes
    cursor.commit()

    return redirect('/')


@app.route('/upload', methods=['POST'])
def upload():
    # Define file request key
    key = 'image'

    # If request files do not contain key, return HTTP error "400 Bad Request"
    if key not in request.files:
        return abort(400)

    # Get uploaded file
    image = request.files.get(key)

    # If uploaded file does not have filename, return HTTP error "400 Bad Request"
    if image.filename == '':
        return abort(400)

    # If uploaded file size > MAX_UPLOAD_SIZE, return HTTP error "400 Bad Request"
    image_stream = image.stream
    image_stream.seek(0, 2)
    if image_stream.tell() > MAX_UPLOAD_SIZE:
        return abort(400)

    # Check file extension
    if image and allowed_file(image.filename):
        filename = os.path.join(UPLOAD_FOLDER, secure_filename(image.filename))

        if os.path.exists(filename):
            return abort(400)

        image.save(filename)
        return redirect('/')


@app.route('/uploads/<path:filename>')
def uploaded_file(filename):
    return send_from_directory(UPLOAD_FOLDER, filename)
