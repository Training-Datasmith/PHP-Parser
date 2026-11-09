#!/usr/bin/env bash
set -euo pipefail

export COMPOSER_ALLOW_SUPERUSER=1
git config --global --add safe.directory /app 2>/dev/null || true

COMPOSER_URL="https://getcomposer.org/download/2.8.10/composer.phar"
COMPOSER_SHA256="28dbb6bd8bef31479c7985b774c130a8bda37dbe63c35b56f6cb6bc377427573"
curl -fsSL -o /tmp/composer.phar "${COMPOSER_URL}"
echo "${COMPOSER_SHA256}  /tmp/composer.phar" | sha256sum -c -

php -r '
$v = PHP_VERSION;
if ($v !== "7.4.33") {
    fwrite(STDERR, "PHP {$v} is not required 7.4.33\n");
    exit(1);
}
'

# Pre-rename baseline: Packagist PHPUnit 9.6.33+ only (no index alias yet).
php -r '
$path = "/app/composer.json";
$data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$data["require-dev"]["phpunit/phpunit"] = "^9.6.33";
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
'

php /tmp/composer.phar update --no-interaction --prefer-dist --no-progress

phpunit_out="$(mktemp)"
set +e
vendor/bin/phpunit --colors=never --do-not-cache-result --no-coverage >"${phpunit_out}" 2>&1
phpunit_status=$?
set -e
cat "${phpunit_out}"
if [[ ${phpunit_status} -ne 0 ]]; then
    exit "${phpunit_status}"
fi

ok_line="$(grep -E '^OK \([0-9]+ tests?, [0-9]+ assertions\)$' "${phpunit_out}" | tail -n 1)"
if [[ -z "${ok_line}" ]]; then
    echo 'baseline capture: missing OK line' >&2
    exit 1
fi

commit="$(git -C /app rev-parse HEAD)"
tests="$(sed -n 's/^OK (\([0-9]*\) tests\?, .*/\1/p' <<<"${ok_line}")"
assertions="$(sed -n 's/^OK ([0-9]* tests\?, \([0-9]*\) assertions\?)$/\1/p' <<<"${ok_line}")"

mkdir -p /app/scripts
cat > /app/scripts/baseline-counts.txt <<EOF
commit=${commit}
tests=${tests}
assertions=${assertions}
ok_line=${ok_line}
EOF

cat /app/scripts/baseline-counts.txt
rm -f "${phpunit_out}"
