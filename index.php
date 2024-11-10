<?php
session_start();

// Konfiguration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in Bytes
define('UPLOAD_DIR', 'uploads/');

// Initialisierung der Arrays für Dropdown-Menüs
$stellentypen = ["Jobs", "Praxisphasen & Praktika", "Abschlussarbeiten", " Werkstudentenstellen", "Traineestellen", "Studentische Hilfskräfte", "Tutorentätigkeit", "Jobs im Ausland", "Promotionen", "Nebenjobs (in der Region)", "Praktika", "Sonstiges"];
$fachbereiche = ["Fachbereich 01 Chemie", "Fachbereich 02 Design", "Fachbereich 03 Elektrotechnik und Informatik", "Fachbereich 04 Maschinenbau und Verfahrenstechnik", "Fachbereich 05 Oecotrophologie", "Fachbereich 06 Sozialwesen", "Fachbereich 07 Textil- und Bekleidungstechnik", "Fachbereich 08 Wirtschaftswissenschaften", "Fachbereich 09 Wirtschaftsingenieurwesen", "Fachbereich 10 Gesundheitswesen"];

// CAPTCHA generieren
function generateCaptcha() {
    $code = rand(1000, 9999);
    $_SESSION['captcha'] = $code;
    return $code;
}

// PDF auf Schadcode prüfen
function checkPDFSecurity($filepath) {
    // Überprüfung der PDF-Datei auf bekannte schädliche Muster
    $content = file_get_contents($filepath);
    $suspicious_patterns = [
        '/JavaScript/i',
        '/JS/i',
        '/Launch/i',
        '/OpenAction/i',
        '/AA/i'
    ];
    
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return false;
        }
    }
    
    return true;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CAPTCHA Validierung
    if ($_POST['captcha'] != $_SESSION['captcha']) {
        $message = '<div class="error">Falscher CAPTCHA-Code!</div>';
    } else {
        // Validierung der Formulardaten
        $firmenname = filter_input(INPUT_POST, 'firmenname', FILTER_SANITIZE_STRING);
        $standort = filter_input(INPUT_POST, 'standort', FILTER_SANITIZE_STRING);
        $stellenbezeichnung = filter_input(INPUT_POST, 'stellenbezeichnung', FILTER_SANITIZE_STRING);
        $stellentyp = filter_input(INPUT_POST, 'stellentyp', FILTER_SANITIZE_STRING);
        $fachbereich = filter_input(INPUT_POST, 'fachbereich', FILTER_SANITIZE_STRING);

        if (!empty($_FILES['pdf_file'])) {
            $file = $_FILES['pdf_file'];
            
            // Überprüfung der Dateigröße
            if ($file['size'] > MAX_FILE_SIZE) {
                $message = '<div class="error">Die Datei ist zu groß! Maximale Größe ist 5MB.</div>';
            }
            // Überprüfung des Dateityps
            elseif ($file['type'] !== 'application/pdf') {
                $message = '<div class="error">Nur PDF-Dateien sind erlaubt!</div>';
            }
            else {
                $upload_path = UPLOAD_DIR . basename($file['name']);
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Sicherheitscheck der PDF
                    if (checkPDFSecurity($upload_path)) {
                        // Hier könnte man die Metadaten in einer Datenbank speichern
                        $message = '<div class="success">Datei wurde erfolgreich hochgeladen!</div>';
                    } else {
                        unlink($upload_path); // Verdächtige Datei löschen
                        $message = '<div class="error">Die PDF-Datei enthält möglicherweise schädlichen Code!</div>';
                    }
                } else {
                    $message = '<div class="error">Fehler beim Hochladen der Datei!</div>';
                }
            }
        }
    }
}

// Neuen CAPTCHA-Code generieren
$captcha = generateCaptcha();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>HSNR PDF Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            padding: 10px;
            border: 1px solid red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            padding: 10px;
            border: 1px solid green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>PDF-Dateiupload für die Stellenbörse der Hochschule Niederrhein</h1>
    
    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="firmenname">Firmenname:</label>
            <input type="text" name="firmenname" id="firmenname" required>
        </div>

        <div class="form-group">
            <label for="standort">Standort:</label>
            <input type="text" name="standort" id="standort" required>
        </div>

        <div class="form-group">
            <label for="stellenbezeichnung">Stellenbezeichnung:</label>
            <input type="text" name="stellenbezeichnung" id="stellenbezeichnung" required>
        </div>

        <div class="form-group">
            <label for="stellentyp">Stellentyp:</label>
            <select name="stellentyp" id="stellentyp" required>
                <?php foreach ($stellentypen as $typ): ?>
                    <option value="<?php echo htmlspecialchars($typ); ?>"><?php echo htmlspecialchars($typ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="fachbereich">Fachbereich:</label>
            <select name="fachbereich" id="fachbereich" required>
                <?php foreach ($fachbereiche as $fb): ?>
                    <option value="<?php echo htmlspecialchars($fb); ?>"><?php echo htmlspecialchars($fb); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pdf_file">PDF-Datei (max. 5MB):</label>
            <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required>
        </div>

        <div class="form-group">
            <label for="captcha">CAPTCHA-Code eingeben: <?php echo $captcha; ?></label>
            <input type="text" name="captcha" id="captcha" required>
        </div>

        <button type="submit">Hochladen</button>
    </form>
</body>
</html>
