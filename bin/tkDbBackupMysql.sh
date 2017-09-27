## ###############################################
#! /bin/bash
#
# Server side backup
# @source: http://dev.mensfeld.pl/2013/04/backup-mysql-dump-all-your-mysql-databases-in-separate-files/
#
#
## ###############################################


TIMESTAMP=$(date +"%F")
#TIMESTAMP=$(date +"%A")
PWD=`pwd`
TEMP_DIR="$PWD/__tmp"
BACKUP_DIR="$TEMP_DIR/$TIMESTAMP"
MYSQL_DIR="$BACKUP_DIR/mysql"

mkdir -p "$BACKUP_DIR"


### TODO: we need to make these arguments
MYSQL_USER="dev"
MYSQL_PASSWORD="dev007"
MYSQLDUMP=/usr/bin/mysqldump
MYSQL=/usr/bin/mysql

mkdir -p "$MYSQL_DIR"
databases=`$MYSQL --user=$MYSQL_USER -p$MYSQL_PASSWORD -e "SHOW DATABASES;" | grep -Ev "(Database|information_schema|performance_schema)"`
for db in $databases; do
  $MYSQLDUMP --force --opt --user=$MYSQL_USER -p$MYSQL_PASSWORD --databases $db | gzip > "$MYSQL_DIR/$db.gz"
done

#TODO: count the sql files and exit if none exist in the MYSQL_DIR

### TODO: we need to make this options with arguments
# Backup etc files
#cp -R "/etc" "$BACKUP_DIR"

cd $TEMP_DIR
tar zcf "$TIMESTAMP.bak.tgz" "$TIMESTAMP"
mv "$TIMESTAMP.bak.tgz" ../
cd "$PWD" 
rm -rf $TEMP_DIR


