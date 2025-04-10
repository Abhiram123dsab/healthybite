# HealthyBites Project Documentation

## Project Overview
HealthyBites is a college food ordering system that offers nutritious meals and custom juices with doorstep delivery. The platform focuses on providing healthy eating options for busy students and health-conscious individuals.

## Project Structure
```
├── api/                 # API handlers for cart, orders, and payments
├── config/             # Database and payment configurations
├── css/                # Styling files
├── img/                # Image assets
├── includes/           # PHP components for auth, cart, etc.
├── js/                 # JavaScript files
└── Various HTML/PHP    # Main application pages
```

## Features
- User Authentication (login.php, signup.php)
- Menu Categories:
  - Breakfast
  - Lunch
  - Dinner
  - Snacks
  - Custom Juice Builder
- Shopping Cart System
- Secure Checkout Process
- Order Management
- Contact Form

## Database Schema
The database configuration can be found in `config/schema.sql` and includes:
- Users table
- Products table
- Orders table
- Cart table

## API Endpoints
- `/api/cart_handler.php`: Manages cart operations
- `/api/order_handler.php`: Handles order processing
- `/api/payment_handler.php`: Processes payments

## Frontend Components
- Responsive navigation
- Interactive menu cards
- Custom juice builder interface
- Shopping cart with real-time updates
- Animations using AOS library
- Icons using Font Awesome

## Development Guidelines
1. Follow PHP >= 7.4 coding standards
2. Use prepared statements for database queries
3. Implement input validation for all forms
4. Maintain responsive design for all screen sizes
5. Keep JavaScript modular and documented
6. Follow security best practices for authentication

## Setup Instructions
1. Install required dependencies from requirements.txt
2. Configure database settings in config/db_config.php
3. Import database schema from config/schema.sql
4. Set up a local PHP development server
5. Configure payment gateway settings if needed

## Security Considerations
- Implement HTTPS
- Sanitize user inputs
- Use secure password hashing
- Protect against SQL injection
- Implement CSRF protection
- Regular security audits