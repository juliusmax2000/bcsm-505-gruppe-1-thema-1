<?php
session_start();

// Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in Bytes
define('UPLOAD_DIR', 'uploads/');
define('DATA_FILE', 'Datenhalde.json');

// Variable for enable/disable submit button
$is_button_enabled = true;

// Arrays for dropdown menus
$stellentypen = ["Jobs", "Praxisphasen & Praktika", "Abschlussarbeiten", "Werkstudentenstellen", "Traineestellen", "Studentische Hilfskräfte", "Tutorentätigkeit", "Jobs im Ausland", "Promotionen", "Nebenjobs (in der Region)", "Praktika", "Sonstiges"];
$fachbereiche = ["Fachbereich 01 Chemie", "Fachbereich 02 Design", "Fachbereich 03 Elektrotechnik und Informatik", "Fachbereich 04 Maschinenbau und Verfahrenstechnik", "Fachbereich 05 Oecotrophologie", "Fachbereich 06 Sozialwesen", "Fachbereich 07 Textil- und Bekleidungstechnik", "Fachbereich 08 Wirtschaftswissenschaften", "Fachbereich 09 Wirtschaftsingenieurwesen", "Fachbereich 10 Gesundheitswesen"];

// Function to save data to Datenhalde file
function saveToDatahalde($data)
{
    $timestamp = date('Y-m-d H:i:s');
    $dataString = array();
    $dataString = array(
        'Firma' => $data['firmenname'],
        'Standort' => $data['standort'],
        'Stelle' => $data['stellenbezeichnung'],
        'Typ' => $data['stellentyp'],
        'Fachbereich' => $data['fachbereich'],
        'PDF' => $data['pdf_filename'],
        'hochgeladen' => $timestamp
    );
    // Parse form data to json
    $jsonData = json_encode($dataString);

    return file_put_contents(DATA_FILE, $jsonData, FILE_APPEND | LOCK_EX);
}

// Check if file is a valid PDF
function isPDF($filepath)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    // Check for PDF mime type
    if ($mimeType === 'application/pdf') {
        // Additional basic PDF header check
        $handle = fopen($filepath, 'rb');
        if ($handle) {
            $header = fread($handle, 4);
            fclose($handle);
            return $header === '%PDF';
        }
    }
    return false;
}

function checkCaptcha():bool {
    // Get the Turnstile response token and IP address
    $captchaToken = $_POST['cf-turnstile-response'];
    $ip = $_SERVER['REMOTE_ADDR']; // Get the user's IP address

    // Prepare the data for the verification request
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => '0x4AAAAAAAzudDvOJ1LZy4uA5Ni44ZoDvSE',
        'response' => $captchaToken,
        'remoteip' => $ip
    ];

    $ch = curl_init();
        
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
    // Execute the cURL request and get the response
    $response = curl_exec($ch);
        
    // Check if there was an error with the cURL request
    if ($response === false) {
        echo 'Error with cURL: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }

    // Close the cURL session
    curl_close($ch);
    
    // Decode the JSON response from Cloudflare
    $outcome = json_decode($response, true);

    // Return token validation message
    return $outcome['success'];
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify turnkey captcha
    if (checkCaptcha()) {
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
    } else {
        $message = '<div class="error">Captcha wurde nicht authorisiert!</div>';
    }
} else {
    ?>
    <script>
        // Generate new CAPTCHA for fresh page load
        turnstile.reset(cfCaptcha);
    </script>
    <?php
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>HSNR PDF Upload</title>
    <style>
        .error {
            color: red;
            margin: 10px 0;
        }

        .success {
            color: green;
            margin: 10px 0;
        }

        .form-group {
            margin: 10px 0;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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

        <div id="cfCaptcha" class="cf-turnstile" data-sitekey="0x4AAAAAAAzudMkcbeUQtbl5"></div>

        <script>
            // Function to execute every 30 seconds
            function resetTurnstile() {
                if (typeof turnstile !== 'undefined') {
                    turnstile.reset(cfCaptcha);
                }
            }
            setInterval(resetTurnstile, 30000);
        </script>

        <button type="submit" <?php echo $is_button_enabled ? '' : 'disabled'; ?>>Hochladen</button>
    </form>
</body>

</html>