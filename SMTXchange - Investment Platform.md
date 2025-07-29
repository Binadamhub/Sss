# SMTXchange - Investment Platform

A fully functional investment website built with Laravel, featuring user and admin dashboards, investment plans, referral system, and automated financial processing.

## Features

### User Features
- **User Registration & Authentication**: Email verification, login/logout, password reset
- **Dashboard**: Overview of balance, investments, referrals, and recent activities
- **Investment System**: Two investment plans with automated profit calculation
  - Plan A: 30% profit in 5 days
  - Plan B: 60% profit in 7 days
- **Referral System**: Earn ₦500 for each successful referral
- **Withdrawal System**: Request withdrawals with 10% fee and recommit requirement
- **Bank Details Management**: Add Nigerian bank account details for withdrawals
- **Transaction History**: Complete record of all financial activities

### Admin Features
- **Admin Dashboard**: Comprehensive overview with statistics and charts
- **User Management**: View, activate/deactivate users, adjust balances
- **Withdrawal Management**: Approve/decline withdrawal requests with bulk operations
- **Investment Plan Management**: Create, edit, and manage investment plans
- **Financial Overview**: Complete platform financial statistics

### Automated Features
- **Investment Processing**: Automatic profit crediting when investments mature
- **Referral Bonuses**: Automatic ₦500 bonus when referred users invest
- **Scheduled Tasks**: Hourly investment processing and 30-minute referral processing

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js & NPM (for frontend assets)

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd smtxchange
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Configuration
Edit your `.env` file with your database credentials:
```env
APP_NAME=SMTXchange
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smtxchange
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@smtxchange.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Database Setup
```bash
php artisan migrate --seed
```

This will create all necessary tables and seed the database with:
- Two investment plans (Plan A & Plan B)
- Admin user account

### 6. Build Frontend Assets
```bash
npm run build
```

### 7. Start the Application
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Default Admin Account

After seeding, you can login to the admin panel with:
- **Email**: admin@smtxchange.com
- **Password**: admin123

**Important**: Change the admin password immediately after first login!

## Scheduled Tasks

To enable automated processing, add this to your server's crontab:
```bash
* * * * * cd /path/to/smtxchange && php artisan schedule:run >> /dev/null 2>&1
```

Or run manually for testing:
```bash
php artisan investments:process-matured
php artisan referrals:process-bonuses
```

## File Structure

```
smtxchange/
├── app/
│   ├── Console/Commands/          # Automated financial processing commands
│   ├── Http/Controllers/          # User controllers
│   ├── Http/Controllers/Admin/    # Admin controllers
│   ├── Http/Middleware/           # Custom middleware
│   ├── Models/                    # Eloquent models
│   └── Services/                  # Business logic services
├── database/
│   ├── migrations/                # Database schema
│   └── seeders/                   # Database seeders
├── resources/views/
│   ├── admin/                     # Admin dashboard views
│   ├── investments/               # Investment views
│   ├── referrals/                 # Referral views
│   ├── withdrawals/               # Withdrawal views
│   └── bank-details/              # Bank details views
└── routes/
    └── web.php                    # Application routes
```

## Key Models

- **User**: User accounts with balance and referral tracking
- **InvestmentPlan**: Investment plan configurations
- **Investment**: User investments with profit calculations
- **Referral**: Referral relationships and bonus tracking
- **Withdrawal**: Withdrawal requests and processing
- **BankDetail**: User bank account information
- **Transaction**: Complete transaction history

## Security Features

- CSRF protection on all forms
- Email verification for new accounts
- Admin-only access to admin routes
- User activation/deactivation system
- Input validation and sanitization
- Secure password hashing

## Financial Logic

### Investment Processing
1. User selects investment plan and amount
2. Amount is deducted from user balance
3. Investment is created with calculated maturity date
4. Automated command processes matured investments hourly
5. Principal + profit is credited to user balance

### Referral System
1. User shares referral link with unique code
2. New user registers using referral link
3. When referred user makes first investment, referrer gets ₦500 bonus
4. Automated command processes referral bonuses every 30 minutes

### Withdrawal System
1. User requests withdrawal (requires bank details)
2. 10% fee is calculated and deducted
3. Admin reviews and approves/declines request
4. Recommit requirement: user must reinvest same amount as last investment
5. Upon approval, amount is deducted from user balance

## API Endpoints

### User Routes (Protected)
- `GET /dashboard` - User dashboard
- `GET /investments` - Investment plans and history
- `POST /investments/store/{plan}` - Create new investment
- `GET /referrals` - Referral dashboard
- `GET /withdrawals` - Withdrawal history and requests
- `POST /withdrawals` - Create withdrawal request

### Admin Routes (Admin Only)
- `GET /admin` - Admin dashboard
- `GET /admin/users` - User management
- `GET /admin/withdrawals` - Withdrawal management
- `GET /admin/investments` - Investment plan management

## Deployment

### Production Environment Setup

1. **Server Requirements**
   - PHP 8.1+ with required extensions
   - MySQL 5.7+
   - Web server (Apache/Nginx)
   - SSL certificate for HTTPS

2. **Environment Configuration**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   ```

3. **Optimize for Production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

4. **Set Up Cron Jobs**
   ```bash
   * * * * * cd /path/to/smtxchange && php artisan schedule:run >> /dev/null 2>&1
   ```

5. **File Permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

## Troubleshooting

### Common Issues

1. **Migration Errors**
   - Ensure database exists and credentials are correct
   - Check PHP MySQL extension is installed

2. **Permission Errors**
   - Set proper permissions on storage and cache directories
   - Ensure web server can write to these directories

3. **Email Not Working**
   - Configure MAIL settings in .env
   - Test with `php artisan tinker` and Mail facade

4. **Scheduled Tasks Not Running**
   - Verify cron job is set up correctly
   - Check server logs for errors
   - Test commands manually first

## Support

For technical support or questions about the investment platform, please contact the development team.

## License

This project is proprietary software. All rights reserved.

