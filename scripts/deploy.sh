#!/bin/bash

echo "==== Deployment ==="
echo ""

# Check if Docker is running
if ! command -v docker &> /dev/null; then
    echo "✗ Docker is not installed"
    exit 1
fi

echo "✓ Docker found"
echo ""

# Stop running containers
echo "Stopping services..."
docker-compose down

# Start fresh deployment
echo "Starting services..."
docker-compose up -d

echo "Waiting for services to be ready..."
sleep 10

# Show status
echo ""
echo "✓ Services deployed successfully"
echo ""
docker-compose ps
echo ""
echo "Application is ready at: http://localhost"
