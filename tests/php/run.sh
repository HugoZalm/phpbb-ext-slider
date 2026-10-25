#!/usr/bin/env bash
# Run the PHPUnit tests inside the phpBB web container (the host has no PHP).
# PHPUnit is fetched as a phar on first run and kept out of git.
set -euo pipefail

here="$(cd "$(dirname "$0")" && pwd)"
container="${PHPBB_CONTAINER:-phpbb-dev-environment-web-1}"

if [ ! -f "$here/phpunit.phar" ]; then
	curl -sSL -o "$here/phpunit.phar" https://phar.phpunit.de/phpunit-11.phar
fi

# The extensions folder is mounted at /var/www/html/ext-external.
MSYS_NO_PATHCONV=1 docker exec "$container" \
	php /var/www/html/ext-external/hugozalm/slider/tests/php/phpunit.phar \
	--do-not-cache-result /var/www/html/ext-external/hugozalm/slider/tests/php "$@"
