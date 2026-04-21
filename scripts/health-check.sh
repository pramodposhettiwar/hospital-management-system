#!/bin/bash

echo "==== Health Check ==="
echo ""

echo "1. Container Status:"
docker-compose ps

echo ""
echo "2. Service Checks:"

echo -n "  • Nginx: "
docker-compose exec -T nginx nginx -t > /dev/null 2>&1 && echo "✓" || echo "✗"

echo -n "  • PHP: "
docker-compose exec -T app php -v > /dev/null 2>&1 && echo "✓" || echo "✗"

echo -n "  • Database: "
docker-compose exec -T db mysql -u root -ppramod2004 -e "SELECT 1;" > /dev/null 2>&1 && echo "✓" || echo "✗"

echo -n "  • Website: "
curl -s http://localhost > /dev/null 2>&1 && echo "✓" || echo "✗"

echo ""
echo "✓ Health check complete"
