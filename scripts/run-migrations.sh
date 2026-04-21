#!/bin/bash

echo "==== Database Setup ==="
echo ""

echo "1. Waiting for MySQL to be ready..."
for i in {1..30}; do
    docker-compose exec -T db mysql -u root -ppramod2004 -e "SELECT 1" > /dev/null 2>&1 && break
    [ $i -eq 30 ] && echo "   ✗ MySQL not responding" && exit 1
    sleep 1
done
echo "   ✓ MySQL ready"

echo "2. Creating database and user..."
docker-compose exec -T db mysql -u root -ppramod2004 << EOF
CREATE DATABASE IF NOT EXISTS hmisphp;
CREATE USER IF NOT EXISTS 'hospital'@'%' IDENTIFIED BY 'pramod2004';
GRANT ALL PRIVILEGES ON hmisphp.* TO 'hospital'@'%';
FLUSH PRIVILEGES;
EOF
echo "   ✓ Database ready"

echo "3. Importing database schema..."
if [ -f "DATABASE FILE/hmisphp.sql" ]; then
    docker-compose exec -T db mysql -u root -ppramod2004 hmisphp < "DATABASE FILE/hmisphp.sql"
    echo "   ✓ Schema imported"
else
    echo "   ✗ Schema file not found: DATABASE FILE/hmisphp.sql"
    exit 1
fi

echo ""
echo "✓ Database setup complete"
