<?php

    $userURL        = $_GET['instagram_addres'];
    $loginInstagram = $_GET['instagram_login'];
    $passInstagram  = $_GET['instagram_password'];
    $loginFacebook  = $_GET['facebook_login'];
    $passFacebook   = $_GET['facebook_password'];

    $createNewTable = false;
    $betweenWriting = 60;   //сколько секунд крутим список до анализа и записи 600 defolt
    $totalExceptions = 500; //количество исключений после которого стоп прокрутка
    $continueScrolling = false;
    $indexWrite = 0;        //стартовый индекс для записи в бд
