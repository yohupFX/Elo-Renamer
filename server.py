from flask import Flask, request, send_file
import os
import zipfile
import pandas as pd

app = Flask(__name__)
UPLOAD_FOLDER = "uploads"
EXTRACT_FOLDER = os.path.join(UPLOAD_FOLDER, "extracted")
os.makedirs(EXTRACT_FOLDER, exist_ok=True)

@app.route("/process", methods=["POST"])
def process_file():
    if "file" not in request.files or "excelPath" not in request.form:
        return "No file uploaded or missing Excel path.", 400

    file = request.files["file"]
    file_path = os.path.join(UPLOAD_FOLDER, file.filename)
    file.save(file_path)

    excel_path = request.form["excelPath"]

    if not os.path.exists(excel_path):
        return f"Excel file not found: {excel_path}", 400

    try:
        df = pd.read_excel(excel_path, usecols=[0, 1], header=0)
        name_dict = dict(zip(df.iloc[:, 0].astype(str), df.iloc[:, 1]))
    except Exception as e:
        return f"Error reading Excel file: {e}", 400

    zip_path = os.path.join(UPLOAD_FOLDER, "processed_files.zip")

    with zipfile.ZipFile(file_path, 'r') as zip_ref:
        zip_ref.extractall(EXTRACT_FOLDER)

    with zipfile.ZipFile(zip_path, "w") as zipf:
        for file in os.listdir(EXTRACT_FOLDER):
            original_path = os.path.join(EXTRACT_FOLDER, file)
            new_name = name_dict.get(file, f"renamed_{file}")
            zipf.write(original_path, new_name)

    return send_file(zip_path, as_attachment=True)

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
