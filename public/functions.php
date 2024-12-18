<?php
// functions.php
// Function to save data to JSON file
function saveToDatahalde($data, $filepath) {
    try {
        // Prepare new entry -  stellentyp and fachbereich are now arrays
        $newEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'firma' => $data['firmenname'],
            'standort' => $data['standort'],
            'stelle' => $data['stellenbezeichnung'],
            'typ' => $data['stellentyp'], // Store as an array
            'fachbereich' => $data['fachbereich'], // Store as an array
            'pdf_filename' => $data['pdf_filename']
        ];
        
        // Save back to file
        return file_put_contents(substr($filepath, 0, -4) . '.json', json_encode($newEntry, JSON_PRETTY_PRINT), LOCK_EX);
    } catch (Exception $e) {
        error_log("Error saving to Datenhalde: " . $e->getMessage());
        return false;
    }
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

// Check if the captcha is valid
function checkCaptcha() {
    // Import the "$secret_key"-Variable from Config-File
    global $secret_key;
    // Get the Turnstile response token and IP address
    $captchaToken = $_POST['cf-turnstile-response'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Prepare the data for the verification request
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secret_key,
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
        error_log('Error with cURL: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    // Close the cURL session
    curl_close($ch);
    // Decode the JSON response from Cloudflare
    $outcome = json_decode($response, true);
    // Return token validation message or false
    return $outcome['success'] ?? false;
}
?>