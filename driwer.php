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
ini_set('display_errors', 0);
ini_set('error_reporting', 0);
//error_reporting(E_ALL);

require_once('autoload.php');
include "input.php";
include "write_db.php";


$urlFacebook = 'https://www.facebook.com/';
$urlInstagram = 'https://www.instagram.com/';

if ($createNewTable === true) {
    createTable($userURL);
}

$indexWrite = 0; //добавил

if ($continueScrolling === false)
{
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

    $element = $driver->findElements(WebDriverBy::tagName("li"));
    $element[1]->click();                                   //нажимаем на подписчиков
    sleep(3);

    $i = 0;
    ////$driver->executeScript('window.open(arguments[0])');   //открываем js новую вкладку
}

///$driver = RemoteWebDriver::createBySessionID($SessionID, 'http://localhost:4444/wd/hub');
///$driver->manage()->timeouts()->pageLoadTimeout(10000);
//$driver->manage()->timeouts()->implicitlyWait(0);
$handles = []; $timeHis = 0;
////$handles = $driver->getWindowHandles();                //получаем список вкладок
////$newTab = $handles[1]; $mainTab = $handles[0];
////$driver->switchTo()->window($mainTab);
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
        $i = 0;
    } catch (Exception $e) {
        //$flagfind = false;
        if ($i >= $totalExceptions) {
            break;
        }
        $i++;
    }
}
workWithHtml($indexWrite, $handles);
createTxt($userURL);

function workWithHtml($shiftArray, $handles)
{
    global $driver;
    global $urlFacebook;
    global $urlInstagram;
    global $userURL;
    global $indexWrite;
    global $timeHis;
    ////$newTab = $handles[1]; $mainTab = $handles[0];

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
        //$name = str_ireplace("'",'',$name);

        $parseArr[$i]['loginUser'] = $loginUser;
        $parseArr[$i]['userName'] = $name;
        $parseArr[$i]['fotoLink'] = $foto;
        $i++;
    }
    //____________________ связываем фейсбук с инстаграммом ______________________

    $arrayResult = [];
    ////$driver->switchTo()->window($newTab);  //переключаемся на новую вкладку
    $Arr = new LimitIterator(new ArrayIterator($parseArr), $shiftArray);
    if (count($parseArr) - $shiftArray > 1) {
        foreach ($Arr as $key => $element) {
            $instagramLink = $urlInstagram . $element['loginUser'];
            $facebookLink = $urlFacebook . $element['loginUser'];

            $parseArr[$key]['instagramLink'] = $instagramLink;        //добаляем ссылку на инстаграм в массив
            $parseArr[$key]['facebookLink'] = ' ';                    //по умолчанию
            $parseArr[$key]['facebookUID'] = NULL;                    //по умолчанию

            //_____ не используя curl _____
            /*$dr = @$driver->get($facebookLink);                        //загружаем фейсбук
            usleep(200000);
            if(is_object($dr)) {
                $htmlFb = $driver->getPageSource();                 //получаем код страницы фб
            }*/
            //______ используем curl ______
            $htmlFb = @fetchData($facebookLink);

            $texeErr = 'недоступ';                              //эта часть текста есть в случаи ошибки
            //____________________
            $explodeName = explode(" ", $element['userName']);
            $word = $explodeName[0]; //первая часть имени до пробела

            $match4 = [];
            preg_match_all('/(meta)( )(property).*?(>)/is', $htmlFb, $match4);
            if (!empty($match4[0][2])) {
                $parseArr[$key]['facebookUID'] = preg_replace("/[^,.0-9]/", '', $match4[0][2]);
            }
            //____________________
            if (///!empty($htmlFb) && stristr($htmlFb, $texeErr, 0) === false  //проверяем нет ли ошибки добавить @
                !empty($word) && stristr($htmlFb, $word,0) !== false && strlen($word) > 0 //проверяем есть ли имя на странице в фб
                && !empty($match4[0][2]))  //доп проверка, найден ли uid
            { //ищем на странице имя пользователя

                $parseArr[$key]['facebookLink'] = $facebookLink;
            }

            //сохранить ключь
            $indexWrite = $key + 1;
            $arrayResult[$key] = $parseArr[$key];
        }
        $link = connectDb();
        writeDbArray($link, $arrayResult, $userURL);
    }

    $timeHis = time(); //добавил

    ////$driver->switchTo()->window($mainTab); //переключаемся на старую вкладку
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

function fetchData($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/4.0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
