<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Post extends CI_Controller {
	
	function __construct() {
		parent::__construct();
		$this->load->model("post_model");
        $this->load->helper('cookie');
	}

	function index() {
		$this->load->view("layout");
	}

	function auth() {
        $login =  $this -> input -> post("login");
        $pass =  $this -> input -> post("pass");
        if ($login && $pass) {
            $user = $this->post_model->auth($login, $pass);


            if ($user) {
                $enc_data = json_encode(array(
                    'login' => $user->login,
                    'ip' => $this->input->ip_address()
                ));

                $encrypted = openssl_encrypt($enc_data, AES_256_CBC, SECRET_KEY, 0, AES_IV);
                $encrypted = str_replace('=', '-', $encrypted);
                $encrypted = str_replace('+', '.', $encrypted);
                $encrypted = str_replace('/', '_', $encrypted);
                $token_cookie = array(
                    'name' => 'token',
                    'value' => $encrypted,
                    'expire' => '400000',
                    'secure' => false
                );

                $login_cookie = array(
                    'name' => 'login',
                    'value' => $user->login,
                    'expire' => '400000',
                    'secure' => false
                );

                $this->input->set_cookie($token_cookie);
                $this->input->set_cookie($login_cookie);

                echo json_encode($user); // очуметь! Вы серьёзно? Эхо фигачит прямо в респонс?

            } else {
                echo json_encode(array('error' => 'user not found or wrong password'));
            }
        } else {
            echo json_encode(array('error' => 'Переданы не все данные'));
        }
	}

    function reg() {
        $login = $this->input->post("login");
        $pass = $this->input->post("pass");
        $name = $this->input->post("name");
        if ($login && $pass && $name){
            $ok = $this->post_model->reg($login, $pass, $name);

            if ($ok) {
                echo json_encode(array('ok' => 'registered')); //
            } else {
                echo json_encode(array('error' => 'Пользователь с таким логином "жив и, как видите, вполне упитан"'));
            }
        } else {
            echo json_encode(array('error' => 'Заполнены не все поля'));
        }
    }
}

?>