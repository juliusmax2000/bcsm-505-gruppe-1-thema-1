<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'functions.php';

$error = $success = '';

// Handle login
if (isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        $success = "Logged in successfully";
        logAction("Admin logged in");
    } else {
        $error = "Invalid password";
        logAction("Failed login attempt");
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['logged_in']);
    session_destroy();
    $success = "Logged out successfully";
    logAction("Admin logged out");
    header("Location: index.php");
    exit();
}

// Handle job submission
if (isset($_POST['submit_job']) && isset($_SESSION['logged_in'])) {
    $result = handleJobSubmission($_POST, $_FILES);
    if ($result === true) {
        $success = "Job listing submitted successfully";
        logAction("Job listing submitted successfully");
    } else {
        $error = $result;
        logAction("Job submission failed: " . $error);
    }
}

// Handle listing extension
if (isset($_POST['extend_listing']) && isset($_SESSION['logged_in'])) {
    extendListingDuration($_POST['listing_id']);
    $success = "Listing duration extended successfully";
    logAction("Listing duration extended for ID: " . $_POST['listing_id']);
}

// Handle listing removal
if (isset($_POST['remove_listing']) && isset($_SESSION['logged_in'])) {
    removeListing($_POST['listing_id']);
    $success = "Listing removed successfully";
    logAction("Listing removed for ID: " . $_POST['listing_id']);
}

// Get job listings
$jobListings = getJobListings();

// Remove expired listings
removeExpiredListings();

// Notify about expiring listings
notifyExpiringListings();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hochschule Niederrhein Job Board</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold">HN Job Board</a>
            <?php if (isset($_SESSION['logged_in'])): ?>
                <a href="?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</a>
            <?php else: ?>
                <button onclick="document.getElementById('loginModal').classList.remove('hidden')" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded">Login</button>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto mt-8 p-4">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['logged_in'])): ?>
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-2xl font-bold mb-4">Submit a Job Listing</h2>
            <form action="" method="post" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Job Title:</label>
                    <input type="text" id="title" name="title" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div>
                    <label for="company" class="block text-gray-700 text-sm font-bold mb-2">Company:</label>
                    <input type="text" id="company" name="company" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div>
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Job Description:</label>
                    <textarea id="description" name="description" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                <div>
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Contact Email:</label>
                    <input type="email" id="email" name="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div>
                    <label for="pdf" class="block text-gray-700 text-sm font-bold mb-2">PDF Document (Max 2MB, single page):</label>
                    <input type="file" id="pdf" name="pdf" accept=".pdf" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div>
                    <input type="submit" name="submit_job" value="Submit Job Listing" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-2xl font-bold mb-4">Job Listings</h2>
            <?php if (empty($jobListings)): ?>
                <p class="text-gray-600">No job listings available at the moment.</p>
            <?php else: ?>
                <?php foreach ($jobListings as $listing): ?>
                    <div class="border-b border-gray-200 py-4">
                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($listing['title']); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($listing['company']); ?></p>
                        <p class="mt-2"><?php echo htmlspecialchars($listing['description']); ?></p>
                        <p class="mt-2">Contact: <?php echo htmlspecialchars($listing['email']); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Expires: <?php echo date('Y-m-d', $listing['expiresAt']); ?></p>
                        <?php if (isset($_SESSION['logged_in'])): ?>
                            <form action="" method="post" class="mt-2">
                                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                <input type="submit" name="extend_listing" value="Extend Duration" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded text-sm mr-2">
                                <input type="submit" name="remove_listing" value="Remove Listing" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded text-sm">
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Admin Login</h3>
                <div class="mt-2 px-7 py-3">
                    <form action="" method="post">
                        <input type="password" name="password" placeholder="Password" required class="mt-2 px-3 py-2 border shadow-sm border-gray-300 placeholder-gray-400 focus:outline-none focus:border-sky-500 focus:ring-sky-500 block w-full rounded-md sm:text-sm focus:ring-1">
                        <button type="submit" name="login" class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Login</button>
                    </form>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="closeModal" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('loginModal').classList.add('hidden');
        });
    </script>
</body>
</html>