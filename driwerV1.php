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
include "parsing.php";
include "write_db.php";

$urlFacebook = 'https://www.facebook.com/';
$urlInstagram = 'https://www.instagram.com/';


if($createNewTable === true) {
    createTable(tableName($userURL));
}
if($_POST['Checkbox1'] == 1) {
    printFromBase($userURL); exit();
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
    $desired_capabilities->setVersion("71.0");
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
    echo '<br/>';
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
        sleep(7);
        $i = 0;
    } catch (Exception $e) {
        if ($i >= $totalExceptions) {
            break;
        }
        $i++;
    }
}
workWithHtml();
//$driver->switchTo()->window($newTab);  //переключаемся на новую вкладку
$countRecords = countRecords('instagram'.tableName($userURL)); echo $countRecords; echo '<br/>'; echo '<br/>';
//printResult($arrayResult, true);
startBots($parseArr, $localRepository);

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
    writeDbArray(1, $link, $parseArr, 'instagram'.tableName($userURL), $shiftArray);
    $shiftArray = $i + 1;
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

function printResult($arr, $printHat)
{
    if($printHat === true) $htmlPrint = 'fn,ln,dob,phone,email,ct'.'<br/>';
    foreach($arr as $key => $element)
    {
        $email = ''; $phone = ''; $birthday = '';
        if(strlen($element['ln']) > 1 && strlen($element['fn']) > 1) {
            if (strlen($element['email']) > 0 || strlen($element['phone']) > 0 || (strlen($element['birthday']) > 6 && (2019 - substr($element['birthday'], -4) >= 18)) || strlen($element['CityNew']) > 1) {
                if (strlen($element['email']) > 0) {
                    $email = $element['email'];
                }
                if (strlen($element['phone']) > 0) {
                    $phone = $element['phone'];
                }
                if (strlen($element['birthday']) > 6 && (2019 - substr($element['birthday'], -4) >= 18)) {
                    $birthday = $element['birthday'];
                }
		if(strlen($element['CityNew']) > 1) {
		    $CityNew = $element['CityNew'];
		}
                $htmlPrint .= $element['ln'] . ',' . $element['fn'] . ',' . $birthday . ',' . $phone . ',' . $email . ',' . $CityNew.'<br/><br/>';
            }
        }
    }
    echo $htmlPrint;
}
function fetchData($url, $arr)
{
    global $admin, $adminPass; 	
    $ch = curl_init($url.'bot.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/4.0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_USERPWD, $admin.":".$adminPass);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 60000);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr));
    $result = curl_exec($ch);
    print_r(curl_getinfo($ch)); echo '<br/>'; print_r(curl_error($ch)); 
    curl_close($ch);
    return $result;
}

function printFromBase($URL)
{
    $slink = connectDb();
    $arrRes = readDb('*', 'facebook'.tableName($URL), $slink);
    printResult($arrRes, true);
}
function startBots($arr, $localRepository)
{
    global $userURL; echo '<br/>'; print_r(count($arr)); echo '<br/>';
    $totalAccount = 0;
    $auth = [];
    $index = 0;
    for($i = 1; $i <= 5; $i++){
        if(!empty($_POST['facebook_login'.$i]) && !empty($_POST['facebook_password'.$i])){
            $auth[$index]['login'] = $_POST['facebook_login'.$i];
            $auth[$index]['password'] = $_POST['facebook_password'.$i];
            $totalAccount++;
            $index++;
        }
    }
    if ($totalAccount > 0) {
        $arrParts = array_chunk($arr, (count($arr) / $totalAccount));
        for ($i = 0; $i < $totalAccount; $i++) {
            $arrParts[$i]['login'] = $auth[$i]['login'];
            $arrParts[$i]['password'] = $auth[$i]['password'];
	    $arrParts[$i]['instagram_addres'] = $userURL;
            fetchData($localRepository,$arrParts[$i]);
        }
    }
}
