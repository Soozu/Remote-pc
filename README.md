# Remote PC Control Application

## Overview
This is a web-based remote PC control and management application that allows users to remotely manage and control computers through a secure web interface.

## Features
- Remote system control and monitoring
- File management and transfer capabilities
- Secure login system
- Database integration
- Screen viewing functionality
- Server management tools
- Apache server configuration
- Firewall configuration

## Installation
1. Configure your web server (Apache recommended)
2. Run `setup_database.php` to initialize the database
3. Configure the application settings in `setup.php`
4. Run `start_services.bat` to start required services
5. Access the application through `login.php`

## File Structure
```
├── js/                    # JavaScript files
├── uploads/              # File upload directory
├── vendor/               # Dependencies
├── .gitattributes       # Git attributes
├── build.php            # Build script
├── cleanup.php          # Cleanup utilities
├── configure_firewall.bat # Firewall configuration
├── control.php          # Main control interface
├── db_config.php        # Database configuration
├── file_viewer.php      # File viewing interface
├── login.php            # Authentication
├── screen.php           # Screen viewing
├── setup.php            # Setup script
└── stream_server.php    # Streaming functionality
```

## Requirements
- PHP 7.4 or higher
- MySQL/MariaDB
- Apache Web Server
- Windows OS (for .bat scripts)

## Security
- Ensure proper firewall configuration
- Use strong authentication
- Keep all components updated
- Configure SSL/TLS for secure connections

## Configuration
1. Edit `setup.sql` for database settings
2. Modify `.htaccess` for security rules
3. Update `db_config.php` with database credentials
4. Configure firewall settings using `configure_firewall.bat`

## Usage
1. Start the services using `start_services.bat`
2. Access the web interface through your browser
3. Log in using your credentials
4. Use the control panel to manage remote systems

## License
This software is proprietary and confidential.

## Support
For technical support and issues, please contact the system administrator. 