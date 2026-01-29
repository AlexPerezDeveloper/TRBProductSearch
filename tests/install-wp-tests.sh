#!/bin/bash
# Install WordPress Test Suite
#
# This script downloads and sets up the WordPress test suite
# for running real integration tests.
#
# Usage: bash tests/install-wp-tests.sh

set -e

echo "=============================================="
echo "  WordPress Test Suite Installer"
echo "=============================================="
echo ""

# Default values
WP_VERSION=${WP_VERSION:-"latest"}
TESTS_DIR=${WP_TESTS_DIR:-"$PWD/tmp/wordpress-tests-lib"}

# Detect the path to the plugin directory
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
echo "Plugin directory: $PLUGIN_DIR"

# Check if we're in the plugin root
if [[ ! -f "$PLUGIN_DIR/trb-product-search.php" ]]; then
    echo "Error: trb-product-search.php not found in $PLUGIN_DIR"
    echo "Please run this script from the plugin directory or provide the correct path."
    exit 1
fi

# Create temporary directory
mkdir -p "$TESTS_DIR"

echo "Test suite directory: $TESTS_DIR"
echo ""

# Download WordPress test suite
echo "Downloading WordPress test suite..."
SVN_URL="https://develop.svn.wordpress.org/trunk/"

if [[ "$WP_VERSION" == "latest" ]]; then
    svn co "$SVN_URL" "$TESTS_DIR" --quiet
else
    svn co "$SVN_URL" "$TESTS_DIR" -r "$WP_VERSION" --quiet
fi

echo "✓ WordPress test suite downloaded"
echo ""

# Create wp-tests-config.php
echo "Creating wp-tests-config.php..."
cat > "$TESTS_DIR/wp-tests-config.php" <<'EOF'
<?php
/**
 * Configuration for WordPress test suite
 */

// Database settings
define('DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'wordpress_test');
define('DB_USER', getenv('WP_TESTS_DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('WP_TESTS_DB_PASS') ?: '');
define('DB_HOST', getenv('WP_TESTS_DB_HOST') ?: 'localhost');

// Table prefix
define('WP_TESTS_TABLE_PREFIX', 'wptests_');

// Test site URL
define('WP_TESTS_DOMAIN', 'example.org');

// Disable email
define('WP_PHP_BINARY', 'php');

// Force SSL
define('WP_TESTS_FORCE_KNOWN_SSL', true);

// Plugin path (relative to ABSPATH)
define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');

EOF

echo "✓ wp-tests-config.php created"
echo ""

# Create test database if it doesn't exist
echo "Checking database..."
DB_NAME=${WP_TESTS_DB_NAME:-wordpress_test}

MYSQL_CMD="mysql -u${WP_TESTS_DB_USER:-root} ${WP_TESTS_DB_PASS:+-p$WP_TESTS_DB_PASS} -h${WP_TESTS_DB_HOST:-localhost}"

# Try to create database
if $MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`" 2>/dev/null; then
    echo "✓ Database '$DB_NAME' created or already exists"
else
    echo "ℹ Could not create database. Please create manually:"
    echo "  CREATE DATABASE $DB_NAME;"
    echo ""
fi

# Run the install script
echo ""
echo "Running WordPress test suite installation..."
cd "$TESTS_DIR"

if [[ -f "install.sh" ]]; then
    bash install.sh
else
    # Older version, run manually
    echo "Running manual installation..."
    cp wp-tests-config.php wp-config.php
fi

cd "$PLUGIN_DIR"

echo ""
echo "=============================================="
echo "  Installation Complete!"
echo "=============================================="
echo ""
echo "Environment variables to set:"
echo ""
echo "  export WP_TESTS_DIR=$TESTS_DIR"
echo "  export WP_TESTS_DB_NAME=$DB_NAME"
echo ""
echo "Run tests:"
echo ""
echo "  # Run all tests"
echo "  WP_TESTS_DIR=$TESTS_DIR composer test"
echo ""
echo "  # Run only real integration tests"
echo "  WP_TESTS_DIR=$TESTS_DIR phpunit --bootstrap tests/bootstrap-real.php tests/integration-real/"
echo ""
echo "Or add to your ~/.bashrc or ~/.zshrc:"
echo ""
echo "  export WP_TESTS_DIR=$TESTS_DIR"
echo "  export WP_TESTS_DB_NAME=$DB_NAME"
echo ""
