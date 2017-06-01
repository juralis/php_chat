<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends CI_Model {

	function auth($login, $pass) {
        $pass = openssl_digest($pass, 'sha256', false);

		$this->db->select('login, name, pass');
  		$this->db->from('users');
		$this->db->where('login', $login);
		log_message('info', $this->db->get());
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
        $this->db->insert("users", array('login' => $login, 'pass' => $pass, 'name' => $name));
        if ($this->db->affected_rows() > 0){
            return true;
        } else {
            return false;
        }
    }
}