#!/bin/bash
# GitHub Repository Setup Script
# Run this after creating your GitHub repository

echo "Setting up GitHub repository connection..."

# Prompt for GitHub details
read -p "Enter your GitHub username: " github_username
read -p "Enter your repository name: " repo_name

# Add remote origin
git remote add origin "https://github.com/$github_username/$repo_name.git"

# Rename branch to main
git branch -M main

# Push to GitHub
git push -u origin main

echo "Repository connected successfully!"
echo "You can now access your code at: https://github.com/$github_username/$repo_name"
