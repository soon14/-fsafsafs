<?php

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
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding("UTF-8");
require_once('autoload.php');
include "input.php";
include "write_db.php";
include "parsing.php";
$testTime = time();
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

if (!empty($_POST['login']) && !empty($_POST['password'])) { //если введены данные фейсбука
    loginFb($_POST['login'], $_POST['password']);
}
//file_put_contents("test.log", "start\n", FILE_APPEND);
function shutDownFunction() { 
    chdir(dirname(__FILE__));
    $error = error_get_last();
    //file_put_contents("test.log",implode(",",$error)."|11|\n", FILE_APPEND);
}
//register_shutdown_function('shutDownFunction');
$parseArr = $_POST;

getFbInfo($handles, $fromMs, $toMs);
	//file_put_contents("test.log", "final step one\n", FILE_APPEND);
getFbInfo2($fromMs, $toMs, $_POST);
	//file_put_contents("test.log", "final step two\n", FILE_APPEND);
function getFbInfo($handles, $fromMs, $toMs)
{   global $testTime;
    global $driver;
    global $userURL;
    global $parseArr;
    global $betweenWriting;
    global $arrayResult;
    $urlGetFacebook = 'https://m.facebook.com/';
    //$newTab = $handles[1];
    //$mainTab = $handles[0];
    $shiftArray = 0;
    $shift = 0;
    $noPage = 'Страница';
    $noPage2 = 'Содержание';
    $noPage3 = 'Facebook';
    //____________________________________________________________________________
//file_put_contents("test.log",implode(",",error_get_last())."|1|\n", FILE_APPEND);
    $Arr = new LimitIterator(new ArrayIterator($parseArr), $shiftArray);
    //$testString = '';
    if (count($parseArr) - $shiftArray > 1) {
        foreach ($Arr as $key => $element) {
            $resultUrl = $urlGetFacebook . $element['loginUser'].'/about?section=contact-info';
            $driver->get($resultUrl);
//file_put_contents("test.log",implode(",",error_get_last())."|2|\n", FILE_APPEND);
			randomScroll(1500000, 1600000);
            $htmlFb = $driver->getPageSource();
            $lnfn = getLnFn($htmlFb);
//file_put_contents("test.log",implode(",",error_get_last())."|3|\n", FILE_APPEND);
            $arrNameInstagram = explode(' ', $element['userName']);
//file_put_contents("test.log",implode(",",error_get_last())."|4|\n", FILE_APPEND);		
            if (!Empty($lnfn['ln']) && stristr($lnfn['ln'], $noPage2, 0) !== false) {
                $resultUrl = $urlGetFacebook . $element['loginUser'];
                $driver->get($resultUrl);

                $htmlFb = $driver->getPageSource();
                $lnfn = getLnFn($htmlFb);
            }
echo '<br/>'; file_put_contents("test.log",implode(",",mb_detect_encoding($lnfn['ln']), FILE_APPEND); echo '<br/>'; 		
//file_put_contents("test.log",implode(",",error_get_last())."|".(time() - $testTime)."~|\n", FILE_APPEND);
            if ((stristr($lnfn['ln'], $noPage, 0) === false && stristr($lnfn['fn'], $noPage, 0) === false) &&
                (stristr($lnfn['ln'], $noPage2, 0) === false && stristr($lnfn['fn'], $noPage, 0) === false) &&
                (stristr($lnfn['ln'], $noPage3, 0) === false && stristr($lnfn['fn'], $noPage, 0) === false)){

                $ArrHtml = getArrHtml($htmlFb);
                $digital = getOnlyDigital($ArrHtml, 9, 15);
                $ArrName = getLnFn($htmlFb);
//file_put_contents("test.log",implode(",",error_get_last())."|7|\n", FILE_APPEND);

                $arrayResult[$shift]['url'] = $resultUrl;
                $parseArr[$key]['facebookLink'] = $resultUrl;
                $arrayResult[$shift]['ln'] = $ArrName['ln'];
                $arrayResult[$shift]['fn'] = $ArrName['fn'];
                $arrayResult[$shift]['phone'] = $digital[0];
                $arrayResult[$shift]['email'] = getMail($htmlFb);
                $arrayResult[$shift]['birthday'] = getBirthday($htmlFb);
		$arrayResult[$shift]['citynew'] = getCityNew($htmlFb);
		$arrayResult[$shift]['cityold'] = getCityOld($htmlFb);
                $shift++;
//file_put_contents("test.log",implode(",",error_get_last())."|8|\n", FILE_APPEND);		    
                if (time() - $timeHis > $betweenWriting) {
//file_put_contents("test.log",implode(",",error_get_last())."|81|\n", FILE_APPEND);				
                    if ($timeHis > 0) {
//file_put_contents("test.log",implode(",",error_get_last())."|82|\n", FILE_APPEND);			    
                        //передическая запись в бд
                        $link = connectDb(); writeDbArray(2, $link, $arrayResult, 'facebook'.tableName($userURL), $shiftArray); 
//file_put_contents("test.log",implode(",",error_get_last())."|9|\n", FILE_APPEND);
			//createTxt('test', mysqli_error($link));    
			//echo mysqli_error($link);    
			mysqli_close($link);
                        $shiftArray = $shift + 1;
                    }
                    $timeHis = time();
                }

            }
        }
    }
    $link = connectDb(); writeDbArray(2, $link, $arrayResult, 'facebook'.tableName($userURL), $shiftArray); 
//file_put_contents("test.log",implode(",",error_get_last())."|10|\n", FILE_APPEND);
	//echo mysqli_error($link);   
	//createTxt('test', mysqli_error($link));  
	mysqli_close($link);
}
function getFbInfo2($fromMs, $toMs, $parseArr)
{
    global $driver;
    global $userURL;
    global $betweenWriting;

    $urlFacebook = 'https://www.facebook.com/';
    $urlGetFacebook = 'https://m.facebook.com/';

    $shiftArray = 0;
    $shift = 0;
    //____________________________________________________________________________
    $Arr = new LimitIterator(new ArrayIterator($parseArr), $shiftArray);
    //$testString = '';
    if (count($parseArr) - $shiftArray > 1) {
        foreach ($Arr as $key => $element) {
            if(Empty($element['facebookLink'])){
                $explodeName = explode(" ", $element['userName']);
                $ln = $explodeName[0];
                $fn = $explodeName[1];
                if (!Empty($ln) && !Empty($fn) && strlen($ln) > 0 && strlen($ln) > 0) {
                    $driver->get($urlFacebook . 'search/str/' . $ln . '+' . $fn . '/keywords_users?epa=SEE_MORE');

                    randomScroll($fromMs, $toMs);

                    $elements = $driver->getPageSource();
                    $resultArr = getFindPioples($elements);
                    $texeErr = 'Запрашиваемая вами страница недоступна';
                    foreach ($resultArr as $key2 => $element2) {
                        if (((stristr(mb_strtolower($element2['ln']), mb_strtolower($ln)) !== false && stristr(mb_strtolower($element2['fn']), mb_strtolower($fn)) !== false) ||
                                (stristr(mb_strtolower($element2['ln']), mb_strtolower($fn)) !== false && stristr(mb_strtolower($element2['fn']), mb_strtolower($ln)) !== false))
                            && !Empty($element2['url']) && strlen($element2['url']) > 0) {
                            $resultUrl = $urlGetFacebook . $element2['url'] . '/about?section=contact-info';
                            $driver->get($resultUrl);
                            //$sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
                            //usleep($sleep);
                            $htmlFb = $driver->getPageSource();
                            if (!empty($htmlFb) && stristr($htmlFb, $texeErr, 0) !== false) {
                                $resultUrl = $urlGetFacebook . $element2['url'];
                                $driver->get($resultUrl);
                                //$sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
                                //usleep($sleep);
                            }
                            $htmlFb = $driver->getPageSource();
                            $ArrHtml = getArrHtml($htmlFb);
                            $digital = getOnlyDigital($ArrHtml, 9, 15);
                            $arrayResult2[$shift]['url'] = $resultUrl;
                            $arrayResult2[$shift]['ln'] = $element2['ln'];
                            $arrayResult2[$shift]['fn'] = $element2['fn'];
                            $arrayResult2[$shift]['phone'] = $digital[0];
                            $arrayResult2[$shift]['email'] = getMail($htmlFb);
                            $arrayResult2[$shift]['birthday'] = getBirthday($htmlFb);
			    $arrayResult2[$shift]['citynew'] = getCityNew($htmlFb);
			    $arrayResult2[$shift]['cityold'] = getCityOld($htmlFb);
                            $shift++;

                            if (time() - $timeHis > $betweenWriting) {
                                if ($timeHis > 0) {
                                    //передическая запись в бд
                                    $link = connectDb(); writeDbArray(2, $link, $arrayResult2, 'facebook'.tableName($userURL), $shiftArray); mysqli_close($link);
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
    $link = connectDb(); writeDbArray(2, $link, $arrayResult2, 'facebook'.tableName($userURL), $shiftArray); mysqli_close($link);
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

