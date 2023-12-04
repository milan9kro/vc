// Pripojnie do databazy : $conn
// a SSH pripojenie k serveru s n�zvom $ssh

// cesta pre z�lohy
$backupDirectory = 'public_html/zalohy/01';
// Pripojenie cez SSH, vytvor adres�r
$ssh->exec("mkdir -p $backupDirectory");
// Z�skaj d�tum a �as pre n�zov s�boru
$backupFileName = 'backup_' . date('Ymd_His') . '.sql';
// Cesta k z�loze na vzd�len�m serveru
$backupFilePath = $backupDirectory . '/' . $backupFileName;
// tabulky pre zalohovanie
$tables = array('v_ule', 'v_stanovistia');
// Pr�kaz pre vytvorenie zalohy
$command = "mysqldump -h $dbserver -u $dbuser -p$dbpass $dbname " . implode(' ', $tables) . " > $backupFilePath";
// spusti pr�kaz cez  SSH
$ssh->exec($command);

// Pripojenie cez SSH a zistenie obsahu adres�ra
$ssh->exec("ls -l $backupDirectory");

echo 'Z�loha bola �spe�ne vytvoren� a ulo�en� v ' . $backupFilePath;
// Uzavretie pripojenia cez SSH
$ssh->disconnect();