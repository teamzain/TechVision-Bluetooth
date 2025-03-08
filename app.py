from flask import Flask, render_template, jsonify
import subprocess

app = Flask(__name__)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/fetch-trunks', methods=['POST'])
def fetch_trunks():
    # Run the Puppeteer script using Node.js
    result = subprocess.run(['node', 'fetch_trunks.js'], capture_output=True, text=True)
    trunks = result.stdout.strip().split('\n')  # Assuming each trunk is on a new line in the output
    return jsonify(trunks)

if __name__ == '__main__':
    app.run(debug=True)
