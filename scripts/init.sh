#!/bin/bash

echo "==== Initialize Project ==="
echo ""

# Step 1: Create .env file
echo "1. Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "   ✓ .env created"
else
    echo "   ✓ .env exists"
fi

# Step 2: Make scripts executable
echo "2. Making scripts executable..."
chmod +x scripts/*.sh
echo "   ✓ Done"

# Step 3: Build and start Docker
echo "3. Building Docker images..."
docker-compose build
echo "   ✓ Images built"

echo "4. Starting services..."
docker-compose up -d
echo "   ✓ Services started"

# Step 4: Wait for services
echo "5. Waiting for services (20 seconds)..."
sleep 20

# Step 5: Setup database
echo "6. Setting up database..."
bash scripts/run-migrations.sh

# Step 6: Run checks
echo "7. Running health check..."
bash scripts/health-check.sh

echo ""
echo "✓ Project initialized successfully!"
echo ""
echo "Next steps:"
echo "  bash scripts/deploy.sh   (to deploy)"
echo "  bash scripts/health-check.sh (to check status)"

echo ""
log_info "✓ Initialization completed successfully!"
echo ""
echo "=========================================="
echo "Next Steps:"
echo "=========================================="
echo ""
echo "1. Access the application:"
echo "   - Homepage: http://localhost"
echo "   - Admin: http://localhost/backend/admin/index.php"
echo "   - Doctor: http://localhost/backend/doc/index.php"
echo "   - Database: http://localhost:8081"
echo ""
echo "2. Default Credentials:"
echo "   - Admin Email: admin@mail.com"
echo "   - Admin Password: Password@123"
echo "   - Doctor ID: YDS7L"
echo "   - Doctor Password: password"
echo ""
echo "3. Important Tasks:"
echo "   ⚠️  Change default admin password"
echo "   ⚠️  Change doctor credentials"
echo "   ⚠️  Review security settings"
echo "   ⚠️  Configure email settings"
echo ""
echo "4. Configuration Files:"
echo "   - .env (Environment variables)"
echo "   - config/nginx/ (Nginx configuration)"
echo "   - config/mysql/ (MySQL configuration)"
echo "   - config/php.ini (PHP configuration)"
echo ""
echo "5. Useful Commands:"
echo "   docker-compose logs -f        # View logs"
echo "   docker-compose ps             # Check status"
echo "   bash scripts/backup.sh        # Create backup"
echo "   bash scripts/health-check.sh  # Run health checks"
echo ""
echo "For more information, see DEPLOYMENT_GUIDE.md and QUICK_REFERENCE.md"
echo ""
