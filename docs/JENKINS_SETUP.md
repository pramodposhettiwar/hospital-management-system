# Jenkins Setup and Configuration Guide

## Prerequisites

- Docker installed
- Access to the project repository
- AWS credentials (for deployment)

## Quick Jenkins Setup

### Option 1: Docker (Recommended)

```bash
# Create Docker network
docker network create jenkins

# Create volumes
docker volume create jenkins-data

# Run Jenkins
docker run -d \
  --name jenkins \
  --network jenkins \
  -p 8080:8080 \
  -p 50000:50000 \
  -v jenkins-data:/var/jenkins_home \
  -v /var/run/docker.sock:/var/run/docker.sock \
  jenkins/jenkins:lts
```

### Option 2: Manual Installation

```bash
# For Amazon Linux 2
sudo yum install -y java-11-openjdk
cd /tmp
wget -q -O jenkins.war https://get.jenkins.io/war-stable/latest/jenkins.war
java -jar jenkins.war --webroot=/var/cache/jenkins/war --logfile=/var/log/jenkins/jenkins.log &
```

## Initial Setup

### 1. Access Jenkins

```
http://localhost:8080
```

### 2. Unlock Jenkins

```bash
# Get initial admin password
docker logs jenkins | grep "initial AdminPassword"
```

### 3. Install Recommended Plugins

- Pipeline
- Docker
- AWS CodePipeline
- Git
- GitHub
- Blue Ocean

### 4. Configure System

#### Configure Jenkins Location
- Manage Jenkins → System
- Jenkins URL: `http://jenkins-server:8080/`

#### Configure Email (Optional)
- Manage Jenkins → System
- Email notification settings
- SMTP server, credentials, etc.

## Setting Up Credentials

### AWS Credentials

1. Go to **Manage Jenkins** → **Credentials**
2. Click **Add Credentials** → **AWS Credentials**
3. Enter:
   - Access Key ID
   - Secret Access Key
4. ID: `aws-credentials`

### Docker Registry

1. Go to **Manage Jenkins** → **Credentials**
2. Click **Add Credentials** → **Username with password**
3. Enter:
   - Username: Docker Hub username
   - Password: Docker Hub token
4. ID: `docker-registry`

### SSH Key for EC2

1. Go to **Manage Jenkins** → **Credentials**
2. Click **Add Credentials** → **SSH Username with private key**
3. Enter:
   - Username: `ec2-user`
   - Private Key: Your EC2 private key
4. ID: `ec2-ssh-key`

## Creating Pipeline Job

### Step 1: Create New Item

1. Click **New Item**
2. Enter name: `Hospital-Management-System`
3. Select **Pipeline**
4. Click **OK**

### Step 2: Configure Pipeline

In **Pipeline** section:
- Definition: **Pipeline script from SCM**
- SCM: **Git**
- Repository URL: `https://github.com/your-repo/Hospital-Management-System.git`
- Branch: `*/main`
- Script Path: `Jenkinsfile`

### Step 3: Configure Build Triggers

#### GitHub Webhook
1. Go to GitHub repository
2. Settings → Webhooks → Add webhook
3. Payload URL: `http://jenkins-server:8080/github-webhook/`
4. Content type: `application/json`
5. Events: Push events, Pull requests

#### Poll SCM
Alternative if webhook isn't available:
- In Pipeline configuration
- Check: "Poll SCM"
- Schedule: `H/5 * * * *` (every 5 minutes)

### Step 4: Save and Test

1. Click **Save**
2. Click **Build Now**
3. Monitor progress in **Build History**

## Pipeline Environment Variables

Add to Jenkinsfile for testing:

```groovy
environment {
    AWS_REGION = 'us-east-1'
    DOCKER_REGISTRY = 'ghcr.io/your-org'
    DB_USER = 'hospital'
    DB_NAME = 'hmisphp'
}
```

## Declarative Pipeline Example

```groovy
pipeline {
    agent any
    
    environment {
        DOCKER_IMAGE = "${DOCKER_REGISTRY}/hospital:${BUILD_NUMBER}"
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Build') {
            steps {
                script {
                    sh 'docker build -t ${DOCKER_IMAGE} .'
                }
            }
        }
        
        stage('Test') {
            steps {
                script {
                    sh 'docker run --rm ${DOCKER_IMAGE} php -l src/*.php'
                }
            }
        }
        
        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                script {
                    sh 'bash scripts/deploy.sh production ${DOCKER_IMAGE}'
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            echo "✓ Build successful"
        }
        failure {
            echo "✗ Build failed"
        }
    }
}
```

## Monitoring and Logs

### View Jenkins Logs

```bash
# Docker
docker logs -f jenkins

# Manual installation
tail -f /var/log/jenkins/jenkins.log
```

### Monitor Build Progress

1. Click on build number in **Build History**
2. Click **Console Output** to see real-time logs
3. Check **Artifacts** for build outputs

## Troubleshooting

### Pipeline Build Fails

1. Check **Console Output** for error messages
2. Verify credentials are correct
3. Check Docker daemon is running
4. Check repository URL is accessible

### Git Clone Fails

1. Verify SSH key is added to GitHub
2. Check repository is public or SSH key has access
3. Test: `ssh -T git@github.com`

### Docker Build Fails

1. Check Docker daemon is running: `docker ps`
2. Check Dockerfile syntax
3. Check Docker registry credentials

### Deployment Fails

1. Check EC2 instance is running
2. Verify SSH key and permissions
3. Check security groups allow SSH (22)
4. Test SSH access manually

## Best Practices

1. **Use Declarative Pipelines** - Easier to read and maintain
2. **Parameterize Jobs** - Allow different environments
3. **Version Control** - Keep Jenkinsfile in Git
4. **Security** - Use Jenkins credentials for secrets
5. **Notifications** - Set up email/Slack alerts
6. **Artifact Retention** - Clean old builds regularly
7. **Documentation** - Document custom steps

## Advanced Configuration

### Distributed Builds

Use agents/nodes for:
- Parallel testing
- Different environments
- Load distribution

```groovy
pipeline {
    agent {
        label 'docker-agent'
    }
}
```

### Slack Notifications

1. Install Slack plugin
2. Get Slack webhook URL
3. Add to Jenkinsfile:

```groovy
post {
    success {
        slackSend(
            color: 'good',
            message: 'Deployment successful'
        )
    }
    failure {
        slackSend(
            color: 'danger',
            message: 'Deployment failed'
        )
    }
}
```

### Email Notifications

```groovy
post {
    failure {
        emailext(
            subject: "Build Failed: ${env.JOB_NAME} #${env.BUILD_NUMBER}",
            body: "Build failed. See console output at ${env.BUILD_URL}",
            to: "team@hospital.local"
        )
    }
}
```

## Security Recommendations

1. **Change default admin password** immediately
2. **Enable matrix-based security** for user management
3. **Use API tokens** instead of passwords
4. **Enable HTTPS** in production
5. **Restrict pipeline execution** to approved users
6. **Audit logs** for compliance
7. **Keep Jenkins updated** regularly
8. **Scan images** before deployment

## Backup and Recovery

### Backup Jenkins Configuration

```bash
docker exec jenkins tar czf - /var/jenkins_home > jenkins-backup.tar.gz
```

### Restore Jenkins

```bash
docker stop jenkins
docker rm jenkins
docker run -d \
  --name jenkins \
  -v jenkins-data:/var/jenkins_home \
  -p 8080:8080 \
  jenkins/jenkins:lts

tar xzf jenkins-backup.tar.gz -C jenkins-data
docker restart jenkins
```

---

For more information, visit [Jenkins Documentation](https://www.jenkins.io/doc/)
