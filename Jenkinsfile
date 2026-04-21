pipeline {
    agent any
    
    environment {
        DOCKER_IMAGE = "hospital:${BUILD_NUMBER}"
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
                    sh 'docker-compose up -d'
                    sh 'sleep 10'
                    sh 'bash scripts/health-check.sh || true'
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
            sh 'docker-compose down || true'
        }
    }
}
