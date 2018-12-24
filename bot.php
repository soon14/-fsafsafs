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
require_once('autoload.php');
include "input.php";
include "write_db.php";
include "parsing.php";

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

if (!empty($_POST['login']) && !empty($_POST['password'])) { //если введены данные фейсбука
    loginFb($_POST['login'], $_POST['password']);
}

getFbInfo2($fromMs, $toMs, $_POST);

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
                            $shift++;

                            if (time() - $timeHis > $betweenWriting) {
                                if ($timeHis > 0) {
                                    //передическая запись в бд
                                    $link = connectDb(); writeDbArray(2, $link, $arrayResult2, 'facebook'.tableName($userURL), $shiftArray);
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
    $link = connectDb(); writeDbArray(2, $link, $arrayResult2, 'facebook'.tableName($userURL), $shiftArray);
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