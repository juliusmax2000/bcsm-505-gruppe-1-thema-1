<?php
require_once 'config.php';

function getJobListings() {
    $listings = [];
    if (file_exists('listings.json')) {
        $listings = json_decode(file_get_contents('listings.json'), true);
    }
    return $listings;
}

function saveJobListings($listings) {
    return file_put_contents('listings.json', json_encode($listings)) !== false;
}

function handleJobSubmission($post, $files) {
    if (!checkUploadLimit($_SERVER['REMOTE_ADDR'])) {
        return "Upload limit exceeded. Please try again later.";
    }

    if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
        return "Invalid email address";
    }

    if (!isset($files['pdf']) || $files['pdf']['error'] !== UPLOAD_ERR_OK) {
        return "PDF file is required. Error: " . $files['pdf']['error'];
    }

    $pdf = $files['pdf'];
    if ($pdf['size'] > MAX_FILE_SIZE) {
        return "File size exceeds the limit of 2MB. Size: " . $pdf['size'];
    }

    // Check file extension
    $fileExtension = strtolower(pathinfo($pdf['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'pdf') {
        return "Only PDF files are allowed.";
    }

    // Check MIME type using multiple methods
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($pdf['tmp_name']);
    
    if ($mimeType !== 'application/pdf') {
        return "Uploaded file is not a valid PDF. Detected MIME type: " . $mimeType;
    }

    $pdfContent = file_get_contents($pdf['tmp_name']);
    if ($pdfContent === false) {
        return "Failed to read PDF content.";
    }

    // Enhanced security checks
    if (stripos($pdfContent, '/JS') !== false || 
        stripos($pdfContent, '/JavaScript') !== false ||
        stripos($pdfContent, '/AA') !== false ||
        stripos($pdfContent, '/OpenAction') !== false ||
        stripos($pdfContent, '/Launch') !== false) {
        return "PDF contains potentially dangerous elements";
    }

    // Check for single page
    if (preg_match_all("/\/Page\W/", $pdfContent, $matches) > 1) {
        return "Only single-page PDFs are allowed. Detected pages: " . count($matches[0]);
    }

    // Generate a secure filename
    $filename = bin2hex(random_bytes(16)) . '.pdf';
    $uploadPath = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return "Failed to create upload directory.";
        }
    }

    if (!is_writable(UPLOAD_DIR)) {
        return "Upload directory is not writable.";
    }

    if (!move_uploaded_file($pdf['tmp_name'], $uploadPath)) {
        return "Failed to save the file. PHP Error: " . error_get_last()['message'];
    }

    // Set secure permissions
    chmod($uploadPath, 0644);

    $newListing = [
        'id' => bin2hex(random_bytes(16)),
        'title' => sanitizeInput($post['title']),
        'company' => sanitizeInput($post['company']),
        'description' => sanitizeInput($post['description']),
        'email' => sanitizeInput($post['email']),
        'pdf' => $filename,
        'createdAt' => time(),
        'expiresAt' => time() + LISTING_DURATION,
        'ip' => $_SERVER['REMOTE_ADDR']
    ];

    $listings = getJobListings();
    $listings[] = $newListing;
    if (!saveJobListings($listings)) {
        return "Failed to save job listing data.";
    }

    recordUpload($_SERVER['REMOTE_ADDR']);
    logAction("New job listing created: " . $newListing['title']);

    return true;
}

function removeExpiredListings() {
    $listings = getJobListings();
    $currentTime = time();
    $listings = array_filter($listings, function($listing) use ($currentTime) {
        if ($listing['expiresAt'] <= $currentTime) {
            @unlink(UPLOAD_DIR . $listing['pdf']);
            logAction("Expired listing removed: " . $listing['title']);
            return false;
        }
        return true;
    });
    saveJobListings(array_values($listings));
}

function notifyExpiringListings() {
    $listings = getJobListings();
    $sevenDaysFromNow = time() + (7 * 24 * 60 * 60);
    foreach ($listings as $listing) {
        if ($listing['expiresAt'] <= $sevenDaysFromNow && $listing['expiresAt'] > time()) {
            // In a real application, send an email notification here
            logAction("Notification: Job listing '{$listing['title']}' is expiring soon. Contact: {$listing['email']}");
        }
    }
}

function extendListingDuration($id) {
    $listings = getJobListings();
    foreach ($listings as &$listing) {
        if ($listing['id'] === $id) {
            $listing['expiresAt'] += LISTING_DURATION;
            logAction("Listing duration extended: " . $listing['title']);
            break;
        }
    }
    saveJobListings($listings);
}

function removeListing($id) {
    $listings = getJobListings();
    foreach ($listings as $key => $listing) {
        if ($listing['id'] === $id) {
            @unlink(UPLOAD_DIR . $listing['pdf']);
            unset($listings[$key]);
            logAction("Listing manually removed: " . $listing['title']);
            break;
        }
    }
    saveJobListings(array_values($listings));
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function checkUploadLimit($ip) {
    $uploads = getUploads();
    $currentTime = time();
    $recentUploads = array_filter($uploads, function($upload) use ($currentTime, $ip) {
        return $upload['ip'] === $ip && $currentTime - $upload['time'] < UPLOAD_LIMIT_WINDOW;
    });
    return count($recentUploads) < UPLOAD_LIMIT;
}

function recordUpload($ip) {
    $uploads = getUploads();
    $uploads[] = ['ip' => $ip, 'time' => time()];
    saveUploads($uploads);
}

function getUploads() {
    $uploads = [];
    if (file_exists('uploads.json')) {
        $uploads = json_decode(file_get_contents('uploads.json'), true);
    }
    return $uploads;
}

function saveUploads($uploads) {
    file_put_contents('uploads.json', json_encode($uploads));
}

function logAction($message) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}