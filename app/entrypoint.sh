#!/bin/bash
set -e

# Resolve reverse proxy container's IP
NGINX_IP=$(getent hosts nginx | awk '{ print $1 }')

while ! NGINX_IP=$(getent hosts nginx | awk '{ print $1 }'); do
  echo "⏳ Waiting for nginx to resolve..."
  sleep 1
done

echo "✅ NGINX resolved to $NGINX_IP"
echo "$NGINX_IP tweaky.skidz.ncsc redacted.skidz.ncsc" >> /etc/hosts

# Start Backend service
exec uvicorn app.main:app --host 0.0.0.0 --port 8000