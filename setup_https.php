<?php
// Check if OpenSSL is available
if (!extension_loaded('openssl')) {
    die("OpenSSL extension is required but not installed.");
}

// Configuration for the certificate
$config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

// Generate private key
$privkey = openssl_pkey_new($config);

// Generate CSR
$dn = array(
    "countryName" => "US",
    "stateOrProvinceName" => "State",
    "localityName" => "City",
    "organizationName" => "Local Development",
    "commonName" => "localhost",
    "emailAddress" => "admin@localhost"
);

$csr = openssl_csr_new($dn, $privkey, $config);

// Generate self-signed certificate
$cert = openssl_csr_sign($csr, null, $privkey, 365, $config);

// Save private key
openssl_pkey_export_to_file($privkey, __DIR__ . '/ssl/private.key');

// Save certificate
$certout = '';
openssl_x509_export_to_file($cert, __DIR__ . '/ssl/certificate.crt');

echo "SSL certificate and private key have been generated.\n";
echo "Please configure your web server to use these files for HTTPS.\n";

// Example Apache configuration
$apache_config = "
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot \"" . __DIR__ . "\"
    SSLEngine on
    SSLCertificateFile \"" . __DIR__ . "/ssl/certificate.crt\"
    SSLCertificateKeyFile \"" . __DIR__ . "/ssl/private.key\"
</VirtualHost>
";

echo "\nExample Apache configuration:\n";
echo $apache_config;
?> 