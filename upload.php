<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $uploadsDir = "uploads/";
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    $fileName = basename($_FILES["file"]["name"]);
    $filePath = $uploadsDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $filePath)) {
        // Create a ZIP archive
        $zip = new ZipArchive();
        $zipName = $uploadsDir . "processed_" . pathinfo($fileName, PATHINFO_FILENAME) . ".zip";

        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($filePath, "renamed_" . $fileName);
            $zip->close();
            
            // Serve the ZIP file to the user
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=" . basename($zipName));
            readfile($zipName);

            // Clean up
            unlink($filePath);
            unlink($zipName);
        } else {
            echo "Failed to create ZIP file.";
        }
    } else {
        echo "File upload failed.";
    }
} else {
    echo "No file uploaded.";
}
?>
