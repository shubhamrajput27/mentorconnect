#!/bin/bash
# MentorConnect Code Optimization Script

echo "🚀 Starting MentorConnect Code Optimization..."

# Remove old optimization files and duplicate documentation
rm -f FRONTEND_100_OPTIMIZATION_REPORT.md
rm -f LIGHTHOUSE_PERFORMANCE_REPORT.md
rm -f OPTIMIZATION_IMPLEMENTATION.md
rm -f OPTIMIZATION_REPORT.md
rm -f optimization-report.html
rm -f frontend-analysis-report.html
rm -f debug-theme-button.html
rm -f minify-assets.php
rm -f nav-button-fix.css
rm -f performance-analysis.ps1
rm -f run-lighthouse.bat
rm -f run-lighthouse.ps1

# Remove test files that might still exist
rm -f *test*.php
rm -f *debug*.php
rm -f *temp*.php

# Remove duplicate optimization files in root
find . -maxdepth 1 -name "*optimization*" -type f -delete
find . -maxdepth 1 -name "*report*" -type f -delete
find . -maxdepth 1 -name "*analysis*" -type f -delete

echo "✅ Cleanup completed!"
