
# PFX to PEM Converter

A secure, web-based tool for converting PFX (PKCS#12) files to PEM format, primarily used for SSL/TLS certificate management. This tool allows users to upload a `.pfx` file, enter the corresponding password, and download the extracted private key and certificate in a ZIP archive. Designed with security in mind, this tool ensures that uploaded files are deleted immediately after processing to protect user privacy.

## Features

- **File Upload**: Allows users to upload `.pfx` files directly from their local machine.
- **Password Protection**: Users provide a password associated with the `.pfx` file to decrypt it and extract its contents.
- **Secure Download**: Outputs a ZIP archive containing the private key and certificate in PEM format.
- **Automatic Deletion**: All uploaded files are deleted immediately after the conversion process to ensure privacy and security.
- **CSRF Protection**: Includes Cross-Site Request Forgery (CSRF) protection to ensure secure user sessions.

## Installation

Follow these steps to set up the project on a local machine or server.

### Prerequisites

- **[PHP](https://www.php.net/)**: Version 7.4 or later
- **[OpenSSL](https://www.openssl.org/)**: Required for handling certificate files
- **ZipArchive**: Required for creating ZIP files
- **Web Server**: Apache or Nginx with PHP support

### Steps

1. Clone the repository:

   ```bash
   git clone https://github.com/itxshakil/pfx-to-pem-converter.git
   cd pfx-to-pem-converter
   ```

2. Install necessary PHP dependencies:

   ```bash
   composer install
   ```

3. Set up environment variables (e.g., `.env` file) as needed for your setup.

4. Start the PHP server or configure your web server to point to the project directory.

5. Open the application in a browser, typically at `http://localhost:8000`.

## Usage

1. **Upload the PFX File**: Click the upload button and select the `.pfx` file you wish to convert.
2. **Enter the Password**: Input the password associated with the `.pfx` file for decryption.
3. **Download the ZIP Archive**: Upon successful conversion, download a ZIP file containing the private key and certificate in PEM format.

### Handling Errors

- **Invalid Password**: If the password does not match, the tool will display an error message.
- **File Validation**: Only `.pfx` files are accepted; any other file format will trigger a validation error.

## Security Considerations

- **CSRF Protection**: Implements CSRF tokens to prevent unauthorized requests.
- **File Validation**: Only files with a `.pfx` extension are accepted to mitigate malicious file uploads.
- **Automatic File Deletion**: All uploaded files are deleted immediately after the conversion process, ensuring user data remains private.

## Dependencies

- **PHP**: For server-side processing.
- **OpenSSL**: Handles the extraction of PEM data from PFX files.
- **ZipArchive**: Required to package the PEM files into a downloadable ZIP archive.

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Make your changes, following code style guidelines.
4. Submit a pull request with a detailed description of your changes.

Please ensure your code follows the established code style, and include any necessary tests for new features or bug fixes.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
