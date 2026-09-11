#!/bin/sh
# Playwright container entrypoint.
# Injects Docker service hostnames into /etc/hosts so Chromium can resolve them
# without relying on its own DNS resolver (which fails in Docker internal networks).
set -e

for HOST in app-tenant-a app-tenant-b; do
  IP=$(getent hosts "$HOST" 2>/dev/null | awk '{print $1}')
  if [ -n "$IP" ]; then
    echo "$IP $HOST" >> /etc/hosts
    echo "[entrypoint] $HOST -> $IP (added to /etc/hosts)"
  else
    echo "[entrypoint] WARNING: could not resolve $HOST — Chromium may fail DNS"
  fi
done

exec "$@"
