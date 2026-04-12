#!/bin/bash

# Navigate to plugin directory
cd /root/jeveseat

# Initialize Git
git init
git add .
git commit -m "Initial commit for jEveSeAT SeAT plugin"

# Create GitHub repository and push
# Assumes gh CLI is authenticated and configured
gh repo create Apokavkos/jEveSeAT --public --source=. --remote=origin --push

echo "✅ jEveSeAT repository initialized and pushed to git@github.com:Apokavkos/jEveSeAT.git"
