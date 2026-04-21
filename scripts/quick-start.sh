#!/bin/bash

echo "==== Quick Start ==="
echo ""

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "✗ Docker not installed"
    exit 1
fi

echo "✓ Docker found"

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "✗ Docker Compose not installed"
    exit 1
fi

echo "✓ Docker Compose found"

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ .env created"
else
    echo "✓ .env exists"
fi

# Start services
echo "Starting services..."
docker-compose up -d

echo "Waiting for database (15 seconds)..."
sleep 15

# Setup database
echo "Setting up database..."
bash scripts/run-migrations.sh

echo ""
echo "✓ Setup Complete!"
echo ""
echo "Access the application:"
echo "  Website: http://localhost"
echo "  Admin: http://localhost/backend/admin/index.php"
echo "  Doctor: http://localhost/backend/doc/index.php"
echo ""
echo "Default Login:"
echo "  Admin: admin / pramod2004"
echo "  Doctor: admin / pramod2004"
echo ""
