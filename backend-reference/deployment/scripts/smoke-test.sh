#!/usr/bin/env bash
BASE_URL="${1:-https://yourdomain.com}"
curl -fsS "$BASE_URL/api/v1/health"
