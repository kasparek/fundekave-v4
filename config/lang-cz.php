<?php
define('LABEL_WEEK','Tyden');

define('LABEL_FORUMS','Kluby');
define('LABEL_BLOGS','Blogy');
define('LABEL_GALERIES','Galerie');
define('LABEL_THUMBS','Palce');

define('LABEL_POCKET_PUSH','Do kapsy');

define('LABEL_POST','Posta');
define('LABEL_INFO','Info');
define('LABEL_FILE','Soubor');

define('LABEL_OWNER','Majitel');
define('LABEL_YES','Ano');
define('LABEL_NO','Ne');
define('LABEL_NOTREGISTEREDUSERS','Neprihlaseni');

define('BUTTON_PAGE_SETTINGS','Nastavení stránky');
define('BUTTON_PAGE_BACK','Zpět');
define('BUTTON_EDIT','Upravit');
define('MESSAGE_PAGE_LOCKED','Stránka je zamčena administrátory. Připadné dotazy: <a href="?k=fl168">.:--<< FUN.de.KAVE >>--:. typy, rady, chyby</a>');

define('MESSAGE_USER_NEWREGISTERED','Zaregistroval se nový uživatel: <a href="?k=finfo&who={NEWUSERID}">{NEWUSERNAME}</a><br />Pokud chcete uživatele zablokovat, tady se udělují <a href="?k=sbann">banány</a>');
define('MESSAGE_DIARY_REMINDER','Pripominka z diare<br /><a href="{LINK}">{NAME}</a><br />Datum: {DATE}');

define('LABEL_BOOK','Do oblíbených');
define('LABEL_UNBOOK','Nesledovat');

define('LABEL_PAGE_NEW','Nová stránka');
define('ERROR_PAGE_ADD_NONAME','Zadejte prosím jméno stránky');

define('ERROR_DATA_FORMAT','Zadali jste špatné datum');
define('ERROR_DIARY_NAME','Nezadali jste název poznámky');
define('ERROR_DIARY_TEXT','Nezadali jste text poznámky');

define('ERROR_SURF_URL','Nezadali jste odkaz');

define('LABEL_USER','Uživatel');
define('LABEL_NOTEXISTS','neexistuje');
define('LABEL_ADD','Pridat');
define('LABEL_SETTINGS','Nastaveni');

define('LABEL_POLL','Anketa');
define('ERROR_POLL_QUESTION','Nezadali jste otazku');

define('LABEL_STATS','Statistiky');
define('LABEL_HOME','Nastenka');
define('LABEL_DELETE','Smazat');

$TAGLABELS = array(
'forum'=>array('Palec nahoru','Palec','Palce','Palcu','Palec dolu'),
'blog'=>array('Palec nahoru','Palec','Palce','Palcu','Palec dolu'),
'galery'=>array('Palec nahoru','Palec','Palce','Palcu','Palec dolu'),
'event'=>array('Přijdu','přijde','přijdou','přijde','Nepřijdu'),
);

define('LABEL_TAG_PAGE','Popisuj fotky');
define('LABEL_RULES_ACCESS','Pristup');
define('LABEL_RULES_HELP','Napoveda');
define('LABEL_RULES_HELP_TEXT','Pokud je pristup nasteveny na Soukromy - pristup maji jen uzivatele uvedeni v poli Uzivatele a Administratori');

define('LABEL_DELETED_OK','Uspesne smazano');
define('LABEL_EVENTS_ARCHIV','Archiv');
define('LABEL_EVENT_NEW','Novy tip');
define('LABEL_LOGOUT','Odhlaseni');
define('LABEL_FRIEND_ADD','Do pratel');
define('LABEL_FRIEND_REMOVE','Odebrat z pratel');
define('ERROR_DATE_FORMAT','Spatny format data');
define('MESSAGE_SUCCESS_CREATE','Úspěšně založeno');
define('MESSAGE_SUCCESS_SAVED','Úspěšně uloženo');
define('MESSAGE_FORUM_READONLY','Klub je zamčený pro zápis');
define('MESSAGE_FORUM_REGISTEREDONLY','Pro zapis do diskuze musite byt registrovani');
define('MESSAGE_FORUM_HOME_EMPTY','Nastenka je praznda');

define('ERROR_NAME_EMPTY','Nezadali jste nazev');

define('ERROR_GALERY_NAMEEMPTY','Nezadali jste jmeno galerie');
define('ERROR_GALERY_NAMEEXISTS','Takove jmeno galerie jiz existuje');
define('ERROR_FORUM_NAMEEMPTY','Nezadali jste jmeno klubu');
define('ERROR_FORUM_NAMEEXISTS','Takove jmeno klubu jiz existuje');
define('ERROR_BLOG_NAMEEMPTY','Nezadali jste jmeno blogu');
define('ERROR_BLOG_NAMEEXISTS','Takove jmeno blogu jiz existuje');

define('ERROR_REGISTER_TOSHORTNAME',"Jméno je příliš krátké");
define('ERROR_REGISTER_TOLONGNAME',"Jméno je příliš dlouhé");
define('ERROR_REGISTER_NOTALLOWEDNAME',"Jméno muze obsahovat pouze pismena, cisla a znaky bez diakritiky");
define('ERROR_REGISTER_NAMEEXISTS',"Uživatelské jméno již exituje");
define('MESSAGE_USERNAME_NOTEXISTS',"Uživatelské jméno neexistuje");
define('ERROR_REGISTER_PASSWORDNOTSAFE',"Heslo se shoduje se jménem a to není příliš bezpečné");
define('ERROR_REGISTER_PASSWORDTOSHORT',"Heslo je příliš krátké");
define('ERROR_REGISTER_PASSWORDDONTMATCH',"Hesla se neshodují");
define('MESSAGE_PASSWORD_SET',"Nove heslo nastaveno");
define('MESSAGE_REGISTER_SUCCESS','<strong>REGISTRACE PROBĚHLA ÚSPĚŠNĚ</strong><br />Nyní se již okamžitě můžete přihlásit.');
define('ERROR_LOGIN_WRONGUSERORPASS','Zadali jste spatne jmeno nebo heslo');
define('ERROR_PAGE_NOTEXISTS','Stranka neexistuje');
define('ERROR_ACCESS_DENIED','Nemate prava pro pristup na tuto stranku');
define('MESSAGE_LOGOUT_OK','Uspesne jses odhlasil');
define('ERROR_USER_KICKED','Z nejakeho duvodu jsi byl odhlasen.');
define('ERROR_USER_NOTEXISTS','Uživatel neexistuje: ');
define('PAGER_PREVIOUS','&lt;&lt; predchozi');
define('PAGER_NEXT','dalsi &gt;&gt;');
define('ERROR_UPLOAD_NOTLOADED','Nepodařilo se nahrát soubor [1]');
define('ERROR_UPLOAD_TOBIG','Překročena maximální velikost souboru [2]');
define('ERROR_UPLOAD_NOTALLOWEDTYPE','Nepovoleny typ souboru [3]');
define('ERROR_UPLOAD_FILEEXISTS','Soubor již existuje [4]');
define('ERROR_UPLOAD_NOTALLOWEDFILENAME','Soubor obsahuje nepovolene znaky [5]');
define('ERROR_UPLOAD_NOTSAVED','Chyba při ukládání souboru [6]');
define('MESSAGE_UPLOAD_SUCCESS','Uspesne nahrano');

define('ERROR_RULES_CREATE','Nemate dostatecna prava pro zakladani');
define('ERROR_CAPTCHA','Opiste spravne kontrolni kod');
define('ERROR_GALERY_DIREMPTY','Nezadali jste adresar');
define('ERROR_GALERY_DIRWRONG',"Zadali jste neplatne jmeno adresar, jsou povolene znaky pouze / - _ 0-9 a-z A-Z");
define('ERROR_GALERY_NOFOTO',"Bohuzel galerie zatim neobsahuje fotografie");

define('ERROR_SEARCH_TOSHORT','Zadaný řetězec pro vyhledávání je příliš krátký');
define('LABEL_FOUND','Nalezeno');
define('LABEL_SEARCH_RESULTS_JUSTLISTED','Zobrazeno bude pouze');

define('LABEL_POLL_RESULTS','Výsledky ankety');
define('MESSAGE_POLL_NOTPUBLIC','Výsledky ankety nejsou verejné');
define('MESSAGE_POLL_NORESULTS','Nejsou k dispozici zadne vysledku anket.');
define('MESSAGE_POLL_RESULTSRESOURCE','Odsud mužete zkopírovat výsledky ankety do klubu:');
define('MESSAGE_POLL_NOANSWERS','Pro tuto odpoved nikdo nehlasoval');

define('TEXT_PERMISSIONS_SET','Nastavení přístupových práv');

define('ERROR_BANNER_TARGETEMPTY','Nezadali jste cilovou adresu banneru');
define('ERROR_BANNER_EMPTY','Nezadali jste banner');

//---category
define('LABEL_CATEGORY_NAME','Nazev');
define('LABEL_CATEGORY_DESCRIPTION','Popis');
define('LABEL_CATEGORY_FUNCTION','Funkce');
define('LABEL_CATEGORY_GROUP','Skupina');
define('LABEL_CATEGORY_ORDER','Poradi');
define('LABEL_CATEGORY_PUBLIC','Verejne');
define('LABEL_CATEGORY_DELETE','Smazat');

define('LABEL_PAGES_DEFAULTFORALL','Default pro vsechny stranky');
define('LABEL_PAGES_USEDON','Pouzito na strankach');

$ARRPUBLIC = array("0"=>"Soukromé","1"=>"Veřejné","2"=>"Pro registrované");
$ARRPERMISSIONS = array(0=>'Nemá přístup',1=>'Uživatelé',2=>'Administrátoři');
$ARRLOCKED = array(0=>'',1=>'Zamčeny - mozno číst',2=>'Zamčený, skrytý',3=>'Smazaný - nevidí ani majitel');
$ARRWHERESEARCHLABELS = array(0=>"-- Kde --",1=>"Vložil",2=>"Text",3=>"Datum");
$DAYSSHORT=array("1"=>"Po","2"=>"Út","3"=>"St","4"=>"Ct","5"=>"Pá","6"=>"So","7"=>"Ne");
$MONTHS=array("01"=>"Leden","02"=>"Únor","03"=>"Brezen","04"=>"Duben","05"=>"Kveten","06"=>"Cerven","07"=>"Cervenec","08"=>"Srpen","09"=>"Zárí","10"=>"Ríjen","11"=>"Listopad","12"=>"Prosinec");
$DIARYREMINDER=array(0=>"Nepripomínat",1=>"Den akce",2=>"Den predem",3=>"2 dny pred",4=>"3 dny pred",5=>"4 dny pred",6=>"5 dní pred",7=>"6 dní pred",8=>"7 dní pred",9=>"8 dní pred");
$DIARYREPEATER=array(0=>"Neopakovat",1=>"Každý rok",2=>"Každý mesíc");
$TYPEID = array('galery'=>'Galerie','forum'=>'Klub','blog'=>'Blog');