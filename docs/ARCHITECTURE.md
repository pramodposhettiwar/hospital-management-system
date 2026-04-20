# Project Architecture and Design

## System Overview

Hospital Management System is a comprehensive web-based platform designed to streamline hospital operations, patient management, and healthcare workflows.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        End Users                                │
│  (Admin, Doctors, Staff via Web Browser)                       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Load Balancer / Nginx                        │
│                    (SSL/TLS Termination)                        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   Application Layer                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              PHP-FPM (7.4+)                              │  │
│  │  (Hospital Management System Application)               │  │
│  │                                                          │  │
│  │  - Admin Module                                         │  │
│  │  - Doctor Module                                        │  │
│  │  - Patient Management                                   │  │
│  │  - Reports & Analytics                                  │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   Data Layer                                    │
│  ┌────────────────────────────────────────────────────────┐    │
│  │        MySQL Database (8.0)                            │    │
│  │  (Patient Records, Medical History, Staff, etc.)       │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                │
│  ┌────────────────────────────────────────────────────────┐    │
│  │        Redis Cache (Optional)                          │    │
│  │  (Session Storage, Performance Caching)                │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│              Storage & Backup Layer                             │
│  ┌────────────────────────────────────────────────────────┐    │
│  │     S3 Buckets / File Storage                          │    │
│  │  (Backups, Medical Documents, User Uploads)            │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

## Technology Stack

### Frontend
- **Framework**: Bootstrap 4.1.3
- **CSS**: Custom CSS + Animate.css
- **JavaScript**: jQuery + Custom JS
- **Languages**: HTML5, CSS3, JavaScript
- **Responsive**: Yes (Mobile, Tablet, Desktop)

### Backend
- **Language**: PHP 7.4+
- **Web Server**: Nginx (Alpine)
- **Application Server**: PHP-FPM
- **Framework**: Vanilla PHP (MVC-like structure)

### Database
- **DBMS**: MySQL 8.0
- **Backup**: Automated daily backups
- **Replication**: Optional master-slave

### Caching
- **Redis**: Optional for session storage
- **OPcache**: PHP bytecode caching enabled

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Orchestration**: Manual via Docker Compose
- **CI/CD**: Jenkins, GitHub Actions
- **IaC**: Terraform
- **Cloud Provider**: AWS EC2, RDS, S3

## Directory Structure

```
Hospital-PHP/
├── index.php                          # Homepage
├── README.md                          # Project documentation
├── DEPLOYMENT_GUIDE.md               # Detailed deployment guide
├── QUICK_REFERENCE.md                # Quick command reference
├── ARCHITECTURE.md                   # This file
├── Dockerfile                        # Docker image definition
├── docker-compose.yml                # Production compose
├── docker-compose.dev.yml            # Development compose
├── Jenkinsfile                       # CI/CD pipeline
├── .gitignore                        # Git ignore rules
├── .env.example                      # Environment template
│
├── assets/                           # Frontend assets
│   ├── css/                          # Stylesheets
│   ├── js/                           # JavaScript files
│   ├── images/                       # Images and logos
│   └── fonts/                        # Web fonts
│
├── backend/                          # Backend application
│   ├── admin/                        # Admin dashboard
│   │   ├── index.php                # Admin login
│   │   ├── his_admin_dashboard.php  # Admin dashboard
│   │   ├── his_admin_manage_employee.php
│   │   ├── his_admin_manage_patient.php
│   │   ├── his_admin_manage_payroll.php
│   │   └── ...other modules
│   │
│   ├── doc/                          # Doctor portal
│   │   ├── index.php                # Doctor login
│   │   ├── his_doc_dashboard.php    # Doctor dashboard
│   │   ├── his_doc_manage_patient.php
│   │   └── ...other modules
│   │
│   └── assets/
│       ├── inc/config.php            # Database config
│       └── ...other includes
│
├── config/                           # Configuration files
│   ├── nginx/                        # Nginx configuration
│   │   ├── nginx.conf
│   │   └── conf.d/default.conf
│   ├── mysql/                        # MySQL configuration
│   │   └── my.cnf
│   ├── php.ini                       # PHP configuration
│   └── ssl/                          # SSL certificates
│
├── scripts/                          # Deployment scripts
│   ├── init.sh                       # Initialize project
│   ├── quick-start.sh                # Quick start
│   ├── deploy.sh                     # Deploy to EC2
│   ├── health-check.sh               # Health checks
│   ├── run-migrations.sh             # Database setup
│   ├── smoke-tests.sh                # Smoke tests
│   ├── backup.sh                     # Backup database
│   ├── restore.sh                    # Restore database
│   ├── setup-aws.sh                  # AWS infrastructure
│   └── setup-ssl.sh                  # SSL setup
│
├── terraform/                        # Infrastructure as Code
│   ├── main.tf                       # Main configuration
│   ├── variables.tf                  # Variables
│   ├── terraform.tfvars              # Terraform values
│   └── user_data.sh                  # EC2 initialization
│
├── .github/
│   └── workflows/
│       └── ci-cd.yml                 # GitHub Actions
│
├── docs/                             # Documentation
│   ├── JENKINS_SETUP.md             # Jenkins guide
│   ├── AWS_DEPLOYMENT.md            # AWS guide
│   └── ARCHITECTURE.md              # Architecture (this file)
│
└── DATABASE FILE/
    └── hmisphp.sql                   # Database schema
```

## Data Model

### Core Entities

#### 1. Users
- Admin users (Hospital staff)
- Doctor users (Medical professionals)
- Relationships to departments, roles

#### 2. Patients
- Personal information
- Contact details
- Insurance information
- Medical history

#### 3. Medical Records
- Patient consultations
- Diagnoses
- Treatments
- Lab results
- Vital signs

#### 4. Staff
- Employees
- Roles and departments
- Salary information
- Work schedules

#### 5. Inventory
- Medical equipment
- Pharmaceuticals
- Stock levels
- Suppliers

#### 6. Financial
- Accounts receivable
- Accounts payable
- Vendor information

## Module Organization

### Admin Module
- Dashboard
- User Management
- Department Management
- Employee Management
- Patient Management
- Payroll Management
- Equipment Inventory
- Pharmaceutical Inventory
- Financial Management
- Reports

### Doctor Module
- Dashboard
- Patient Management
- Lab Tests & Results
- Vital Signs
- Medical Records
- Prescriptions

### Features
- Role-based access control
- Audit trails
- Multi-user support
- Data validation
- Security considerations

## Deployment Architecture

### Local Development
```
Developer Machine
├── Docker Desktop
├── docker-compose up
├── MySQL Container
├── Nginx Container
└── PHP Container
```

### AWS Production
```
AWS Region
├── VPC (10.0.0.0/16)
│   ├── Public Subnet
│   │   └── EC2 Instance
│   │       ├── Nginx Container
│   │       └── PHP Container
│   │
│   └── Private Subnet
│       └── RDS MySQL
│
├── S3 Bucket (Backups)
└── Security Groups
```

## Security Architecture

### Network Security
- VPC isolation
- Security groups
- NACLs
- Private subnets for databases

### Application Security
- Input validation
- SQL parameterized queries
- CSRF protection
- XSS prevention
- Password hashing

### Data Security
- Encryption at rest (EBS, RDS)
- Encryption in transit (TLS/SSL)
- Backup encryption
- Access controls

### Authentication
- User login with credentials
- Session management
- Password policies
- Audit logging

## Performance Optimization

### Database
- Indexes on frequently queried fields
- Query optimization
- Connection pooling
- Caching layer (Redis)

### Application
- PHP OPcache enabled
- Gzip compression
- Static file caching
- Lazy loading

### Infrastructure
- Load balancing (optional)
- Auto-scaling (optional)
- CDN for static assets
- Database read replicas

## Monitoring and Observability

### Metrics
- CPU utilization
- Memory usage
- Disk space
- Network traffic
- Application response time

### Logging
- Application logs
- Web server logs
- Database logs
- Audit logs

### Alerting
- High CPU usage
- High memory usage
- Disk space
- Database errors
- Application errors

## Backup and Disaster Recovery

### Backup Strategy
- **Frequency**: Daily
- **Retention**: 30 days
- **Location**: S3 (separate region)
- **Type**: Full database backup

### Recovery RTO/RPO
- **RTO**: 4 hours
- **RPO**: 24 hours

### Disaster Recovery Steps
1. Provision new EC2 instance
2. Restore database from backup
3. Restore application files from S3
4. Update DNS records
5. Verify functionality

## Scalability Considerations

### Vertical Scaling
- Upgrade instance type
- Increase RAM/CPU
- Larger database instance

### Horizontal Scaling
- Load balancer for multiple EC2 instances
- Read replicas for database
- CDN for static content
- Separate microservices (future)

## Technology Choices and Rationale

| Component | Choice | Rationale |
|-----------|--------|-----------|
| Language | PHP 7.4 | Legacy compatibility, simplicity |
| Database | MySQL 8.0 | Reliability, cost-effective |
| Frontend | Bootstrap | Responsive, easy maintenance |
| Containerization | Docker | Consistency, portability |
| Cloud | AWS | Market leader, broad services |
| IaC | Terraform | Declarative, multi-cloud |
| CI/CD | Jenkins | Flexible, self-hosted option |

## Future Improvements

1. **Microservices Architecture**
   - Separate services per module
   - Independent scaling

2. **Modern Frontend**
   - React/Vue.js frontend
   - REST/GraphQL APIs

3. **Advanced Features**
   - Real-time notifications
   - Mobile app
   - Integration with external APIs

4. **DevOps Improvements**
   - Kubernetes orchestration
   - Istio service mesh
   - ArgoCD for GitOps

5. **Security**
   - OAuth2/OIDC authentication
   - API rate limiting
   - Advanced threat detection

## Conclusion

The Hospital Management System is architected as a monolithic PHP application with Docker containerization for easy deployment. It's designed for healthcare operations with modular components for different user roles. The current architecture is suitable for small to medium-sized hospitals and can be scaled vertically or with additional infrastructure as needed.

---

For questions or clarifications, refer to other documentation files or contact the development team.
