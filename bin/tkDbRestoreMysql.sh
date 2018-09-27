## ###############################################
#! /bin/bash

#
# Server side restore
# @source: http://dev.mensfeld.pl/2013/04/backup-mysql-dump-all-your-mysql-databases-in-separate-files/
#
#
## ###############################################


PWD=`pwd`
MYSQL_USER="root"
MYSQL=/usr/bin/mysql

echo -n "Enter root DB pass: "
# The -n option to echo suppresses newline.
read dbpass
# Note no '$' in front of var1, since it is being set.
#echo "var1 = $dbpass"


for file in ./*.gz
do

    if [ -e "$file" ]; then
    # Check whether file exists.
        if [ "$file" = "./mysql.gz" ] || [ "$file" = "./phpmyadmin.gz" ]; then
            continue
        fi
        echo "Importing: $file"
        gunzip < "$file" | mysql -u root -p"$dbpass"
    fi

done

exit 0