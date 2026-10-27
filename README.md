StudentHub Portal
StudentHub is a web portal for academic institutions designed for managing student accounts, campus events, registrations, and inquiries. The system is built with native PHP, JavaScript (Fetch API), HTML5, CSS3, and MySQL/MariaDB.

System Architecture & Modules
Authentication & Session Security

Role-based access control (Admin and Student).
Secure password hashing using PASSWORD_DEFAULT (Bcrypt).
Session fixation protection using session_regenerate_id(true).
Remember-me cookie tokens and session timeout enforcement (session_helper.php).
Graceful fallback authentication with local JSON storage (data/students.json) if MySQL is offline.
Student Module

Student Registration (register.html & process_register.php) with client and server validation.
Student Dashboard (dashboard.php) displaying academic stats, upcoming events, and quick actions.
Profile Management (profile.php) with live in-place editing of student details.
Event Registration (register_event.php) with pass generation (REG-...) and duplicate prevention.
Administrator Command Center

Restricted to Admin role (admin_dashboard.php).
Live portal metrics: Total Students, Total Events, Event Registrations, Active Accounts, Inquiries.
Real-time latest registrations and recent student tables.
Live asynchronous event management powered by Fetch API.
RESTful JSON API Endpoints

api_events.php: List, create, update, and delete events asynchronously.
api_students.php: List, create, update, and delete student records.
session_status.php: Lightweight session inspector for dynamic role-based menus across pages.
Campus Events Catalog

Dynamic event listings (index.php) with server pre-rendering.
Real-time keyword search, category filtering, sorting, and pagination (events.js).
Contact & Inquiries

Contact form with validation (contact.html & process_contact.php).
Generates inquiry tickets (TCK-...) stored in MySQL contacts table and data/contacts.json.
Records Inspector

Admin view (view_records.php) showing MySQL database records alongside JSON and CSV files.
Setup & Deployment (XAMPP / Apache)
1. Requirements
XAMPP with PHP 8.0+ and MySQL / MariaDB 10.4+
Apache Web Server
2. Installation
Place the project directory inside your XAMPP web root:
C:\xampp\htdocs\Student portal\
Start the Apache and MySQL modules from the XAMPP Control Panel.
Initialize the database schema and seed data by running in your terminal:
c:\xampp\php\php.exe setup_database.php
Or visit in your browser:
http://localhost/Student%20portal/setup_database.php
3. Optional: Apache VirtualHost Configuration
To configure a local virtual host (e.g. http://studenthub.local):

Edit C:\xampp\apache\conf\extra\httpd-vhosts.conf and append:
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/Student portal"
    ServerName studenthub.local
    <Directory "C:/xampp/htdocs/Student portal">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
Open C:\Windows\System32\drivers\etc\hosts as Administrator and add:
127.0.0.1 studenthub.local
Restart Apache in XAMPP.
Demo Credentials
Role	Username / Email	Password	Target Page
Administrator	admin / admin@studenthub.com	Admin@123	admin_dashboard.php
Student	rahul / rahul.sharma@example.com	Student@123	dashboard.php
Student	priya / priya.patel@example.com	Student@123	dashboard.php
Quick-fill demo buttons are provided on login.php.

API Documentation
Events API (api_events.php)
GET api_events.php
Query parameters: id (optional), category (optional), search (optional)
Returns array of event objects or single event.
POST api_events.php
Header: Content-Type: application/json
Body: {"name":"...","category":"...","event_date":"YYYY-MM-DD","venue":"...","description":"...","max_participants":100,"status":"Upcoming","image_url":"..."}
Returns created event ID.
PUT api_events.php
Body: same fields including "id": <int>
Updates existing event record.
DELETE api_events.php
Body or Query: {"id": <int>}
Deletes event by ID.
Students API (api_students.php)
GET api_students.php
Admin only. Optional filters: search, course, id.
POST api_students.php
Admin only. Registers student record.
PUT api_students.php
Admin only. Updates student record.
DELETE api_students.php
Admin only. Deletes student record.
File Structure
Student portal/
├── api_events.php          # RESTful JSON API for events (CRUD)
├── api_students.php        # RESTful JSON API for students (CRUD)
├── session_status.php      # Session state API for dynamic role menus
├── admin_dashboard.php     # Admin dashboard with metrics & Fetch API CRUD
├── dashboard.php           # Student dashboard
├── profile.php             # Student profile editor
├── login.php               # Login authentication & fallback handler
├── logout.php              # Session destruction
├── process_register.php    # Server-side registration processor
├── process_contact.php     # Contact inquiry handler
├── register_event.php      # Event registration & pass ticket generator
├── view_records.php        # Unified MySQL, JSON, and CSV records viewer
├── setup_database.php      # Automated database installation script
├── schema.sql              # MySQL schema and initial seed data
├── db_connect.php          # Database connection & query helper
├── session_helper.php      # Session management & dynamic navigation
├── home.html               # Public home page
├── about.html              # About campus page
├── events.html             # Events catalog
├── index.php               # Dynamic event search & filter page
├── contact.html            # Contact inquiry form
├── faq.html                # Frequently asked questions
├── feedback.html           # Feedback form
├── forgetpassword.html     # Password reset page
├── register.html           # Student registration page
├── events.js               # Event filtering, search, pagination
├── studenthub.js           # Client-side dynamic menu & theme handler
├── login.js                # Login client validation & autofill
├── register.js             # Registration client validation
├── common.css              # Global styles & design system
├── dashboard.css           # Dashboard styles
├── events.css              # Events grid & card styles
└── data/                   # Fallback data storage (JSON & CSV)
