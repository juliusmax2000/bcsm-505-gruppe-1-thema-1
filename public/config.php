<?php 
// config.php
// Configuration with absolute paths
$root_path = dirname(__FILE__);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in Bytes
define('UPLOAD_DIR', $root_path . '/uploads/');

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Variables for Cloudflare Turnstile Captcha-Keys
$site_key = '0x4AAAAAAAzudMkcbeUQtbl5';
$secret_key = '0x4AAAAAAAzudDvOJ1LZy4uA5Ni44ZoDvSE';

// Arrays for dropdown menus
$stellentypen = ["Jobs", "Praxisphasen & Praktika", "Abschlussarbeiten", "Werkstudentenstellen", "Traineestellen", "Studentische Hilfskräfte", "Tutorentätigkeit", "Jobs im Ausland", "Promotionen", "Nebenjobs (in der Region)", "Praktika", "Sonstiges"];
$fachbereiche = ["Fachbereich 01 Chemie", "Fachbereich 02 Design", "Fachbereich 03 Elektrotechnik und Informatik", "Fachbereich 04 Maschinenbau und Verfahrenstechnik", "Fachbereich 05 Oecotrophologie", "Fachbereich 06 Sozialwesen", "Fachbereich 07 Textil- und Bekleidungstechnik", "Fachbereich 08 Wirtschaftswissenschaften", "Fachbereich 09 Wirtschaftsingenieurwesen", "Fachbereich 10 Gesundheitswesen"];

// Variables for error and success message
$error_message = '';
$success_message = '';

// Variable for enable/disable submit button
$is_button_enabled = true;
?>