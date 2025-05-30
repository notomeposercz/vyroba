#!/bin/zsh
# Automatický commit a nasazení na FTP pomocí standardního sftp

git add .
git commit -m "Automatický commit: změny v projektu"

# Vytvoření dočasného sftp batch souboru
TMP_BATCH=$(mktemp /tmp/sftp_batch.XXXXXX)
cat > $TMP_BATCH <<EOF
lcd $(pwd)
cd /_sub/vyroba/
put -r *
EOF

# Spuštění sftp s heslem (nutno mít nainstalováno sshpass)
if command -v sshpass >/dev/null 2>&1; then
  sshpass -p '58xoSDKh' sftp -oBatchMode=no -oStrictHostKeyChecking=no myrec.cz@62.109.154.144 < $TMP_BATCH
else
  echo "Pro automatické zadání hesla nainstalujte sshpass: brew install hudochenkov/sshpass/sshpass"
  echo "Nebo spusťte ručně: sftp myrec.cz@62.109.154.144 a použijte příkazy v $TMP_BATCH"
fi

rm $TMP_BATCH
