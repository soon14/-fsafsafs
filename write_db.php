<?php
include 'dbConfiguration.php';

function connectDb()
{
    $link = @mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB)
    or die("Error: " . mysqli_error($link));
    if (!mysqli_set_charset($link, "utf8")) {
        printf("Error: " . mysqli_error($link));
    }
    return $link;
}

function writeDbArray($link, $array, $tableName)
{
    $query = "";

    foreach ($array as $key => $element) {
        //$columns = implode(", ", array_keys($element));
        //$escaped_values = array_map(array($link, 'real_escape_string'), array_values($element));
        //$values = implode(", ", array_values($element));

        $query = "INSERT INTO `".MYSQL_DB."`.`" . $tableName . "`(loginUser, userName, fotoLink, instagramLink, facebookLink, facebookUID) VALUES ('" . $element['loginUser'] . "', '" . $element['userName'] . "', '" . $element['fotoLink'] . "', '" . $element['instagramLink'] . "', '" . $element['facebookLink'] . "', '" .$element['facebookUID']."'); ";
        $result = @mysqli_query($link, $query);
    }

    return $result;
}

function createTable($tableName)
{
    $slink = connectDb();
    $tquery = @mysqli_query($slink, "SELECT COUNT(*) FROM `useid`.`" . $tableName . "`");
    if (!$tquery) {
        $query = "CREATE TABLE `".MYSQL_DB."`.`" . $tableName . "` ( `id` INT NOT NULL AUTO_INCREMENT , `loginUser` TEXT NULL DEFAULT NULL , `userName` TEXT NULL DEFAULT NULL , `fotoLink` TEXT NULL DEFAULT NULL , `instagramLink` TEXT NULL DEFAULT NULL , `facebookLink` TEXT NULL DEFAULT NULL , `facebookUID` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB";
        $result = mysqli_query($slink, $query);
    }
    return $result;
}

function readDb($column, $tableName, $slink)
{
    $query = "SELECT ".$column." FROM `".MYSQL_DB."`.`" . $tableName.'`';
    $result = mysqli_query($slink, $query);
    $feedbeck = mysqli_fetch_all($result);
    return $feedbeck;
}

function createTxt($tableName)
{
    $name = preg_replace('/[^ a-zа-яё\d]/ui', '_',$tableName );
    $name .= '.txt';
    $feedbeck = [];

    $link = connectDb();
    $feedbeck = readDb('facebookUID', $tableName, $link);

    if(!empty($feedbeck)){
        $id_file = file_open($name);
        writeTxt($feedbeck, $id_file);
        fclose($id_file);
    }
}

function writeTxt($feedbeck, $id_file)
{
    foreach($feedbeck as $key => $element) {
        if(!empty($element[0])) {
            fwrite($id_file, iconv('UTF-8', 'Windows-1251', $element[0]) . "\n");
        }
    }
}

function file_open($name)
{
    $id_file = fopen($name, 'a+t'); //Проверяет есть ли такой файл

    if($id_file == false)
    {
        $id_file = fopen($name, 'wt');  //Создаёт новый, если его нет
    }

    return $id_file;
}
