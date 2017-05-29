# php_chat

## Установка окружения

### Ставим nginx

Дистрибутив debian-based.

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
apt-get update
apt-cache policy nginx
apt-get install nginx
```
Nginx установлен.
