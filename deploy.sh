#!/bin/zsh
# Automatický commit a nasazení na FTP s přímými parametry pro sftp-deploy

git add .
git commit -m "Automatický commit: změny v projektu"
sftp-deploy --host 62.109.154.144 --user myrec.cz --password 58xoSDKh --path /_sub/vyroba/
