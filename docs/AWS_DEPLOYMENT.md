# AWS Deployment Guide

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                  AWS EC2 Instance                    │
│  ┌──────────────────────────────────────────────┐   │
│  │          Docker & Docker Compose             │   │
│  │  ┌────────────┐  ┌────────────┐ ┌────────┐ │   │
│  │  │   Nginx    │  │ PHP-FPM    │ │ Redis  │ │   │
│  │  └────────────┘  └────────────┘ └────────┘ │   │
│  └──────────────────────────────────────────────┘   │
│                                                      │
│  ┌─────────────────────────────────────────────┐   │
│  │      AWS RDS MySQL Database Instance        │   │
│  └─────────────────────────────────────────────┘   │
│                                                      │
│  ┌─────────────────────────────────────────────┐   │
│  │        AWS S3 Bucket (Backups)              │   │
│  └─────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

## Prerequisites

- AWS Account with appropriate permissions
- AWS CLI v2 installed and configured
- Terraform installed (v1.0+)
- SSH key pair created
- GitHub repository set up

## Deployment Methods

### Method 1: Terraform (Recommended for Production)

#### Step 1: Prepare Terraform

```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
```

Edit `terraform.tfvars`:
```hcl
aws_region     = "us-east-1"
environment    = "production"
instance_type  = "t3.medium"
public_key     = "ssh-rsa AAAA..."
github_repo    = "https://github.com/your-org/Hospital-Management-System.git"
```

#### Step 2: Initialize Terraform

```bash
terraform init
```

#### Step 3: Plan Deployment

```bash
terraform plan -out=tfplan
```

#### Step 4: Apply Configuration

```bash
terraform apply tfplan
```

#### Step 5: Get Outputs

```bash
terraform output instance_public_ip
terraform output db_endpoint
```

### Method 2: AWS CLI Script

```bash
bash scripts/setup-aws.sh
```

### Method 3: AWS Management Console

1. **Create VPC**
   - CIDR: 10.0.0.0/16
   - Enable DNS

2. **Create Subnets**
   - Public: 10.0.1.0/24
   - Private: 10.0.2.0/24

3. **Create Security Groups**
   - Allow HTTP (80)
   - Allow HTTPS (443)
   - Allow SSH (22)
   - Allow MySQL (3306) for internal

4. **Create EC2 Instance**
   - AMI: Amazon Linux 2
   - Type: t3.medium
   - Public IP: Enable
   - Security Group: As above
   - Key Pair: Create or select

5. **Create RDS Database**
   - Engine: MySQL 8.0
   - Instance: db.t3.micro
   - Storage: 20GB
   - Multi-AZ: Yes (production)

## Post-Deployment Configuration

### SSH into EC2 Instance

```bash
ssh -i hospital-key.pem ec2-user@<public-ip>
```

### Verify Services

```bash
cd /opt/Hospital-Management-System
docker-compose ps
docker-compose logs app
```

### Check Application

```bash
curl http://localhost/health
```

### Access Application

- **Admin**: http://<public-ip>/backend/admin/index.php
- **Doctor**: http://<public-ip>/backend/doc/index.php
- **Database**: mysql -h <db-endpoint> -u hospital -p

### Configure SSL/TLS

#### Option 1: Let's Encrypt (Recommended)

```bash
docker run -it --rm -v /etc/letsencrypt:/etc/letsencrypt \
  certbot/certbot certbot certonly \
  --standalone \
  --email your-email@example.com \
  -d your-domain.com
```

#### Option 2: Self-Signed Certificate

```bash
bash scripts/setup-ssl.sh
```

### Update DNS Records

Point your domain to the EC2 public IP:

```
A record: your-domain.com → <public-ip>
```

## Database Setup

### Connect to RDS

```bash
mysql -h <db-endpoint> -u hospital -p -D hmisphp
```

### Import Schema (if not done automatically)

```bash
mysql -h <db-endpoint> -u hospital -p -D hmisphp < DATABASE\ FILE/hmisphp.sql
```

### Create Backups

```bash
# Manual backup
cd /opt/Hospital-Management-System
bash scripts/backup.sh

# Automated backup (cron)
0 2 * * * cd /opt/Hospital-Management-System && bash scripts/backup.sh >> /var/log/hospital-backup.log
```

## Monitoring and Logging

### CloudWatch Metrics

1. Navigate to CloudWatch Console
2. View metrics under `Hospital/Application`
3. Set up alarms for:
   - CPU Utilization > 80%
   - Memory Utilization > 85%
   - Disk Usage > 80%
   - Database Connection Errors

### View Logs

```bash
# Application logs
docker-compose logs -f app

# Nginx logs
docker-compose logs -f nginx

# Database logs
docker-compose logs -f db

# CloudWatch logs
aws logs tail /aws/hospital/application --follow
```

### Set Up Alarms

```bash
# CPU Alarm
aws cloudwatch put-metric-alarm \
  --alarm-name hospital-high-cpu \
  --alarm-description "Alert when CPU exceeds 80%" \
  --metric-name CPUUtilization \
  --namespace AWS/EC2 \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --alarm-actions arn:aws:sns:us-east-1:123456789012:alert-topic
```

## Scaling Configuration

### Vertical Scaling (Larger Instance)

```bash
# Stop instance
aws ec2 stop-instances --instance-ids i-xxxxx

# Change instance type
aws ec2 modify-instance-attribute --instance-id i-xxxxx --instance-type t3.large

# Start instance
aws ec2 start-instances --instance-ids i-xxxxx
```

### Horizontal Scaling (Load Balancer)

```bash
# Create Application Load Balancer
aws elbv2 create-load-balancer \
  --name hospital-alb \
  --subnets subnet-xxxxx subnet-yyyyy \
  --security-groups sg-xxxxx

# Create Auto Scaling Group
aws autoscaling create-auto-scaling-group \
  --auto-scaling-group-name hospital-asg \
  --launch-template LaunchTemplateName=hospital,Version='$Latest' \
  --min-size 1 \
  --max-size 3 \
  --desired-capacity 2
```

## Database Scaling

### RDS Read Replicas

```bash
aws rds create-db-instance-read-replica \
  --db-instance-identifier hospital-db-read \
  --source-db-instance-identifier hospital-db
```

### Multi-AZ Deployment

```bash
aws rds modify-db-instance \
  --db-instance-identifier hospital-db \
  --multi-az \
  --apply-immediately
```

## Cost Optimization

### Strategies

1. **Use Reserved Instances** for production
2. **Set up auto-shutdown** for non-production
3. **Use AWS Free Tier** resources for testing
4. **Enable S3 lifecycle policies** for old backups
5. **Monitor costs** with AWS Cost Explorer

### Cost Estimate (Monthly)

| Service | Instance | Cost/Month |
|---------|----------|-----------|
| EC2 | t3.medium | ~$35 |
| RDS | db.t3.micro | ~$35 |
| S3 | Backups | ~$5 |
| Data Transfer | Out | ~$5 |
| **Total** | | **~$80** |

*Prices vary by region and usage*

## Disaster Recovery

### Backup Strategy

- **Frequency**: Daily
- **Retention**: 30 days
- **Location**: S3 (separate region)
- **Testing**: Monthly restore tests

### Recovery Procedures

```bash
# Restore database from backup
mysql -h <new-db> -u hospital -p < backup-file.sql

# Restore files from S3
aws s3 cp s3://hospital-backups/uploads.tar.gz .
tar -xzf uploads.tar.gz
```

## Security Best Practices

1. **Enable VPC Security Groups**
   - Restrict inbound traffic
   - Use security group rules

2. **Use IAM Roles**
   - For EC2 instances
   - For database access
   - Principle of least privilege

3. **Enable Encryption**
   - EBS volume encryption
   - RDS encryption at rest
   - S3 bucket encryption
   - SSL/TLS in transit

4. **Audit and Logging**
   - CloudTrail for API calls
   - VPC Flow Logs for network traffic
   - Application logs in CloudWatch

5. **Regular Updates**
   - Security patches
   - OS updates
   - Application updates
   - Database updates

6. **Network Security**
   - Private subnets for databases
   - NAT Gateway for outbound traffic
   - VPN for admin access
   - Security Group rules

## Troubleshooting

### Cannot SSH to Instance

```bash
# Check security group allows SSH (22)
aws ec2 describe-security-groups --group-ids sg-xxxxx

# Check instance is running
aws ec2 describe-instances --instance-ids i-xxxxx

# Check key pair permissions
chmod 600 hospital-key.pem
```

### Database Connection Error

```bash
# Check RDS is available
aws rds describe-db-instances --db-instance-identifier hospital-db

# Test connection
mysql -h <endpoint> -u hospital -p -e "SELECT 1;"

# Check security group allows 3306
aws ec2 describe-security-groups --group-ids sg-xxxxx
```

### Application Not Responding

```bash
# SSH into instance
ssh -i hospital-key.pem ec2-user@<public-ip>

# Check services
docker-compose ps

# Check logs
docker-compose logs app

# Restart services
docker-compose restart
```

## Cleanup (Teardown)

### Delete Infrastructure with Terraform

```bash
terraform destroy
```

### Manual Deletion

```bash
# Terminate EC2 instance
aws ec2 terminate-instances --instance-ids i-xxxxx

# Delete RDS database
aws rds delete-db-instance \
  --db-instance-identifier hospital-db \
  --skip-final-snapshot

# Delete S3 bucket (empty first)
aws s3 rm s3://hospital-backups --recursive
aws s3api delete-bucket --bucket hospital-backups
```

---

## Support

- **AWS Documentation**: https://docs.aws.amazon.com/
- **EC2 Guide**: https://docs.aws.amazon.com/ec2/
- **RDS Guide**: https://docs.aws.amazon.com/rds/
- **Terraform AWS Provider**: https://registry.terraform.io/providers/hashicorp/aws/latest/docs
