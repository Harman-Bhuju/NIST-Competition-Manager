# NIST Competition Manager

A comprehensive web-based platform for managing the NIST competitions (C-Debug and UI/UX). This system handles team management, real-time timer synchronization, volunteer coordination, scoring, and automated PDF export of results.

## Features

### Admin Portal

- **Dashboard**: Overview of competition status.
- **Volunteer Management**: Approve/reject volunteer requests, assign volunteers to teams.
- **Timer Control**: Master control for the global competition timer (Start/Stop/Reset).
- **Scoring System**: Enter marks for teams (C-Debug: Easy/Medium/Hard questions).
- **Results**: View and export top performers.

### Volunteer Portal

- **Real-time Sync**: View the global competition timer in real-time.
- **Team Management**: Stop timers for assigned teams upon task completion.
- **Attendance**: Mark team attendance.
- **Status Updates**: Monitor "Alive" (Running) vs "Stopped" status for teams.

### Public/Team View

- **Live Timer**: Teams can see the remaining time.
- **Status Board**: Display current status of all teams.

### Reporting

- **PDF Export**: Generate professional PDF reports of team standings and details using `TCPDF`.
- **Winners List**: Automatically calculate and display top 3 winners.

## Technology Stack

- **Frontend**: HTML5, CSS3 (Custom styling with variables), JavaScript (Fetch API for real-time updates).
- **Backend**: PHP (Native).
- **Database**: MySQL.
- **Libraries**:
  - [TCPDF](https://github.com/tecnickcom/TCPDF) for PDF generation.
  - Google Fonts (Inter, Outfit).

## Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   ```

2. **Setup Database**
   - Import the provided `database.sql` file into your MySQL database.
   - Configure database credentials in `db.php`:
     ```php
     $host = 'localhost';
     $user = 'root';
     $pass = '';
     $db   = 'nist';
     ```

3. **Configure Server**
   - Serve the application using Apache (XAMPP/WAMP/MAMP recommended).
   - Ensure `session_config.php` path is accessible.

4. **Access the Application**
   - Navigate to `http://localhost/nist/` in your browser.

## Usage

- **Admin Login**: Access `/nist_admin/admin_login.php`.
- **Volunteer Access**: Volunteers register/login via the main portal.
- **Competition Logic**:
  1. Admin starts the Global Timer.
  2. detailed scoring and timing is handled via individual team controls.

## License

[MIT](https://choosealicense.com/licenses/mit/)
