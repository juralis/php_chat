<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends CI_Model {

	function auth($login, $pass) {
        $pass = openssl_digest($pass, 'sha256', false);

		$this->db->select('login, name, last_visit, pass');
  		$this->db->from('users');
		$this->db->where('login', $login);
		$user = $this->db->get()->result();

		if (!empty($user)){
		    if ($user[0]->pass == $pass) {
                unset($user[0]->pass);
                return $user[0];
            }
        }

        return null;
	}
	
	function reg($login, $pass, $name) {
        $pass = openssl_digest($pass, 'sha256', false);

        $this->db->select('login');
        $this->db->from('users');
        $this->db->where('login', $login);
        $user = $this->db->get()->result();
        // не понял как поймать ошибку при инсёрте. Так-то вроде нужно через него определять...
        if (!empty($user)) {
            return false;
        } else {
            $this->db->insert("users", array('login' => $login, 'pass' => $pass, 'name' => $name));
            return true;
        }
    }
}