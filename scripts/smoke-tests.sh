#!/bin/bash

echo "==== Smoke Tests ==="
echo ""

PASSED=0
FAILED=0

echo "Running tests..."
echo ""

echo -n "1. Homepage: "
if curl -sf http://localhost > /dev/null 2>&1; then
    echo "✓"
    ((PASSED++))
else
    echo "✗"
    ((FAILED++))
fi

echo -n "2. Admin Page: "
if curl -sf http://localhost/backend/admin/index.php > /dev/null 2>&1; then
    echo "✓"
    ((PASSED++))
else
    echo "✗"
    ((FAILED++))
fi

echo -n "3. Doctor Page: "
if curl -sf http://localhost/backend/doc/index.php > /dev/null 2>&1; then
    echo "✓"
    ((PASSED++))
else
    echo "✗"
    ((FAILED++))
fi

echo -n "4. Database: "
if docker-compose exec -T db mysql -u root -ppramod2004 -e "SELECT 1" > /dev/null 2>&1; then
    echo "✓"
    ((PASSED++))
else
    echo "✗"
    ((FAILED++))
fi

echo -n "5. Nginx: "
if docker-compose exec -T nginx nginx -t > /dev/null 2>&1; then
    echo "✓"
    ((PASSED++))
else
    echo "✗"
    ((FAILED++))
fi

echo ""
echo "Results: $PASSED passed, $FAILED failed"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "✓ All tests passed!"
    exit 0
else
    echo "✗ Some tests failed"
    exit 1
fi
