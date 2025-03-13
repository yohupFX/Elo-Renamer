<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"]) && isset($_POST["excelPath"])) {
    $uploadsDir = "uploads/";
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    $fileName = basename($_FILES["file"]["name"]);
    $filePath = $uploadsDir . $fileName;
    $excelPath = $_POST["excelPath"];

    if (!file_exists($excelPath)) {
        die("Excel file not found: " . htmlspecialchars($excelPath));
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $filePath)) {
        $zip = new ZipArchive();
        $extractPath = $uploadsDir . "extracted/";

        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0777, true);
        }

        if ($zip->open($filePath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            unlink($filePath); // Remove original ZIP after extraction
        } else {
            die("Failed to extract ZIP file.");
        }

        // Process files based on Excel data
        require 'vendor/autoload.php'; // If using PHPSpreadsheet
        use PhpOffice\PhpSpreadsheet\IOFactory;

        try {
            $spreadsheet = IOFactory::load($excelPath);
            $sheet = $spreadsheet->getActiveSheet();
            $nameDict = [];

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }
                if (count($cells) >= 2) {
                    $nameDict[$cells[0]] = $cells[1];
                }
            }
        } catch (Exception $e) {
            die("Error reading Excel file: " . $e->getMessage());
        }

        $processedZip = $uploadsDir . "processed_files.zip";
        $zip = new ZipArchive();
        $zip->open($processedZip, ZipArchive::CREATE);

        foreach (scandir($extractPath) as $file) {
            if ($file !== "." && $file !== "..") {
                $originalPath = $extractPath . $file;
                $newName = isset($nameDict[$file]) ? $nameDict[$file] . "_" . $file : "renamed_" . $file;
                $zip->addFile($originalPath, $newName);
            }
        }

        $zip->close();

        // Serve ZIP file to user
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=" . basename($processedZip));
        readfile($processedZip);

        // Cleanup
        array_map("unlink", glob("$extractPath/*"));
        rmdir($extractPath);
        unlink($processedZip);
    } else {
        echo "File upload failed.";
    }
} else {
    echo "No file uploaded or missing Excel path.";
}
?>
