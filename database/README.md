# EduCoach Database Setup

This repository contains the database schema and initial data for the EduCoach platform, a coaching/tutoring platform that connects learners with expert coaches.

## Database Access Information

### Remote Database (Production)
- Host: sql8.freesqldatabase.com
- Port: 3306
- Database: sql8768039
- Username: sql8768039
- Password: 59k7waVH8w

### phpMyAdmin Access (Production)
- URL: http://www.phpmyadmin.co
- Use the remote database credentials above to login

### Local Development (XAMPP)
- Host: localhost
- Port: 3306
- Database: educoach
- Username: root
- Password: [empty]

## Production Setup

1. Access phpMyAdmin at http://www.phpmyadmin.co
2. Log in using the remote database credentials
3. Import the following files in order:
   - schema.sql (creates tables and relationships)
   - initial_data.sql (populates tables with sample data)

## Local Development Setup

1. Install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start Apache and MySQL services from XAMPP Control Panel
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Create a new database named 'educoach'
5. Import the following files in order:
   - schema.sql (creates tables and relationships)
   - initial_data.sql (populates tables with sample data)

## Manual Setup (Local)

If you prefer to set up the database manually:

1. Create the database:
```sql
CREATE DATABASE educoach;
USE educoach;
```

2. Run the schema.sql file:
```bash
C:\xampp\mysql\bin\mysql.exe -u root educoach < schema.sql
```

3. Import the initial data:
```bash
C:\xampp\mysql\bin\mysql.exe -u root educoach < initial_data.sql
```

## Connection Examples

### Production Environment
```php
$host = 'sql8.freesqldatabase.com';
$dbname = 'sql8768039';
$username = 'sql8768039';
$password = '59k7waVH8w';
```

### Development Environment
```php
$host = 'localhost';
$dbname = 'educoach';
$username = 'root';
$password = '';
```

## File Structure

- `schema.sql`: Contains the database structure including tables, relationships, and indexes
- `initial_data.sql`: Contains sample data to populate the database
- `database_design.md`: Detailed documentation of the database design
- `README.md`: This file

## Testing the Setup

After setup, you can verify the installation by:

1. Logging into phpMyAdmin (local or production)
2. Selecting the appropriate database
3. Verifying that all tables are present
4. Running a test query:
```sql
SELECT * FROM Users WHERE user_type = 'business';
```

## Common Issues

1. **Port Conflict**: If port 3306 is already in use
   - Change MySQL port in XAMPP Control Panel
   - Update connection settings accordingly

2. **Access Denied**: If root password is set
   - Use the correct password in connection settings
   - Or reset root password through phpMyAdmin

3. **Import Fails**: If file import fails
   - Check file permissions
   - Try importing through phpMyAdmin interface

4. **Remote Connection Issues**: If unable to connect to remote database
   - Verify your internet connection
   - Check if the IP is whitelisted (if required)
   - Ensure credentials are entered correctly

## Support

For any issues or questions, please refer to:
- XAMPP documentation: [https://www.apachefriends.org/docs/](https://www.apachefriends.org/docs/)
- MySQL documentation: [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/)
- phpMyAdmin documentation: [https://www.phpmyadmin.net/docs/](https://www.phpmyadmin.net/docs/) 