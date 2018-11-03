<?php
	define('MYSQL_SERVER', 'localhost');
	define('MYSQL_USER','root');
	define('MYSQL_PASSWORD','');
	define('MYSQL_DB','useid');

	function connectDb()
    {
		$link = @mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB)
			or die("Error: ".mysqli_error($link));
		if(!mysqli_set_charset($link,"utf8")){
			printf("Error: ".mysqli_error($link));
		}
		return $link;
	}

    function writeDbArray($link, $array, $tableName)
    {
        $query = "";
        //try {
            foreach ($array as $key => $element) {
                //$columns = implode(", ", array_keys($element));
                //$escaped_values = array_map(array($link, 'real_escape_string'), array_values($element));
                //$values = implode(", ", array_values($element));

                $query = "INSERT INTO `useid`.`" . $tableName . "`(loginUser, userName, fotoLink, instagramLink, facebookLink) VALUES ('" . $element['loginUser'] . "', '" . $element['userName'] . "', '" . $element['fotoLink'] . "', '" . $element['instagramLink'] . "', '" . $element['facebookLink'] . "'); ";


                $result = @mysqli_query($link, $query);
            }
        /*}
        catch(Exception $e){

        }*/
        return $result;
    }

    function createTable($tableName)
    {
        $slink = connectDb();
        $tquery = @mysqli_query($slink, "SELECT COUNT(*) FROM `useid`.`".$tableName."`");
        if(!$tquery) {
            $query = "CREATE TABLE `useid`.`" . $tableName . "` ( `id` INT NOT NULL AUTO_INCREMENT , `loginUser` TEXT NULL DEFAULT NULL , `userName` TEXT NULL DEFAULT NULL , `fotoLink` TEXT NULL DEFAULT NULL , `instagramLink` TEXT NULL DEFAULT NULL , `facebookLink` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB";
            $result = mysqli_query($slink, $query);
        }
        return $result;
    }
