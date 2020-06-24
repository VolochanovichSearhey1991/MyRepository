<?php
require_once('XMLasDatabase.php');
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		
		// ключ type массива $_POST указывает какую операцию запросил пользователь
		
		//добавление пользователя
		if($_POST['type'] == 'registration') {
			$fieldsArr = getOnlyFields($_POST);
			$xmlDB = new XMLasDatabase("../db/db.xml");
			foreach($fieldsArr as $key => $value) {
				$fieldsArr[$key] = secure($value);
			}
			$response = json_encode($xmlDB->addUser($fieldsArr));
			echo $response;
		
		//авторизация пользователя
		} else if ($_POST['type'] == 'authorize') {
			$xmlDB = new XMLasDatabase("../db/db.xml");
			$receivedLogin = secure(mb_strtolower($_POST['userAuthorizeLogin']));
			$receivedPass = secure($_POST['userAuthorizePass']);
			$response = $xmlDB->verifyUser($receivedLogin, $receivedPass);
			echo json_encode($response);
			
			if (strpos($response,'Hello')!== false) {
				//если авторизация успешна создаем session + записываем в cookie уникальный идентификатор,
				//и добавляем пользователю в бд(xml)
				//тэг cookieKey где храним так же этот уникальный идентификатор
				$cookieKey = StartAuthorize($receivedLogin);
				
				if ($xmlDB->getCookieId($receivedLogin )) {
					$xmlDB->updateCookieId($receivedLogin, $cookieKey); //если тэг cookieKey существует обновляем его значение
				} else {
					$xmlDB->setCookieId($receivedLogin, $cookieKey);
				}
				
			}
			
		//проверяем был ли пользователь авторизован сверяя уникальный идентификатор из cookie
		//с идентификатором этого пользователя из бд
		} else if($_POST['type'] == 'checkAuthorization') {
			$xmlDB = new XMLasDatabase("../db/db.xml");
			
			if (!empty($_COOKIE['userLogin']) && !empty($_COOKIE['userKey'])) {
				$cookieLogin = $_COOKIE['userLogin'];
				$cookieKey = $_COOKIE['userKey'];
				$coocieKeyDB = $xmlDB->getCookieId($cookieLogin);
				
					if ($cookieKey == $coocieKeyDB) {
						echo json_encode("Hello ".$cookieLogin);
					} else {
						echo json_encode("incorrect");
					}
				
				} else {
					echo json_encode("incorrect");
				}
		
		// отмена авторизации пользователя(удаляем cookies, session и идентификатор из бд)	
		} else if ($_POST['type'] == 'exit') {
			
			if (!empty($_COOKIE['userLogin']) && !empty($_COOKIE['userKey'])) {
				$receivedLogin = $_COOKIE['userLogin'];
				$xmlDB = new XMLasDatabase("../db/db.xml");
				$xmlDB->deleteCookieId($receivedLogin);
			}
			
			logout();
			
		}
		
	}
	
	//удаляем ключ type (нужно когда передаем поля для регистрации или авторизации )
	function getOnlyFields($arr) {
		$fieldsArr = $arr;
		unset($fieldsArr['type']);
		return $fieldsArr;
	}
	
	//записывает данные в cookie, session, и возвращает уникальный идентификатор который используется
	//для подтверждения что пользователь авторизован
	function StartAuthorize($userLogin) {
		$userLogin = str_replace('Hello ','',$userLogin);
		session_set_cookie_params(0);
		session_start();
		$_SESSION['auth'] = true;
		$_SESSION['login'] = $userLogin;
		$coocieKey = cookieKeyGen();
		setcookie('userLogin', $userLogin, time() + 60 * 60 * 24 * 30);
		setcookie('userKey', $coocieKey, time() + 60 * 60 * 24 * 30);
		return $coocieKey;
	}
	
	//генерирует и возвращает уникальный идентификатор
	function cookieKeyGen() {
		$key = "";
		for ($i = 0; $i < 8; $i++) {
			$key .= chr(mt_rand(33,126));
		}
		return $key;
	}
	
	//очищает cookie и session
	function logout() {
		session_start();
		
		if (!empty($_SESSION['login']) && $_SESSION['auth'] === true) {
			unset($_SESSION['auth']);
			unset($_SESSION['login']);
			session_destroy();
		}
		
		unset($_COOKIE['PHPSESSID']);
		setcookie('userLogin', '');
		setcookie('userKey', '');
	}
	
	function secure($receivedData) {
		return trim(htmlentities($receivedData));
	}
?>