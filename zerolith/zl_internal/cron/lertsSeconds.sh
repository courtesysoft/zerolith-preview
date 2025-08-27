#replace your per-minute cron with this script to cheaply get per-second 'lerts checks
i=0
while [ $i -lt 12 ]; do # 12 five-second intervals in 1 minute ( 60 sec / 5 = 12 )
  php lerts.php
  sleep 5
  i=$(( i + 1 ))
done