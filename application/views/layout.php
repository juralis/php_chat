<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ПХП-чат с вебсокетами как в лучших домах Ландона</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link type="text/css" rel="stylesheet" href="/static/css/style.css">
</head>
<body>
<div id="log"></div>
<textarea id="text" cols="30" rows="10" class="hidden" autocomplete="off"></textarea>
<div id="regform" class="hidden">
    <p><label for="login">Логин<input type="text" id="login" autocomplete="off"></label></p>
    <p><label for="name" class="hidden">Имя<input type="text" id="name" autocomplete="off"></label></p>
    <p><label for="pass">Пароль<input type="password" id="pass"></label></p>
    <p><label for="pass2" class="hidden">Ещё пароль<input type="password" id="pass2"></label></p>
    <p><button id="auth">Залогиниться</button></p>
    <p><button id="show_reg">Нет логина?</button></p>
    <p><button id="reg" class="hidden">Зарегиться</button></p>
</div>
<button id="send" class="hidden">Отправить</button>
<button id="logout" class="hidden">Разлогиниться</button>
<script>
    var base_url = "<?php echo base_url(); ?>";
    var ws_port = "<?php echo WS_PORT; ?>";
    var ws_host = "<?php echo WS_HOST; ?>";

    var ws = {push: function(){console.log('Нет связи')}};
    var ws_timer = null;
    var last_msg = null;

    var Methods = {
        chat_log: function(data){
            data = data.reverse();
            for (var i in data ){
                $('#log').append('<p><small>'+strftime(data[i].time, '%d.%M.%Y %h:%m:%s')+'</small> <i>'+data[i].login+'</i>:'+data[i].text + '</p>');
                console.log(data[i])
            }
        },
        new_msg: function(data){
            $('#log').append('<p><small>'+strftime(data.time, '%d.%M.%Y %h:%m:%s')+'</small> <i>'+data.login+'</i>:'+data.text + '</p>');
            console.log(data)
        },
        sync: function(data){
            // если в наборе будет сообщение, которое уже есть в логе, тогда всё ок. Если нет, тогда надо поставить черту и кнопку подгрузки прошлых сообщений.
            for (var i in data ){
                // append to log_window (reversed)
                console.log(data[i])
            }
        }
    };

    function ws_connect(){
        ws = new WebSocket('ws://'+ws_host+':'+ws_port+'/ws/');
        ws.onopen = function() {
            if(ws_timer){
                clearInterval(ws_timer);
                ws_timer = null;
            }
            if (last_msg) {
                ws.push('sync', {last_msg: last_msg})
            } else {
                ws.push('get_log', {})
            }
            console.log("ws-соединение установлено.");
        };

        ws.onmessage = function(msg) {
            var data = JSON.parse(msg.data);
            if (Methods.hasOwnProperty(data.method)) {
                Methods[data.method](data.body)
            } else {
                console.log('Вызванный метод ' + data.method + ' - не реализован в текущей версии.')
            }
        };
        ws.push = function(method, data){
            ws.send(JSON.stringify({method: method, body: data, token: get_cookie('token')}))
        };
        ws.onclose = function(event) {
            ws = {push: function(){console.log('Нет связи')}};
            if (event.wasClean) {
            } else {
                if(!ws_timer) {
                    console.log('Обрыв соединения ws. Код: ' + event.code);
                    ws_timer = setInterval(ws_connect, 5000);
                    console.log('Через 5 секунд будет попытка восстановить соединение - не надо тут ничего рефрешить. Само отрефрешится.');
                }
            }
        };
    }

    function set_cookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            expires = '; expires='+date.toGMTString();
        }
        document.cookie = name+'='+encodeURI(value)+expires+'; path=/';
    }

    function get_cookie(name) {
        name = name + '=';
        var cooks = document.cookie.split(';');
        for(var i=0;i < cooks.length;i++) {
            var c = cooks[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(name) == 0) return decodeURI(c.substring(name.length,c.length));
        }
        return false;
    }

    function erase_cookie(name) {
        set_cookie(name, '', -1);
    }

    function humanize(time) {
        return (time.toString().length == 1) ? '0' + time : time;
    }

    function strftime(ts, temp){
        var date = new Date(parseInt(ts)*1000);
        temp = temp || "%d.%M.%Y %h:%m:%s";
        return temp
            .replace(/%d/g, humanize(date.getDate()))
            .replace(/%M/g, humanize(date.getMonth()+1))
            .replace(/%Y/g, date.getFullYear())
            .replace(/%y/g, date.getYear() - 100)
            .replace(/%h/g, humanize(date.getHours()))
            .replace(/%m/g, humanize(date.getMinutes()))
            .replace(/%s/g, humanize(date.getSeconds()))
    }

    $('document').ready(function(){

        if (get_cookie('token')){
            ws_connect();
            $('#send').removeClass('hidden');
            $('#text').removeClass('hidden');
            $('#logout').removeClass('hidden');
        } else {
            $('#regform').removeClass('hidden')
        }
        $('#send').on('click', function(){
            ws.push('send_msg', {text: $('#text').val() || 'test_text'});
            $('#text').val('')
        });

        $('#auth').on('click', function(){
            if ($('#login').val() && $('#pass').val()) {
                $.ajax({
                    type: 'post',
                    contentType: 'application/x-www-form-urlencoded',
                    url: '/auth',
                    dataType: 'json',
                    data: {
                        login: $('#login').val(),
                        pass: $('#pass').val()
                    },
                    success: function (data) {
                        if (!data.error) {
                            ws_connect();
                            $('#send').removeClass('hidden');
                            $('#logout').removeClass('hidden');
                            $('#text').removeClass('hidden');
                            $('#regform').addClass('hidden')
                        } else {
                            alert(data.error)
                        }
                    }, error: function (request, error) {

                    }
                });
            } else {
                alert('Так не залогинишься.')
            }
        });

        $('#reg').on('click', function(){
            var ok = true;
            if (!$('#login').val()){
                alert('Логин надо');
                ok = false;
            }
            if (!$('#pass').val()){
                alert('пароль надо');
                ok = false;
            }
            if (!$('#name').val()){
                alert('имя надо');
                ok = false;
            }
            if (!$('#pass2').val()){
                alert('пароль2 надо');
                ok = false;
            }
            if ($('#pass2').val() != $('#pass2').val()){
                alert('пароль и пароль2 должны быть одинаковы');
                ok = false;
            }
            if (ok) {
                $.ajax({
                    type: 'post',
                    contentType: 'application/x-www-form-urlencoded',
                    url: '/reg',
                    dataType: 'json',
                    data: {
                        login: $('#login').val(),
                        pass: $('#pass').val(),
                        name: $('#pass').val()
                    },
                    success: function (data) {
                        if (!data.error) {
                            alert('Саксэсс!');
                            location.reload()
                        } else {
                            alert(data.error)
                        }
                    }, error: function (request, error) {

                    }
                });
            }
        });

        $('#logout').on('click', function(){
            erase_cookie('token');
            erase_cookie('login');
            location.reload()
        });
        $('#show_reg').on('click', function(){
            if($('#name').parent().is('.hidden')) {
                $('#name').parent().removeClass('hidden');
                $('#pass2').parent().removeClass('hidden');
                $('#show_reg').text('Отмена');
                $('#reg').removeClass('hidden');
                $('#auth').addClass('hidden');
            } else {
                $('#name').parent().addClass('hidden');
                $('#pass2').parent().addClass('hidden');
                $('#show_reg').text('Нет логина?');
                $('#reg').addClass('hidden');
                $('#auth').removeClass('hidden');
            }
        });
    });

</script>
</body>
</html>
