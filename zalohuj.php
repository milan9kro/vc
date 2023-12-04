<?php

define('IN_CODE', true);
include_once("config.php");
include_once("myweb.php");

if ($GLOBALS["prmyctenar"]->ctenarstav == 0) {
    $GLOBALS['vzhledwebu']->Generuj();
    ObrTabulka();
    header("Location:" . $GLOBALS['baseurl'] . "readers.php");
    die;
}

$GLOBALS['vzhledwebu']->Generuj();
ObrTabulka();

$conn = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
mysqli_set_charset($conn, "utf8");
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

require 'vendor/autoload.php';

use phpseclib3\Net\SSH2;

$hostname = 'xxxxx';
$username = 'u9695';
$password = 'xxxxxx';
$port = 318;

$ssh = new SSH2($hostname, $port);

if (!$ssh->login($username, $password)) {
    exit('Nepodarilo sa pripojiť cez SSH. Chyba: ' . $ssh->getLastError());
}



// Pripojnie do databazy : $conn
// a SSH pripojenie k serveru s názvom $ssh

// cesta pre zálohy
$backupDirectory = 'public_html/zalohy/01';
// Pripojenie cez SSH, vytvor adresár
$ssh->exec("mkdir -p $backupDirectory");
// Získaj dátum a čas pre názov súboru
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

    KonecObrTabulka();
    $GLOBALS['vzhledwebu']->Generuj();

    // Ukončenie pripojenia k databáze
    mysqli_close($conn);
    ?>

