<?php

######################################################################
# phpRS Readers 1.6.1
######################################################################

// Copyright (c) 2001-2011 by Jiri Lukas (jirilukas@supersvet.cz) & phpRS community
// http://www.supersvet.cz/phprs/
// This program is free software. - Toto je bezplatny a svobodny software.

// vyuzivane tabulky: rs_ctenari

/*
  Pro funkcnost prace se ctenarskymi profily je potrebna trida CMyReader(), ktera se nacita prostrednictvim souboru "myweb.php".
  Trida CMyReader() zabezpecuje komletni proces generovani ctenarskych identifikacnich cookies a ucastni se procesu tvorby ctenarskeho profilu.
*/

// nepovoli prihlaseni pomoci GET
if (isset($_GET['rjmeno'])) {
  die('Nepovoleny pristup! / Hacking attempt!');
}

define('IN_CODE',true); // inic. ochranne konstanty

include_once("config.php");
include_once("myweb.php");


// captcha overovacie funkcie
function NactiKontrolniRetezec()
{
$vysl['captcha_id']='';
$vysl['captcha_otazka']='';

// dotaz na vypis vsech dostupnych kontrolnich otazek
// tento zpusob ziskani kontrolni otazky nepocitani s prilis velkym mnoztvim otazek v databazi; v pripade vetsiho mnozstvi by se musel upravit dotazovaci mechanizmus
$dotaz="select * from ".$GLOBALS["rspredpona"]."captcha_test_otazky where zobrazit=1 order by idc";
$dotazpol=phprs_sql_query($dotaz,$GLOBALS["dbspojeni"]);
$pocetpol=phprs_sql_num_rows($dotazpol);

if ($pocetpol>0):
  $akt_vybrana_otazka=rand(0,($pocetpol-1));
  if (phprs_sql_data_seek($dotazpol,$akt_vybrana_otazka)):
    // nacteni kontrolni otazky
    $pole_data=phprs_sql_fetch_assoc($dotazpol);
    // zaplneni vysledkoveho pole
    $vysl['captcha_id']=$pole_data['identifikator'];
    $vysl['captcha_otazka']=$pole_data['otazka'];
  endif;
endif;

return $vysl;
}

function OverKontrolniRetezec($captcha_id = '', $captcha_odpoved = '')
{
// uprava odpovedi
$captcha_odpoved=strtolower(trim($captcha_odpoved));

// bezpecnostni korekce
$captcha_id=phprs_sql_escape_string($captcha_id);
$captcha_odpoved=phprs_sql_escape_string($captcha_odpoved);

// kontrolni dotaz
$dotaz="select idc from ".$GLOBALS["rspredpona"]."captcha_test_otazky where identifikator='".$captcha_id."' and odpoved='".$captcha_odpoved."' and zobrazit=1";
$dotazpol=phprs_sql_query($dotaz,$GLOBALS["dbspojeni"]);
if ($dotazpol!==false&&phprs_sql_num_rows($dotazpol)==1):
  return 1; // OK; kontrolni retezec plati
else:
  return 0; // chyba
endif;
}


// overeni existence potrebnych promennych
if (!isset($GLOBALS['akce'])): $GLOBALS['akce']='logmenu'; endif;
// test na automaticke prihlaseni
if ($GLOBALS['akce']=='logmenu'):
  if ($prmyctenar->ctenarstav==1):
    $GLOBALS['akce']='autologin';
  endif;
endif;
// inic. pomocne textove promenne
$GLOBALS['cte_modul_text']='';

function OptJazykSlovniky($hledam = '')
{
$vysl='';

$adr = dir("lang");
while($prehled_jazyku=$adr->read()):
  if (mb_substr($prehled_jazyku,0,3)=='sl_'):
    $jazyk_zkratka=mb_substr($prehled_jazyku,3,2);
    $vysl.="<option value=\"".$jazyk_zkratka."\"";
    if ($jazyk_zkratka==$hledam): $vysl.=" selected"; endif;
    $vysl.=">".$jazyk_zkratka."</option>";
  endif;
endwhile;
$adr->close();

return $vysl;
}

// zakladni login menu
function ZobrazLogin()
{
echo "<form action=\"readers.php\" method=\"post\">
<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
<tr class=\"z\"><td>".RS_CT_JMENO.":</td><td><input type=\"text\" size=\"15\" name=\"rjmeno\" class=\"textpole\" /></td></tr>
<tr class=\"z\"><td>".RS_CT_HESLO.":</td><td><input type=\"password\" size=\"15\" name=\"rheslo\" class=\"textpole\" /></td></tr>
</table>
<p align=\"center\"><input type=\"submit\" value=\"  ".RS_ODESLAT."  \" class=\"tl\" /></p>
<p align=\"center\" class=\"z\">
<a href=\"readers.php?akce=new\">".RS_CT_NAVIG_REG_NOVY."</a> -
<a href=\"readers.php?akce=del\">".RS_CT_NAVIG_ZRUSIT."</a> -
<a href=\"readers.php?akce=newpw\">".RS_CT_NAVIG_ZAPOMNEL."</a>
</p>
<input type=\"hidden\" name=\"akce\" value=\"login\" />
</form>
<br>\n";
}

// registrace noveho ctenare
function NovyCt()
{
// inic. cisteho ctenarskeho profilu
$GLOBALS["prmyctenar"]->GenerujCistyCtenar();
// inic. formluare
$GLOBALS["typakce"]='insert'; // definice modu
$GLOBALS["typtlacitka"]=RS_CT_TL_ZAREG; // text tlacitko: Zaregistrovat
// volani formulare
$GLOBALS['akce']='formular'; // zmena akce
}

// uprava nastaveni ctenare
function EditujCt()
{
// bezpecnostni kontrola - provadi se az ve funkci NactiCtenare(...)
if ($GLOBALS["prmyctenar"]->NactiCtenare($GLOBALS["rjmeno"],$GLOBALS["rheslo"])==1): // test na autenticitu uzivatele
  // inic. formulare
  $GLOBALS["typakce"]='save'; // definice modu
  $GLOBALS["typtlacitka"]=RS_CT_TL_ULOZ; // text tlatictko: Uložit změny
  // volani formulare
  $GLOBALS['akce']='formular'; // zmena akce
else:
  // CHYBA: Spatne uzivatelke jmeno nebo heslo! - Nove prihlaseni
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\"><b>".RS_CT_ERR_1." - <a href=\"readers.php\">".RS_CT_NOVY_LOGIN."</a></b></p>\n";
  $GLOBALS['akce']='showtxt'; // zmena akce
endif;
}

// uprava nastaveni ctenare - automaticky rezim prihlaseni skrze session
function EditujAutoCt()
{
if ($GLOBALS["prmyctenar"]->ctenarstav==1):
  // inic. formulare
  $GLOBALS["typakce"]='save'; // // definice modu
  $GLOBALS["typtlacitka"]=RS_CT_TL_ULOZ; // text tlatictko: Uložit změny
  // volani formulare
  $GLOBALS['akce']='formular'; // zmena akce
else:
  // CHYBA: System nemuze identifikovat ctenare! - Nove prihlaseni
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\"><b>".RS_CT_ERR_2." - <a href=\"readers.php\">".RS_CT_NOVY_LOGIN."</a></b></p>\n";
  $GLOBALS['akce']='showtxt'; // zmena akce
endif;
}

function FormCtenari()
{
// test na existenci promennych
if (!isset($GLOBALS["typakce"])): $GLOBALS["typakce"]=''; endif;
if (!isset($GLOBALS["typtlacitka"])): $GLOBALS["typtlacitka"]=''; endif;

echo "<form action=\"readers.php\" method=\"post\">
<input type=\"hidden\" name=\"akce\" value=\"".$GLOBALS["typakce"]."\" />\n";
if ($GLOBALS["typakce"]=='save'): // jen save mod
  echo "<input type=\"hidden\" name=\"ridc\" value=\"".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('id'))."\" />\n";
  echo "<input type=\"hidden\" name=\"rjmeno\" value=\"".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('username'))."\" />\n";
  echo "<input type=\"hidden\" name=\"roldpass\" value=\"".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('heslo'))."\" />\n"; // TODO: neposielat heslo?
endif;
if ($GLOBALS['cte_modul_text']!=''): // test na pritomnost komentare
  echo $GLOBALS['cte_modul_text'];
endif;
echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
if ($GLOBALS["typakce"]=='save'): // jen save mod
  echo "<tr class=\"z\"><td>** ".RS_CT_JMENO.":</td><td>".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('username')). "</td></tr>\n";
  echo "<tr class=\"z\"><td>** ".RS_CT_HESLO.":</td><td><input type=\"password\" size=\"15\" name=\"rheslo\" value=\"\" class=\"textpole\" /><br>".RS_CT_INFO_HESLO."</td></tr>\n";
  echo "<tr class=\"z\"><td>** ".RS_CT_HESLO_KONTRLA.":</td><td><input type=\"password\" size=\"15\" name=\"rheslo2\" value=\"\" class=\"textpole\" /><br>".RS_CT_INFO_HESLO."</td></tr>\n";
else: // ostatni mody
  echo "<tr class=\"z\"><td>** ".RS_CT_JMENO.":</td><td><input type=\"text\" size=\"15\" name=\"rjmeno\" value=\"".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('username'),ENT_QUOTES)."\" class=\"textpole\" /></td></tr>\n";
  echo "<tr class=\"z\"><td>** ".RS_CT_HESLO.":</td><td><input type=\"password\" size=\"15\" name=\"rheslo\" value=\"\" class=\"textpole\" /></td></tr>\n";
  echo "<tr class=\"z\"><td>** ".RS_CT_HESLO_KONTRLA.":</td><td><input type=\"password\" size=\"15\" name=\"rheslo2\" value=\"\" class=\"textpole\" /></td></tr>\n";
endif;
echo "<tr class=\"z\"><td>".RS_CT_CELE_JMENO.":</td><td><input type=\"text\" size=\"40\" name=\"rcelejmeno\" value=\"".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('jmeno'))."\" class=\"textpole\" /></td></tr>
<tr class=\"z\"><td>".RS_CT_EMAIL.":</td><td><input type=\"text\" size=\"40\" name=\"rmail\" value=\"".htmlspecialchars($GLOBALS["prmyctenar"]->Ukaz('email'))."\" class=\"textpole\" /></td></tr>
<tr class=\"z\"><td>".RS_CT_JAZYK.":</td><td><select name=\"rjazyk\" size=\"1\">".OptJazykSlovniky($GLOBALS["prmyctenar"]->Ukaz('jazyk'))."</select></td></tr>
</table>

<p align=\"center\" class=\"z\">".RS_CT_NOVINKY."<br>\n";

if ($GLOBALS["prmyctenar"]->Ukaz('info')==1):
  echo "<input type=\"radio\" name=\"rinfo\" value=\"1\" checked />".RS_ANO." &nbsp;&nbsp; <input type=\"radio\" name=\"rinfo\" value=\"0\" />".RS_NE."\n";
else:
  echo "<input type=\"radio\" name=\"rinfo\" value=\"1\" />".RS_ANO." &nbsp;&nbsp; <input type=\"radio\" name=\"rinfo\" value=\"0\" checked />".RS_NE."\n";
endif;
//upravene
echo "</p><p align=\"center\" class=\"z\">** ".RS_CT_GDPR."&nbsp;&nbsp;";

if ($GLOBALS["prmyctenar"]->Ukaz('zobrazitdata')==1):
  //echo "<input type=\"radio\" name=\"rzobrazmenu\" value=\"1\" checked />".RS_ANO." &nbsp;&nbsp; <input type=\"radio\" name=\"rzobrazmenu\" value=\"0\" />".RS_NE."\n";
  echo "<input type=\"checkbox\" name=\"rzobrazmenu\" value=\"1\" checked />" . RS_ANO ."\n";

else:
  //echo "<input type=\"radio\" name=\"rzobrazmenu\" value=\"1\" />".RS_ANO." &nbsp;&nbsp; <input type=\"radio\" name=\"rzobrazmenu\" value=\"0\" checked />".RS_NE."\n";
  echo "<input type=\"checkbox\" name=\"rzobrazmenu\" value=\"1\" />" . RS_ANO .  "\n";
endif;
echo "<p align=\"center\" class=\"z\"><i>".RS_CT_INFO_POVINNA."</i></p>\n";
//echo $dbserver ." ". $dbuser." ".  $dbpass." ".  $dbname;


//echo "<p align=\"center\" class=\"z\">".RS_CT_GDPR."<br>\n"; 
 /*
if ($GLOBALS["prmyctenar"]->Ukaz('zobrazitdata')==1):
  echo "<input type=\"checkbox\" name=\"rzobrazmenu\" value=\"1\"  />".RS_ANO."\n";
else:
  echo "<input type=\"checkbox\" name=\"rzobrazmenu\" value=\"1\"  />".RS_ANO."\n";
endif;
*/

echo "</p>
<p align=\"center\"><input type=\"submit\" value=\" ".htmlspecialchars($GLOBALS["typtlacitka"])." \" class=\"tl\" />&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"reset\" value=\" ".RS_RESET." \" class=\"tl\" /></p>\n";
if ($GLOBALS["typakce"]=='insert'): // jen insert mod
  // overovaci retezec - captcha
  $akt_pole_test_robot=NactiKontrolniRetezec();
  echo '<hr><p align="center" class="z">'.RS_KO_ZPR_KONTROLA.'<br /><br />'.htmlspecialchars($akt_pole_test_robot['captcha_otazka']).' <input type="text" name="captchaodpoved" size="12" class="textpole" /><input type="hidden" name="captchaid" value="'.htmlspecialchars($akt_pole_test_robot['captcha_id']).'" /></p><hr>';
  // info
  //echo "<p align=\"center\" class=\"z\"><i>".RS_CT_INFO_POVINNA."</i></p>\n";
endif;
echo "</form>
<br>\n";  
}

// pridani noveho ctenare
function PridejCt()
{
// inic. chyba
$chyba=0;

// vyhodnoceni captcha testu - nutno provest test
if (isset($GLOBALS['captchaodpoved'])): $GLOBALS['captchaodpoved']=phprs_sql_escape_string($GLOBALS['captchaodpoved']); else: $GLOBALS['captchaodpoved']=''; endif;
if (isset($GLOBALS['captchaid'])): $GLOBALS['captchaid']=phprs_sql_escape_string($GLOBALS['captchaid']); else: $GLOBALS['captchaid']=''; endif;
// vyhodnoceni captcha testu - zalezi na nastaveni systemu
$vysl_captcha_test=OverKontrolniRetezec($GLOBALS['captchaid'],$GLOBALS['captchaodpoved']); // captcha zapnuta - nutno provest vyhodnoceni

// test na vysledek kontrolni otazky (captcha kontrola)
if ($vysl_captcha_test!==1):
  // chyba - neuspesny kontrolni test (captcha kontrola)
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_KO_ERR6."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba    
endif;

// test na pritomnost vsech povinnych poli
if ($GLOBALS["rjmeno"]==''||$GLOBALS["rheslo"]==''||$GLOBALS["rzobrazmenu"]==0):
  // CHYBA: Nektere z povinnych poli je pradne!
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_3."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba
endif;
// overeni jedinecnosti zvoleneho username - ! sql escape string rjmeno
$dotazjmeno=phprs_sql_query("select idc from ".$GLOBALS["rspredpona"]."ctenari where prezdivka='".phprs_sql_escape_string($GLOBALS["rjmeno"])."'",$GLOBALS["dbspojeni"]);
$pocetjmeno=phprs_sql_num_rows($dotazjmeno);
if ($pocetjmeno>0):
  // CHYBA: Vami vybrana prezdivka X je jiz obsazena! Zvolte si jinou.
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_4_A." \"".htmlspecialchars($GLOBALS["rjmeno"])."\" ".RS_CT_ERR_4_B."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba
endif;
// overeni min. a max. povolene delky username
if (mb_strlen($GLOBALS["rjmeno"])<3 || mb_strlen($GLOBALS["rjmeno"])>20):
  // CHYBA: Delka vaseho jmena (prezdivky) porusuje pravidla. - Minimalni povolena delka je 3 znaky; maximalni hodnota je 20 znaku.
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_13."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba
endif;
// TODO: test na silu hesla?

// test na HTML - login a celejmeno
if (($GLOBALS["rjmeno"].$GLOBALS["rcelejmeno"]) !== htmlspecialchars($GLOBALS["rjmeno"].$GLOBALS["rcelejmeno"])):
  // CHYBA: Obsah vaseho jmena (prezdivky) nebo celeho jmena porusuje pravidla. Udaje nesmi obsahovat HTML znaky
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_14."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba
endif;
// test na shodu HESLA s jeho kontrolnim zadanim
if ($GLOBALS["rheslo"]!=$GLOBALS["rheslo2"]):
  // CHYBA: Zadana hesla nejsou shodna!
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_12."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba
endif;

if ($chyba==0): // test na bezchybny stav

  require('admin/hash_functions.php');
  $GLOBALS["rheslo"]=calculate_hash($GLOBALS["rheslo"]); // heslo je sifrovane
  // escapujem az pred zapisom do db
  $GLOBALS["rjmeno"]=phprs_sql_escape_string($GLOBALS["rjmeno"]);
  $GLOBALS["rheslo"]=phprs_sql_escape_string($GLOBALS["rheslo"]);
  $GLOBALS["rheslo2"]=phprs_sql_escape_string($GLOBALS["rheslo2"]);
  $GLOBALS["rcelejmeno"]=phprs_sql_escape_string($GLOBALS["rcelejmeno"]);
  $GLOBALS["rmail"]=phprs_sql_escape_string($GLOBALS["rmail"]);
  $GLOBALS["rjazyk"]=phprs_sql_escape_string($GLOBALS["rjazyk"]);
  $GLOBALS["rinfo"]=phprs_sql_escape_string($GLOBALS["rinfo"]);
  $GLOBALS["robsahmenu"]=phprs_sql_escape_string(strip_tags($GLOBALS["robsahmenu"]));
  $GLOBALS["rzobrazmenu"]=phprs_sql_escape_string($GLOBALS["rzobrazmenu"]);
  
  
  // ziskani defaultni nastaveni levelu
  $dotaz="select hodnota from ".$GLOBALS["rspredpona"]."config where promenna='default_reg_level'";
  $dotazconfig=phprs_sql_query($dotaz,$GLOBALS["dbspojeni"]);
  if ($dotazconfig!==false&&phprs_sql_num_rows($dotazconfig)>0):
    list($nast_level)=phprs_sql_fetch_row($dotazconfig);
  else:
    $nast_level=0;
  endif;
  // vlozeni noveho ctenare do databaze
  $dnesnidatum=date("Y-m-d H:i:s");
  @$dotazctenar=phprs_sql_query("insert into ".$GLOBALS["rspredpona"]."ctenari values (null,'".$GLOBALS["rjmeno"]."','".$GLOBALS["rheslo"]."','".$GLOBALS["rcelejmeno"]."','".$GLOBALS["rmail"]."','".$dnesnidatum."','0','".$GLOBALS["rinfo"]."','".$GLOBALS["robsahmenu"]."','".$GLOBALS["rzobrazmenu"]."','".$GLOBALS["rjazyk"]."','".$dnesnidatum."','".$nast_level."')",$GLOBALS["dbspojeni"]);
  if ($dotazctenar===false):
    // chyba pri vkladani noveho ctenare
    // CHYBA: V průběhu zakládání vašeho profilu došlo k neočekávané chybě. Zopakujte akci.
    $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_5."</p>\n";
    $GLOBALS['akce']='showtxt'; // zmena akce
  else:
    // zjisteni id noveho ctenare
    $dotazid=phprs_sql_query("select idc from ".$GLOBALS["rspredpona"]."ctenari where prezdivka='".$GLOBALS["rjmeno"]."' and password='".$GLOBALS["rheslo"]."'",$GLOBALS["dbspojeni"]);
    if ($dotazid!==false&&phprs_sql_num_rows($dotazid)==1):
      // ziskani ID ctenare a vygenerovani identifikacni session
      list($akt_id_ctenare)=phprs_sql_fetch_row($dotazid);
      $akt_sess_ctenare=md5(time().$GLOBALS["rjmeno"].$GLOBALS["rheslo"].$GLOBALS["rcelejmeno"].$GLOBALS["rmail"]);
      // vygenerovani ctenarske session + cookies
      if ($GLOBALS['prmyctenar']->GenerujSession($akt_id_ctenare,$akt_sess_ctenare)==0):
        // chyba pri generovani session
        // CHYBA: Neočekávaná chyba!
        $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_6."</p>\n";
        $GLOBALS['akce']='showtxt'; // zmena akce
      endif;
      // finalni hlaseni - Vaše registrace byla úspěšně dokončena.
      include_once("v_ctenari.php");  ////vlozenie zakladnych doplnkovych udajov pri vytvarani noveho profilu
      $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\"><b>".RS_CT_REG_VSE_OK."</b></p>\n";
      $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\"><a href=\"index.php\">".RS_CT_HL_STR."</a> - <a href=\"readers.php\">".RS_CT_OSOBNI_UCET."</a></p>\n";
      $GLOBALS['akce']='showtxt'; // zmena akce
    else:
      // chyba pri indetifikaci ctenare
      // CHYBA: Neočekávaná chyba!
      $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_6."</p>\n";
      $GLOBALS['akce']='showtxt'; // zmena akce
    endif;
  endif;
else:
  // z duvodu chyb pri registraci noveho ctenare nelze tuto akci provest; je nastaveno presmerovani na editacni formular, aby bylo mozne chyby opravit
  // je nutne provest inic. formluare
  $GLOBALS["typakce"]='insert'; // definice modu
  $GLOBALS["typtlacitka"]=RS_CT_TL_ZAREG; // text tlacitko: Zaregistrovat
  // inic. cisteho ctenarskeho profilu
  $GLOBALS["prmyctenar"]->GenerujCistyCtenar();
  // prednastaveni polozek do formulare
  $GLOBALS["prmyctenar"]->Nastav('username',$GLOBALS["rjmeno"]);
  $GLOBALS["prmyctenar"]->Nastav('jmeno',$GLOBALS["rcelejmeno"]);
  $GLOBALS["prmyctenar"]->Nastav('email',$GLOBALS["rmail"]);
  $GLOBALS["prmyctenar"]->Nastav('jazyk',$GLOBALS["rjazyk"]);
  $GLOBALS["prmyctenar"]->Nastav('info',$GLOBALS["rinfo"]);
  $GLOBALS["prmyctenar"]->Nastav('databox',$GLOBALS["robsahmenu"]);
  $GLOBALS["prmyctenar"]->Nastav('zobrazitdata',$GLOBALS["rzobrazmenu"]);
endif;
}

// ulozeni nove konfigurace ctenare
function UlozCt()
{
// inic. chyba
$chyba=0;

// test na pritomnost vsech povinnych poli
if ($GLOBALS["rjmeno"]==''||$GLOBALS["rzobrazmenu"]==0):
  // CHYBA: Některé z povinných polí je prádné!
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_3."</p>\n";
  $GLOBALS['akce']='showtxt'; // zmena akce
  $chyba=1; // chyba
endif;
// overeni jedinecnosti zvoleneho username - z kontroly je odstranet samotny prihlaseny ctenar - ! sql escape string
$dotazjmeno=phprs_sql_query("select idc from ".$GLOBALS["rspredpona"]."ctenari where prezdivka='".phprs_sql_escape_string($GLOBALS["rjmeno"])."' and idc!='".phprs_sql_escape_string($GLOBALS["ridc"])."'",$GLOBALS["dbspojeni"]);
$pocetjmeno=phprs_sql_num_rows($dotazjmeno);
if ($pocetjmeno>0):
  // CHYBA: Vámi vybraná přezdívka X je již obsazená! Zvolte si jinou.
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_4_A." \"".htmlspecialchars($GLOBALS["rjmeno"])."\" ".RS_CT_ERR_4_B."</p>\n";
  $GLOBALS['akce']='showtxt'; // zmena akce
  $chyba=1; // chyba
endif;
// test na shodu HESLA s jeho kontrolnim zadanim
if ($GLOBALS["rheslo"]!=$GLOBALS["rheslo2"]):
  // CHYBA: Zadaná hesla nejsou shodná!
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_12."</p>\n";
  $GLOBALS['akce']='showtxt'; // zmena akce
  $chyba=1; // chyba
endif;
// test na HTML - login a celejmeno
if (($GLOBALS["rjmeno"].$GLOBALS["rcelejmeno"]) !== htmlspecialchars($GLOBALS["rjmeno"].$GLOBALS["rcelejmeno"])):
  // CHYBA: Obsah vaseho jmena (prezdivky) nebo celeho jmena porusuje pravidla. Udaje nesmi obsahovat HTML znaky
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_14."</p>\n";
  $GLOBALS['akce']='formular'; // zmena akce
  $chyba=1; // chyba
endif;
if ($chyba==0): // test na bezchybny stav

  // escapujem az pred zapisom do db
  $GLOBALS["rjmeno"]=phprs_sql_escape_string($GLOBALS["rjmeno"]);
  $GLOBALS["roldpass"]=phprs_sql_escape_string($GLOBALS["roldpass"]); // TODO: odstranit z formulara?
  $GLOBALS["ridc"]=phprs_sql_escape_string($GLOBALS["ridc"]);
  $GLOBALS["rheslo"]=phprs_sql_escape_string($GLOBALS["rheslo"]);
  $GLOBALS["rheslo2"]=phprs_sql_escape_string($GLOBALS["rheslo2"]);
  $GLOBALS["rcelejmeno"]=phprs_sql_escape_string($GLOBALS["rcelejmeno"]);
  $GLOBALS["rmail"]=phprs_sql_escape_string($GLOBALS["rmail"]);
  $GLOBALS["rjazyk"]=phprs_sql_escape_string($GLOBALS["rjazyk"]);
  $GLOBALS["rinfo"]=phprs_sql_escape_string($GLOBALS["rinfo"]);
  $GLOBALS["robsahmenu"]=phprs_sql_escape_string(strip_tags($GLOBALS["robsahmenu"]));
  $GLOBALS["rzobrazmenu"]=phprs_sql_escape_string($GLOBALS["rzobrazmenu"]);

  // overeni existence upravovaneho ctenarskeho profilu
  $dotazjmeno=phprs_sql_query("select idc from ".$GLOBALS["rspredpona"]."ctenari where idc='".$GLOBALS["ridc"]."' and password='".$GLOBALS["roldpass"]."'",$GLOBALS["dbspojeni"]);
  if ($dotazjmeno!==false&&phprs_sql_num_rows($dotazjmeno)==1): // kontrola existence, vse OK
    // uprava existujiciho ctenare
    $dnesnidatum=date("Y-m-d H:i:s");
    // priprava hesla
    if ($GLOBALS["rheslo"]==''):
      $GLOBALS["rheslo"]=$GLOBALS["roldpass"]; // heslo je sifrovane; pouzito puvodni // TODO: odstranit z formulara roldpass?
    else:
      require('admin/hash_functions.php');
	  $GLOBALS["rheslo"]=calculate_hash($GLOBALS["rheslo"]); // heslo je sifrovane
    endif;
    // TODO: kontrola uprade musi byt bez roldpass z formulara?
    @$dotazctenar=phprs_sql_query("update ".$GLOBALS["rspredpona"]."ctenari set password='".$GLOBALS["rheslo"]."',jmeno='".$GLOBALS["rcelejmeno"]."',email='".$GLOBALS["rmail"]."',info='".$GLOBALS["rinfo"]."',data='".$GLOBALS["robsahmenu"]."',visible='".$GLOBALS["rzobrazmenu"]."',jazyk='".$GLOBALS["rjazyk"]."',posledni_login='".$dnesnidatum."' where idc='".$GLOBALS["ridc"]."' and password='".$GLOBALS["roldpass"]."'",$GLOBALS["dbspojeni"]);
    if ($dotazctenar===false):
      // chyba pri aktualizacei ctenare
      // CHYBA: V průběhu úpravy vašeho profilu došlo k neočekávané chybě. Zopakujte akci.
      $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_7."</p>\n";
      $GLOBALS['akce']='showtxt'; // zmena akce
    else:
      // ziskani ID ctenare a vygenerovani identifikacni session
      $akt_id_ctenare=$GLOBALS["ridc"];
      $akt_sess_ctenare=md5(time().$GLOBALS["rjmeno"].$GLOBALS["rheslo"].$GLOBALS["rcelejmeno"].$GLOBALS["rmail"]);
      // vygenerovani ctenarske session + cookies
      if ($GLOBALS['prmyctenar']->GenerujSession($akt_id_ctenare,$akt_sess_ctenare)==0):
        // chyba pri generovani session
        // CHYBA: Neočekávaná chyba!
        $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_6."</p>\n";
        $GLOBALS['akce']='showtxt'; // zmena akce
      endif;
      // finalni hlaseni - Vaše osobní nastavení bylo aktualizováno.
      $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_EDIT_VSE_OK."</p>\n";
      $GLOBALS['akce']='showtxt'; // zmena akce
    endif;
  else:
    // chyba test na existenci ctenarskeho profilu
    // CHYBA: Neočekávaná chyba!
    $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_6."</p>\n";
    $GLOBALS['akce']='showtxt'; // zmena akce
  endif;
endif;

// navrat
$GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\"><a href=\"readers.php\">".RS_CT_NOVY_LOGIN."</a></p>\n<br>\n";
$GLOBALS['akce']='showtxt'; // zmena akce
}

function JenLogin()
{
// bezpecnostni kontrola
$GLOBALS["rjmeno"]=phprs_sql_escape_string($GLOBALS["rjmeno"]);
require('admin/hash_functions.php');
$GLOBALS["rheslo"]=calculate_hash($GLOBALS["rheslo"]); // heslo je sifrovane

if (!isset($GLOBALS["nacti"])): $GLOBALS["nacti"]='index.php'; endif; // pro pripad doplneni domeny lze pouzit promenne $GLOBALS["baseadr"]

// overeni existence ctenarskeho profilu
$dotazjmeno=phprs_sql_query("select idc,jmeno,email from ".$GLOBALS["rspredpona"]."ctenari where prezdivka='".$GLOBALS["rjmeno"]."' and password='".$GLOBALS["rheslo"]."'",$GLOBALS["dbspojeni"]);
$pocetjmeno=phprs_sql_num_rows($dotazjmeno);
if ($pocetjmeno==1): // kontrola existence, vse OK
  // nacteni dat do pole
  $pole_akt_data=phprs_sql_fetch_assoc($dotazjmeno);
  // priprava ctenare na prihlaseni - generovani session
  $GLOBALS["rcelejmeno"]=$pole_akt_data["jmeno"];
  $GLOBALS["rmail"]=$pole_akt_data["email"];
  $akt_id_ctenare=$pole_akt_data["idc"];
  $akt_sess_ctenare=md5(time().$GLOBALS["rjmeno"].$GLOBALS["rheslo"].$GLOBALS["rcelejmeno"].$GLOBALS["rmail"]);
  // vygenerovani ctenarske session + cookies
  if ($GLOBALS['prmyctenar']->GenerujSession($akt_id_ctenare,$akt_sess_ctenare)==0):
    // chyba pri generovani session
    // CHYBA: Neočekávaná chyba!
    $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_6."</p>\n";
    $GLOBALS['akce']='showtxt'; // zmena akce
  else:
    // presmerovani na novou stranku
    header("Location: ".$GLOBALS["nacti"]);
    exit();
  endif;
else:
  // chyba ctenar nenalezen
  // CHYBA: Špatné uživatelké jméno nebo heslo!
  $GLOBALS['cte_modul_text'].="<p align=\"center\" class=\"z\">".RS_CT_ERR_1."</p>\n";
  $GLOBALS['akce']='showtxt'; // zmena akce
endif;
}

function OdhlasCt()
{
if (!isset($GLOBALS["nacti"])): $GLOBALS["nacti"]='index.php'; endif; // pro pripad doplneni domeny lze pouzit promenne $GLOBALS["baseadr"]

// zruseni ctenarske session / odhlaseni ctenare
$GLOBALS["prmyctenar"]->ZrusitSession();
// presmerovani na novou stranku
header("Location: ".$GLOBALS["nacti"]);
exit();
}

function VymazatCt()
{
echo "<form action=\"readers.php\" method=\"post\">
<p align=\"center\" class=\"z\">".RS_CT_INFO_ZRUSIT."</p>
<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
<tr class=\"z\"><td>".RS_CT_JMENO.":</td><td><input type=\"text\" size=\"15\" name=\"rjmeno\" class=\"textpole\" /></td></tr>
<tr class=\"z\"><td>".RS_CT_HESLO.":</td><td><input type=\"password\" size=\"15\" name=\"rheslo\" class=\"textpole\" /></td></tr>
</table>
<p align=\"center\"><input type=\"submit\" value=\" ".RS_CT_TL_ZRUSIT." \" class=\"tl\" /></p>
<input type=\"hidden\" name=\"akce\" value=\"delreader\" />
</form>
<br>\n";
}

function AcVymazatCt()
{
// bezpecnostni kontrola
$GLOBALS["rjmeno"]=phprs_sql_escape_string($GLOBALS["rjmeno"]);
require('admin/hash_functions.php');
$GLOBALS["rheslo"]=calculate_hash($GLOBALS["rheslo"]); // heslo je sifrovane

$dotazcte=phprs_sql_query("select idc,prezdivka from ".$GLOBALS["rspredpona"]."ctenari where prezdivka='".$GLOBALS["rjmeno"]."' and password='".$GLOBALS["rheslo"]."'",$GLOBALS["dbspojeni"]);
$pocetcte=phprs_sql_num_rows($dotazcte);

if ($pocetcte==0):
  // CHYBA: Špatné uživatelké jméno nebo heslo! - Nové přihlášení
  echo "<p align=\"center\" class=\"z\"><b>".RS_CT_ERR_1." - <a href=\"readers.php?akce=del\">".RS_CT_NOVY_LOGIN."</a></b></p>\n";
else:
  $ctenardata=phprs_sql_fetch_assoc($dotazcte); // nacteni dat

  @$error=phprs_sql_query("delete from ".$GLOBALS["rspredpona"]."ctenari where idc='".phprs_sql_escape_string($ctenardata["idc"])."' and prezdivka='".phprs_sql_escape_string($ctenardata["prezdivka"])."'",$GLOBALS["dbspojeni"]);
   if ($error === false):
     // chyba pri ruseni registrace ctenare
     // CHYBA: V průběhu odstraňování registrace došlo k neočekávané chybě!
     echo "<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_8."</p>\n";
   else:
     // vse ok - Vaše registrace byla úspěšně zrušena!
     echo "<p align=\"center\" class=\"z\"><b>".RS_CT_DEL_VSE_OK."</b></p>\n";
   endif;
   // navrat
   echo "<p align=\"center\" class=\"z\"><a href=\"index.php\">".RS_CT_HL_STR."</a> - <a href=\"readers.php\">".RS_CT_OSOBNI_UCET."</a></p>\n";
endif;
}

function NoveHesloCt()
{
echo "<form action=\"readers.php\" method=\"post\">
<p align=\"center\" class=\"z\">".RS_CT_INFO_ZAPOMNEL."</p>
<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
<tr class=\"z\"><td>".RS_CT_JMENO.":</td><td><input type=\"text\" size=\"30\" name=\"rjmeno\" class=\"textpole\" /></td></tr>
<tr class=\"z\"><td>".RS_CT_DEL_EMAIL_ADR.":</td><td><input type=\"text\" size=\"30\" name=\"rmail\" class=\"textpole\" /></td></tr>
</table>
<p align=\"center\"><input type=\"submit\" value=\" ".RS_CT_TL_NOVE_HESLO." \" class=\"tl\" /></p>
<input type=\"hidden\" name=\"akce\" value=\"newpwsend\" />
</form>
<br>\n";
}

function AcNoveHesloCt()
{
// bezpecnostni kontrola
$GLOBALS["rjmeno"]=phprs_sql_escape_string($GLOBALS["rjmeno"]);
$GLOBALS["rmail"]=phprs_sql_escape_string($GLOBALS["rmail"]);

$dotazcte=phprs_sql_query("select idc,prezdivka,email from ".$GLOBALS["rspredpona"]."ctenari where prezdivka='".$GLOBALS["rjmeno"]."' and email='".$GLOBALS["rmail"]."'",$GLOBALS["dbspojeni"]);
$pocetcte=phprs_sql_num_rows($dotazcte);

if ($pocetcte==0):
  // CHYBA: Špatné uživatelké jméno nebo e-mailová adresa! - Nové přihlášení
  echo "<p align=\"center\" class=\"z\"><b>".RS_CT_ERR_9." - <a href=\"readers.php?akce=newpw\">".RS_CT_NOVY_LOGIN."</a></b></p>\n";
else:
  // inic. generovani noveho hesla
  $pocitadlo=0;
  $nove_heslo='';
  $delka_hesla=8;
  $str_znaku="ABCDEFGHIJKLMNOPQRSTUVWXYZ"
            ."abcdefghijklmnopqrstuvwxyz"
            ."0123456789";
  $poc_str_znaku=mb_strlen($str_znaku)-1; // odecita se navic 1, protoze s retezcem se dale pocita od 0

  // vygenerovani noveho hesla
  while($pocitadlo++ < $delka_hesla):
    $nove_heslo.=mb_substr($str_znaku,mt_rand(0,$poc_str_znaku),1);
  endwhile;

  // nastaveni noveho hesla
  require('admin/hash_functions.php');
  @$error=phprs_sql_query("update ".$GLOBALS["rspredpona"]."ctenari set password='".phprs_sql_escape_string(calculate_hash($nove_heslo))."' where prezdivka='".$GLOBALS["rjmeno"]."' and email='".$GLOBALS["rmail"]."'",$GLOBALS["dbspojeni"]);
  if ($error === false):
    // CHYBA: V průběhu ukládání nového hesla došlo k neočekávané chybě!
    echo "<p align=\"center\" class=\"chybastredni\">".RS_CT_ERR_10."</p>\n";
  else:
    // vse OK - Nastavení nového hesla bylo úspěšně provedeno!
    echo "<p align=\"center\" class=\"z\"><b>".RS_CT_NOVE_HESLO_VSE_OK."</b></p>\n";
  endif;

  // odeslani info e-mailu
  include_once('admin/astdlib_mail.php'); // vlozeni tridy CPosta()
  $postovni_sluzby = new CPosta();
  $postovni_sluzby->Nastav('adresat',$GLOBALS["rmail"]);
  $postovni_sluzby->Nastav('predmet',RS_CT_GNH_PREDMET);
  $postovni_sluzby->Nastav('obsah',RS_CT_GNH_OBS_1." \"".$GLOBALS["rjmeno"]."\" ".RS_CT_GNH_OBS_2.": ".$nove_heslo."\n");

  if ($postovni_sluzby->Odesilac()==1):
    // vse OK - Na vaši e-mailovou adresu byl úspěšně odeslán informační e-mail!
    echo "<p align=\"center\" class=\"z\"><b>".RS_CT_SEND_MAIL_VSE_OK."</b></p>\n";
  else:
    // CHYBA: V průběhu odesílání informačního e-mailu došlo k neočekávané chybě!
    echo "<p align=\"center\" class=\"chybastredni\"><b>".RS_CT_ERR_11."</b></p>\n";
  endif;

  // navrat
  echo "<p align=\"center\" class=\"z\"><a href=\"index.php\">".RS_CT_HL_STR."</a> - <a href=\"readers.php\">".RS_CT_OSOBNI_UCET."</a></p>\n";
endif;
}

// rozhodnuti o obsahu stranky
switch ($GLOBALS['akce']):
  case "new": NovyCt(); break;
  case "login": EditujCt(); break;
  case "autologin": EditujAutoCt(); break;
  case "insert": PridejCt(); break;
  case "save": UlozCt(); break;
  case "quicklog": JenLogin(); break;
  case "logout": OdhlasCt(); break;
endswitch;

$GLOBALS["vzhledwebu"]->Generuj();
ObrTabulka();  // Vlozeni layout prvku

echo "<p class=\"nadpis\">".RS_CT_NADPIS."</p>\n"; // nadpis

// rozhodnuti o obsahu stranky
switch($GLOBALS['akce']):
  case "logmenu": ZobrazLogin(); break;
  case "del": VymazatCt(); break;
  case "delreader": AcVymazatCt(); break;
  case "newpw": NoveHesloCt(); break;
  case "newpwsend": AcNoveHesloCt(); break;
  case "formular": FormCtenari(); break; // pouze interni volani
  case "showtxt": echo $GLOBALS['cte_modul_text']; break; // pouze interni volani
endswitch;

// Dokonceni tvorby stranky
KonecObrTabulka();  // Vlozeni layout prvku
$vzhledwebu->Generuj();
?>
