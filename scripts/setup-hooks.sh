#!/bin/sh
mkdir -p .git/hooks
ln -sf ../../.git-hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
echo "✓ Git pre-commit hook installed."