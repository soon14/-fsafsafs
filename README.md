Запускаем проект в браузере. В первые пять полей html страницы обязательно нужно ввести ссылку на инстаграм которую хотим парсить, свой логин и пароль от инстаграма, и емейл и пароль от фейсбука. Дополнительные поля для фейсбука нужны для запуска ботов которые ищут подписчиков, если все поля заполнены - запустятся 4 бота которые будут паралельно работать, бот работает со скоростью 1 поиск примено в минуту, если эти поля оставить не заполненными - боты не запустятся. после чего нажать start, должен открыться браузер в контейнере который будет работать со страницей инстаграм. За его жизнью можно следить через selenoid-ui на порте 8080 
В конце работы алгоритма методом подстановки, на экран должны вывестись ценные данные которые удалось получить. 
Если мы хотим выгрузить ценную информацию из базы, нужно вставить в верхнее поле точную ссылку с которой мы уже работали, поставить галочку, и нажать старт, боты в процессе работы переодически складывают информацию в базу. 
                                                   
                                                   Установка
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

Конфигурационный файл базы данных лежит в папке проекта и называется dbConfiguration.php 
