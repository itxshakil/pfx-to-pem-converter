    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <div class="flex items-center mb-6 md:mb-0">
                    <i class="fas fa-key text-2xl text-yellow-400 mr-3"></i>
                    <span class="text-2xl font-bold">PFX to PEM Converter</span>
                </div>
                <div class="flex space-x-6">
                    <a href="https://www.x.com/itxshakil/" aria-label="X (Twitter)" class="text-gray-300 hover:text-white transition duration-200">
                        <i class="fab fa-twitter text-xl" aria-hidden="true"></i>
                    </a>
                    <a href="https://www.github.com/itxshakil/" aria-label="GitHub" class="text-gray-300 hover:text-white transition duration-200">
                        <i class="fab fa-github text-xl" aria-hidden="true"></i>
                    </a>
                    <a href="https://www.linkedin.com/in/itxshakil/" aria-label="LinkedIn" class="text-gray-300 hover:text-white transition duration-200">
                        <i class="fab fa-linkedin text-xl" aria-hidden="true"></i>
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
                            <li><a href="/" class="hover:text-white transition duration-200">Home</a></li>
                            <li><a href="/#features" class="hover:text-white transition duration-200">Features</a></li>
                            <li><a href="/#how-it-works" class="hover:text-white transition duration-200">How It Works</a></li>
                            <li><a href="/#faq" class="hover:text-white transition duration-200">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Related Articles</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/blogs/extract-ssl-certificate-from-pfx/" class="hover:text-white transition duration-200">Extract SSL Certificate from PFX</a></li>
                            <li><a href="/blogs/install-ssl-apache/" class="hover:text-white transition duration-200">Install SSL on Apache</a></li>
                            <li><a href="/blogs/renew-ssl-certificate/" class="hover:text-white transition duration-200">Renew SSL Certificate</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <p class="mb-4 md:mb-0 text-gray-400">Developed with <i class="fas fa-heart text-red-500" aria-hidden="true"></i> by <a href="https://shakiltech.com?utm_source=pfx2pem" class="text-blue-400 hover:text-blue-300 transition duration-200">Shakil Alam</a></p>
                    <p class="text-gray-400">&copy; <?= date('Y') ?> PFX to PEM Converter. All rights reserved.</p>
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

<script src="/js/app.js" defer></script>
</body>
</html>
