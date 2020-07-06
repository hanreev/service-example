from flask import Flask, request, Response
from werkzeug.utils import secure_filename
import os
import psycopg2

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
app = Flask(__name__)

ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg'}


def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


@app.route('/')
def index():
    conn = psycopg2.connect('pgsql:host=localhost;port=5432')
    return 'Hello World'


@app.route('/upload/', methods=['POST'])
def upload():
    key = 'image'
    if key not in request.files:
        return Response('Invalid request', status=400, headers={'Content-Type': 'text/plain; charset=utf-8'})

    upload_dir = os.path.join(BASE_DIR, 'uploads')
    os.makedirs(upload_dir, exist_ok=True)

    image = request.files.get(key)
    if image.filename == '':
        return Response('Invalid request', status=400, headers={'Content-Type': 'text/plain; charset=utf-8'})

    if image and allowed_file(image.filename):
        filename = os.path.join(upload_dir, secure_filename(image.filename))

        if os.path.exists(filename):
            return Response('File already exists', status=400, headers={'Content-Type': 'text/plain; charset=utf-8'})

        image.save(filename)
        return 'File is uploaded successfully.'
