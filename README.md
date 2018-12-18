# CounselorScheduling
A web app made for Sidwell Friends School's college counseling office to automate scheduling appointments with students.
# Features
- Custom time slots based on school periods
- Email remainders and calendar support
- Admin portal for managing availability and appointments
  - Automated email reminders and cancellation messages
  - Unlimited number of counselors and appointments
# Setup
Written in procedural PHP 5 and using an SQL database for storing appointments, this system is adaptable for custom implementations.
## Database
The SQL database (table structure available in database.sql) contains three tables:
- CCApp
  - Stores appointments and includes the ID, name of student, name of counselor, student email, and appointment time.
- CCAppBusy
  - Stores slots designed by the admin as blocked per counselor and timeslot
- CCAppSettings
  > Settings were stored in the database for implementation reasons at Sidwell, but this can easily be adjusted for a different storage method (especially for credentials). Many of these settings are also designed to be changed within the admin panel. Look at database.sql for default settings.

  - Available settings:
    - AdminPassword
      - Hashed admin panel password to secure access
    - ApptIDLength
      - Length of appointment IDs
    - Counselors
      - Array of names for available counselors
    - DaysAtATime
      - Maximum number of days the main page should show at a time
    - FarAhead
      - How far ahead people are allowed to schedule appointment in terms of DaysAtATime showed
    - MailerSettings
      - Settings for PHPMailer (refer to their documentation for more detailed information), but generally should include DebugLevel, Host, Username, Password, and ReturnName
    - SendConfEmail
      - Boolean to toggle confirmation emails
    - Timeslots
      - Custom timeslots for appointments
    - VisibleErrors
      - Boolean to toggle error visibility
- CCAppLog
  - Generally only used for debugging or logging incidents, so use as desired
## Libraries
Two libraries, PHPMailer and crypto_random, are included for email alerts and random ID generation, respectively. To allow for more .ics customization, consider using EasyPeasyICS, iCalcreator, or something similar.
