<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.12.2018
 * Time: 6:47
 */
//include "maskRU.php";
include "maskWorld.php";
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
function getMail($html)
{
    preg_match_all('/(mailto)(:).*?(")(>)/is', $html, $matches);
    $result = implode('',$matches[0]);
    $result = stristr($result,':');
    $result = stristr($result,'">',true);
    $result = str_replace('"', '', $result);
    $result = str_replace(':', '', $result);
    $result = urldecode($result);
    if(strlen($result) > 50) return '';
    return $result;
}
function getLnFn($html)
{
    preg_match_all('/(title).*?(<)/is', $html, $matches);
    $result = str_replace('<', '', $matches[0][0]);
    $result = str_replace('title>', '', $result);
    $result = str_replace('title id="pageTitle">','', $result);
    $result = preg_replace("/[^a-zA-ZА-Яа-я0-9\s]/","",$result);
    $arrResult = explode(' ', $result);
    return ['ln' => $arrResult[0], 'fn' => $arrResult[1], 'lnfn' => $result];
}
function getCityNew($html)
{
    preg_match_all('/(u_0_0).*?(Facebook)/is', $html, $matches);
	preg_match_all('/(href).*?(a)/is', $matches[0][0], $result); 
	$result = $result[0][0];
	$result = str_replace('</a', '', $result);
	$result = getArrHtml($result);
	if(strlen($result[1]) > 50) return '';
	return $result[1];
}
function getCityOld($html)
{	
	preg_match_all('/(u_0_1).*?(Facebook)/is', $html, $matches);
	preg_match_all('/(href).*?(a)/is', $matches[0][0], $result); 
	$result = $result[0][3];
	$result = str_replace('</a', '', $result);
	$result = getArrHtml($result);
	if(strlen($result[1]) > 50) return '';
    return $result[1];
}
function randomScroll($fromMs, $toMs)
{
    global $driver;
    $sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
    usleep($sleep);
    $randomScroll = rand(0, 1000);
    $driver->executeScript('window.scrollTo(0, ' . $randomScroll . ')');
    $sleep = rand($fromMs, $toMs); //случайная пауза между переходами по страницам
    usleep($sleep);
}
