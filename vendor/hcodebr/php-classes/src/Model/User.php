<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\mailer;

class User extends Model {

	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret";
	const ERROR = "UserErro";
	const ERROR_REGISTER = "userErroRegister";
	const SUCCESS = "UserSuccess";

	public static function getFromSession(){

		$user = new User();

		if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0 ){

			$user->setData($_SESSION[User::SESSION]);

		}else{


		}

		return $user;

	}

	public static function checkLogin($inadmin = true){

		if(

			!isset($_SESSION[User::SESSION]) 
			|| 
			!$_SESSION[User::SESSION] 
			|| 
			!(int)$_SESSION[User::SESSION]["iduser"] > 0  

		){

			//não esta logado
			return false;

		}else{

			if ($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true){

				return true;

			}else if($inadmin === false){

				return true;

			}else { 
			
				return false; 
			
			}

		}

	}

	public static function verifyLogin( $inadmin = true){

		//if ( 
		//	!isset($_SESSION[User::SESSION]) 
		//	|| 
			//$_SESSION[User::SESSION] 
			//|| 
		//	!(int)$_SESSION[User::SESSION]["iduser"] > 0  
		//	|| 
		//	(bool)$_SESSION[User::SESSION]["inadmin"] != $inadmin
		//){
		if (!User::checkLogin($inadmin)){
			
			if ($inadmin){

				header("Location: /admin/login");
				
			}else{
				header("Location: /login");
			}

			exit;
		//	return false;
		}
		//else return true;

	}

	public static function login ($login, $password){

		$sql = new Sql();

		$results = $sql->select("
							SELECT * 
							FROM tb_users a
							INNER JOIN tb_persons b 
									ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count ($results) === 0){

			throw new \Exception("Usuário inexistente ou senha inválida", 1); //coloca a barra \ para poder pegar a exception principal
			
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]))
		{

			$user = new User();

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		}else {

			throw new \Exception("Usuário inexistente ou senha inválida", 1); //coloca a barra \ para poder pegar a exception principal

		}

	}



	public static function logout(){

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listAll(){

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY desperson");

		 
	}

	public function save()
	{

		/*
		CALL `db_ecommerce`.`sp_users_save`(<{pdesperson VARCHAR(64)}>, <{pdeslogin VARCHAR(64)}>, <{pdespassword VARCHAR(256)}>, <{pdesemail VARCHAR(128)}>, <{pnrphone BIGINT}>, <{pinadmin TINYINT}>);

		*/
		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>utf8_decode($this->getdesperson()),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()

		));		

		$this->setData($results[0]);

	}

	public function get($iduser){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) 
			WHERE a.iduser = :iduser", array(
				":iduser"=>$iduser
			));
		$this->setData($results[0]);

	}

	public function update(){
		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()

		));		

		$this->setData($results[0]);
	}

	public function delete(){

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)",array(
			":iduser"=>$this->getiduser()
		));

	}

	public static function getForgot($email, $inadmin = true){

		$sql = new Sql();

		$results = $sql->select("
					SELECT * 
					FROM db_ecommerce.tb_persons a
					INNER JOIN tb_users b USING (idperson)
					WHERE desemail = :email
				", array(
					":email"=>$email
				));

		if (count($results) === 0){

			throw new \Exception("Não foi possível recuperar a senha");

		}
		else
		{

			$data = $results[0];

			$results_recovey = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($results_recovey) ===0){

				throw new \Exception("Não foi possível recuperar a senha");

			}
			else{

				$dataRecovery = $results_recovey[0];

				$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET , $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

				if ($inadmin){

					$link = "http:localhost/admin/forgot/reset?code=$code";

				}else{

					$link = "http:localhost/forgot/reset?code=$code";

				}

				$mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir senha Hcode Store", "forgot", 
					array(
						"name"=>$data["desperson"],
						"link"=>$link
					)
				);

				$mailer->send();

				return $data;

			}

		}

	}

	public static function validForgotDecrypt($code){

		$idrecovery = base64_decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $code, MCRYPT_MODE_ECB));
		//verificar erro?
		$idrecovery = 17;

		$sql = new Sql();

		$results = $sql->select("SELECT * 

		FROM tb_userspasswordsrecoveries as a

		INNER JOIN tb_users as b USING (iduser)

		INNER JOIN tb_persons as c USING (idperson)

		WHERE 

			a.idrecovery = :idrecovery
		    
		    AND 
		    
		    a.dtrecovery IS NULL
		    
		    AND
		    
		    DATE_ADD(a.dtregister, INTERVAL 60 MINUTE) >= NOW();", array(
		    	":idrecovery"=>$idrecovery)
		);

		if (count($results) === 0){
			throw new \Exception("Não foi possível recuperar a senha id: ". $idrecovery." code: ".$code);
			
		}
		else
		{

			return $results[0];

		}

	}

	public static function setForgotUsed($idrecovery){

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",array(
			"idrecovery"=>$idrecovery 
		));

	}

	public function setPassword($password){

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser",array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));

	}

	public static function setError($msg){
		$_SESSION[User::ERROR] = $msg;
	}

	public static function getError(){

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "";

		User::clearError();

		return $msg;
	}

	public static function clearError(){
		$_SESSION[User::ERROR] = NULL;
	}

	public static function setSuccess($msg){
		$_SESSION[User::SUCCESS] = $msg;
	}

	public static function getSuccess(){

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : "";

		User::clearSuccess();

		return $msg;
	}

	public static function clearSuccess(){
		$_SESSION[User::SUCCESS] = NULL;
	}

	public static function getPasswordHash($password){

		return password_hash($password, PASSWORD_DEFAULT,[
			'cost'=>12
		]);

	}

	public static function setErrorRegister($msg){
		$_SESSION[User::ERROR_REGISTER]	= $msg;
	}

	public static function getErrorRegister(){
		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';
		User::clearErrorRegister();
		return $msg;
	} 

	public static function clearErrorRegister(){
		$_SESSION[User::ERROR_REGISTER] = NULL;
	}

	public static function checkLoginExist($email){

		$sql = new Sql();

		$results = $sql->select("SELECT * 
								FROM tb_persons
								WHERE desemail = :desemail",[
									':desemail'=>$email
								]);

		return (count($results) > 0);

	}

	public function getOrders(){

		$sql = new Sql();

		$results = $sql->select("
						SELECT * 
						FROM tb_orders a 
						INNER JOIN tb_ordersstatus b USING(idstatus)
						INNER JOIN tb_carts c USING(idcart)
						INNER JOIN tb_users d ON d.iduser = a.iduser
						INNER JOIN tb_addresses e USING(idaddress)
						INNER JOIN tb_persons f ON f.idperson = d.idperson
						WHERE a.iduser = :iduser",[
							"iduser"=>$this->getiduser()
						]);

		if (count($results) > 0){
			return $results;
		}

	}
}

?>