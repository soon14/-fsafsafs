<?php

    $userURL        = $_POST['instagram_addres'];
    $loginInstagram = $_POST['instagram_login'];
    $passInstagram  = $_POST['instagram_password'];
    $loginFacebook  = $_POST['facebook_login'];
    $passFacebook   = $_POST['facebook_password'];

    $createNewTable = true;
    $betweenWriting = 300;  //сколько секунд крутим до следующей записи 600 defolt
    $totalExceptions = 1000; //количество исключений после которого стоп прокрутка
    $continueScrolling = false;
    $indexWrite = 0;        //стартовый индекс для записи в бд
    $fromMs        = 30500000;
    $toMs          = 33000000;
    $localRepository = 'https://selenoid.useid.pro/';
    $admin = 'admin', $adminPass = '';
