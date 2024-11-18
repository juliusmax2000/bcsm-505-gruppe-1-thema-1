<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Import configuration and functions file
require_once 'config.php';
require_once 'functions.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCaptcha()) {
        $error_message = "Captcha-Verifizierung fehlgeschlagen. Bitte versuchen Sie es erneut.";
    } else {
        // Form data validation and sanitization
        $formData = [
            'firmenname' => filter_var($_POST['firmenname'] ?? '', FILTER_SANITIZE_STRING),
            'standort' => filter_var($_POST['standort'] ?? '', FILTER_SANITIZE_STRING),
            'stellenbezeichnung' => filter_var($_POST['stellenbezeichnung'] ?? '', FILTER_SANITIZE_STRING),
            'stellentyp' => filter_var($_POST['stellentyp'] ?? '', FILTER_SANITIZE_STRING),
            'fachbereich' => filter_var($_POST['fachbereich'] ?? '', FILTER_SANITIZE_STRING)
        ];

        if (empty(array_filter($formData))) {
            $message = '<div class="error">Bitte füllen Sie alle Pflichtfelder aus!</div>';
        } elseif (!isset($_FILES['pdf_file']) || empty($_FILES['pdf_file']['name'])) {
            $message = '<div class="error">Bitte laden Sie eine PDF-Datei hoch!</div>';
        } else {
            $file = $_FILES['pdf_file'];

            if ($file['size'] > MAX_FILE_SIZE) {
                $message = '<div class="error">Die Datei ist zu groß! Maximale Größe ist 5MB.</div>';
            } else {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                $pdf_filename = uniqid() . '_' . basename($file['name']);
                $upload_path = UPLOAD_DIR . $pdf_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    if (isPDF($upload_path)) {
                        $formData['pdf_filename'] = $pdf_filename;

                        if (saveToDatahalde($formData)) {
                            $message = '<div class="success">Datei wurde erfolgreich hochgeladen und Daten gespeichert!</div>';
                        } else {
                            $message = '<div class="error">Fehler beim Speichern der Daten!</div>';
                            unlink($upload_path);
                        }
                    } else {
                        unlink($upload_path);
                        $message = '<div class="error">Die hochgeladene Datei ist keine gültige PDF-Datei!</div>';
                    }
                } else {
                    $message = '<div class="error">Fehler beim Hochladen der Datei!</div>';
                }
            }
        }
    }
} else {
    ?>
    <script>
        // Reset CAPTCHA for fresh page load
        turnstile.reset(cfCaptcha);
    </script>
    <?php
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
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ffcdd2;
            border-radius: 4px;
            background-color: #ffebee;
        }
        .success {
            color: green;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #c8e6c9;
            border-radius: 4px;
            background-color: #e8f5e9;
        }
        .file-info {
            margin-top: 20px;
            padding: 10px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
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
                <?php foreach ($stellentypen as $typ): ?>
                    <option value="<?php echo htmlspecialchars($typ); ?>"><?php echo htmlspecialchars($typ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="fachbereich">Fachbereich:</label>
            <select id="fachbereich" name="fachbereich" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($fachbereiche as $fb): ?>
                    <option value="<?php echo htmlspecialchars($fb); ?>"><?php echo htmlspecialchars($fb); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pdf_file">PDF-Datei (max. 5MB):</label>
            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
        </div>

        <div class="form-group">
            <div id="cfCaptcha" class="cf-turnstile" data-sitekey="0x4AAAAAAAzudMkcbeUQtbl5"></div>
        </div>

        <div class="file-info">
            <strong>Upload-Informationen:</strong><br>
            - Maximale Dateigröße: 5MB<br>
            - Erlaubtes Format: PDF<br>
            - Upload-Verzeichnis: <?php echo UPLOAD_DIR; ?><br>
            - Daten-Speicherort: <?php echo DATA_FILE; ?>
        </div>

        <button type="submit" <?php echo $is_button_enabled ? '' : 'disabled'; ?>>Hochladen</button>
    </form>

    <script>
        // Function to reset Turnstile every 30 seconds
        function resetTurnstile() {
            if (typeof turnstile !== 'undefined') {
                turnstile.reset(cfCaptcha);
            }
        }
        setInterval(resetTurnstile, 30000);
    </script>
</body>
</html>