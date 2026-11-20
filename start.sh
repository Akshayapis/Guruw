#!/bin/sh
# Render injects PORT (usually 10000); Apache must listen there.
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf 2>/dev/null || true
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
  sed -i "s|<VirtualHost \*:80>|<VirtualHost *:${PORT}>|" /etc/apache2/sites-available/000-default.conf
fi
echo "Cashpe starting on port ${PORT}"
exec apache2-foreground
