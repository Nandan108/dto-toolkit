#!/bin/sh
mkdir -p .git/hooks
ln -sf ../../.git-hooks/pre-commit .git/hooks/pre-commit
ln -sf ../../.git-hooks/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
echo "✓ Git pre-commit and pre-push hooks installed."
