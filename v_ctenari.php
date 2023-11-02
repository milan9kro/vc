<?php

include_once("config.php");
include_once("myweb.php");
if (include_once("config.php")) {
    // Pokračovat v kódu
    echo "Nacitanie suboru OK config.php";
} else {
    die("Nedá sa načítať config.php");
}

$conn = mysqli_connect($dbserver, $dbuser, $dbpass, $dbname);
mysqli_set_charset($conn, "utf8");

// Kontrola pripojenia
if (!$conn) {
    die("Pripojenie zlyhalo: " . mysqli_connect_error());
}
echo "Pripojenie OK";
$ule = 20;
$ramiky = 5;

$ctenariSQL = "INSERT INTO `v_ctenari` (`id`, `pocet_ulov`, `ramiky`) VALUES ($akt_id_ctenare, $ule, $ramiky)";

if (mysqli_query($conn, $ctenariSQL)) {
    echo "Záznam bbol vložený.";
} else {
    echo "Chyba pri vkladaní záznamu: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
