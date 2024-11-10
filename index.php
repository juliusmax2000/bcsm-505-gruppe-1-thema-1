<?php
session_start();

// Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in Bytes
define('UPLOAD_DIR', 'uploads/');
define('DATA_FILE', 'Datenhalde.txt');

// Arrays for dropdown menus
$stellentypen = ["Jobs", "Praxisphasen & Praktika", "Abschlussarbeiten", "Werkstudentenstellen", "Traineestellen", "Studentische Hilfskräfte", "Tutorentätigkeit", "Jobs im Ausland", "Promotionen", "Nebenjobs (in der Region)", "Praktika", "Sonstiges"];
$fachbereiche = ["Fachbereich 01 Chemie", "Fachbereich 02 Design", "Fachbereich 03 Elektrotechnik und Informatik", "Fachbereich 04 Maschinenbau und Verfahrenstechnik", "Fachbereich 05 Oecotrophologie", "Fachbereich 06 Sozialwesen", "Fachbereich 07 Textil- und Bekleidungstechnik", "Fachbereich 08 Wirtschaftswissenschaften", "Fachbereich 09 Wirtschaftsingenieurwesen", "Fachbereich 10 Gesundheitswesen"];

// Function to save data to Datenhalde file
function saveToDatahalde($data) {
    $timestamp = date('Y-m-d H:i:s');
    $dataString = sprintf(
        "[%s] Firma: %s | Standort: %s | Stelle: %s | Typ: %s | Fachbereich: %s | PDF: %s\n",
        $timestamp,
        $data['firmenname'],
        $data['standort'],
        $data['stellenbezeichnung'],
        $data['stellentyp'],
        $data['fachbereich'],
        $data['pdf_filename']
    );
    
    return file_put_contents(DATA_FILE, $dataString, FILE_APPEND | LOCK_EX);
}

// Generate CAPTCHA
function generateCaptcha() {
    $code = rand(1000, 9999);
    $_SESSION['captcha'] = $code;
    return $code;
}

// Check PDF for malicious code
function checkPDFSecurity($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
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
    // CAPTCHA validation
    if (!isset($_POST['captcha']) || !isset($_SESSION['captcha']) || $_POST['captcha'] != $_SESSION['captcha']) {
        $message = '<div class="error">Falscher CAPTCHA-Code!</div>';
    } else {
        // Form data validation and sanitization
        $formData = [
            'firmenname' => filter_var($_POST['firmenname'] ?? '', FILTER_SANITIZE_STRING),
            'standort' => filter_var($_POST['standort'] ?? '', FILTER_SANITIZE_STRING),
            'stellenbezeichnung' => filter_var($_POST['stellenbezeichnung'] ?? '', FILTER_SANITIZE_STRING),
            'stellentyp' => filter_var($_POST['stellentyp'] ?? '', FILTER_SANITIZE_STRING),
            'fachbereich' => filter_var($_POST['fachbereich'] ?? '', FILTER_SANITIZE_STRING)
        ];

        // Validate required fields
        if (empty(array_filter($formData))) {
            $message = '<div class="error">Bitte füllen Sie alle Pflichtfelder aus!</div>';
        } elseif (!isset($_FILES['pdf_file']) || empty($_FILES['pdf_file']['name'])) {
            $message = '<div class="error">Bitte laden Sie eine PDF-Datei hoch!</div>';
        } else {
            $file = $_FILES['pdf_file'];
            
            // Check file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $message = '<div class="error">Die Datei ist zu groß! Maximale Größe ist 5MB.</div>';
            }
            // Check file type
            elseif ($file['type'] !== 'application/pdf') {
                $message = '<div class="error">Nur PDF-Dateien sind erlaubt!</div>';
            }
            else {
                // Create upload directory if it doesn't exist
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                
                // Generate unique filename
                $pdf_filename = uniqid() . '_' . basename($file['name']);
                $upload_path = UPLOAD_DIR . $pdf_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Security check for PDF
                    if (checkPDFSecurity($upload_path)) {
                        // Add PDF filename to form data
                        $formData['pdf_filename'] = $pdf_filename;
                        
                        // Save data to Datenhalde
                        if (saveToDatahalde($formData)) {
                            $message = '<div class="success">Datei wurde erfolgreich hochgeladen und Daten gespeichert!</div>';
                        } else {
                            $message = '<div class="error">Fehler beim Speichern der Daten!</div>';
                            // Clean up uploaded file if data saving fails
                            unlink($upload_path);
                        }
                    } else {
                        unlink($upload_path); // Delete suspicious file
                        $message = '<div class="error">Die PDF-Datei enthält möglicherweise schädlichen Code!</div>';
                    }
                } else {
                    $message = '<div class="error">Fehler beim Hochladen der Datei!</div>';
                }
            }
        }
    }
}

// Generate new CAPTCHA code
$captcha = generateCaptcha();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>HSNR PDF Upload</title>
    <style>
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        .form-group { margin: 10px 0; }
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
