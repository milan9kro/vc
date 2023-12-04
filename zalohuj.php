// Pripojnie do databazy : $conn
// a SSH pripojenie k serveru s názvom $ssh

// cesta pre zálohy
$backupDirectory = 'public_html/zalohy/01';
// Pripojenie cez SSH, vytvor adresár
$ssh->exec("mkdir -p $backupDirectory");
// Získaj dátum a èas pre názov súboru
$backupFileName = 'backup_' . date('Ymd_His') . '.sql';
// Cesta k záloze na vzdáleném serveru
$backupFilePath = $backupDirectory . '/' . $backupFileName;
// tabulky pre zalohovanie
$tables = array('v_ule', 'v_stanovistia');
// Príkaz pre vytvorenie zalohy
$command = "mysqldump -h $dbserver -u $dbuser -p$dbpass $dbname " . implode(' ', $tables) . " > $backupFilePath";
// spusti príkaz cez  SSH
$ssh->exec($command);

// Pripojenie cez SSH a zistenie obsahu adresára
$ssh->exec("ls -l $backupDirectory");

echo 'Záloha bola úspešne vytvorená a uložená v ' . $backupFilePath;
// Uzavretie pripojenia cez SSH
$ssh->disconnect();