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

    <meta name="theme-color" content="#1e40af" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="#1e40af" />
    <meta name="apple-mobile-web-app-title" content="PFX to PEM Converter" />
    <meta name="msapplication-TitleImage" content="/images/ios/144.png" />
    <meta name="msapplication-TitleColor" content="#1e40af" />
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/images/ios/512.png">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Extract private key and certificate from your .pfx file and convert it to PEM format. Securely extract the private key and certificate from PFX files for server compatibility and SSL management.">
    <meta name="keywords" content="extract private key from PFX, extract certificate from PFX, PFX to PEM, PFX converter, PEM converter, SSL certificate tool, PKCS#12 to PEM, PFX private key extraction, PFX certificate extraction">
    <meta name="author" content="Shakil Alam">

    <!-- Open Graph Meta Tags for Social Sharing -->
    <meta property="og:title" content="Extract Private Key and Certificate from PFX | Secure Conversion">
    <meta property="og:description" content="Extract private key and certificate from PFX files, then convert to PEM format with our secure and easy-to-use tool. Perfect for SSL/TLS certificate management.">
    <meta property="og:image" content="https://ui-avatars.com/api/?name=PFX+to+PEM&background=1e40af&color=ffffff&size=512">
    <meta property="og:url" content="https://pfx-to-pem-converter.shakiltech.com">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Data -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Extract Private Key and Certificate from PFX | Secure Conversion">
    <meta name="twitter:description" content="Extract private key and certificate from your PFX file and easily convert it to PEM format with a secure and user-friendly tool.">
    <meta name="twitter:image" content="https://ui-avatars.com/api/?name=PFX+to+PEM&background=1e40af&color=ffffff&size=512">

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="manifest" href="manifest.json" />
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-08FR6JHTZX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-08FR6JHTZX');
    </script>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            scroll-behavior: smooth;
        }

        :focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
            border-radius: 4px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .dark .glass-card {
            background: rgba(30, 41, 59, 0.8);
        }

        .custom-file-input {
            position: relative;
            overflow: hidden;
            display: block;
            cursor: pointer;
        }

        .custom-file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .btn-gradient {
            background: linear-gradient(to right, #1e40af, #3b82f6);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(to right, #3b82f6, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .grow-on-hover {
            transition: all 0.3s ease;
        }

        .grow-on-hover:hover {
            transform: scale(1.03);
        }

        /* Skip offscreen layout/paint work on below-the-fold sections */
        .cv-section {
            content-visibility: auto;
            contain-intrinsic-size: 0 600px;
        }

        /* Drag-and-drop active state for the upload area */
        #dropzone {
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        #dropzone.is-dragover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.08);
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 dark:text-white scroll-mt-20 scroll-smooth">

<div class="min-h-[100svh] flex flex-col">
    <!-- Navigation -->
    <header class="gradient-bg text-white py-4 sticky top-0 z-50">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="/" class="flex items-center space-x-2">
                <i class="fas fa-key text-2xl text-yellow-300"></i>
                <span class="text-2xl font-bold">PFX to PEM Converter</span>
            </a>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="#features" class="text-white hover:text-yellow-200 transition duration-200 flex items-center">
                    <i class="fas fa-star mr-2"></i>Features
                </a>
                <a href="#how-it-works" class="text-white hover:text-yellow-200 transition duration-200 flex items-center">
                    <i class="fas fa-cogs mr-2"></i>How It Works
                </a>
                <a href="#faq" class="text-white hover:text-yellow-200 transition duration-200 flex items-center">
                    <i class="fas fa-question-circle mr-2"></i>FAQ
                </a>
            </nav>
            <button id="mobile-menu-button" type="button" class="md:hidden text-white focus:outline-none" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
                <i class="fas fa-bars text-xl" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden">
            <div class="glass-effect px-4 py-2 mx-4 mt-2 rounded-lg">
                <a href="#features" class="block py-2 px-4 text-white hover:bg-blue-700 rounded">
                    <i class="fas fa-star mr-2"></i>Features
                </a>
                <a href="#how-it-works" class="block py-2 px-4 text-white hover:bg-blue-700 rounded">
                    <i class="fas fa-cogs mr-2"></i>How It Works
                </a>
                <a href="#faq" class="block py-2 px-4 text-white hover:bg-blue-700 rounded">
                    <i class="fas fa-question-circle mr-2"></i>FAQ
                </a>
            </div>
        </div>
    </header>

    <main class="container max-w-7xl mx-auto mt-6 px-4 flex-grow">
        <section class="relative flex flex-col md:flex-row items-center justify-between py-12 mb-12 overflow-hidden rounded-3xl gradient-bg">
            <div class="absolute top-0 right-0 w-full h-full opacity-15">
                <svg width="100%" height="100%" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <path id="path1" d="M 100 300 Q 150 50 200 300 Q 250 550 300 300 Q 350 50 400 300 Q 450 550 500 300 Q 550 50 600 300 Q 650 550 700 300" />
                    </defs>
                    <use href="#path1" fill="none" stroke="white" stroke-width="100" stroke-opacity="0.1" />
                </svg>
            </div>
            <div class="z-10 px-8 md:px-16 py-6 md:w-1/2">
                <h1 class="text-4xl font-bold text-white mb-4">Secure PFX to PEM Conversion</h1>
                <p class="text-white text-lg opacity-90 mb-8">
                    Extract private keys and certificates from your PFX files with our secure, fast, and easy-to-use tool.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#converter" class="px-6 py-3 bg-white text-blue-700 rounded-full font-bold hover:bg-yellow-100 transition duration-300 shadow-lg flex items-center">
                        <i class="fas fa-arrow-right mr-2"></i> Get Started
                    </a>
                    <a href="#how-it-works" class="px-6 py-3 border-2 border-white text-white rounded-full font-bold hover:bg-white hover:text-blue-700 transition duration-300 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Learn More
                    </a>
                </div>
            </div>
            <div class="hidden md:block md:w-1/2 p-4">
                <div class="relative">
                    <div class="absolute inset-0 bg-blue-600 rounded-full opacity-20 blur-3xl"></div>
                    <div class="relative glass-effect p-6 rounded-xl">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <div class="text-white ml-2">Terminal</div>
                        </div>
                        <pre class="text-xs sm:text-sm text-green-400 font-mono">
$ openssl pkcs12 -in certificate.pfx -nocerts -out key.pem
$ openssl pkcs12 -in certificate.pfx -clcerts -nokeys -out cert.pem
$ openssl rsa -in key.pem -out private.key

<span class="text-yellow-300">✓ Certificate extracted successfully</span>
<span class="text-white">Now you can use these files with your server</span>
                        </pre>
                    </div>
                </div>
            </div>
        </section>

        <section id="converter" class="mx-auto max-w-4xl p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl mb-16 relative scroll-mt-32 overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500 rounded-full filter blur-3xl opacity-10 -mr-20 -mt-20"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-blue-500 rounded-full filter blur-3xl opacity-10 -ml-20 -mb-20"></div>

            <div class="relative z-10">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full gradient-bg flex items-center justify-center mr-4">
                        <i class="fas fa-exchange-alt text-xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Extract Private Key and Certificate</h2>
                </div>

                <p class="text-gray-600 dark:text-gray-300 mb-8 border-l-4 border-blue-500 pl-4 italic">
                    Upload your .pfx file to extract the private key and certificate in PEM format. Perfect for configuring web servers, mail servers, and other SSL/TLS applications.
                </p>

                <!-- Display error messages -->
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <span><strong>Error:</strong> <?= htmlspecialchars($_SESSION['error']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="process.php" method="post" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div id="dropzone" class="bg-gray-50 dark:bg-gray-700 p-6 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <label class="custom-file-input w-full flex flex-col items-center justify-center">
                            <div class="mb-4 text-blue-600 dark:text-blue-400 text-center">
                                <i class="fas fa-file-upload text-5xl" aria-hidden="true"></i>
                            </div>
                            <div class="text-center mb-2">
                                <span class="text-gray-700 dark:text-gray-200 font-medium">Drag your .pfx file here or click to browse</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm text-center">
                                Maximum file size: 10MB
                            </p>
                            <input id="file" type="file" name="file" class="hidden" required accept=".pfx" aria-describedby="file-error">
                        </label>
                        <div id="file-name" class="mt-3 text-sm"></div>
                        <p id="file-error" class="mt-3 text-sm text-red-600 dark:text-red-400 hidden" role="alert"></p>
                    </div>

                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2" for="password">
                            <i class="fas fa-key mr-2"></i>Password:
                        </label>
                        <div class="relative">
                            <input id="password" type="password" name="password"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100"
                                   >
                            <button type="button" id="toggle-password" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 focus:outline-none" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            This is the password that protects your .pfx file
                        </p>
                    </div>

                    <button type="submit" class="w-full btn-gradient text-white font-bold py-3 px-6 rounded-lg shadow-lg flex items-center justify-center">
                        <i class="fas fa-download mr-2"></i>
                        Extract & Download PEM Files
                    </button>
                </form>
            </div>
        </section>

        <!-- What happens to your file (honest trust block) -->
        <section aria-labelledby="privacy-heading" class="mx-auto max-w-4xl mb-12 bg-blue-50 dark:bg-gray-800 border border-blue-100 dark:border-gray-700 rounded-2xl p-6 md:p-8">
            <div class="flex items-start">
                <i class="fas fa-user-shield text-blue-600 dark:text-blue-400 text-2xl mr-4 mt-1" aria-hidden="true"></i>
                <div>
                    <h2 id="privacy-heading" class="text-lg font-semibold text-gray-800 dark:text-white mb-2">What happens to your file</h2>
                    <p class="text-gray-600 dark:text-gray-300">
                        Your <code class="text-sm">.pfx</code> file is uploaded over an encrypted (HTTPS) connection and processed in an isolated, temporary folder on our server. The private key, certificate, and ZIP are generated, sent to you, and then permanently deleted in the same request. We never store your file or your password, and nothing is logged.
                    </p>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 scroll-mt-20">
            <div class="glass-card p-6 flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center mb-4">
                    <i class="fas fa-shield-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">Private by Design</h3>
                <p class="text-gray-600 dark:text-gray-300">Your file is processed over a secure connection and permanently deleted the moment your download is ready — never stored.</p>
            </div>
            <div class="glass-card p-6 flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center mb-4">
                    <i class="fas fa-bolt text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">Lightning Fast</h3>
                <p class="text-gray-600 dark:text-gray-300">Instant conversion with no waiting time. Get your PEM files in seconds.</p>
            </div>
            <div class="glass-card p-6 flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center mb-4">
                    <i class="fas fa-laptop-code text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">Server Ready</h3>
                <p class="text-gray-600 dark:text-gray-300">Compatible with Apache, Nginx, IIS and other popular web servers.</p>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="mb-16 scroll-mt-20 cv-section">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Why Choose Our Converter?</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto mt-4"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="glass-card p-6 grow-on-hover">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 rounded-full gradient-bg flex items-center justify-center mr-4">
                            <i class="fas fa-lock text-xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Secure Processing</h3>
                            <p class="text-gray-600 dark:text-gray-300 mt-2">Your file is sent over an encrypted connection, processed in an isolated temporary space, and deleted immediately after conversion. We never store your file or password.</p>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 grow-on-hover">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 rounded-full gradient-bg flex items-center justify-center mr-4">
                            <i class="fas fa-check-circle text-xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Simple Process</h3>
                            <p class="text-gray-600 dark:text-gray-300 mt-2">Just upload your PFX file, enter your password, and download the converted files. No technical skills required.</p>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 grow-on-hover">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 rounded-full gradient-bg flex items-center justify-center mr-4">
                            <i class="fas fa-certificate text-xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Certificate & Key Extraction</h3>
                            <p class="text-gray-600 dark:text-gray-300 mt-2">Extract both private key and certificate in PEM format for immediate use with web servers.</p>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 grow-on-hover">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 rounded-full gradient-bg flex items-center justify-center mr-4">
                            <i class="fas fa-cloud-download-alt text-xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Instant Downloads</h3>
                            <p class="text-gray-600 dark:text-gray-300 mt-2">Get your converted files instantly in a convenient ZIP package with properly named files.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- How It Works Section -->
        <section id="how-it-works" class="mx-auto max-w-4xl mb-16 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg scroll-mt-20 cv-section">
            <div class="flex items-center mb-8">
                <div class="w-10 h-10 rounded-full gradient-bg flex items-center justify-center mr-4">
                    <i class="fas fa-magic text-xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">How It Works</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass-card p-6 flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center mb-4">
                        <i class="fas fa-upload text-2xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">1. Upload Your File</h4>
                    <p class="text-gray-600 dark:text-gray-300">
                        Upload your .pfx file through our secure form. It is processed in an isolated temporary space and deleted right after.
                    </p>
                </div>

                <div class="glass-card p-6 flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center mb-4">
                        <i class="fas fa-key text-2xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">2. Enter Password</h4>
                    <p class="text-gray-600 dark:text-gray-300">
                        Enter the password associated with your .pfx file to unlock its contents.
                    </p>
                </div>

                <div class="glass-card p-6 flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center mb-4">
                        <i class="fas fa-download text-2xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">3. Download Files</h4>
                    <p class="text-gray-600 dark:text-gray-300">
                        Receive your private key and certificate in PEM format in a convenient ZIP package.
                    </p>
                </div>
            </div>

            <div class="mt-8 p-6 bg-blue-50 dark:bg-blue-900 rounded-xl">
                <div class="flex items-center mb-4">
                    <i class="fas fa-lightbulb text-yellow-500 text-xl mr-3"></i>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Pro Tips</h4>
                </div>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Make sure you have the correct password for your PFX file before starting.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Keep your private key secure and never share it with unauthorized parties.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>For web servers, you'll typically need both the certificate and private key files.</span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="mx-auto max-w-4xl mb-16 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg scroll-mt-20 cv-section">
            <div class="flex items-center mb-8">
                <div class="w-10 h-10 rounded-full gradient-bg flex items-center justify-center mr-4">
                    <i class="fas fa-question-circle text-xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Frequently Asked Questions</h3>
            </div>

            <div class="space-y-6">
                <div class="glass-card p-6">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-semibold text-gray-800 dark:text-white">What is a .pfx file?</span>
                        <i class="fas fa-chevron-down text-blue-600 dark:text-blue-400 transition-transform duration-300 transform"></i>
                    </button>
                    <div class="faq-content mt-4 text-gray-600 dark:text-gray-300">
                        <p>A .pfx file (Personal Exchange Format) is a PKCS#12 archive file format that stores the server certificate, any intermediate certificates, and the private key in a single encrypted file. It's commonly used for securing websites, emails, and other resources requiring SSL/TLS encryption.</p>
                    </div>
                </div>

                <div class="glass-card p-6">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-semibold text-gray-800 dark:text-white">Why do I need to convert PFX to PEM?</span>
                        <i class="fas fa-chevron-down text-blue-600 dark:text-blue-400 transition-transform duration-300 transform"></i>
                    </button>
                    <div class="faq-content mt-4 text-gray-600 dark:text-gray-300">
                        <p>Many web servers like Apache, Nginx, and various applications require certificates and private keys in PEM format. Converting from PFX to PEM allows you to use your certificate with these systems. The PEM format is a base64 encoded format that's widely supported.</p>
                    </div>
                </div>

                <div class="glass-card p-6">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-semibold text-gray-800 dark:text-white">Why do I need a password to upload?</span>
                        <i class="fas fa-chevron-down text-blue-600 dark:text-blue-400 transition-transform duration-300 transform"></i>
                    </button>
                    <div class="faq-content mt-4 text-gray-600 dark:text-gray-300">
                        <p>The password is required to access the contents of the .pfx file because PFX files are encrypted to protect the sensitive private key information they contain. Without the correct password, the PFX file cannot be decrypted and the conversion process will fail.</p>
                    </div>
                </div>

                <div class="glass-card p-6">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-semibold text-gray-800 dark:text-white">Is my file stored on the server?</span>
                        <i class="fas fa-chevron-down text-blue-600 dark:text-blue-400 transition-transform duration-300 transform"></i>
                    </button>
                    <div class="faq-content mt-4 text-gray-600 dark:text-gray-300">
                        <p>No, your file and all generated files are processed temporarily and deleted automatically after download to ensure your privacy and security. We take your data security seriously and do not store any of your certificate files or private keys on our servers.</p>
                    </div>
                </div>

                <div class="glass-card p-6">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-semibold text-gray-800 dark:text-white">What formats will I receive after conversion?</span>
                        <i class="fas fa-chevron-down text-blue-600 dark:text-blue-400 transition-transform duration-300 transform"></i>
                    </button>
                    <div class="faq-content mt-4 text-gray-600 dark:text-gray-300">
                        <p>After conversion, you'll receive a ZIP file containing:</p>
                        <ul class="list-disc list-inside mt-2 ml-4 space-y-1">
                            <li>cert.pem - Your SSL/TLS certificate in PEM format</li>
                            <li>private.pem - Your unencrypted private key in PEM format</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Articles -->
        <section class="mx-auto max-w-4xl mb-16 scroll-mt-20 cv-section">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white mt-8 mb-4 flex items-center">
                <i class="fas fa-newspaper text-blue-600 dark:text-blue-400 mr-3"></i>
                Related Articles
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <a href="/blogs/extract-ssl-certificate-from-pfx/" class="glass-card p-6 hover:shadow-xl transition duration-300">
                    <div class="h-40 bg-blue-100 dark:bg-blue-900 rounded-lg mb-4 flex items-center justify-center">
                        <i class="fas fa-certificate text-4xl text-blue-500 dark:text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2 hover:text-blue-600 dark:hover:text-blue-400">
                        How to Extract SSL Certificates from PFX: A Complete Guide
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Learn how to extract SSL certificates from a PFX file using OpenSSL. A step-by-step guide to converting your SSL certificates quickly and securely.
                    </p>
                    <div class="flex items-center text-blue-600 dark:text-blue-400 font-medium">
                        Read more
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>

                <a href="/blogs/renew-ssl-certificate/" class="glass-card p-6 hover:shadow-xl transition duration-300">
                    <div class="h-40 bg-green-100 dark:bg-green-900 rounded-lg mb-4 flex items-center justify-center">
                        <i class="fas fa-sync text-4xl text-green-500 dark:text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2 hover:text-blue-600 dark:hover:text-blue-400">
                        How to Renew Your SSL Certificates: A Quick Guide
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Learn how to renew your SSL certificates and keep your website secure. A quick, step-by-step guide for easy certificate renewal.
                    </p>
                    <div class="flex items-center text-blue-600 dark:text-blue-400 font-medium">
                        Read more
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>
            </div>
        </section>
    </main>

    <!-- CTA Section -->
    <section class="gradient-bg py-16 px-4 scroll-mt-20">
        <div class="container mx-auto max-w-4xl text-center">
            <h2 class="text-3xl font-bold text-white mb-6">Ready to Convert Your PFX Files?</h2>
            <p class="text-white text-lg opacity-90 mb-8 max-w-2xl mx-auto">
                Our tool makes it easy to extract private keys and certificates from your PFX files. Try it now for free!
            </p>
            <a href="#converter" class="px-8 py-4 bg-white text-blue-700 rounded-full font-bold hover:bg-yellow-100 transition duration-300 shadow-lg inline-flex items-center">
                <i class="fas fa-arrow-right mr-2"></i> Convert Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <div class="flex items-center mb-6 md:mb-0">
                    <i class="fas fa-key text-2xl text-yellow-400 mr-3"></i>
                    <span class="text-2xl font-bold">PFX to PEM Converter</span>
                </div>
                <div class="flex space-x-6">
                    <a href="https://www.x.com/itxshakil/" class="text-gray-300 hover:text-white transition duration-200">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="https://www.github.com/itxshakil/" class="text-gray-300 hover:text-white transition duration-200">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="https://www.linkedin.com/in/itxshakil/" class="text-gray-300 hover:text-white transition duration-200">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 pb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div>
                        <h4 class="text-lg font-semibold mb-4">About</h4>
                        <p class="text-gray-400">
                            A free tool to extract private keys and certificates from PFX files and convert them to PEM format for use with web servers and applications.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white transition duration-200">Home</a></li>
                            <li><a href="#features" class="hover:text-white transition duration-200">Features</a></li>
                            <li><a href="#how-it-works" class="hover:text-white transition duration-200">How It Works</a></li>
                            <li><a href="#faq" class="hover:text-white transition duration-200">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Related Tools</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white transition duration-200">SSL Checker</a></li>
                            <li><a href="#" class="hover:text-white transition duration-200">CSR Generator</a></li>
                            <li><a href="#" class="hover:text-white transition duration-200">Certificate Decoder</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <p class="mb-4 md:mb-0 text-gray-400">Developed with <i class="fas fa-heart text-red-500"></i> by <a href="https://shakiltech.com?utm_source=pfx2pem" class="text-blue-400 hover:text-blue-300 transition duration-200">Shakil Alam</a></p>
                    <p class="text-gray-400">&copy; <?= date("Y") ?> PFX to PEM Converter. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    <div class="fixed bottom-0 end-0 p-4">
        <button id="shareButton" type="button" aria-label="Share this tool" class="px-6 py-3 bg-white text-blue-700 rounded-full font-bold hover:bg-yellow-100 transition duration-300 shadow-lg flex items-center">
            <i class="fas fa-share mr-2" aria-hidden="true"></i> Share
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const shareButton = document.getElementById('shareButton');

        // Check if Web Share API is supported
        if (navigator.share) {
            shareButton.addEventListener('click', async () => {
                try {
                    await navigator.share({
                        title: '🔒 PFX to PEM Converter - Free Secure Tool',
                        text: 'I found this amazing tool that converts PFX to PEM files instantly, right in your browser! No data is sent to servers - completely secure and private. Perfect for setting up SSL on Apache, Nginx, or any web server. Save hours of command-line work! ✨',
                        url: window.location.href
                    });
                    console.log('Successfully shared');
                } catch (error) {
                    console.error('Error sharing:', error);
                }
            });
        } else {
            // Fallback for browsers that don't support the Web Share API
            shareButton.addEventListener('click', () => {
                // Create a temporary input to copy the URL
                const input = document.createElement('input');
                input.value = window.location.href;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);

                // Change button text temporarily to show feedback
                const originalText = shareButton.innerHTML;
                shareButton.innerHTML = '<i class="fas fa-check mr-2"></i> URL Copied!';

                setTimeout(() => {
                    shareButton.innerHTML = originalText;
                }, 2000);
            });
        }
    });

    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        const isHidden = mobileMenu.classList.toggle('hidden');
        this.setAttribute('aria-expanded', String(!isHidden));
    });

    // Password visibility toggle
    document.getElementById('toggle-password').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            this.setAttribute('aria-pressed', 'true');
            this.setAttribute('aria-label', 'Hide password');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            this.setAttribute('aria-pressed', 'false');
            this.setAttribute('aria-label', 'Show password');
        }
    });

    // File upload: drag-and-drop, client-side validation, and a removable file chip
    (function () {
        const MAX_BYTES = 10 * 1024 * 1024; // keep in sync with process.php
        const fileInput = document.getElementById('file');
        const dropzone = document.getElementById('dropzone');
        const chip = document.getElementById('file-name');
        const errorEl = document.getElementById('file-error');

        const formatSize = (bytes) => {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        };

        const showError = (msg) => {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        };
        const clearError = () => {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        };

        const clearFile = () => {
            fileInput.value = '';
            chip.innerHTML = '';
        };

        const renderChip = (file) => {
            chip.innerHTML = '';
            const wrap = document.createElement('div');
            wrap.className = 'py-2 px-3 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg flex items-center justify-between gap-3';
            const label = document.createElement('span');
            label.className = 'flex items-center min-w-0';
            label.innerHTML = '<i class="fas fa-file-alt mr-2" aria-hidden="true"></i>';
            const name = document.createElement('span');
            name.className = 'truncate';
            name.textContent = `${file.name} (${formatSize(file.size)})`;
            label.appendChild(name);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'shrink-0 text-blue-700 dark:text-blue-300 hover:text-red-600 focus:outline-none';
            remove.setAttribute('aria-label', 'Remove selected file');
            remove.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            remove.addEventListener('click', () => { clearFile(); clearError(); });
            wrap.appendChild(label);
            wrap.appendChild(remove);
            chip.appendChild(wrap);
        };

        const validate = (file) => {
            if (!file.name.toLowerCase().endsWith('.pfx')) {
                return 'Only .pfx files are allowed.';
            }
            if (file.size > MAX_BYTES) {
                return `That file is ${formatSize(file.size)}. The maximum size is 10MB.`;
            }
            return null;
        };

        const handleFile = (file) => {
            if (!file) return;
            const problem = validate(file);
            if (problem) {
                showError(problem);
                clearFile();
                return;
            }
            clearError();
            renderChip(file);
        };

        fileInput.addEventListener('change', function () {
            handleFile(this.files[0]);
        });

        ['dragenter', 'dragover'].forEach(evt =>
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            })
        );
        ['dragleave', 'dragend'].forEach(evt =>
            dropzone.addEventListener(evt, () => dropzone.classList.remove('is-dragover'))
        );
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
            const dropped = e.dataTransfer.files && e.dataTransfer.files[0];
            if (!dropped) return;
            // Assign the dropped file to the input so it submits with the form
            const dt = new DataTransfer();
            dt.items.add(dropped);
            fileInput.files = dt.files;
            handleFile(dropped);
        });
    })();

    // FAQ accordion with ARIA state
    document.querySelectorAll('.faq-toggle').forEach((toggle, i) => {
        const content = toggle.nextElementSibling;
        const panelId = `faq-panel-${i}`;
        content.id = panelId;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', panelId);

        toggle.addEventListener('click', function () {
            const icon = this.querySelector('i');
            const isOpen = !!content.style.maxHeight;

            if (isOpen) {
                content.style.maxHeight = null;
                icon.classList.remove('rotate-180');
                this.setAttribute('aria-expanded', 'false');
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.classList.add('rotate-180');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Service Worker Registration
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