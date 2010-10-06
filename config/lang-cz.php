<?php
class FLang {

static $TPL_SIDEBAR_PAGE_CATEGORIES = 'sidebar.page.categories.tpl.html';
static $TPL_SIDEBAR_POLL = 'sidebar.poll.tpl.html';
static $TPL_SIDEBAR_CALENDAR = 'sidebar.calendar.tpl.html';
static $TPL_SIDEBAR_USER_LOGGED = 'sidebar.user.logged.tpl.html';
static $TPL_SIDEBAR_USER_LOGIN = 'sidebar.user.login.tpl.html';
static $TPL_USER_AVATAR = 'user.avatar.tpl.html';


static $LABEL_PAGES_LIVE = 'Živě';
static $LABEL_WEEK = 'Tyden';
static $LABEL_TOP = 'Nej';
static $LABEL_FORUMS = 'Kluby';
static $LABEL_BLOGS = 'Blogy';
static $LABEL_GALERIES = 'Galerie';
static $LABEL_THUMBS = 'Palce';
static $LABEL_CALENDAR = 'Kalendar';

static $LABEL_POCKET_PUSH = 'Do kapsy';

static $LABEL_POST = 'Posta';
static $LABEL_INFO = 'Info';
static $LABEL_FILE = 'Soubor';

static $LABEL_OWNER = 'Majitel';
static $LABEL_YES = 'Ano';
static $LABEL_NO = 'Ne';
static $LABEL_NOTREGISTEREDUSERS = 'Neprihlaseni';

static $BUTTON_PAGE_SETTINGS = 'SA';
static $BUTTON_PAGE_BACK = 'Zpět';
static $BUTTON_PAGE_BACK_ALBUM = 'Zpět na album';
static $BUTTON_PAGE_NEXT = 'Dalsi &gt;&gt;';
static $BUTTON_PAGE_PREV = '&lt;&lt; Predchozi';
static $BUTTON_EDIT = 'Upravit';
static $MESSAGE_PAGE_LOCKED = 'Stránka je zamčena administrátory. Připadné dotazy: <a href="?k=fl168">.:--<< FUN.de.KAVE >>--:. typy, rady, chyby</a>';

static $MESSAGE_USER_NEWREGISTERED = 'Zaregistroval se nový uživatel: <a href="?k=finfo&who={NEWUSERID}">{NEWUSERNAME}</a><br />Pokud chcete uživatele zablokovat, tady se udělují <a href="?k=sbann">banány</a>';
static $MESSAGE_DIARY_REMINDER = 'Pripominka z diare<br /><a href="{LINK}">{NAME}</a><br />Datum: {DATE}';

static $LABEL_BOOK = 'Do oblíbených';
static $LABEL_UNBOOK = 'Nesledovat';
static $SEND_MESSAGE = 'Poslat zpravu';
static $LABEL_FRIEND_ADD = 'Pridat do pratel';
static $LABEL_FRIEND_REMOVE = 'Odebrat z pratel';
static $LABEL_FRIEND_REMOVE_CONFIRM = 'Opravdu chcete odebrat?';
static $MSG_FRIEND_REMOVED = 'Pritel odebran';
static $MSG_FRIEND_ADDED = 'Pritel pridan';
static $MSG_FRIEND_CANCEL = 'Zruseno';
static $MSG_REQUEST_WAITING = 'Pozadavek ceka na schvaleni uzivatele';
static $MSG_FRIEND_REQUEST_ACCEPTED = "Od ted jsme pratele <br /><br />[automaticka zprava]";
static $FRIENDS = 'Pratele';
static $FRIENDS_COMMON = 'Spolecni Pratele';

static $MSG_AVATAR_SET = 'Avatar nastaven';

static $LABEL_PAGE_NEW = 'Nová stránka';
static $ERROR_PAGE_ADD_NONAME = 'Zadejte prosím jméno stránky';

static $ERROR_DATA_FORMAT = 'Zadali jste špatné datum';
static $ERROR_DIARY_NAME = 'Nezadali jste název poznámky';
static $ERROR_DIARY_TEXT = 'Nezadali jste text poznámky';

static $ERROR_SURF_URL = 'Nezadali jste odkaz';

static $LABEL_USER = 'Uživatel';
static $LABEL_NOTEXISTS = 'neexistuje';
static $LABEL_ADD = 'Pridat';
static $LABEL_SETTINGS = 'Nastaveni';

static $LABEL_POLL = 'Anketa';
static $ERROR_POLL_QUESTION = 'Nezadali jste otazku';

static $LABEL_STATS = 'Statistiky';
static $LABEL_HOME = 'Nastenka';
static $LABEL_DELETE = 'Smazat';

static $LABEL_TAG_PAGE = 'Popisuj fotky';
static $LABEL_RULES_ACCESS = 'Pristup';
static $LABEL_RULES_HELP = 'Napoveda';
static $LABEL_RULES_HELP_TEXT = 'Pokud je pristup nasteveny na Soukromy - pristup maji jen uzivatele uvedeni v poli Uzivatele a Administratori';

static $LABEL_DELETED_OK = 'Uspesne smazano';
static $LABEL_EVENTS_ARCHIV = 'Archiv';
static $LABEL_EVENT_NEW = 'Novy tip';
static $LABEL_LOGOUT = 'Odhlaseni';
static $ERROR_DATE_FORMAT = 'Spatny format data';
static $MESSAGE_SUCCESS_CREATE = 'Úspěšně založeno';
static $MESSAGE_SUCCESS_SAVED = 'Úspěšně uloženo';
static $MESSAGE_FORUM_READONLY = 'Klub je zamčený pro zápis';
static $MESSAGE_FORUM_REGISTEREDONLY = 'Pro zapis do diskuze musite byt registrovani';
static $MESSAGE_TAG_REGISTEREDONLY = 'Zvednout palec muze jen registrovany uzivatel';
static $MESSAGE_TAG_ONLYONE = 'Zvednout palec muzes jen jednou';
static $MESSAGE_FORUM_HOME_EMPTY = 'Nastenka je praznda';
static $MESSAGE_EMPTY = 'Nezadali jste zadny text';
static $MESSAGE_NAME_EMPTY = 'Nezadali jste jmeno';
static $MESSAGE_NAME_USED = 'Jmeno uz nekdo pouziva';

static $ERROR_NAME_EMPTY = 'Nezadali jste nazev';

static $ERROR_PAGE_NAMEEXISTS = 'Takove jmeno stranky jiz existuje';
static $ERROR_GALERY_NAMEEMPTY = 'Nezadali jste jmeno galerie';
static $ERROR_GALERY_NAMEEXISTS = 'Takove jmeno galerie jiz existuje';
static $ERROR_FORUM_NAMEEMPTY = 'Nezadali jste jmeno klubu';
static $ERROR_FORUM_NAMEEXISTS = 'Takove jmeno klubu jiz existuje';
static $ERROR_BLOG_NAMEEMPTY = 'Nezadali jste jmeno blogu';
static $ERROR_BLOG_NAMEEXISTS = 'Takove jmeno blogu jiz existuje';

static $REGISTER_WELCOME = '<strong>REGISTRACE PROBĚHLA ÚSPĚŠNĚ</strong><br />Vitejte na Fundekave. Zde si muzete nastavit svuj profil.';
static $ERROR_INVALID_EMAIL = 'Neplatny email';
static $ERROR_USED_EMAIL = 'Tento email je jiz u nas registrovany';
static $ERROR_REGISTER_TOSHORTNAME = 'Jméno je příliš krátké';
static $ERROR_REGISTER_TOLONGNAME = 'Jméno je příliš dlouhé';
static $ERROR_REGISTER_NOTALLOWEDNAME = 'Jméno muze obsahovat pouze pismena, cisla a znaky bez diakritiky';
static $ERROR_REGISTER_NAMEEXISTS = 'Uživatelské jméno již exituje';
static $ERROR_REGISTER_BADUSERNAME = 'Uživatelské jméno obsahuje nepovolene znaky. Zkuste tohle:';
static $MESSAGE_USERNAME_NOTEXISTS = 'Uživatelské jméno neexistuje';
static $MESSAGE_RECIPIENT_EMPTY = 'Zadejte alespon jedno jmeno';
static $ERROR_REGISTER_PASSWORDNOTSAFE = 'Heslo se shoduje se jménem a to není příliš bezpečné';
static $ERROR_REGISTER_PASSWORDTOSHORT = 'Heslo je příliš krátké';
static $ERROR_REGISTER_PASSWORDDONTMATCH = 'Hesla se neshodují';
static $MESSAGE_PASSWORD_SET = 'Nove heslo nastaveno';
static $ERROR_LOGIN_WRONGUSERORPASS = 'Zadali jste spatne jmeno nebo heslo';
static $ERROR_PAGE_NOTEXISTS = 'Stranka neexistuje';
static $ERROR_ACCESS_DENIED = 'Nemate prava pro pristup na tuto stranku';
static $MESSAGE_LOGOUT_OK = 'Úspěšně ses odhlásil/a';
static $ERROR_USER_KICKED = 'Z nejakeho duvodu jsi byl odhlasen.';
static $ERROR_USER_NOTEXISTS = 'Uživatel neexistuje: ';
static $PAGER_PREVIOUS = '&lt;&lt; predchozi';
static $PAGER_NEXT = 'dalsi &gt;&gt;';
static $ERROR_UPLOAD_NOTLOADED = 'Nepodařilo se nahrát soubor [1]';
static $ERROR_UPLOAD_TOBIG = 'Překročena maximální velikost souboru [2]';
static $ERROR_UPLOAD_NOTALLOWEDTYPE = 'Nepovoleny typ souboru [3]';
static $ERROR_UPLOAD_FILEEXISTS = 'Soubor již existuje [4]';
static $ERROR_UPLOAD_NOTALLOWEDFILENAME = 'Soubor obsahuje nepovolene znaky [5]';
static $ERROR_UPLOAD_NOTSAVED = 'Chyba při ukládání souboru [6]';
static $MESSAGE_UPLOAD_SUCCESS = 'Uspesne nahrano';

static $ERROR_RULES_CREATE = 'Nemate dostatecna prava pro pridavani';
static $ERROR_CAPTCHA = 'Opiste spravne kontrolni kod';
static $ERROR_GALERY_DIREMPTY = 'Nezadali jste adresar';
static $ERROR_GALERY_DIRWRONG = 'Zadali jste neplatne jmeno adresar, jsou povolene znaky pouze / - _ 0-9 a-z A-Z';
static $ERROR_GALERY_NOFOTO = 'Bohuzel galerie zatim neobsahuje fotografie';

static $LABEL_SEARCH = 'Vyhledavani';
static $LABEL_SEARCH_ITEMS_FORUMS = 'Hledani v prispevcich';
static $LABEL_SEARCH_ITEMS_BLOGS = 'Hledani v clancich';
static $LABEL_SEARCH_ITEMS_EVENTS = 'Hledani v tipech';
static $ERROR_SEARCH_TOSHORT = 'Zadaný řetězec pro vyhledávání je příliš krátký';
static $LABEL_FOUND = 'Nalezeno';
static $LABEL_SEARCH_RESULTS_JUSTLISTED = 'Zobrazeno bude pouze';

static $LABEL_POLL_RESULTS = 'Výsledky ankety';
static $MESSAGE_POLL_NOTPUBLIC = 'Výsledky ankety nejsou verejné';
static $MESSAGE_POLL_NORESULTS = 'Nejsou k dispozici zadne vysledku anket.';
static $MESSAGE_POLL_RESULTSRESOURCE = 'Odsud mužete zkopírovat výsledky ankety do klubu:';
static $MESSAGE_POLL_NOANSWERS = 'Pro tuto odpoved nikdo nehlasoval';

static $TEXT_PERMISSIONS_SET = 'Nastavení přístupových práv';

static $ERROR_BANNER_TARGETEMPTY = 'Nezadali jste cilovou adresu banneru';
static $ERROR_BANNER_EMPTY = 'Nezadali jste banner';

static $PERSONAL_FOTO_FOLDER_FULL = 'Vas osobni adresar na fotky je plny';

//---category
static $LABEL_CATEGORY_NAME = 'Nazev';
static $LABEL_CATEGORY_DESCRIPTION = 'Popis';
static $LABEL_CATEGORY_FUNCTION = 'Funkce';
static $LABEL_CATEGORY_GROUP = 'Skupina';
static $LABEL_CATEGORY_ORDER = 'Poradi';
static $LABEL_CATEGORY_PUBLIC = 'Verejne';
static $LABEL_CATEGORY_DELETE = 'Smazat';

static $LABEL_PAGES_DEFAULTFORALL = 'Default pro vsechny stranky';
static $LABEL_PAGES_USEDON = 'Pouzito na strankach';

static $ARRPUBLIC = array("0"=>"Soukromé","1"=>"Veřejné","2"=>"Pro registrované");
static $ARRPERMISSIONS = array(0=>'Nemá přístup',1=>'Uživatelé',2=>'Administrátoři');
static $ARRLOCKED = array(1=>'Zamčeny - mozno číst',2=>'Zamčený, skrytý',3=>'Smazaný - nevidí ani majitel');
static $ARRWHERESEARCHLABELS = array(0=>"-- Kde --",1=>"Vložil",2=>"Text",3=>"Datum");
static $DAYSSHORT=array("1"=>"Po","2"=>"Út","3"=>"St","4"=>"Ct","5"=>"Pá","6"=>"So","7"=>"Ne");
static $MONTHS=array("01"=>"Leden","02"=>"Únor","03"=>"Brezen","04"=>"Duben","05"=>"Kveten","06"=>"Cerven","07"=>"Cervenec","08"=>"Srpen","09"=>"Zárí","10"=>"Ríjen","11"=>"Listopad","12"=>"Prosinec");
static $TYPEID = array('galery'=>'Galerie', 'forum'=>'Klub', 'blog'=>'Blog', 'culture'=>'Stranka');
}