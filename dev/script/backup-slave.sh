date=`date +%Y-%m-%d`
cd /home/backup/

for db in data dev today farm;
do
    mysqldump -u backup -ppOkajqU45 --default-character-set=utf8mb4 $db > $db.sql
    tar -czf $date-$db.sql.tgz $db.sql
    rm -f $db.sql
    scp $date-$db.sql.tgz www-data@ouvretaferme:/var/www/backup/
    rm $date-$db.sql.tgz
done;