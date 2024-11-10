# HN Job Board Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Security Features](#security-features)
3. [Hosting Guide](#hosting-guide)
   - [Caddy](#caddy)

## Introduction
Irgendwas hin hallo ihr netten Menschen -- Hallo ^^
Hallo!
Juuuuuuulius!

The HN Job Board is a PHP-based web application that allows administrators to post job listings and manage them. This documentation focuses on the security features implemented in the application and provides a step-by-step guide for hosting the application using Nginx and Caddy web servers.

## Security Features

The HN Job Board implements several security measures to protect against common vulnerabilities and ensure safe operation:

1. **Input Sanitization**: 
   - All user inputs are sanitized using the `sanitizeInput()` function, which applies `htmlspecialchars()`, `strip_tags()`, and `trim()` to prevent XSS attacks and remove unwanted whitespace.

2. **File Upload Security**:
   - File size limit: Uploads are restricted to a maximum of 2MB.
   - File type validation: Only PDF files are allowed, verified through both file extension and MIME type checking.
   - Secure file naming: Uploaded files are renamed using a random hexadecimal string to prevent overwriting and filename-based attacks.
   - Content scanning: The PDF content is checked for potentially dangerous elements like JavaScript or automatic actions.
   - Single-page restriction: Only single-page PDFs are allowed to prevent large file uploads.

3. **Upload Rate Limiting**:
   - A limit of 5 uploads per hour per IP address is enforced to prevent abuse.

4. **Secure File Permissions**:
   - Uploaded files are set to 644 permissions (read-write for owner, read-only for others).

5. **Admin Authentication**:
   - A simple password-based authentication system is implemented for admin access.
   - The admin password is stored in a configuration file, which should be kept secure and not exposed to the web root.

6. **CSRF Protection**:
   - While not explicitly implemented in the provided code, it's recommended to add CSRF tokens to all forms for protection against Cross-Site Request Forgery attacks.

7. **Error Handling and Logging**:
   - Detailed error messages are logged but not displayed to users, preventing information leakage.
   - All significant actions (job submissions, listing removals, etc.) are logged for auditing purposes.

8. **Database Security**:
   - The application uses file-based storage (JSON files) instead of a database, which eliminates SQL injection risks.
   - However, proper file permissions and server configuration are crucial to prevent unauthorized access to these files.

9. **Expiration and Cleanup**:
   - Job listings automatically expire after a set duration, and expired listings are removed along with their associated files.

10. **Secure Configuration**:
    - Sensitive configuration options are stored in a separate `config.php` file, which should be placed outside the web root.

## Hosting Guide

### Nginx

Follow these steps to host the HN Job Board using Nginx:

1. Install Nginx and PHP-FPM:
   ```
   sudo apt update
   sudo apt install nginx php-fpm
   ```

2. Create a new Nginx server block configuration:
   ```
   sudo nano /etc/nginx/sites-available/hnjobboard
   ```

3. Add the following configuration (adjust paths as needed):
   ```nginx
   server {
       listen 80;
       server_name your_domain.com;
       root /var/www/hnjobboard;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }

       location ~ /\.ht {
           deny all;
       }

       location /uploads {
           deny all;
           return 403;
       }
   }
   ```

4. Enable the site and restart Nginx:
   ```
   sudo ln -s /etc/nginx/sites-available/hnjobboard /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

5. Set proper permissions:
   ```
   sudo chown -R www-data:www-data /var/www/hnjobboard
   sudo chmod -R 755 /var/www/hnjobboard
   sudo chmod -R 644 /var/www/hnjobboard/*.php
   sudo chmod -R 644 /var/www/hnjobboard/*.json
   sudo chmod 755 /var/www/hnjobboard/uploads
   ```

6. Configure PHP:
   ```
   sudo nano /etc/php/7.4/fpm/php.ini
   ```
   Set the following values:
   ```
   upload_max_filesize = 2M
   post_max_size = 2M
   max_execution_time = 30
   ```

7. Restart PHP-FPM:
   ```
   sudo systemctl restart php7.4-fpm
   ```

### Caddy

Follow these steps to host the HN Job Board using Caddy:

1. Install Caddy:
   ```
   sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https
   curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
   curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
   sudo apt update
   sudo apt install caddy
   ```

2. Install PHP-FPM:
   ```
   sudo apt install php-fpm
   ```

3. Create a Caddyfile:
   ```
   sudo nano /etc/caddy/Caddyfile
   ```

4. Add the following configuration (adjust paths as needed):
   ```
   your_domain.com {
       root * /var/www/hnjobboard
       php_fastcgi unix//var/run/php/php7.4-fpm.sock
       file_server
       encode gzip

       @uploads {
           path /uploads/*
       }
       respond @uploads 403

       handle_errors {
           rewrite * /error.php?error={http.error.status_code}
       }
   }
   ```

5. Set proper permissions:
   ```
   sudo chown -R www-data:www-data /var/www/hnjobboard
   sudo chmod -R 755 /var/www/hnjobboard
   sudo chmod -R 644 /var/www/hnjobboard/*.php
   sudo chmod -R 644 /var/www/hnjobboard/*.json
   sudo chmod 755 /var/www/hnjobboard/uploads
   ```

6. Configure PHP:
   ```
   sudo nano /etc/php/7.4/fpm/php.ini
   ```
   Set the following values:
   ```
   upload_max_filesize = 2M
   post_max_size = 2M
   max_execution_time = 30
   ```

7. Restart PHP-FPM and Caddy:
   ```
   sudo systemctl restart php7.4-fpm
   sudo systemctl restart caddy
   ```

For both Nginx and Caddy setups, ensure that you:

- Place the `config.php` file outside the web root for added security.
- Set up HTTPS using Let's Encrypt or another SSL certificate provider.
- Regularly update your server, PHP, and all installed packages.
- Monitor your logs for any suspicious activity.
- Consider implementing additional security measures like fail2ban to protect against brute-force attacks.

Remember to adjust file paths, PHP versions, and domain names according to your specific setup. Always test your configuration in a staging environment before deploying to production.