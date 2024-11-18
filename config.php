<?php
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

// Variable for enable/disable submit button
$is_button_enabled = true;

// Arrays for dropdown menus
$stellentypen = ["Jobs", "Praxisphasen & Praktika", "Abschlussarbeiten", "Werkstudentenstellen", "Traineestellen", "Studentische Hilfskräfte", "Tutorentätigkeit", "Jobs im Ausland", "Promotionen", "Nebenjobs (in der Region)", "Praktika", "Sonstiges"];
$fachbereiche = ["Fachbereich 01 Chemie", "Fachbereich 02 Design", "Fachbereich 03 Elektrotechnik und Informatik", "Fachbereich 04 Maschinenbau und Verfahrenstechnik", "Fachbereich 05 Oecotrophologie", "Fachbereich 06 Sozialwesen", "Fachbereich 07 Textil- und Bekleidungstechnik", "Fachbereich 08 Wirtschaftswissenschaften", "Fachbereich 09 Wirtschaftsingenieurwesen", "Fachbereich 10 Gesundheitswesen"];

// Variables for error and success message
$error_message = '';
$success_message = '';
?>