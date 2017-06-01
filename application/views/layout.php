<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ПХП-чат с вебсокетами как в лучших домах Ландона</title>
    <link type="text/css" rel="stylesheet" href="/static/css/style.css">
</head>
<body>
<div id="wrapper">
    <div id="chat" class="hidden">
        <div id="log"></div>
        <div id="roster"></div>
    </div>

    <div id="tools" class="hidden">
        <p><textarea id="text" autocomplete="off"></textarea></p>
        <p><button id="send">ctrl+enter</button></p>
        <p><button id="logout">Разлогиниться</button></p>
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

</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="/static/js/script.js"></script>
<script>
    $('document').ready(function(){

        if (get_cookie('token')){
            ws_connect();
            $('#chat').removeClass('hidden');
            $('#tools').removeClass('hidden');
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
                            $('#chat').removeClass('hidden');
                            $('#tools').removeClass('hidden');
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
