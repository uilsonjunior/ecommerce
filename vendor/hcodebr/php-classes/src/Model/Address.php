<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\mailer;
use \Hcode\Model\User;


class Address extends Model {

	public static function getCep($nrcep){

		$nrcep = str_replace('-', '', $nrcep);
		//$nrcep = str_replace('.', '', $nrcep);

		$link = "http://viacep.com.br/ws/$nrcep/json";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL,$link );

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$data = json_decode(curl_exec($ch),true);

		curl_close($ch);

		return $data;


	}

	public function loadFromCEP($nrcep){

		$data = Address::getCep($nrcep);

		if (isset($data['logradouro']) && $data['logradouro']){

			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry("Brasil");
			$this->setdeszipcode($nrcep); // 
			$this->setdesdistrict('bairro');
				
		}		

	}

	public function save(){

		$sql = new Sql();

		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, 
					:descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)",[
						":idaddress"=>$this->getidaddress(),
						":idperson"=>$this->getidperson(),
						":desaddress"=>$this->getdesaddress(),
						":descomplement"=>$this->getdescomplement(),
						":descity"=>$this->getdescity(),
						":desstate"=>$this->getdesstate(),
						":descountry"=>$this->getdescountry(),
						":deszipcode"=>$this->getdeszipcode(),
						":desdistrict"=>$this->getdesdistrict()
					]);


		if (count($results) > 0){
			$this->setData($results[0]);
		}

	}
	
}
?>