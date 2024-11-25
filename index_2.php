<?php
// Initialize error message variable
$error_message = '';
$success_message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form fields
    $firmenname = $_POST['firmenname'] ?? '';
    $standort = $_POST['standort'] ?? '';
    $stellenbezeichnung = $_POST['stellenbezeichnung'] ?? '';
    $stellentyp = $_POST['stellentyp'] ?? '';
    $fachbereich = $_POST['fachbereich'] ?? '';

    // Validate required fields
    if (empty($firmenname) || empty($standort) || empty($stellenbezeichnung) || 
        empty($stellentyp) || empty($fachbereich)) {
        $error_message = "Bitte füllen Sie alle Pflichtfelder aus.";
    }

    // Check if file was uploaded
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $file = $_FILES['pdf_file'];
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_size = $file['size'];
        $max_size = 5 * 1024 * 1024; // 5MB in bytes

        // Validate file type and size
        if ($file_type != "pdf") {
            $error_message = "Nur PDF-Dateien sind erlaubt.";
        } elseif ($file_size > $max_size) {
            $error_message = "Die Datei ist zu groß. Maximale Größe ist 5MB.";
        } else {
            // Generate unique filename
            $upload_dir = "uploads/";
            $new_filename = uniqid() . "_" . $file['name'];
            
            // Create upload directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                $success_message = "Ihre Stellenanzeige wurde erfolgreich hochgeladen!";
                // Here you would typically save the form data and file path to a database
            } else {
                $error_message = "Fehler beim Hochladen der Datei.";
            }
        }
    } else {
        $error_message = "Bitte laden Sie eine PDF-Datei hoch.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>PDF Dateiupload für die Stellenbörse der Hochschule Niederrhein</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <img src="https://app.hn.de/img/logo_big.png">

    <h1>PDF Dateiupload für die Stellenbörse der Hochschule Niederrhein</h1>
    
    <?php if ($error_message): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="firmenname">Firmenname:</label>
            <input type="text" id="firmenname" name="firmenname" required>
        </div>

        <div class="form-group">
            <label for="standort">Standort:</label>
            <input type="text" id="standort" name="standort" required>
        </div>

        <div class="form-group">
            <label for="stellenbezeichnung">Stellenbezeichnung:</label>
            <input type="text" id="stellenbezeichnung" name="stellenbezeichnung" required>
        </div>

        <div class="form-group">
            <label for="stellentyp">Stellentyp:</label>
            <select id="stellentyp" name="stellentyp" required>
                <option value="">Bitte wählen</option>
                <option value="jobs">Jobs</option>
                <option value="praxisphasen">Praxisphasen & Praktika</option>
                <option value="abschlussarbeiten">Abschlussarbeiten</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fachbereich">Fachbereich:</label>
            <select id="fachbereich" name="fachbereich" required>
                <option value="">Bitte wählen</option>
                <?php for($i = 1; $i <= 10; $i++): ?>
                    <option value="fachbereich<?php echo $i; ?>">Fachbereich <?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pdf_file">PDF-Datei (max. 5MB):</label>
            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
        </div>

        <input type="submit" value="Hochladen">
    </form>
</body>
</html>