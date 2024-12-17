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
            'firmenname' => $_POST['firmenname'] ?? '',
            'standort' => $_POST['standort'] ?? '',
            'stellenbezeichnung' => $_POST['stellenbezeichnung'] ?? '',
            'stellentyp' => $_POST['stellentyp'] ?? [], 
            'fachbereich' => $_POST['fachbereich'] ?? []  
        ];

        if (empty(array_filter($formData))) {
            $error_message = 'Bitte füllen Sie alle Pflichtfelder aus!';
        } elseif (!isset($_FILES['pdf_file']) || empty($_FILES['pdf_file']['name'])) {
            $error_message = 'Bitte laden Sie eine PDF-Datei hoch!';
        } else {
            $file = $_FILES['pdf_file'];

            if ($file['size'] > MAX_FILE_SIZE) {
                $error_message = 'Die Datei ist zu groß! Maximale Größe ist 5MB.';
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
                            $success_message = 'Datei wurde erfolgreich hochgeladen und Daten gespeichert!';
                        } else {
                            $error_message = 'Fehler beim Speichern der Daten!';
                            unlink($upload_path);
                        }
                    } else {
                        unlink($upload_path);
                        $error_message = 'Die hochgeladene Datei ist keine gültige PDF-Datei!';
                    }
                } else {
                    $error_message = 'Fehler beim Hochladen der Datei!';
                }
            }
        }
    }
} 
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="https://www.hs-niederrhein.de/fileadmin/favicon.ico" type="image/vnd.microsoft.icon">
    <img src="https://app.hn.de/img/logo_big.png" alt="Hochschule Niederrhein" align="center" height="258px" width="800px">
    <title>PDF Dateiupload</title>
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
            margin-top: 15px;
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
        /* Style für die Mehrfachauswahl */
        select[multiple] option:checked { 
            background-color: #f0f0f5; 
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <h1>PDF Dateiupload für die Stellenbörse</h1>

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
      <label for="stellentyp">Stellentyp: <small>(durch Halten der Strg Taste Mehrfachauswahl möglich)</small></label>
      <select id="stellentyp" name="stellentyp[]" multiple required size="<?php echo count($stellentypen); ?>">
        <?php foreach ($stellentypen as $typ): ?>
        <option value="<?php echo htmlspecialchars($typ); ?>">
          <?php echo htmlspecialchars($typ); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="fachbereich">Fachbereich: <small>(durch Halten der Strg Taste Mehrfachauswahl möglich)</small></label>
      <select id="fachbereich" name="fachbereich[]" multiple required size="<?php echo count($fachbereiche); ?>">
        <?php foreach ($fachbereiche as $fb): ?>
        <option value="<?php echo htmlspecialchars($fb); ?>">
          <?php echo htmlspecialchars($fb); ?>
        </option>
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
        </div>

        <button id="submit" type="submit" <?php echo $is_button_enabled ? '' : 'disabled'; ?>>Hochladen</button>
        <button type="reset">Reset</button>
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