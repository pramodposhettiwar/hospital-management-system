# Hospital Management System - README

## Overview

Hospital Management Information System (HMIS) is a comprehensive web-based solution for managing hospital operations including patient records, staff management, inventory, and more.

## Project Information

- **Developer:** Pramod Poshettiwar
- **Version:** 1.0.0
- **PHP Version:** 7.4+
- **Database:** MySQL 8.0+

## Features

### Admin Module
- Dashboard with statistics
- Patient management
- Staff/Employee management
- Department management
- Payroll management
- Equipment inventory
- Pharmaceutical inventory
- Medical records
- Lab management
- Financial tracking (Accounts Receivable/Payable)
- Theatre management

### Doctor Module
- Patient consultation records
- Lab test orders and results
- Vital signs tracking
- Prescription management
- Medical records viewing

### Database
- Comprehensive schema with multiple entities
- Support for complex healthcare workflows
- Optimized for performance

## System Requirements

### Development Environment
- Docker 20.10+
- Docker Compose 2.0+
- Git 2.30+
- Bash 4.0+

### Production Environment (AWS EC2)
- Amazon Linux 2 or Ubuntu 20.04+
- t3.medium instance or larger
- 2GB RAM minimum
- 20GB storage minimum

### Browser Requirements
- Modern browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Cookies enabled

## Quick Start

### Local Development (Using Docker)

```bash
# 1. Clone repository
git clone <your-repo-url>
cd Hospital-PHP

# 2. Run quick start
chmod +x scripts/quick-start.sh
./scripts/quick-start.sh

# 3. Access application
# Homepage: http://localhost
# Admin: http://localhost/backend/admin/index.php
# Doctor: http://localhost/backend/doc/index.php
# Database: http://localhost:8081
```

### Login Credentials

| Role | Email/ID | Password |
|------|----------|----------|
| Admin | admin | pramod2004 |
| Doctor | admin | pramod2004 |

⚠️ **Change these credentials in production!**

## Deployment Options

### Option 1: Docker Compose (Recommended for Development)
```bash
docker-compose up -d
bash scripts/health-check.sh
```

### Option 2: AWS EC2 (Production)
```bash
bash scripts/setup-aws.sh
```

### Option 3: Manual Installation
See `DEPLOYMENT_GUIDE.md` for detailed instructions.

## Project Structure

```
Hospital-PHP/
├── index.php                 # Homepage
├── assets/                   # CSS, JS, Images
│   ├── css/
│   ├── js/
│   └── images/
├── backend/
│   ├── admin/               # Admin dashboard
│   ├── doc/                 # Doctor portal
│   └── assets/inc/config.php
├── DATABASE FILE/           # Database schema
├── config/                  # Configuration files
│   ├── nginx/
│   ├── mysql/
│   └── php.ini
├── scripts/                 # Deployment scripts
│   ├── deploy.sh
│   ├── health-check.sh
│   ├── run-migrations.sh
│   ├── smoke-tests.sh
│   ├── setup-aws.sh
│   └── quick-start.sh
├── Dockerfile              # Docker image definition
├── docker-compose.yml      # Docker services
├── Jenkinsfile             # CI/CD pipeline
├── .env.example            # Environment variables template
├── .gitignore              # Git ignore rules
├── DEPLOYMENT_GUIDE.md     # Detailed deployment guide
├── QUICK_REFERENCE.md      # Quick command reference
└── README.md               # This file
```

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and update:

```bash
cp .env.example .env
nano .env
```

Key variables:
- `APP_ENV` - Environment (development/staging/production)
- `DB_HOST` - Database host
- `DB_USER` - Database username
- `DB_PASSWORD` - Database password
- `DB_NAME` - Database name
- `NGINX_PORT` - Nginx port
- `PMA_PORT` - PHPMyAdmin port

### Database Configuration

The application uses MySQL with the following default:
- Database Name: `hmisphp`
- User: `hospital`
- Password: `pramod2004` (Database)

## API Endpoints

### Admin Endpoints
- `/backend/admin/` - Admin dashboard
- `/backend/admin/his_admin_manage_employee.php` - Employee management
- `/backend/admin/his_admin_manage_patient.php` - Patient management

### Doctor Endpoints
- `/backend/doc/` - Doctor dashboard
- `/backend/doc/his_doc_manage_patient.php` - Patient management

## Docker Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f [service]

# Execute command in container
docker-compose exec [service] [command]

# Rebuild image
docker-compose build --no-cache

# Health check
bash scripts/health-check.sh
```

## Database Management

### Backup Database
```bash
docker-compose exec db mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} > backup.sql
```

### Restore Database
```bash
docker-compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} < backup.sql
```

### Access MySQL CLI
```bash
docker-compose exec db mysql -u root -p
```

## Monitoring and Logs

### View Application Logs
```bash
docker-compose logs -f app
```

### View Nginx Logs
```bash
docker-compose logs -f nginx
```

### View Database Logs
```bash
docker-compose logs -f db
```

### Monitor Resources
```bash
docker stats
```

## Security Considerations

- [ ] Change default admin password after first login
- [ ] Update doctor credentials
- [ ] Use strong database passwords
- [ ] Enable HTTPS/SSL in production
- [ ] Configure firewall rules
- [ ] Regular security updates
- [ ] Enable database backups
- [ ] Use environment variables for secrets
- [ ] Restrict file uploads
- [ ] Implement access controls

## CI/CD Pipeline

The project includes Jenkins pipeline for automated deployment:

1. **Checkout** - Clone code from Git
2. **Build** - Create Docker image
3. **Test** - Run code quality checks
4. **Deploy** - Push to production
5. **Verify** - Run health checks

See `Jenkinsfile` for detailed pipeline configuration.

## Troubleshooting

### Application won't start
```bash
docker-compose logs app
```

### Database connection error
```bash
docker-compose exec db mysql -u root -p -e "SELECT 1;"
```

### Port already in use
```bash
# Change port in .env
# Rebuild: docker-compose up -d
```

### Permission issues
```bash
docker-compose exec app chown -R www-data:www-data /app
```

For more troubleshooting, see `DEPLOYMENT_GUIDE.md`

## Performance Optimization

- Enabled PHP OPcache
- Gzip compression in Nginx
- Database query caching
- Static file caching (7 days)
- Redis support for advanced caching

## Testing

### Run Health Checks
```bash
bash scripts/health-check.sh
```

### Run Smoke Tests
```bash
bash scripts/smoke-tests.sh
```

### Run Code Quality Checks
```bash
docker run --rm -v $(pwd):/app php:7.4-cli php -l $(find . -name "*.php" -type f | head -5)
```

## Maintenance

### Regular Tasks
- Monitor disk space and memory
- Review application logs
- Update system packages
- Backup database daily
- Test backup restoration

### Scheduled Tasks
- Log rotation (daily)
- Old log cleanup (weekly)
- Database optimization (weekly)
- Full backup (daily)

## Support and Documentation

- **Developer Website:** https://codeastro.com
- **GitHub Issues:** [Create an issue in the repository]
- **Documentation:** See `/docs` directory

## License

This project is available through CodeAstro.com

## Changelog

### Version 1.0.0 (2024-01-20)
- Initial deployment setup
- Docker configuration
- Jenkins CI/CD pipeline
- AWS deployment scripts
- Complete documentation

---

## Contact

For questions or support:
1. Check `DEPLOYMENT_GUIDE.md` for detailed instructions
2. Review `QUICK_REFERENCE.md` for common commands
3. Check application logs for errors
4. Contact the development team

---

**Last Updated:** April 20, 2024  
**Status:** Production Ready
