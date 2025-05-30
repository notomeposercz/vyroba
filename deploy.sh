#!/bin/zsh
# Automatický commit, push a nasazení na FTP pomocí SFTP na portu 222
# Ignoruje .git, .gitignore, .vscode, deploy.sh a další vývojové soubory

git add .
git commit -m "Automatický commit: změny v projektu"
git push

# Seznam ignorovaných položek
IGNORE_LIST=(.git .gitignore .vscode deploy.sh README.md)

# Vytvoření seznamu souborů k uploadu
UPLOAD_LIST=$(mktemp /tmp/upload_list.XXXXXX)
find . \( \
  -name ".git" -o \
  -name ".gitignore" -o \
  -name ".vscode" -o \
  -name "deploy.sh" -o \
  -name "README.md" \
\) -prune -false -o -type f -print | sed 's|^./||' > "$UPLOAD_LIST"

# Vytvoření dočasného sftp batch souboru
TMP_BATCH=$(mktemp /tmp/sftp_batch.XXXXXX)
echo "cd /_sub/vyroba/" > $TMP_BATCH
while IFS= read -r file; do
  dir=$(dirname "$file")
  if [ "$dir" != "." ]; then
    echo "-mkdir $dir" >> $TMP_BATCH
    echo "cd /_sub/vyroba/" >> $TMP_BATCH
  fi
  echo "put $file $file" >> $TMP_BATCH
done < "$UPLOAD_LIST"

# Spuštění sftp na portu 222 s heslem (nutno mít nainstalováno sshpass)
if command -v sshpass >/dev/null 2>&1; then
  sshpass -p '58xoSDKh' sftp -P 222 -oBatchMode=no -oStrictHostKeyChecking=no myrec.cz@62.109.154.144 < $TMP_BATCH
else
  echo "Pro automatické zadání hesla nainstalujte sshpass: brew install hudochenkov/sshpass/sshpass"
  echo "Nebo spusťte ručně: sftp -P 222 myrec.cz@62.109.154.144 a použijte příkazy v $TMP_BATCH"
fi

rm $TMP_BATCH
rm $UPLOAD_LIST
# (Automatický import SQL do databáze byl odstraněn na základě požadavku. Soubor vyroba_myrec_cz.sql slouží pouze jako ukázka struktury a nebude nikdy automaticky importován.)
