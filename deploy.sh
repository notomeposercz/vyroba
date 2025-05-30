#!/bin/zsh
# Automatický commit a nasazení na FTP

git add .
git commit -m "Automatický commit: změny v projektu"
sftp-deploy --config .vscode/sftp.json
