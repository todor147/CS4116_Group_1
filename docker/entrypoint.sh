#!/bin/sh
# Bind Apache to $PORT when the host provides one (e.g. Render); default to 80.
set -e

PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
