#!/bin/bash
set -e

FASTAPI_IP=$(getent hosts fastapi | awk '{ print $1 }')

while ! FASTAPI_IP=$(getent hosts fastapi | awk '{ print $1 }'); do
  echo "⏳ Waiting for fastapi to resolve..."
  sleep 1
done

echo "✅ Backend IP resolved to $FASTAPI_IP"

sed "s/{{FASTAPI_IP}}/$FASTAPI_IP/g" /etc/nginx/templates/nginx.template.conf > /etc/nginx/conf.d/default.conf

# Start nginx
echo "🚀 Starting Nginx"
nginx -g "daemon off;"