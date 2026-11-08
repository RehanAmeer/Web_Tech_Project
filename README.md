# StudyVault

StudyVault is a Student Notes & Resource Exchange built with PHP and MySQL. Students and teachers share study material, organized by course and semester.

## Features

- Registration with admin approval; roles: student, teacher, admin
- Upload PDF, Word, Excel, PowerPoint and image files (up to 100 MB, 5 uploads per day)
- Search and filter by course, semester and file type
- Preview PDFs and images in the browser, or download them
- Ratings, comments and bookmarks
- Reporting with admin review: 3 reports hide a resource, 3 strikes ban an uploader
- "Verified teacher" badge on teacher uploads
- Admin panel for users, resources, courses and reports

## Requirements

Ubuntu with Apache, MySQL and PHP.

## Setup

**1. Install the software**

```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring
```

**2. Copy the project to Apache**

The folder must be named `studyvault`, because the site is served from `/studyvault/`.

```bash
sudo cp -r studyvault /var/www/html/studyvault
sudo chown -R www-data:www-data /var/www/html/studyvault
```

**3. Create the upload folder**

Uploaded files are kept outside the web folder so nobody can open them by URL.

```bash
sudo mkdir -p /var/www/studyvault_storage/uploads
sudo chown -R www-data:www-data /var/www/studyvault_storage
sudo chmod -R 750 /var/www/studyvault_storage
```

**4. Set up the database**

This creates the tables, adds the course list and creates the database user used in `config/db.php`.

```bash
cd /var/www/html/studyvault/database
sudo mysql < studyvault.sql
sudo mysql < seed.sql
sudo mysql < create_user.sql
```

**5. Enable the `.htaccess` file**

The `.htaccess` file sets the upload size limits and security headers. Apache ignores it until `AllowOverride` is turned on.

```bash
sudo a2enmod headers
sudo sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
sudo service apache2 restart
```

**6. Create the admin account**

Change the email and password to your own. The password is stored as a hash.

```bash
HASH=$(php -r "echo password_hash('Admin@12345', PASSWORD_DEFAULT);")
sudo mysql studyvault -e "INSERT INTO users (full_name, email, password_hash, role, requested_role, account_status, reviewed_at) VALUES ('StudyVault Administrator', 'admin@studyvault.com', '$HASH', 'admin', 'student', 'approved', NOW());"
```

**7. Open the site**

Go to http://localhost/studyvault/ and log in with the admin email and password from step 6.

## Using the site

1. A new user registers and waits as a pending account.
2. The admin approves the account as a student or teacher from the admin pages.
3. Approved members can upload, rate, comment, bookmark and report.

## Database settings

The connection details are in `config/db.php`: database `studyvault`, user `studyvault_user`, password `password`. If you change the password, change it in `config/db.php` and in `database/create_user.sql`, then update the MySQL user to match.


