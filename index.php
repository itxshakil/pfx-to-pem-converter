<?php
session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFX to PEM Converter</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-800 dark:text-white">

<header class="bg-blue-600 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">PFX to PEM Converter</h1>
        <nav>
            <a href="#faq" class="text-white hover:text-gray-200">FAQ</a>
        </nav>
    </div>
</header>

<div class="container max-w-7xl mx-auto">
    <div class="flex w-100 justify-between items-center" style="width:100%;">
        <div class="mt-4 p-6 bg-white dark:bg-gray-900 dark:text-white rounded-lg shadow-md w-2xl mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Convert Your .pfx File to PEM</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">Upload a .pfx file to extract its private key and certificate as PEM files, then download them in a zip archive.</p>

            <!-- Display error messages -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="bg-red-100 dark:bg-red-200 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <strong>Error:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <form action="process.php" method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="file">Upload your .pfx file:</label>
                    <input type="file" name="file" class="mt-1 block w-full text-gray-800 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded p-2 bg-gray-50 dark:bg-gray-800" required accept=".pfx" >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="password">Password:</label>
                    <input type="password" name="password" class="mt-1 block w-full text-gray-800 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded p-2 bg-gray-50 dark:bg-gray-800" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 dark:bg-blue-700 hover:bg-blue-600 dark:hover:bg-blue-800 text-white font-bold py-2 px-4 rounded">
                    Convert & Download
                </button>
            </form>
        </div>
    </div>

    <hr class="my-6 border-gray-300 dark:border-gray-700">

    <section id="faq" class="w-uxl">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Frequently Asked Questions</h3>
        <div class="space-y-4 mt-4">
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                <p class="font-semibold text-gray-700 dark:text-gray-300">What is a .pfx file?</p>
                <p class="text-gray-600 dark:text-gray-400">A .pfx file (PKCS#12 format) contains both the private key and the certificate. It's commonly used for securing websites, emails, and other resources.</p>
            </div>
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Why do I need a password to upload?</p>
                <p class="text-gray-600 dark:text-gray-400">The password is required to access the contents of the .pfx file securely.</p>
            </div>
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Is my file stored on the server?</p>
                <p class="text-gray-600 dark:text-gray-400">No, your file and all generated files are deleted automatically after download to ensure your privacy.</p>
            </div>
        </div>
    </section>
</div>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-4 mt-8">
    <div class="container mx-auto px-4 text-center">
        <p class="mb-2">Developed with ❤️ by Shakil Alam</p>
        <p>&copy; <?= date("Y") ?> PFX to PEM Converter. All rights reserved.</p>
    </div>
</footer>

</body>
</html>

<?php
unset($_SESSION['error']);
?>
