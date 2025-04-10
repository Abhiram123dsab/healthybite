# HealthyBite - College Food Ordering System

HealthyBite is a web-based food ordering platform designed specifically for college students. It enables students to conveniently browse menus, place orders for various meal times (breakfast, lunch, dinner, and snacks), and make secure payments online.

## Features

- **Easy Menu Navigation**
  - Browse meals by time (breakfast, lunch, dinner, snacks)
  - Customizable juice options
  - Real-time menu updates

- **User-Friendly Ordering**
  - Simple cart management
  - Quick checkout process
  - Order history tracking

- **Secure Payments**
  - Multiple payment options
  - Encrypted payment processing
  - Transaction history

## Technology Stack

- **Frontend**
  - HTML5
  - CSS3
  - JavaScript
  - Responsive design for mobile access

- **Backend**
  - PHP
  - MySQL Database

- **Security**
  - Secure user authentication
  - Payment data encryption
  - SQL injection prevention

## Getting Started

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/healthybite.git
   cd healthybite
   ```

2. Set up the database:
   - Create a new MySQL database
   - Import the schema files:
     ```bash
     mysql -u your_username -p your_database < config/schema.sql
     mysql -u your_username -p your_database < config/payment_schema.sql
     ```

3. Configure the application:
   - Copy `config/db_config.php.example` to `config/db_config.php` (if not exists)
   - Update database credentials in `config/db_config.php`

4. Set up your web server:
   - Configure your web server to point to the project directory
   - Ensure proper file permissions (755 for directories, 644 for files)

5. Start the application:
   - Access the application through your web browser
   - Create an account and start ordering!

## Directory Structure

```
healthybite/
├── api/                # API endpoints for orders and payments
├── config/             # Configuration files
├── css/               # Stylesheets
├── img/               # Image assets
├── includes/          # PHP includes
├── js/                # JavaScript files
└── *.html, *.php      # Main application files
```

## Contributing

We welcome contributions! Please feel free to submit a Pull Request.

## Support

For support, please email support@healthybite.com or open an issue in the repository.

## License

This project is licensed under the MIT License - see the LICENSE file for details.