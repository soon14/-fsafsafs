<?php

    $userURL        = $_POST['instagram_addres'];
    $loginInstagram = $_POST['instagram_login'];
    $passInstagram  = $_POST['instagram_password'];
    $loginFacebook  = $_POST['facebook_login'];
    $passFacebook   = $_POST['facebook_password'];

    $createNewTable = true;
    $betweenWriting = 300;   //сколько секунд крутим список до записи 
    $totalExceptions = 1000; //количество исключений после которого стоп прокрутка
    $continueScrolling = false;
    $shiftArray    = 0;
    $fromMs        = 0; //2000000;
    $toMs          = 0; //3000000;
