# Bibliotheca – Library Management System (XAMPP Version)

**Advanced Web Development Course – Final Project**

<p align="center">
  <img src="https://i.postimg.cc/xjvqCfBB/Twitter-header-2.png" alt="Bibliotheca Banner" width="100%">
</p> 


Bibliotheca is a web-based Library Management System developed using **Laravel**. It provides a centralized platform for managing book records, monitoring book availability, handling borrowing and returns, and viewing library statistics through an administrative dashboard.

> **Local copy (XAMPP version).** This copy is set up to run on a local XAMPP MySQL/MariaDB database. The original project used TiDB Cloud; the data has been exported to `bibliotheca.sql` so no cloud account or credentials are needed.

## Features

- **Dashboard** – View library statistics, borrowing activity, and other library information through summary cards and charts.
- **Book Management** – Add, view, edit, search, and delete book records.
- **Category Management** – Organize books into categories for easier browsing and management.
- **Borrowing Management** – Record and manage book borrowing transactions.
- **Return Management** – Process returned books and update their availability.
- **Inventory Tracking** – Monitor total book quantities and available copies.
- **Search and Filtering** – Find book records more easily.

## Technologies Used

| Technology | Purpose |
|---|---|
| PHP 8.2+ | Backend programming language |
| Laravel | PHP web application framework |
| MySQL / MariaDB (XAMPP) | Database (local copy) |
| Blade | Laravel templating engine |
| Bootstrap 5 | Frontend styling and responsive layout |
| JavaScript | Client-side interactions |
| Chart.js | Dashboard charts and data visualization |
| Open Library Search API | Source of sample book information |

## Requirements

- XAMPP with **PHP 8.2 or higher** (Apache and MySQL modules)
- Composer (not needed if the `vendor/` folder is included in this zip)

**Note:** Node.js and npm are not required. Bootstrap 5 and Chart.js are loaded through CDN links, so an internet connection is needed for styling and charts to load.

## Installation and Setup (XAMPP)

### 1. Extract the project

Extract the zip anywhere, for example `C:\projects\bibliotheca`. The Laravel project is inside the `bibliotheca-laravel` folder.

### 2. Start XAMPP

Open the XAMPP Control Panel and start **MySQL** (Apache is only needed for phpMyAdmin).

### 3. Create the database and import the data

1. Open http://localhost/phpmyadmin
2. Click **New** and create a database named `bibliotheca` (collation `utf8mb4_unicode_ci`).
3. Select the `bibliotheca` database, click **Import**, choose `bibliotheca.sql` (included in this zip), and click **Import**.

The import already contains all tables and the sample book and category records.

### 4. Configure the environment file

Open a terminal in the `bibliotheca-laravel` folder.

**Windows (Command Prompt):**
```cmd
copy .env.example .env
```

**Windows (PowerShell):**
```powershell
Copy-Item .env.example .env
```

**Git Bash / macOS / Linux:**
```bash
cp .env.example .env
```

Open `.env` and set:

```env
APP_NAME=Bibliotheca
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bibliotheca
DB_USERNAME=root
DB_PASSWORD=
```

XAMPP's default MySQL user is `root` with an empty password. If your XAMPP uses a different password, put it in `DB_PASSWORD`. No SSL certificate is needed for a local database.

### 5. Install PHP dependencies

Skip this step if the `vendor/` folder is already included.

```bash
composer install
```

### 6. Generate the application key

```bash
php artisan key:generate
```

### 7. Run the application

```bash
php artisan serve
```

Open http://127.0.0.1:8000 in your browser.

### Troubleshooting

- **"Access denied" or "Connection refused":** make sure MySQL is running in XAMPP and the `.env` values match step 4. After editing `.env`, run `php artisan config:clear`.
- **Import error mentioning `utf8mb4_0900_ai_ci`:** the SQL file was exported from TiDB/MySQL 8 and XAMPP's MariaDB does not know that collation. Open `bibliotheca.sql` in a text editor and replace every `utf8mb4_0900_ai_ci` with `utf8mb4_unicode_ci`, then import again.
- **Wrong PHP version:** run `php -v`. Laravel here needs PHP 8.2 or higher; use a newer XAMPP if yours is older.

## Database Information

The `bibliotheca.sql` file contains the existing book and category records used for demonstration and testing.

- Do not run the sample data import commands below; the data is already in the SQL file.
- Do not run `php artisan migrate` unless needed; the tables are created by the SQL import.

### Why we used cloud (original project)

Although we had been using XAMPP and its MariaDB / MySQL services throughout our previous course work, we used TiDB Cloud during development. Several group members each running their own local database caused reliability and sync problems, so a single centralised, remotely accessible database made collaboration easier. TiDB is MySQL-compatible, so we could keep using the SQL skills we already had. This copy is the same database exported for local use.

## Sample Book Data

Bibliotheca uses the free **[Open Library Search API](https://openlibrary.org/developers/api)** to retrieve sample bibliographic information, including book titles, authors, publishers, and publication years.

Custom Laravel Artisan commands were developed to retrieve books from different Open Library subjects and store them in the project's database. The API was used only for the initial population of sample records. During normal operation, book information is retrieved from the project's own database.

### Sample Data Import Commands

Included for documentation and reference only.

| Command | Description |
|---|---|
| `php artisan books:import` | Imports general books, including fiction, science, history, and technology. |
| `php artisan books:import-filipino` | Imports Filipino-related books, including literature, history, folklore, and culture. |
| `php artisan books:import-more` | Imports additional general and educational books, including psychology, art, programming, comics, IT, nursing, education, and engineering. |
| `php artisan books:import-kids-teens` | Imports books intended for younger audiences. |
| `php artisan books:import-reference` | Imports reference materials, including encyclopedias, dictionaries, and almanacs. |
| `php artisan books:import-manga-comics` | Imports manga, comics, and other educational books. |

**Do not execute these commands.** They only document how the initial sample dataset was generated.

### Sample Data Disclaimer

The book records are intended for academic demonstration and testing purposes. They do not represent actual library inventory.

- Book quantities are randomly generated.
- Some bibliographic information may be unavailable.
- Missing authors or publishers may be represented as `Unknown`.
- Book availability is maintained within the application's database.

## Project Structure

```text
bibliotheca/
│
├── bibliotheca-laravel/
│   ├── app/
│   │   ├── Console/
│   │   │   └── Commands/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   └── Models/
│   │
│   ├── database/
│   ├── public/
│   ├── resources/
│   │   └── views/
│   ├── routes/
│   ├── storage/
│   ├── .env.example
│   └── artisan
│
├── bibliotheca.sql
└── README.md
```

## Third-Party Notices & Attributions

Bibliotheca uses third-party frameworks, libraries, fonts, icons, APIs, and other resources. This section acknowledges them and their respective licenses or attribution requirements.

### Frameworks and Libraries

| Technology | Purpose | License |
|---|---|---|
| [Laravel](https://laravel.com/) | Backend web application framework | MIT |
| [Bootstrap 5](https://getbootstrap.com/) | Responsive frontend styling | MIT |
| [Chart.js](https://www.chartjs.org/) | Dashboard charts and data visualization | MIT |
| [PHP](https://www.php.net/) | Backend programming language | PHP License |

### Icons and Visual Assets

| Resource | Purpose | Attribution |
|---|---|---|
| [Business Library Books Icon](https://icon-icons.com/icon/business-library-books/230) | Application icon / visual asset | Nucleoapp via Icon-Icons.com |

Business Library Books Icon by Nucleoapp, sourced from [Icon-Icons.com](https://icon-icons.com/authors/230-nucleoapp).

### Fonts and Typography

| Font | Purpose | Source | License |
|---|---|---|---|
| [Figtree](https://fonts.google.com/specimen/Figtree) | Interface typography | Google Fonts | SIL Open Font License 1.1 |
| [Momo Display](https://fonts.google.com/) | Display typography and headings | Google Fonts | Verify exact font license |
| [Inter](https://fonts.google.com/specimen/Inter) | Interface typography | Google Fonts | SIL Open Font License 1.1 |

### External APIs and Data Sources

| Resource | Purpose | Attribution |
|---|---|---|
| [Open Library](https://openlibrary.org/) | Retrieves book metadata for populating sample book records | Open Library / Internet Archive |

Bibliotheca uses the Open Library Search API to retrieve bibliographic information, including book titles, authors, publishers, and publication years. The retrieved information is used to populate sample book records for demonstration and testing purposes. Open Library is a project of the [Internet Archive](https://archive.org/).

- API Documentation: https://openlibrary.org/developers/api
- Official Website: https://openlibrary.org/

## License

This project is intended for educational and academic purposes.

## Project Repository

[GitHub – Bibliotheca](https://github.com/anjlalxn/bibliotheca)

# Screenshots
<img src="https://i.postimg.cc/nrXhLQMV/image.png" alt="Screenshot 1">

<img src="https://i.postimg.cc/J0szhBtH/image.png" alt="Screenshot 2">

<img src="https://i.postimg.cc/VNzsCh5F/image.png" alt="Screenshot 3">

<img src="https://i.postimg.cc/QMjNTzHX/image.png" alt="Screenshot 4">

<img src="https://i.postimg.cc/t4Q4XJbQ/image.png" alt="Screenshot 5">

<img src="https://i.postimg.cc/L4G11HR4/image.png" alt="Screenshot 6">

<img src="https://i.postimg.cc/7Ycmbvt4/image.png" alt="Screenshot 7">

<img src="https://i.postimg.cc/tCf270v1/image.png" alt="Screenshot 8">

<img src="https://i.postimg.cc/13qMqWbV/image.png" alt="Screenshot 9">

<img src="https://i.postimg.cc/W1g63dJJ/image.png" alt="Screenshot 10">
</document_content>