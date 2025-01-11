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
    <title>Extract Private Key and Certificate from PFX | Secure PFX to PEM Converter</title>

    <meta name="theme-color" content="white" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="#007bff" />
    <meta name="apple-mobile-web-app-title" content="PFX to PEM Converter" />
    <meta name="msapplication-TitleImage" content="/images/ios/144.png" />
    <meta name="msapplication-TitleColor" content="#007bff" />
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/images/ios/512.png">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Extract private key and certificate from your .pfx file and convert it to PEM format. Securely extract the private key and certificate from PFX files for server compatibility and SSL management.">
    <meta name="keywords" content="extract private key from PFX, extract certificate from PFX, PFX to PEM, PFX converter, PEM converter, SSL certificate tool, PKCS#12 to PEM, PFX private key extraction, PFX certificate extraction">
    <meta name="author" content="Shakil Alam">

    <!-- Open Graph Meta Tags for Social Sharing -->
    <meta property="og:title" content="Extract Private Key and Certificate from PFX | Secure Conversion">
    <meta property="og:description" content="Extract private key and certificate from PFX files, then convert to PEM format with our secure and easy-to-use tool. Perfect for SSL/TLS certificate management.">
    <meta property="og:image" content="https://ui-avatars.com/api/?name=PFX+to+PEM&background=007bff&color=ffffff&size=512">
    <meta property="og:url" content="https://pfx-to-pem-converter.shakiltech.com">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Data -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Extract Private Key and Certificate from PFX | Secure Conversion">
    <meta name="twitter:description" content="Extract private key and certificate from your PFX file and easily convert it to PEM format with a secure and user-friendly tool.">
    <meta name="twitter:image" content="https://ui-avatars.com/api/?name=PFX+to+PEM&background=007bff&color=ffffff&size=512">

    <!-- Canonical Link -->
    <link rel="canonical" href="https://pfx-to-pem-converter.shakiltech.com">

    <!-- Schema.org Structured Data Markup -->
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "SoftwareApplication",
          "name": "PFX to PEM Converter",
          "description": "A tool to extract private key and certificate from .pfx file and convert it into PEM format for SSL/TLS management.",
          "url": "https://pfx-to-pem-converter.shakiltech.com",
          "applicationCategory": "Utilities",
          "operatingSystem": "Web",
          "softwareVersion": "1.0",
          "author": {
            "@type": "Organization",
            "name": "Shakil Alam"
          },
          "license": "https://pfx-to-pem-converter.shakiltech.com/terms",
          "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "https://pfx-to-pem-converter.shakiltech.com"
          },
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.5",
            "reviewCount": "87"
          },
          "offers": {
            "@type": "Offer",
            "url": "https://pfx-to-pem-converter.shakiltech.com",
            "price": 0,
            "priceCurrency" : "INR",
            "eligibleRegion": {
                "@type": "Place",
                "name": "Worldwide"
            },
            "seller": {
                "@type": "Organization",
                "name": "Shakil Alam"
                }
            }
        }

    </script>

    <!-- Tailwind CSS -->
    <link href="/css/app.css" rel="stylesheet">

    <link rel="manifest" href="manifest.json" />
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-08FR6JHTZX"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-08FR6JHTZX');
    </script>
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

<main class="container max-w-7xl mx-auto mt-6">
    <section class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-900 dark:text-white rounded-lg shadow-md">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Extract Private Key and Certificate from Your .pfx File</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            Upload a .pfx file to extract its private key and certificate, then convert them to PEM format for server compatibility and SSL management.
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
                <input id="file" type="file" name="file" class="mt-1 block w-full text-gray-800 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded p-2 bg-gray-50 dark:bg-gray-800" required accept=".pfx" >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="password">Password:</label>
                <input id="password" type="password" name="password" class="mt-1 block w-full text-gray-800 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded p-2 bg-gray-50 dark:bg-gray-800" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 dark:bg-blue-700 hover:bg-blue-600 dark:hover:bg-blue-800 text-white font-bold py-2 px-4 rounded">
                Extract & Download PEM Files
            </button>
        </form>
    </section>

    <!-- 5-Star Rating Section -->
    <section id="reviews" class="mx-4 mt-8 bg-gray-50 dark:bg-gray-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">User Reviews</h3>
        <div class="flex items-center space-x-2 mt-4">
            <span class="text-yellow-500">★★★★☆</span> <!-- 4.5 stars -->
            <span class="text-gray-600 dark:text-gray-400">(100 Reviews)</span>
        </div>
        <p class="text-gray-600 dark:text-gray-400 mt-2">"Great tool! It worked seamlessly to convert my PFX to PEM files."</p>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="mx-4 mt-8 bg-gray-50 dark:bg-gray-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">How It Works</h3>
        <ol class="list-decimal list-inside space-y-2 mt-4 text-gray-600 dark:text-gray-400">
            <li>Upload your .pfx file to extract the private key and certificate.</li>
            <li>Enter the password associated with your .pfx file to unlock it.</li>
            <li>Click <strong>Extract & Download</strong> to receive your PEM files containing the private key and certificate in a downloadable zip archive.</li>
        </ol>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="mx-4 mt-8 bg-gray-50 dark:bg-gray-900 p-6 rounded-lg shadow-md">
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
    <section class="mt-4">
        <h4 class="text-xl underline p-4 font-bold">Related Articles</h4>
        <div class="flex flex-col md:flex-row gap-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-4">
            <div class="w-full h-auto bg-gray-100 dark:bg-gray-900 bg-cover rounded-lg shadow-lg p-6">
                <h3 class="font-semibold text-lg text-gray-900 dark:text-white">
                    <a href="/blogs/extract-ssl-certificate-from-pfx/" class="hover:text-blue-500 dark:hover:text-blue-400">
                    How to Extract SSL Certificates from PFX: A Complete Guide to Converting Your Certificates with Ease
                    </a>
                </h3>
                <p class="text-xl mb-2 text-gray-800 dark:text-gray-300">
                Learn how to extract SSL certificates from a PFX file using OpenSSL. A step-by-step guide to converting your SSL certificates quickly and securely.
                    <a href="/blogs/extract-ssl-certificate-from-pfx/" class="text-blue-600 dark:text-blue-400 hover:underline">read more...</a>
                </p>
            </div>

            <div class="w-full h-auto bg-gray-100 dark:bg-gray-900 bg-cover rounded-lg shadow-lg p-6">
                <h3 class="font-semibold text-lg text-gray-900 dark:text-white">
                    <a href="/blogs/renew-ssl-certificate/" class="hover:text-blue-500 dark:hover:text-blue-400">How to Renew Your SSL Certificates: A Quick Guide</a>
                </h3>
                <p class="text-xl mb-2 text-gray-800 dark:text-gray-300">
                    Learn how to renew your SSL certificates and keep your website secure. A quick, step-by-step guide for easy certificate renewal.
                    <a href="/blogs/renew-ssl-certificate/"  class="text-blue-600 dark:text-blue-400 hover:underline">read more...</a>
                </p>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="bg-gray-800 dark:bg-gray-900 text-white py-4 mt-8">
    <div class="container mx-auto px-4 text-center">
        <p class="mb-2">Developed with ❤️ by <a href="https://shakiltech.com?utm_source=pfx2pem">Shakil Alam</a></p>
        <p>&copy; <?= date("Y") ?> PFX to PEM Converter. All rights reserved.</p>
    </div>
</footer>
<script>
    window.onload = () => {
        'use strict';
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    }
</script>
</body>
</html>

<?php
unset($_SESSION['error']);
?>
