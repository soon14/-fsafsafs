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
function sqlHelper($nRequest, $element, $tableName)
{
    if($nRequest === 1){
        return "INSERT INTO `".MYSQL_DB."`.`" . $tableName . "`(loginUser, userName, fotoLink, instagramLink, facebookLink, facebookUID) VALUES ('" . $element['loginUser'] . "', '" . $element['userName'] . "', '" . $element['fotoLink'] . "', '" . $element['instagramLink'] . "', '" . $element['facebookLink'] . "', '" .$element['facebookUID']."'); ";
    }
    if($nRequest === 2){
        return "INSERT INTO `".MYSQL_DB."`.`" . $tableName . "`(url, ln, fn, phone, email, birthday, CityOld, CityNew) VALUES ('" . $element['url'] . "', '" . $element['ln'] . "', '" . $element['fn'] . "', '" . $element['phone'] . "', '" . $element['email'] . "', '" .$element['birthday']. "', '" . $element['CityOld'] . "', '" . $element['CityNew']."'); ";
    }
}
function writeDbArray($idSql, $link, $array, $tableName, $shiftArray)
{
    $query = "";
    if($shiftArray >= count($array)) $shiftArray = count($array) - 1;
    $Arr = new LimitIterator(new ArrayIterator($array), $shiftArray);
    foreach($Arr as $key => $element) {
        //$columns = implode(", ", array_keys($element));
        //$escaped_values = array_map(array($link, 'real_escape_string'), array_values($element));
        //$values = implode(", ", array_values($element));
        $query = sqlHelper($idSql, $element, $tableName);
        $result = @mysqli_query($link, $query);
    }
    return $result;
}
function createTable($tableName)
{
    $slink = connectDb();
    $tquery = mysqli_query($slink, "SELECT COUNT(*) FROM `".MYSQL_DB."`.`" .'instagram'. $tableName . "`");
    if(!$tquery) {
        $query = "CREATE TABLE `".MYSQL_DB."`.`" .'instagram'. $tableName . "` ( `id` INT NOT NULL AUTO_INCREMENT , `loginUser` TEXT NULL DEFAULT NULL , `userName` TEXT NULL DEFAULT NULL , `fotoLink` TEXT NULL DEFAULT NULL , `instagramLink` TEXT NULL DEFAULT NULL , `facebookLink` TEXT NULL DEFAULT NULL , `facebookUID` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB";
        $result = mysqli_query($slink, $query);
    }
    $tquery = mysqli_query($slink, "SELECT COUNT(*) FROM `".MYSQL_DB."`.`" .'facebook'. $tableName ."`");
    if(!$tquery)
    {
        $query = "CREATE TABLE `".MYSQL_DB."`.`".'facebook'.$tableName."` ( `url` TEXT NULL DEFAULT NULL , `ln` TEXT NULL DEFAULT NULL , `fn` TEXT NULL DEFAULT NULL , `phone` TEXT NULL DEFAULT NULL , `email` TEXT NULL DEFAULT NULL , `birthday` TEXT NULL DEFAULT NULL ,`CityOld` TEXT NULL DEFAULT NULL,`CityNew` TEXT NULL DEFAULT NULL, `id` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`)) ENGINE = InnoDB";
        mysqli_query($slink, $query);
    }
    return $result;
}
function readDb($column, $tableName, $slink)
{
    $query = "SELECT ".$column." FROM `".MYSQL_DB."`.`" . $tableName.'`';
    $result = mysqli_query($slink, $query);
    $feedbeck = mysqli_fetch_all($result, MYSQLI_ASSOC);
    return $feedbeck;
}
function countRecords($tableName)
{
    $slink = connectDb();
    $query = mysqli_query($slink, "SELECT COUNT(*) FROM `".MYSQL_DB."`.`" . $tableName . "`");
    $result = mysqli_fetch_all($query);
    return $result[0][0];
}
function tableName($name)
{
    $result = preg_replace('/[^ a-zа-яё\d]/ui', '',$name);
    $result = str_replace('https', '', $result);
    $result = str_replace('www', '', $result);
    $result = str_replace('com', '', $result);
    $result = str_replace('instagram', '', $result);
    return $result;
}
