<?php
// Указываем пространство имен
namespace Facebook\WebDriver;
// Указываем какие классы будут использоватся
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use LimitIterator;
use ArrayIterator;
use Exception;
//Пподключаем автолоадер классов
set_time_limit(0);
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 0);
//ini_set('display_errors', 0);
//ini_set('error_reporting', 0);
error_reporting(E_ALL & ~E_NOTICE);
require_once('autoload.php');
include "input.php";
include "write_db.php";
//include "maskRU.php";
include "maskWorld.php";
$urlFacebook = 'https://www.facebook.com/';
$urlInstagram = 'https://www.instagram.com/';
if ($createNewTable === true) {
    createTable($userURL);
}
$arrayResult = [];
$shiftArray = 0; 
if ($continueScrolling === false) {
    // Задаем хост на котором запущен Selenium (localhost - если же на этом компьютере) и номер порта (4444 - порт по умолчанию, если мы не задали другой)
    $host = 'http://localhost:4444/wd/hub';
    $chromeOptions = new ChromeOptions();
    $chromeOptions->addExtensions(['Block-image_v1.1.crx']); //плагин блокирующий загрузку изображений
    $chromeOptions->addArguments(['--no-sandbox']);
    $desired_capabilities = DesiredCapabilities::chrome();
    $desired_capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
    $desired_capabilities->setCapability("browserName","chrome");
    $desired_capabilities->setCapability("enableVNC", true);
    $desired_capabilities->setVersion("70.0");
    $desired_capabilities->setCapability("binary","/usr/bin/chrome");
    $driver = RemoteWebDriver::create($host, $desired_capabilities);
    $driver->manage()->window()->setSize(new WebDriverDimension(1280, 1024));
    
    if (!empty($loginFacebook) && !empty($passFacebook)) { //если введены данные фейсбука
        loginFb($loginFacebook, $passFacebook);
    }
    loginInstagram($loginInstagram, $passInstagram);
    sleep(3);
    @$driver->get($userURL);                                 //загружаем страницу инстаграм
    $SessionID = $driver->getSessionID();
    echo $SessionID;
    echo '<br/>'; echo '<br/>';
    $element = $driver->findElements(WebDriverBy::tagName("li"));
    $element[1]->click();                                   //нажимаем на подписчиков
    sleep(3);
    $i = 0;
    $driver->executeScript('window.open(arguments[0])');   //открываем js новую вкладку
} else {
    $driver = RemoteWebDriver::createBySessionID($SessionID, 'http://localhost:4444/wd/hub');
}
$handles = [];
$timeHis = 0;
$handles = $driver->getWindowHandles();                //получаем список вкладок
$newTab = $handles[1];
$mainTab = $handles[0];
$driver->switchTo()->window($mainTab);
//___________________________ скролим список ______________________________
while (true) {
    if (time() - $timeHis > $betweenWriting) {
        if ($timeHis > 0) {
            workWithHtml();
            //break;
        }
        $timeHis = time();
    }
    try {
        $element = $driver->findElement(WebDriverBy::className("oMwYe"));
        $element->getLocationOnScreenOnceScrolledIntoView();
        sleep(4);
        $i = 0;
    } catch (Exception $e) {
        //$flagfind = false;
        if ($i >= $totalExceptions) {
            break;
        }
        $i++;
    }
}
workWithHtml();
getFbInfo($shiftArray, $handles, $fromMs, $toMs);
printResult($arrayResult);
//createTxt($userURL); //исправить
function workWithHtml()
{
    global $driver;
    global $parseArr;
    global $urlInstagram;
    global $userURL;
    global $shiftArray;
    //____________________ Получаем и изучаем итоговую html _____________________
    $html = @$driver->getPageSource();                       //получаем код страницы
    preg_match_all('/(<)(li)( )(class)(=)("wo9IH")(>).*?(<\/li>)/', $html, $match);
    $match = $match[0];                                     //Получаем массив с подписчиками
    $i = 0;
    $parseArr = [];
    foreach ($match as $key => $element) {
        preg_match('/(src)(=)(").*?(")( )(alt)/', $element, $match1);
        preg_match('/(href)(=)(").*?(")(>)/', $element, $match2);
        preg_match('/(")(wFPL8)( )(")(>).*?(<)(\/div)/', $element, $match3);
        $foto = str_ireplace('src="', '', $match1[0]);
        $foto = str_ireplace('" alt', '', $foto);
        $loginUser = str_ireplace('href="/', '', $match2[0]);
        $loginUser = str_ireplace('/" style="width: 30px; height: 30px;">', '', $loginUser);
        $loginUser = str_ireplace('/">', '', $loginUser);
        $name = str_ireplace('"wFPL8 ">', '', $match3[0]);
        $name = @json_encode($name);
        $name = str_ireplace("<\/div", '', $name);
        $name = @json_decode($name, true); //перенести эту строку
        $name = preg_replace('/[^ a-zа-яё\d]/ui', '', $name);
        //$name = str_ireplace("'",'',$name); explode('>', $html);
        $parseArr[$i]['loginUser'] = $loginUser;
        $parseArr[$i]['userName'] = $name;
        $parseArr[$i]['fotoLink'] = $foto;
        $parseArr[$i]['instagramLink'] = $urlInstagram . $loginUser;
        $i++;
    }
    $link = connectDb();
    writeDbArray(1, $link, $parseArr, $userURL, $shiftArray);
    $shiftArray = $i + 1;
}
function getFbInfo($shiftArray, $handles, $fromMs, $toMs)
{
    global $driver;
    global $urlFacebook;
    global $urlInstagram;
    global $userURL;
    global $parseArr;
    global $betweenWriting;
    global $arrayResult;
    $urlGetFacebook = 'https://m.facebook.com/';
    $newTab = $handles[1];
    $mainTab = $handles[0];
    $shiftArray = 0;
    $shift = 0;
    //____________________________________________________________________________
    $driver->switchTo()->window($newTab);  //переключаемся на новую вкладку
    $Arr = new LimitIterator(new ArrayIterator($parseArr), $shiftArray);
    //$testString = '';
    if (count($parseArr) - $shiftArray > 1) {
        foreach ($Arr as $key => $element) {
            $explodeName = explode(" ", $element['userName']);
            $ln = $explodeName[0];
            $fn = $explodeName[1];
            if(!Empty($ln) && !Empty($fn) && strlen($ln) > 0 && strlen($ln) > 0) {
                $driver->get($urlFacebook . 'search/str/'.$ln.'+'.$fn.'/keywords_users?epa=SEE_MORE');
                $sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
                usleep($sleep);
                $elements = $driver->getPageSource();
                $resultArr = getFindPioples($elements); //добавить функцию которая сравнивает имена
                $texeErr = 'Запрашиваемая вами страница недоступна';
                foreach($resultArr as $key2 => $element2)
                {
                    if(((mb_strtolower($element2['ln']) == mb_strtolower($ln) && mb_strtolower($element2['fn']) == mb_strtolower($fn)) ||
                            (mb_strtolower($element2['ln']) == mb_strtolower($fn) && mb_strtolower($element2['fn']) == mb_strtolower($ln)))
                        && !Empty($element2['url']) && strlen($element2['url']) > 0)
                    {
                        $resultUrl = $urlGetFacebook.$element2['url'].'/about?section=contact-info';
                        $driver->get($resultUrl);
                        $sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
                        usleep($sleep);
                        $htmlFb = $driver->getPageSource();
                        if (!empty($htmlFb) && stristr($htmlFb, $texeErr, 0) !== false) {
                            $resultUrl = 'https://www.facebook.com/'.$element2['url'].'/about?section=contact-info';
                            $driver->get($resultUrl);
                            $sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
                            usleep($sleep);
                        }
                        $htmlFb   = $driver->getPageSource();
                        $ArrHtml  = getArrHtml($htmlFb);
                        $digital  = getOnlyDigital($ArrHtml, 9, 15);
                        
                        //подумать над массивом
                        $arrayResult[$shift]['url'] = $resultUrl;
                        $arrayResult[$shift]['ln'] = $element2['ln'];
                        $arrayResult[$shift]['fn'] = $element2['fn'];
                        $arrayResult[$shift]['phone'] = $digital[0];
                        $arrayResult[$shift]['email'] = getMail($htmlFb);
                        $arrayResult[$shift]['birthday'] = getBirthday($htmlFb);
                        $shift++;
                        if (time() - $timeHis > $betweenWriting) {
                            if ($timeHis > 0) {
                                //передическая запись в бд
                                $link = connectDb();
                                writeDbArray(2, $link, $arrayResult, $userURL.'_followersfb', $shiftArray);
                                $shiftArray = $shift + 1;
                            }
                            $timeHis = time();
                        }
                    }
                }
            }
        }
    }
}
function loginInstagram($loginInstagram, $passInstagram)
{
    global $driver;
    $urlLogin = "https://www.instagram.com/accounts/login/";
    @$driver->get($urlLogin);
    sleep(5);
    $elements = $driver->findElements(WebDriverBy::className('_2hvTZ'));
    if (is_object($elements[0])) {
        $elements[0]->sendKeys($loginInstagram);
    }
    if (is_object($elements[1])) {
        $elements[1]->sendKeys($passInstagram);
    }
    $driver->findElement(WebDriverBy::className('L3NKy'))->click();
}
function loginFb($loginFacebook, $passFacebook)
{
    global $driver;
    $urlLogin = "https://ru-ru.facebook.com/login/";
    @$driver->get($urlLogin);
    sleep(5);
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($loginFacebook);
    $driver->findElement(WebDriverBy::id('pass'))->sendKeys($passFacebook);
    $driver->findElement(WebDriverBy::id('loginbutton'))->click();
}
function getUID($html, $key)
{
    preg_match_all('/(meta)( )(property).*?(>)/is', $html, $match4);
    if (!empty($match4[0][2])) {
        $parseArr[$key]['facebookUID'] = preg_replace("/[^,.0-9]/", '', $match4[0][2]);
    }
}
function getArrHtml($html)
{
    $Arr = explode('>', $html);
    return $Arr;
}
function getOnlyDigital($Arr, $shortNumber, $longNumber)
{
    $i = 0;
    $resultArr = [];
    foreach($Arr as $key => $element) {
        $result = preg_replace('/[^+0-9]/', '', $element);
        $result = str_replace('…', '', $result);
        if(!Empty($result) && strlen($result) >= $shortNumber && strlen($result) <= $longNumber)
        {
            $resultArr[$i] = $result;
            $i++;
        }
    }
    $i = 0;
    $arrValidateNumber = [];
    foreach($resultArr as $key => $element) {
        if(validateNumber($element)) {
            $arrValidateNumber[$i] = $element;
            $i++;
        }
    }
    return $arrValidateNumber;
}
function validateNumber($value)
{
    $result = false;
    //$maskRU = maskRU();
    $maskWorld = maskWorld();
    $masks = $maskWorld; //array_merge($maskRU , $maskWorld);
    foreach($masks as $key => $element) {
        if(stristr($value, $element['mask'], 0)  !== false ||
            stristr(substr($value, 0, 2),'89', 0) !== false) {
            $result = true;
            break;
        }
    }
    return $result;
}
function getFindPioples($html)
{
    $i = 0;
    $arrResult = [];
    preg_match_all("/(_)(3)(2)(mo).*?(a)(>)/is", $html, $matches);
    foreach($matches[0] as $key => $element){
        $result = str_replace('</span></a>', '', $element);
        $result = str_replace('_32mo" href="', '', $result);
        $result = str_replace('https://www.facebook.com/', '', $result);
        $result = str_replace('profile.php', '', $result);
        $arrResult[$i]['url'] = stristr($result, '?', true);
        $result = stristr($result, '>');
        $result = str_replace('<span>', '', $result);
        $result = str_replace('>', '', $result);
        $arrName = explode(' ', $result);
        $arrResult[$i]['ln'] = $arrName[0];
        $arrResult[$i]['fn'] = $arrName[1];
        $i++;
    }
    return $arrResult;
}
function getBirthday($html)
{
    preg_match_all('/(<)(div)( )(class).*?(div)(>)/is', $html, $match);
    $month = ['январ', 'феврал', 'март', 'апрел', 'мая', 'июн', 'июл', 'август', 'сентябр', 'октябр', 'ноябр', 'декабр'];
    foreach($match[0] as $key => $element){
        foreach($month as $key2 => $element2){
            if(stristr(mb_strtolower($element), mb_strtolower($element2), 0) !== false) { 
                $resultArr = explode(' ', $element);
                foreach($resultArr as $key3 => $element3){
                    if(stristr(mb_strtolower($element3), mb_strtolower($element2), 0) !== false) {
                        if(count($resultArr) > $key3 + 1) {
                            $result = preg_replace('/[^0-9]/', '',$resultArr[$key3 - 1]) . '/' . ($key2 + 1) . '/' . preg_replace('/[^0-9]/', '',$resultArr[$key3 + 1]);
                            return $result;
                        }
                        else{
                            $result = preg_replace('/[^0-9]/','', $resultArr[$key3 - 1]) . '/' . ($key2 + 1). '/';
                            return $result;
                        } 
                    }
                }
            }
        }
    }
    return '';
}
function getMail($html)
{
    preg_match_all('/(mailto)(:).*?(")(>)/is', $html, $matches);
    $result = implode('',$matches[0]);
    $result = stristr($result,':');
    $result = stristr($result,'">',true);
    $result = str_replace('"', '', $result);
    $result = str_replace(':', '', $result);
    $result = urldecode($result);
    return $result;
}
function printResult($arr)
{
    $htmlPrint = 'fn,ln,dob,phone,email'.'<br/>'; $birthday = ''; $phone = ''; $email = '';
    foreach($arr as $key => $element)
    {
        if(strlen($element['email']) > 0 || strlen($element['phone']) > 0 || strlen($element['birthday']) > 6)
        {
            if(strlen($element['email']) > 0)
            {
                $email = $element['email'];
            }
            if(strlen($element['phone']) > 0)
            {
                $phone = $element['phone'];
            }
            if(strlen($element['birthday']) > 6)
            {
                $birthday = $element['birthday'];
            }
            $htmlPrint .= $element['fn'].','.$element['ln'].','.$birthday.','.$phone.','.$email.'<br/>';
        }
    }
    echo $htmlPrint;
}
