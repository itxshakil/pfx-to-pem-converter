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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-800 dark:text-white">

<header class="bg-blue-600 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">PFX to PEM Converter</h1>
        <nav>
            <a href="#how-it-works" class="text-white hover:text-gray-200 mx-2">How It Works</a>
            <a href="#faq" class="text-white hover:text-gray-200">FAQ</a>
        </nav>
    </div>
</header>

<div class="container max-w-7xl mx-auto mt-6">
    <section class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-900 dark:text-white rounded-lg shadow-md">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Convert Your .pfx File to PEM</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            Upload a .pfx file to extract its private key and certificate as PEM files, then download them in a zip archive.
        </p>

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
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="mt-8 bg-gray-50 dark:bg-gray-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">How It Works</h3>
        <ol class="list-decimal list-inside space-y-2 mt-4 text-gray-600 dark:text-gray-400">
            <li>Upload your .pfx file using the form above.</li>
            <li>Enter the password associated with your .pfx file.</li>
            <li>Click <strong>Convert & Download</strong> to securely retrieve your PEM files in a downloadable zip archive.</li>
        </ol>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="mt-8 bg-gray-50 dark:bg-gray-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Why Use This Converter?</h3>
        <ul class="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-400">
            <li>Quickly convert PFX files to PEM format for server compatibility.</li>
            <li>Securely manage SSL/TLS certificates with a simple and intuitive interface.</li>
            <li>Ensure your privacy: Files are automatically deleted after processing.</li>
        </ul>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="mt-8 bg-gray-50 dark:bg-gray-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Frequently Asked Questions</h3>
        <div class="space-y-4 mt-4">
            <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg">
                <p class="font-semibold text-gray-700 dark:text-gray-300">What is a .pfx file?</p>
                <p class="text-gray-600 dark:text-gray-400">A .pfx file (PKCS#12 format) contains both the private key and the certificate. It's commonly used for securing websites, emails, and other resources.</p>
            </div>
            <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Why do I need a password to upload?</p>
                <p class="text-gray-600 dark:text-gray-400">The password is required to access the contents of the .pfx file securely.</p>
            </div>
            <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Is my file stored on the server?</p>
                <p class="text-gray-600 dark:text-gray-400">No, your file and all generated files are deleted automatically after download to ensure your privacy.</p>
            </div>
        </div>
    </section>
</div>

<!-- Footer -->
<footer class="bg-gray-800 dark:bg-gray-900 text-white py-4 mt-8">
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
