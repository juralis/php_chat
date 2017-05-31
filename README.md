# php_chat

Это что-то вроде дневника-журнала изучения/разработки. В идеале, по нему можно воспроизвести порядок действий и получить примерно аналогичный результат. Это сделано чтобы "дурь каждого видна была" (с). Иными словами, демонстрация действий, чтобы по ней можно было что-то понять о том, что и почему сделано.

## Установка окружения

### Ставим nginx

Дистрибутив debian-based. (Какой-то Netruner - впервые слышу)

```
sudo nano /etc/apt/sources.list
```
Добавляем туда:

```
deb http://ftp.debian.org/debian/ testing main contrib non-free
deb-src http://ftp.debian.org/debian/ testing main contrib non-free
```
`ctrl+x`, `y`

```
sudo nano /etc/apt/preferences
```
Туда вписываем:

```
Package: nginx
Pin: release a=testing
Pin-Priority: 900
```
`ctrl+x`, `y`  
Далее:

```
sudo apt-get update
sudo apt-cache policy nginx
sudo apt-get install nginx
```
Nginx установлен. Потом с конфигом разберёмся.

### Postgresql

По мотивам: https://amkolomna.ru/content/ustanovka-i-nastroyka-servera-postgresql-pod-linux-ubuntu   
(хз, что тут вообще делается. Вступаю на скользкую дорожку)  

```
sudo apt-get install postgresql
sudo passwd -d postgres
sudo su postgres  -c passwd
psql -d postgres
ALTER USER postgres WITH PASSWORD '123456'
```
`123456` - даже не знаю, стоит ли оправдываться?

\+ поставил jetbrains datagrip какие-то там драйверы и вот это всё, подключился - вроде норм.

Конечно, после монги `ВЕСЬ ЭТОТ SQL синтаксис выглядит ВЕСЬМА себе (странно);`

```SQL
CREATE DATABASE php_chat;
```
(Пока вроде просто)

```SQL
CREATE TABLE users
(
    login TEXT NOT NULL,
    pass TEXT NOT NULL,
    name TEXT NOT NULL
);
CREATE UNIQUE INDEX users_login_uindex ON users (login);
```
Не люблю когда неизвестные в общем-то вещи - понятны. Где-то тут должно быть место для выстрела в ногу  

``` 
CREATE TABLE chat_log
(
    login TEXT NOT NULL,
    text TEXT NOT NULL,
    time INT NOT NULL
);
```

Ну, как-то так. Потом подправлю ежели чего.

Ладно, пробуем вставить в табличку какие-то данные

```SQL
INSERT INTO users (
  login,
  pass,
  name
) VALUES (
  'test1',
  'q1w2e3r4t5y6',
  'Христофор',
)
```
тааак...
```SQL
SELECT * FROM users WHERE login = 'test1';
```
Выдало что-то такое вот:
```
test1	q1w2e3r4t5y6	Христофор	2014-04-04 20:00:00.000000	0
```
Жить можно - эта штука может всасывать и отдавать данные. В принципе, больше ничего от неё и не требовалось.  
А из кода это как вызывать? Шаблонизировать как-то? Всегда меня SQL смущал именно тем, что это отдельный язык, который никак не интегрируется в используемый. В той же монге я бы это просто так сделал:
```javascript
db.chat.users.findOne({login: 'test1'})
```
Как-то оно более объектно выходит. А тут...

Ладно. Что там с пхп.

### PHP

Значит, для вебсокетов нашел вот это: http://socketo.me/docs/hello-world
Работает в целом.
На итог, вышла такая вот струтура проэкта:
```
root-|
     |-bin/app.php
     |-static-|
     |        |-css/...
     |        |-js/...
     |        |-img/...
     |        |-index.html
     |        _
     |-src/CoolChat/Events.php
     |-vendor/...
     |-composer.json
     |-composer.lock
     |-composer.phar
     |-.gitignore
     _
```
(примечание) Приятно, что json кушает прямо из коробки. Или тут не принято использовать встроенный json-сериализатор?

... Спустя какое-то время ...

Удалось всё запустить, сообщение из одной вкладки успешно расходится по остальным. Через нгинкс, со всеми делами.
Осталось сложить это в базу, добавить регистрацию/авторизацию и склепать под это какой-нибудь удобоваримый интерфейс. Можно пойти покурить и подумать над варнишем

... ещё спустя некоторое время ...

Не окончено. В принципе, можно с некоторой натяжкой говорить о том, что я многое понял сегодня и костяк приложения готов (по крайней мере, понятно, что оно будет работать как оно будет работать).


### Последок.

Вроде всё это как-то аскетично работает. Делаю публичную версию, если так можно выразиться. Всё вышеозначенное + варниш. Варнишь, варнишь... 

Нгинкс у меня уже стоял, постгрес поставил, как выше написано (там сейчас другие слегка таблички созданы, но в целом тоже самое).

Код брать тут:  

`git clone https://github.com/juralis/php_chat.git`  

На всякой случай, прежде чем, надо вот:

``` 
sudo apt-get install php-fpm 
sudo apt-get install php-pgsql
```

Там, кроме всего прочего, надо раскомментить в php.ini:
```
extension=php_openssl.dll
extension=php_pgsql.dll
extension=php_pdo_pgsql.dll
```
Чтобы крипта взлетела.  

Конфиг для нгинкса лежит вместе с кодом приложения в папке configs (там внутри папка site-enabled, надо из неё взять файл и отправить в /etc/nginx/site-enabled/)
