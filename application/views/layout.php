<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ПХП-чат с вебсокетами как в лучших домах Ландона</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link type="text/css" rel="stylesheet" href="/static/css/style.css">
</head>
<body>
<div id="wrapper">
    <div id="chat">
        <div id="log"></div>
        <div id="roster">

        </div>
    </div>
    <div id="tools">
        <p><textarea id="text" class="hidden" autocomplete="off"></textarea></p>
        <p><button id="send" class="hidden">Отправить</button></p>
        <p><button id="logout" class="hidden">Разлогиниться</button></p>
    </div>

    <div id="regform" class="hidden">
        <h2>Вход</h2>
        <p><input class="hidden" placeholder="Имя" type="text" id="name" autocomplete="off"></p>
        <p><input placeholder="Логин" type="text" id="login" autocomplete="off"></p>
        <p><input placeholder="Пароль" type="password" id="pass"></p>
        <p><input class="hidden" placeholder="Больше паролей богу паролей" type="password" id="pass2"></p>
        <p><button id="auth">Залогиниться</button></p>
        <p><button id="show_reg">Нет логина?</button></p>
        <p><button id="reg" class="hidden">Подтвердить</button></p>
    </div>
</div>
<script>
    var base_url = "<?php echo base_url(); ?>";
    var ws_port = "<?php echo WS_PORT; ?>";
    var ws_host = "<?php echo WS_HOST; ?>";

    var ws = {push: function(){console.log('Нет связи'); return false;}};
    var ws_timer = null;
    var last_msg = null;

    function form_message(msg){
        var msg_p = $('<fieldset>', {id: 'ts'+msg.time, class: 'message'});
        var time = $('<small>').append(strftime(msg.time, '%d.%M.%Y %h:%m:%s'));
        var login = $('<i>').append(msg.login);
        var leg = $('<legend>').append([time, ' ', login, ': ']);
        msg_p.append([leg, msg.text.replace(/\n/g, '<br>')]);
        return msg_p;
    }

    var Methods = {
        chat_log: function(data){
            data = data.reverse();
            for (var i in data ){
                if ($('#ts'+data[i].time).length === 0) {
                    $('#log').append(form_message(data[i]));
                    last_msg = data[i].time
                }
            }
        },
        new_msg: function(data){
            $('#log').append(form_message(data));
            last_msg = data.time
        },
        online: function(data){
            $('#roster').empty();
            for (var i in data) {
                if ($('#user_'+data[i]).length===0) {
                    $('#roster').append('<p id="user_' + data[i] + '">' + data[i] + '</p>')
                }
            }
        },
        user_left: function(data){
            $('#user_'+data).remove();
        },
        user_came: function(data){
            if ($('#user_'+data).length===0) {
                $('#roster').append('<p id="user_' + data + '">' + data + '</p>')
            }
        }
    };

    function ws_connect() {
        if (!ws.onopen) {
            ws = new WebSocket('ws://' + ws_host + ':' + ws_port + '/ws/');

            ws.onopen = function () {
                if (ws_timer) {
                    clearInterval(ws_timer);
                    ws_timer = null;
                }
                ws.push('get_log', {});
                ws.push('online', {});
                console.log("ws-соединение установлено.");
            };

            ws.onmessage = function (msg) {
                var data = JSON.parse(msg.data);
                if (Methods.hasOwnProperty(data.method)) {
                    Methods[data.method](data.body)
                } else {
                    console.log('Вызванный метод ' + data.method + ' - не реализован в текущей версии.')
                }
            };
            ws.push = function (method, data) {
                ws.send(JSON.stringify({method: method, body: data, token: get_cookie('token')}))
                return true;
            };
            ws.onclose = function (event) {
                ws = {
                    push: function () {
                        console.log('Нет связи');
                        return false;
                    }
                };
                if (event.wasClean) {
                } else {
                    if (!ws_timer) {
                        console.log('Обрыв соединения ws. Код: ' + event.code);
                        ws_timer = setInterval(ws_connect, 5000);
                        console.log('Через 5 секунд будет попытка восстановить соединение - не надо тут ничего рефрешить. Само отрефрешится.');
                    }
                }
            };
        }
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
            while (c.charAt(0)==' ')
                c = c.substring(1,c.length);
            if (c.indexOf(name) == 0)
                return decodeURI(c.substring(name.length,c.length));
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
            if ($('#text').val()) {
                if (ws.push('send_msg', {text: $('#text').val() || 'test_text'})) {
                    $('#text').val('')
                } else {
                    $('#send').text('Оказия...');
                    setTimeout(function () {
                        $('#send').text('Отправить')
                    })
                }
            }
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
            if (ok && !$('#pass').val()){
                alert('пароль надо');
                ok = false;
            }
            if (ok && !$('#name').val()){
                alert('имя надо');
                ok = false;
            }
            if (ok && !$('#pass2').val()){
                alert('пароль2 надо');
                ok = false;
            }
            if (ok && $('#pass2').val() != $('#pass2').val()){
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
                    }, error: function (request, error) {}
                });
            }
        });

        $('#logout').on('click', function(){
            erase_cookie('token');
            erase_cookie('login');
            location.reload()
        });

        $('#show_reg').on('click', function(){
            if($('#name').is('.hidden')) {
                $('#name').removeClass('hidden');
                $('#pass2').removeClass('hidden');
                $('#show_reg').text('Отмена');
                $('#reg').removeClass('hidden');
                $('#auth').addClass('hidden');
                $('#regform h2').text('Регистрация');
            } else {
                $('#name').addClass('hidden');
                $('#pass2').addClass('hidden');
                $('#show_reg').text('Нет логина?');
                $('#reg').addClass('hidden');
                $('#auth').removeClass('hidden');
                $('#regform h2').text('Вход');
            }
        });

        $('#pass').keypress(function(event){
            var keycode = (event.keyCode ? event.keyCode : event.which);
            if(keycode == 13){
                $('#auth').click();
            }
        });

        $('#text').keydown(function (e) {
            if (e.ctrlKey && e.keyCode == 13) {
                $('#send').click()
            }
        });
    });

</script>
</body>
</html>
