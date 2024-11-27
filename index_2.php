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
            'stellentyp' => $_POST['stellentyp'] ?? '',
            'fachbereich' => $_POST['fachbereich'] ?? ''
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
} else {
    ?>
    <script>
        // Reset CAPTCHA for fresh page load
        if (typeof turnstile !== 'undefined') {
            turnstile.reset(cfCaptcha);
        }
    </script>
    <?php
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
            <label for="stellentyp">Stellentyp:</label>
            <select id="stellentyp" name="stellentyp" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($stellentypen as $typ): ?>
                    <option value="<?php echo htmlspecialchars($typ); ?>"><?php echo htmlspecialchars($typ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <input type="checkbox" name="departments[]" id="dept1" value="Fachbereich 01 Chemie">
            <label for="dept1">Fachbereich 01 Chemie</label><br>

            <input type="checkbox" name="departments[]" id="dept2" value="Fachbereich 02 Design">
            <label for="dept2">Fachbereich 02 Design</label><br>

            <input type="checkbox" name="departments[]" id="dept3" value="Fachbereich 03 Elektrotechnik und Informatik">
            <label for="dept3">Fachbereich 03 Elektrotechnik und Informatik</label><br>

            <input type="checkbox" name="departments[]" id="dept4" value="Fachbereich 04 Maschinenbau und Verfahrenstechnik">
            <label for="dept4">Fachbereich 04 Maschinenbau und Verfahrenstechnik</label><br>

            <input type="checkbox" name="departments[]" id="dept5" value="Fachbereich 05 Oecotrophologie">
            <label for="dept5">Fachbereich 05 Oecotrophologie</label><br>

            <input type="checkbox" name="departments[]" id="dept6" value="Fachbereich 06 Sozialwesen">
            <label for="dept6">Fachbereich 06 Sozialwesen</label><br>

            <input type="checkbox" name="departments[]" id="dept7" value="Fachbereich 07 Textil- und Bekleidungstechnik">
            <label for="dept7">Fachbereich 07 Textil- und Bekleidungstechnik</label><br>

            <input type="checkbox" name="departments[]" id="dept8" value="Fachbereich 08 Wirtschaftswissenschaften">
            <label for="dept8">Fachbereich 08 Wirtschaftswissenschaften</label><br>

            <input type="checkbox" name="departments[]" id="dept9" value="Fachbereich 09 Wirtschaftsingenieurwesen">
            <label for="dept9">Fachbereich 09 Wirtschaftsingenieurwesen</label><br>

            <input type="checkbox" name="departments[]" id="dept10" value="Fachbereich 10 Gesundheitswesen">
            <label for="dept10">Fachbereich 10 Gesundheitswesen</label><br>

            <br>
            <input type="checkbox" name="departments[]" id="all_dept" value="AlleFachbereiche">
            <label for="all_dept">Alle Fachbereiche</label><br>
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