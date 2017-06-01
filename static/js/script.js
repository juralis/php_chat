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
            ws.send(JSON.stringify({method: method, body: data, token: get_cookie('token')}));
            return true;
        };

        ws.onclose = function (event) {
            ws = {push: function () {
                console.log('Нет связи');
                return false;
            }};
            if (!event.wasClean && !ws_timer) {
                console.log('Обрыв соединения ws. Код: ' + event.code);
                ws_timer = setInterval(ws_connect, 5000);
                console.log('Через 5 секунд будет попытка восстановить соединение - не надо тут ничего рефрешить. Само отрефрешится.');
            }
        }
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
    name += '=';
    var cooks = document.cookie.split(';');
    for(var i=0; i < cooks.length; i++) {
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
