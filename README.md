# School Teacher Timetable Manager

A PHP + MySQL + Bootstrap web application for managing school teachers, weekly timetables, absences, and automatic relief assignments.

## Features
- Admin login system
- Teacher / class / subject management
- Weekly timetable builder (Mon-Fri, 8 periods)
- Absence marking with automatic relief assignment
- Teacher workload summary
- Daily relief report with print-to-PDF support
- Mobile responsive Bootstrap dashboard

## Setup
1. Create a MySQL database (e.g. `school_timetable_manager`).
2. Import `sql/schema.sql`.
3. Update DB credentials in `includes/config.php`.
4. Start local server:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
5. Open `http://localhost:8000`.

## Default admin
- Username: `admin`
- Password: `admin123`

Change this password after first login.
