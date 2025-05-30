#!/bin/zsh
# Automatický commit a nasazení na FTP s proměnnými prostředí

git add .
git commit -m "Automatický commit: změny v projektu"

export DEPLOY_HOST=62.109.154.144
export DEPLOY_USER=myrec.cz
export DEPLOY_PASSWORD=58xoSDKh
export DEPLOY_PATH=/_sub/vyroba/

sftp-deploy
