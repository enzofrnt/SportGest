#!/bin/bash

echo "Starting application..."
echo "Current API URL: $API_URL"

cp /usr/share/nginx/html/assets/json/runtime.json /usr/share/nginx/html/assets/json/runtime.json.original
sed "s#\$API_URL#$API_URL#g" /usr/share/nginx/html/assets/json/runtime.json.original > /usr/share/nginx/html/assets/json/runtime.json

echo "API URL updated in runtime.json"

nginx -g "daemon off;"