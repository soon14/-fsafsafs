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
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);
//ini_set('error_reporting', 0);
error_reporting(E_ALL);

require_once('autoload.php');
include "input.php";
include "write_db.php";


$urlFacebook = 'https://www.facebook.com/';
$urlInstagram = 'https://www.instagram.com/';

//session_start();

if ($createNewTable === true) {
    createTable($userURL);
}

$indexWrite = 0;
if ($continueScrolling === true) { //нужно что бы при падении скрипта подхватить предыдущий сеанс
    if (isset($_SESSION[$userURL . '_indexWrite']) && !empty($_SESSION[$userURL . '_indexWrite'])) {
        $indexWrite = $_SESSION[$userURL . '_indexWrite'];
    }
    if (isset($_SESSION[$userURL . '_SessionID']) && !empty($_SESSION[$userURL . '_SessionID'])) {
        $SessionID = $_SESSION[$userURL . '_SessionID'];
    }
}
if ($continueScrolling === false || empty($_SESSION[$userURL . '_SessionID'])) {
    // Задаем хост на котором запущен Selenium (localhost - если же на этом компьютере) и номер порта (4444 - порт по умолчанию, если мы не задали другой)
    $host = 'http://localhost:4444/wd/hub';
    $chromeOptions = new ChromeOptions();
    $arguments = ["--user-agent=Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)"];

    $chromeOptions->addArguments($arguments);
    $chromeOptions->addExtensions(['Block-image_v1.1.crx']); //плагин блокирующий загрузку изображений
    $chromeOptions->addArguments(['--no-sandbox']);
    
    $desired_capabilities = DesiredCapabilities::chrome();
    $desired_capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
    $driver = RemoteWebDriver::create($host, $desired_capabilities, 1000000000, 1000000000);


    loginInstagram($loginInstagram, $passInstagram);
    sleep(3);
    $driver->get($userURL);                                            //загружаем страницу инстаграм
    $SessionID = $driver->getSessionID();
    echo $SessionID;
    echo '<br/>';
    $_SESSION[$userURL . '_SessionID'] = $SessionID;


    $element = $driver->findElements(WebDriverBy::tagName("li"));
    $element[1]->click();                                               //нажимаем на подписчиков
    sleep(3);

    $i = 0;
    $driver->executeScript('window.open(arguments[0])');         //открываем js новую вкладку
}


$driver = RemoteWebDriver::createBySessionID($SessionID, 'http://localhost:4444/wd/hub');

$handles = $driver->getWindowHandles();                //получаем список вкладок
$newTab = $handles[1];
$mainTab = $handles[0];
$driver->switchTo()->window($mainTab);
//___________________________ скролим список ______________________________
while (true) {
    if (time() - $timeHis > $betweenWriting) {
        if ($timeHis > 0) {
            workWithHtml($indexWrite, $handles);
            //break;
        }
        $timeHis = time();
    }

    try {
        $element = $driver->findElement(WebDriverBy::className("oMwYe"));
        $element->getLocationOnScreenOnceScrolledIntoView();
        sleep(4);
    } catch (Exception $e) {

        if ($i >= $totalExceptions) {
            break;
        }
        $i++;
        echo ' ' . $i . ' ';
    }
}
workWithHtml($indexWrite, $handles);

function workWithHtml($shiftArray, $handles)
{
    global $driver;
    global $urlFacebook;
    global $urlInstagram;
    global $userURL;
    global $indexWrite;
    global $timeHis;
    $newTab = $handles[1];
    $mainTab = $handles[0];

    //____________________ Получаем и изучаем итоговую html _____________________
    $html = $driver->getPageSource();                       //получаем код страницы
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
        $name = @json_decode($name, true);
        $name = preg_replace('/[^ a-zа-яё\d]/ui', '', $name);

        $parseArr[$i]['loginUser'] = $loginUser;
        $parseArr[$i]['userName'] = $name;
        $parseArr[$i]['fotoLink'] = $foto;
        $i++;
    }

    //____________________ связываем фейсбук с инстаграммом ______________________
    $arrayResult = [];
    $driver->switchTo()->window($newTab);  //переключаемся на новую вкладку
    $Arr = new LimitIterator(new ArrayIterator($parseArr), $shiftArray);
    if (count($parseArr) - $shiftArray > 1) {
        foreach ($Arr as $key => $element) {
            $instagramLink = $urlInstagram . $element['loginUser'];
            $facebookLink = $urlFacebook . $element['loginUser'];

            $parseArr[$key]['instagramLink'] = $instagramLink;       //добаляем ссылку на инстаграм в массив
            $parseArr[$key]['facebookLink'] = ' ';                   //по умолчанию

            $driver->get($facebookLink);                            //загружаем фейсбук
            usleep(200000);
            $htmlFb = $driver->getPageSource();                     //получаем код страницы фб

            $texeErr = 'недоступ';                                  //эта часть текста есть в случаи ошибки
            //____________________
            $explodeName = explode(" ", $element['userName']);
            $word = $explodeName[0]; //первая часть имени до пробела

            //____________________
            if (!empty($htmlFb) && stristr($htmlFb, $texeErr, 0) === false  //проверяем нет ли ошибки
                && !empty($word) && stristr($htmlFb, $word, 0) !== false && strlen($word) > 0) //проверяем есть ли имя на странице в фб
            { //ищем на странице имя пользователя

                $parseArr[$key]['facebookLink'] = $facebookLink;
            }
            $indexWrite = $key + 1;
            $arrayResult[$key] = $parseArr[$key];
        }
        $link = connectDb();
        writeDbArray($link, $arrayResult, $userURL);
    }

    $_SESSION[$userURL . '_indexWrite'] = $indexWrite;
    $timeHis = time();
    $driver->switchTo()->window($mainTab); //переключаемся на старую вкладку
}

function loginInstagram($loginInstagram, $passInstagram)
{
    global $driver;
    $urlLogin = "https://www.instagram.com/accounts/login/";
    $driver->get($urlLogin);
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
    $driver->get($urlLogin);
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($loginFacebook);
    $driver->findElement(WebDriverBy::id('pass'))->sendKeys($passFacebook);
    $driver->findElement(WebDriverBy::id('loginbutton'))->click();
}

