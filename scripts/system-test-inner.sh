#!/usr/bin/env bash
set -euo pipefail

export COMPOSER_ALLOW_SUPERUSER=1
git config --global --add safe.directory /app 2>/dev/null || true

fail_deprecation_output() {
    local label="$1"
    local file="$2"
    local stripped
    stripped="$(mktemp)"
    sed -E 's/\x1b\[[0-9;]*[[:alpha:]]//g' "${file}" >"${stripped}"
    if grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" >/dev/null; then
        echo "${label}: output contains Warning, Deprecated, or Notice" >&2
        grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" >&2 || true
        rm -f "${stripped}"
        exit 1
    fi
    rm -f "${stripped}"
}

php -v | tee /tmp/php-version.txt
php -r '
$v = PHP_VERSION;
if ($v !== "7.4.33") {
    fwrite(STDERR, "PHP {$v} is not required 7.4.33\n");
    exit(1);
}
'

for ext in ctype json phar tokenizer; do
    if ! php -m | grep -qi "^${ext}$"; then
        echo "Missing required PHP extension: ${ext}" >&2
        exit 1
    fi
done

COMPOSER_URL="https://getcomposer.org/download/2.8.10/composer.phar"
COMPOSER_SHA256="28dbb6bd8bef31479c7985b774c130a8bda37dbe63c35b56f6cb6bc377427573"
curl -fsSL -o /tmp/composer.phar "${COMPOSER_URL}"
echo "${COMPOSER_SHA256}  /tmp/composer.phar" | sha256sum -c -

composer_out="$(mktemp)"
set +e
php /tmp/composer.phar update --no-interaction --prefer-dist --no-progress >"${composer_out}" 2>&1
composer_status=$?
set -e
cat "${composer_out}"
fail_deprecation_output "composer update" "${composer_out}"
if [[ ${composer_status} -ne 0 ]]; then
    rm -f "${composer_out}"
    exit "${composer_status}"
fi
rm -f "${composer_out}"

lint_out="$(mktemp)"
set +e
find lib/PhpParser -name '*.php' -print0 | xargs -0 -n1 php -l >"${lint_out}" 2>&1
lint_status=$?
set -e
cat "${lint_out}"
fail_deprecation_output "php -l lib" "${lint_out}"
if [[ ${lint_status} -ne 0 ]]; then
    rm -f "${lint_out}"
    exit "${lint_status}"
fi
if ! grep -Fxq 'No syntax errors detected in lib/PhpParser/compatibility_tokens.php' "${lint_out}"; then
    :
fi
if ! grep -q '^No syntax errors detected' "${lint_out}"; then
    echo 'php -l: missing syntax success lines' >&2
    rm -f "${lint_out}"
    exit 1
fi
rm -f "${lint_out}"

parser_out="$(mktemp)"
set +e
php grammar/rebuild_Parsers.php >"${parser_out}" 2>&1
parser_status=$?
set -e
cat "${parser_out}"
fail_deprecation_output "rebuild_Parsers" "${parser_out}"
if [[ ${parser_status} -ne 0 ]]; then
    rm -f "${parser_out}"
    exit "${parser_status}"
fi
if ! grep -q 'function init_reduce_callbacks' lib/PhpParser/Parser/Php7.php \
    || ! grep -q 'function init_reduce_callbacks' lib/PhpParser/Parser/Php8.php; then
    echo 'rebuild_Parsers: generated parsers missing init_reduce_callbacks' >&2
    rm -f "${parser_out}"
    git checkout -- lib/PhpParser/Parser/Php7.php lib/PhpParser/Parser/Php8.php 2>/dev/null || true
    exit 1
fi
rm -f "${parser_out}"
git checkout -- lib/PhpParser/Parser/Php7.php lib/PhpParser/Parser/Php8.php

pins_out="$(mktemp)"
set +e
php scripts/check-cluster-pins.php >"${pins_out}" 2>&1
pins_status=$?
set -e
cat "${pins_out}"
fail_deprecation_output "check-cluster-pins" "${pins_out}"
if [[ ${pins_status} -ne 0 ]]; then
    rm -f "${pins_out}"
    exit "${pins_status}"
fi
if ! grep -Fxq 'check-cluster-pins: index org packages satisfied (0 pinned); Packagist PHPUnit guard passed.' "${pins_out}"; then
    echo 'check-cluster-pins: missing exact success line' >&2
    rm -f "${pins_out}"
    exit 1
fi
rm -f "${pins_out}"

audit_out="$(mktemp)"
set +e
php /tmp/composer.phar audit --no-interaction >"${audit_out}" 2>&1
audit_status=$?
set -e
cat "${audit_out}"
fail_deprecation_output "composer audit" "${audit_out}"
if [[ ${audit_status} -ne 0 ]]; then
    exit "${audit_status}"
fi
if ! grep -Fxq 'No security vulnerability advisories found.' "${audit_out}"; then
    echo 'composer audit: missing exact success line' >&2
    exit 1
fi
rm -f "${audit_out}"

rename_out="$(mktemp)"
set +e
php scripts/check-rename-complete.php >"${rename_out}" 2>&1
rename_status=$?
set -e
cat "${rename_out}"
fail_deprecation_output "check-rename-complete" "${rename_out}"
if [[ ${rename_status} -ne 0 ]]; then
    rm -f "${rename_out}"
    exit "${rename_status}"
fi
if ! grep -Fxq 'check-rename-complete: no pre-rename repo-owned names remain.' "${rename_out}"; then
    echo 'check-rename-complete: missing exact success line' >&2
    rm -f "${rename_out}"
    exit 1
fi
rm -f "${rename_out}"

if [[ ! -f scripts/baseline-counts.txt ]]; then
    echo 'missing scripts/baseline-counts.txt' >&2
    exit 1
fi
EXPECTED_OK_LINE="$(grep '^ok_line=' scripts/baseline-counts.txt | cut -d= -f2-)"

phpunit_out="$(mktemp)"
set +e
vendor/bin/phpunit --colors=never --do-not-cache-result --no-coverage >"${phpunit_out}" 2>&1
phpunit_status=$?
set -e
cat "${phpunit_out}"
fail_deprecation_output "phpunit" "${phpunit_out}"
if [[ ${phpunit_status} -ne 0 ]]; then
    exit "${phpunit_status}"
fi
if [[ -z "${EXPECTED_OK_LINE:-}" ]]; then
    echo 'baseline ok_line not loaded' >&2
    exit 1
fi
if ! grep -Fxq "${EXPECTED_OK_LINE}" "${phpunit_out}"; then
    echo "phpunit: missing strict OK success line (${EXPECTED_OK_LINE})" >&2
    exit 1
fi
rm -f "${phpunit_out}"

echo 'system-test: all steps passed.'
