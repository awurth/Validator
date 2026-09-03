default:
    @just --list

# Run all linters
lint: cs phpstan

# Fix coding standards, then run static analysis
fix: cs-fix phpstan

# Check coding standards
cs:
    vendor/bin/php-cs-fixer fix --dry-run --diff -vvv --show-progress=none

# Fix coding standards
cs-fix:
    vendor/bin/php-cs-fixer fix -vvv --show-progress=none

# Run static analysis
phpstan:
    vendor/bin/phpstan analyse --no-progress

# Run the test suite
test:
    vendor/bin/phpunit --colors=always

# Regenerate the PHPStan baseline
phpstan-baseline:
    vendor/bin/phpstan analyse --no-progress --generate-baseline=phpstan-baseline.neon
