#!/usr/bin/env bash
# Starts the stack from the given compose file, logs in as the demo employee and checks the dashboard.
set -euo pipefail
compose="docker compose -f $1"

$compose up -d
trap '$compose logs --tail=50; $compose down -v' EXIT

echo "Waiting for MySQL"
for i in $(seq 1 60); do
  if $compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -proot --silent 2>/dev/null \
     && $compose exec -T db mysql -h 127.0.0.1 -uroot -proot -N -e "SELECT COUNT(*) FROM eleave.users" 2>/dev/null | grep -q '[1-9]'; then
    echo "MySQL is ready"
    break
  fi
  if [ "$i" = 60 ]; then echo "MySQL did not start"; exit 1; fi
  sleep 5
done

echo "GET the login page"
curl -fsS http://localhost/pages/user_login.php | grep "E-Leave Login" >/dev/null

echo "POST the login form"
curl -fsS -c cookies.txt -o /dev/null -w '%{http_code} %{redirect_url}\n' \
  -d 'email=employee@company.com&password=password' http://localhost/pages/user_login.php

echo "GET the dashboard as the logged-in employee"
curl -fsS -b cookies.txt http://localhost/ | tr -s ' \n' ' ' | grep -o "Welcome to e-Leave, John Doe"

rm -f cookies.txt
echo "Smoke test passed"
