<?php
session_start();

// Configuration with absolute paths
$root_path = dirname(__FILE__);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in Bytes
define('UPLOAD_DIR', $root_path . '/uploads/');
define('DATA_FILE', $root_path . '/Datenhalde.json');

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Create JSON file if it doesn't exist
if (!file_exists(DATA_FILE)) {
    file_put_contents(DATA_FILE, "[]", LOCK_EX);
    chmod(DATA_FILE, 0644);
}

// Arrays for dropdown menus
$stellentypen = ["Jobs", "Praxisphasen & Praktika", "Abschlussarbeiten", "Werkstudentenstellen", "Traineestellen", "Studentische Hilfskräfte", "Tutorentätigkeit", "Jobs im Ausland", "Promotionen", "Nebenjobs (in der Region)", "Praktika", "Sonstiges"];
$fachbereiche = ["Fachbereich 01 Chemie", "Fachbereich 02 Design", "Fachbereich 03 Elektrotechnik und Informatik", "Fachbereich 04 Maschinenbau und Verfahrenstechnik", "Fachbereich 05 Oecotrophologie", "Fachbereich 06 Sozialwesen", "Fachbereich 07 Textil- und Bekleidungstechnik", "Fachbereich 08 Wirtschaftswissenschaften", "Fachbereich 09 Wirtschaftsingenieurwesen", "Fachbereich 10 Gesundheitswesen"];

// Function to save data to JSON file
function saveToDatahalde($data) {
    try {
        // Read existing data
        $jsonContent = file_get_contents(DATA_FILE);
        $existingData = json_decode($jsonContent, true) ?: [];
        
        // Prepare new entry
        $newEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'firma' => $data['firmenname'],
            'standort' => $data['standort'],
            'stelle' => $data['stellenbezeichnung'],
            'typ' => $data['stellentyp'],
            'fachbereich' => $data['fachbereich'],
            'pdf_filename' => $data['pdf_filename']
        ];
        
        // Add new entry to array
        $existingData[] = $newEntry;
        
        // Save back to file
        return file_put_contents(DATA_FILE, json_encode($existingData, JSON_PRETTY_PRINT), LOCK_EX);
    } catch (Exception $e) {
        error_log("Error saving to Datenhalde: " . $e->getMessage());
        return false;
    }
}

// Check if file is a valid PDF
function isPDF($filepath) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    
    return $mimeType === 'application/pdf';
}

function checkCaptcha() {
    $captchaToken = $_POST['cf-turnstile-response'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => '0x4AAAAAAAzudDvOJ1LZy4uA5Ni44ZoDvSE',
        'response' => $captchaToken,
        'remoteip' => $ip
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Error with cURL: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    $outcome = json_decode($response, true);
    return $outcome['success'] ?? false;
}

$error_message = '';
$success_message = '';

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
            $error_message = "Bitte füllen Sie alle Pflichtfelder aus.";
        } elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "Bitte laden Sie eine PDF-Datei hoch.";
        } else {
            $file = $_FILES['pdf_file'];
            
            if ($file['size'] > MAX_FILE_SIZE) {
                $error_message = "Die Datei ist zu groß. Maximale Größe ist 5MB.";
            } else {
                $pdf_filename = uniqid() . '_' . basename($file['name']);
                $upload_path = UPLOAD_DIR . $pdf_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    if (isPDF($upload_path)) {
                        $formData['pdf_filename'] = $pdf_filename;
                        
                        if (saveToDatahalde($formData)) {
                            $success_message = "Ihre Stellenanzeige wurde erfolgreich hochgeladen!";
                        } else {
                            $error_message = "Fehler beim Speichern der Daten.";
                            unlink($upload_path);
                        }
                    } else {
                        unlink($upload_path);
                        $error_message = "Die hochgeladene Datei ist keine gültige PDF-Datei.";
                    }
                } else {
                    $error_message = "Fehler beim Hochladen der Datei.";
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

        <button type="submit">Hochladen</button>
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