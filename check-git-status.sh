#!/bin/bash
# Safe GitHub Connection Test Script
# Run this to verify GitHub connection without affecting production

echo "🔍 Checking current Git status..."
git status

echo ""
echo "🔍 Checking remote repositories..."
git remote -v

echo ""
echo "🔍 Checking if we're connected to GitHub..."
if git remote get-url origin 2>/dev/null; then
    echo "✅ Already connected to GitHub repository"
    echo "Remote URL: $(git remote get-url origin)"
else
    echo "❌ Not connected to GitHub yet"
    echo ""
    echo "To connect to your GitHub repository (Audit-Restaurant):"
    echo "git remote add origin https://github.com/ismou1337/Audit-Restaurant.git"
    echo "git branch -M main"
    echo "git push -u origin main"
fi

echo ""
echo "🔍 Checking what files would be committed..."
git diff --cached --name-only

echo ""
echo "🔍 Checking what files are not tracked..."
git ls-files --others --exclude-standard

echo ""
echo "✅ Safe to proceed? This script only checks, doesn't modify anything."
