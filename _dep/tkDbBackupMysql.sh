## ###############################################
#! /bin/bash
#
# Server side backup
# @source: http://dev.mensfeld.pl/2013/04/backup-mysql-dump-all-your-mysql-databases-in-separate-files/
#
#
## ###############################################


#TIMESTAMP=$(date +"%F")
TIMESTAMP=$(date +"%A")
PWD=`pwd`
TEMP_DIR="$PWD/dbBackup"
BACKUP_DIR="$TEMP_DIR"
MYSQL_DIR="$BACKUP_DIR/mysql"

MYSQL_USER="dev"
MYSQL_PASSWORD="dev007"
MYSQL_HOST="localhost"
MYSQLDUMP=/usr/bin/mysqldump
MYSQL=/usr/bin/mysql

mkdir -p "$BACKUP_DIR"
mkdir -p "$MYSQL_DIR"

databases=`$MYSQL --host=$MYSQL_HOST --user=$MYSQL_USER -p$MYSQL_PASSWORD -e "SHOW DATABASES;" | grep -Ev "(Database|information_schema|performance_schema)"`
for db in $databases; do
  $MYSQLDUMP --force --opt --host=$MYSQL_HOST --user=$MYSQL_USER -p$MYSQL_PASSWORD --databases $db | gzip > "$MYSQL_DIR/$db.gz"
done

cd $TEMP_DIR
tar zcf "$TIMESTAMP.bak.tgz" "$TIMESTAMP"
mv "$TIMESTAMP.bak.tgz" ../
cd "$PWD" 
rm -rf $TEMP_DIR


