default:
    @just --list

# Run all linters
lint: cs rector phpstan

# Apply every automated fix, then run static analysis
fix: rector-fix cs-fix phpstan

# Check coding standards
cs:
    vendor/bin/php-cs-fixer fix --dry-run --diff -vvv --show-progress=none

# Fix coding standards
cs-fix:
    vendor/bin/php-cs-fixer fix -vvv --show-progress=none

# Check for Rector refactorings
rector:
    vendor/bin/rector process --dry-run

# Apply Rector refactorings
rector-fix:
    vendor/bin/rector process

# Run static analysis
phpstan:
    vendor/bin/phpstan analyse --no-progress

# Run the test suite
test:
    vendor/bin/phpunit --colors=always

# Regenerate the PHPStan baseline
phpstan-baseline:
    vendor/bin/phpstan analyse --no-progress --generate-baseline=phpstan-baseline.neon
