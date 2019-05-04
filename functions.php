<?php

Use \Hcode\Model\User;
Use \Hcode\Model\Cart;

	function formatPrice($vlprice){

		if (!$vlprice > 0) $vlprice = 0;

		return number_format($vlprice, 2, ",", ".");

	}

	function formatDate($date){

		return date('d/m/Y', strtotime($date));
		
	}


	function checkLogin($inadmin = true){


		return User::checkLogin($inadmin);

	}

	function getUserName(){

		$user = User::getFromSession();

		return $user->getdesperson();

	}

	function getCartNrQtd(){

		$cart = Cart::getFromSession();

		$totals = $cart->getProductTotals();

		return $totals['nrqtd']>0?$totals['nrqtd']:0;

	}

	function getCartVlSubTotals(){

		$cart = Cart::getFromSession();

		$totals = $cart->getProductTotals();

		return formatPrice($totals['vlprice']);

	}


?>	