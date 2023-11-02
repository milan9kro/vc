<?php

######################################################################
# phpRS Configuration 1.3.4
######################################################################

// Copyright (c) 2001-2011 by Jiri Lukas (jirilukas@supersvet.cz) & phpRS community
// http://www.supersvet.cz/phprs/
// This program is free software. - Toto je bezplatny a svobodny software.

if (!defined("IN_CODE")):
	header("HTTP/1.1 403 Forbidden");
	die("Nepovoleny pristup! / Hacking attempt!");
endif;

// nastavenie kodovania pre mb_string
mb_internal_encoding("UTF-8");

// nasteveni chybovych hlaseni
error_reporting(-1); // zobrazi vsetky chybove spravy php - doporucene pri vyvoji a testovani 
//error_reporting(0); // vypina vsetky chybove spravy php - doporucene pri ostrom nasadeni na webe

// prirazeni GET a POST vstupu do pole $GLOBALS a odstraneni escapovani pri magic_quotes_gpc = on
include "admin/aext_prom.php";

// inic. konfiguracniho pole
$GLOBALS['rsconfig']=array();

// -- [SALT]----------------------------------------------------------
// POZOR! Tuto hodnotu je mozne nastavit JEN PRI PRVNI INSTALACII phpRS, 
// pri zmene teto hodnoty se znefunkcni vsechna hesla v systemu !!!
// doporucena minimalna dlzka tejto premenej je 20 znakov
define("PASSWORD_SALT", "XXX");

//--[db server]-------------------------------------------------------
// typ pouzite databaze; dostupne moznosti: mysql, mysqli
$dbtyp="mysqli";
// adresa db serveru
$dbserver="localhost";
//  uzivatelske informace (user information)
$dbuser="root";
$dbpass="";
// jmeno databaze
$dbname="vcely";
// rozlisujici db predpona phpRS
$rspredpona="rs_";

//--[http server]-----------------------------------------------------
// jmeno WWW serveru
$wwwname="Název webu";
// popis WWW serveru - pouzije se pro HTML META description a RSS description
$wwwdescription="Popis weboveho projektu";
// zakladni URL adresa WWW serveru - napr.: http://www.supersvet.cz/ - adresu nutno ukoncit lomitkem
$baseadr="localhost/vcely";
// e-mailove adresy
$redakceadr="milan@krokavec.sk  ";
$infoadr="milan@krokavec.sk";

//--[podpora SSL pro administraci]------------------------------------
// nastaveni na true bude administracni rozhrani smerovano na https protokol
// doporucene nastaveni je true, pouze v pripade ze server nepodporuje SSL, nastavte na false 
$GLOBALS['rsconfig']['ssl'] = false;

//--[ankety]----------------------------------------------------------
// typ zakonceni hlasovani v pripade hlasovani ze systemoveho bloku: a] index = presmerovani na hl.stranku, b] vysledek = zobrazeni vysledku
$GLOBALS['rsconfig']['anketa_cil_str']="index";
// maximalni povoleny pocet hlasovani z jedne IP adresy za stanoveny casovy limit
$GLOBALS['rsconfig']['anketa_max_pocet_opak']=6;
// delka omezujiciho casoveho limitu; jde o dobu, po kterou lze provest pouze urcity pocet hlasovani z jedne konkretni IP adresy (uvedeno v sekundach)
$GLOBALS['rsconfig']['anketa_delka_omezeni']=3600;

//--[autorizace]------------------------------------------------------
// delka platnosti jednoho prihlaseni (uvedeno v sekundach)
$GLOBALS['rsconfig']['platnost_auth']=7200;
// maximalni pocet povolenych chyb v ramci jednoho prihlasovani; za chybu se pocita spatne zadane heslo
$GLOBALS['rsconfig']['auth_max_pocet_chyb']=3;

//--[interni galerie obrazku]-----------------------------------------
// defaultni sirka nahledu - jedna se pouze o orientacni sirku, ktera se automaticky prizpusobi konkretnimu obrazku
$GLOBALS['rsconfig']['img_nahled_sirka']=120;
// defaultni vyska nahledu - jedna se pouze o orientacni vysku, ktera se automaticky prizpusobi konkretnimu obrazku
$GLOBALS['rsconfig']['img_nahled_vyska']=120;
// galerie - adresar pro upload obrazku; stejne jako u sekce [http server] je i zde nutne relativni adresarovou cestu ukoncit lomitkem
$GLOBALS['rsconfig']['img_adresar']="storage/";

//--[cookies]---------------------------------------------------------
// tato volba urcuje odesilaci mod pro cookies: a] 0 = zakladni cookies bez specifikace domeny, b] 1 = rozsireny mod, ve kterem je pripojeno omezeni na konkretni domenovou adresu (nemusi fungovat na localhostu)
$GLOBALS['rsconfig']['cookies_s_domenou']=0;

//--[komentare]-------------------------------------------------------
// maximalni delka jednoho celeho komentare; delsi komentare bude automaticky zkraceny
$GLOBALS['rsconfig']['max_delka_komentare']=1000;
// maximalni povolena delka jednoho slova; vetsi slova budou automaticky rozdelena
$GLOBALS['rsconfig']['max_delka_slova']=50;

//--[clanky]----------------------------------------------------------
// maximalni povoleny pocet zaregistrovanych precteni clanku pripadajicich na jednu IP adresu za stanoveny casovy limit
$GLOBALS['rsconfig']['cla_max_pocet_opak']=6;
// delka omezujiciho casoveho limitu; jde o dobu, po kterou lze provest pouze urcity pocet zaregistrovanych precteni z jedne konkretni IP adresy (uvedeno v sekundach)
$GLOBALS['rsconfig']['cla_delka_omezeni']=3600;

//--[kodovani stranek]------------------------------------------------
// nastaveni kodovani generovanych HTML stranek; ukazka moznych alternativ: windows-1250, iso-8859-2, UTF-8
$GLOBALS['rsconfig']['kodovani']="utf-8";

//--[sprava souboru]--------------------------------------------------
// download sekce - adresar pro upload souboru; stejne jako u sekce [http server] je i zde nutne relativni adresarovou cestu ukoncit lomitkem
$GLOBALS['rsconfig']['file_adresar']="storage/";

//--[db knihovna, spojeni s db]---------------------------------------
// vlozeni vhodne konverzni databaze knihovny
switch ($dbtyp):
  case 'mysql': include_once("db/phprs_sql_to_mysql.php"); break;
  case 'mysqli': include_once("db/phprs_sql_to_mysqli.php"); break;
  default: die('System nemuze identifikovat vasi databazi! / Could not identify your database!');
endswitch;
// otevreni spojeni s db
$dbspojeni=phprs_sql_dbcon();

$GLOBALS["dbspojeni"]=&$dbspojeni;
$GLOBALS["rspredpona"]=&$rspredpona;

//--[layout fce]------------------------------------------------------
// nacteni zakladni konfigurace layoutu z db - nastaveni globalni sablony
$dotazhod=phprs_sql_query("select g.ident_sab,g.soubor_sab,g.adr_sab from ".$rspredpona."config as c,".$rspredpona."global_sab as g where c.promenna='global_sab' and c.hodnota=g.ids",$dbspojeni);
if ($dotazhod===false):
  die('System nemuze nalezt potrebne databazove tabulky! / Could not find database tables!');
else:
  if (phprs_sql_num_rows($dotazhod)==1):
    // globalni sablona je nastavena
    list($rs_main_sablona,$adrlayoutu,$adrobrlayoutu)=phprs_sql_fetch_row($dotazhod); // cesta k layout souboru; cesta do layout adresare; identifikace pozadovane glob. sablony
  else:
    // globalni sablona neni nastavena
    if (!isset($rs_administrace)): // test na admin pristup
      die('System nemuze identifkovat vybranou globalni sablonu! / Could not identify to choose global template!');
    endif;
  endif;
endif;

//--[kodovani pro komunikaci se serverem]-----------------------------
switch(strtolower($GLOBALS['rsconfig']['kodovani'])):
  // nastaveni probiha automaticky; v pripade nestandardniho nastaveni MySQL serveru nutno pripadne SQL prikazy upravit
  case 'windows-1250': phprs_sql_query("SET NAMES 'cp1250'",$dbspojeni) or die('System nemuze nastavit kodovani cp1250! / Could not set names cp1250!'); break;
  case 'iso-8859-2': phprs_sql_query("SET NAMES 'latin2'",$dbspojeni) or die('System nemuze nastavit kodovani latin2! / Could not set names latin2!'); break;
  case 'utf-8': phprs_sql_query("SET NAMES 'utf8'",$dbspojeni) or die('System nemuze nastavit kodovani utf8! / Could not set names utf8!'); break;
endswitch;

?>
