В файле input.php в переменной $localRepository нужно указать локальный адрес проекта, например:'http://localhost/parser/';

Для того что бы поставить selenoid нам потребуются следующие команды:

wget -O cm https://github.com/aerokube/cm/releases/download/1.5.5/cm_linux_amd64

sudo chmod +x ./cm

./cm selenoid-ui download

sudo ./cm selenoid start --vnc

sudo /cm selenoid-ui start

sudo docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aerokube/cm:1.0.0 selenoid --last-versions 2 --tmpfs 128 --pull /root/.aerokube/selenoid/browsers.json

docker restart selenoid

При возникновении проблем после установки следует сделать следующее:

docker stop selenoid

docker stop selenoid-ui

./cm selenoid start --force

опция force перекачает образы заново, и перезапишет browsers.json

После того как всё пройдёт удачно, у нас должно загрузится две последние версии браузера. Переходим на localhost:8080 Переходим во вкладку CAPABILITIES, нажимаем на select browser. Если всё загрузилось правильно - в списке должен быть crome версии 70.0 его мы используем.

Запускаем проект в браузере. В поля html страницы нужно ввести ссылку на инстаграм которую хотим парсить, свой логин и пароль от инстаграма, и мейл и пароль от фейсбука после чего нажать start, должен открыться браузер в контейнере который будет работать со страницей инстаграм. За его жизнью можно следить через selenoid-ui на порте 8080

Конфигурационный файл базы данных лежит в папке проекта и называется dbConfiguration.php В конце алгоритма на экран должны вывестись ценные данные которые удалось получить.  
