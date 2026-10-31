#!/usr/bin/env bash
# Covid-flow — Selenium 4 Automated Browser Test Runner
# Software Testing and Quality Assurance (STQA)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_ROOT"

# Use virtualenv python and pytest
PYTHON="${PROJECT_ROOT}/.venv/bin/python"
PYTEST="${PROJECT_ROOT}/.venv/bin/pytest"

if [ ! -f "$PYTEST" ]; then
    echo "[!] Virtual environment not found at .venv. Creating one..."
    python3 -m venv .venv
    .venv/bin/pip install -r requirements-dev.txt
fi

mkdir -p reports/screenshots

echo "=========================================================="
echo "  COVID-FLOW — STQA SELENIUM 4 AUTOMATED TEST SUITE"
echo "=========================================================="
echo "Target: Real Browser Automation using Chrome Headless"
echo "Reporting: HTML Report -> reports/selenium-report.html"
echo "=========================================================="

"$PYTEST" tests/selenium -v \
    --html=reports/selenium-report.html \
    --self-contained-html

echo "=========================================================="
echo "  TEST RUN COMPLETED SUCCESSFULLY!"
echo "  HTML Report: ${PROJECT_ROOT}/reports/selenium-report.html"
echo "=========================================================="
