<?php
// класс содержит методы для работы с xml файлом заменяющим бд
class XMLasDatabase{
	private $fileName, $dom;
	
	public function __construct($fileName) {
		$this->fileName = $fileName;
		$this->dom = new DOMDocument('1.0','utf-8');
	}
	
	//добавляет пользователя в список со всеми его данными
	public function addUser($userFields) {
		
		if ($this->findLogin(mb_strtolower($userFields['userLogin']))) {
			return 'duplicate login';
		} 
		
		if ($this->findEmail($userFields['userEmail'])) {
			return 'duplicate email';
		}
		
		$this->dom->load($this->fileName);
		
			$root = $this->dom->documentElement;
			$user = $this->dom->createElement('user');
			$user->setAttribute('login',mb_strtolower($userFields['userLogin']));
		
			foreach ($userFields as $fieldName=>$fieldData) {
				
				if ($fieldName != 'userLogin' && $fieldName != 'confUserPass') {
					
					//к паролю добавляем случайно генерируемую строку чисел(соль) и шифруем
					if ($fieldName == 'userPass') {
						$salt = random_int(-99999, 99999);//генерируем случайную "соль"
						$fieldData = sha1($fieldData.$salt); 
						$userData = $this->dom->createElement($fieldName, $fieldData);
						$user->appendChild($userData);
						$userData = $this->dom->createElement('salt', $salt); //для каждого пользователя храним уникальную "соль"
						$user->appendChild($userData);
					} else {
						$userData = $this->dom->createElement($fieldName, $fieldData);
						$user->appendChild($userData);
					}
					
				}
				
			}
		
			$root->appendChild($user);
			$this->dom->save($this->fileName);
			return 'success';
	}
	
	//проверяем существует ли запрашиваемый пользователь
	public function verifyUser($receivedLogin, $receivedPass) {
		$userData = $this->findLogin($receivedLogin);
		
		//если существует, сверяем пароли
		if ($userData) {
			$userPassVal = $userData->getElementsByTagName('userPass')->item(0)->nodeValue;
			$userSaltVal = $userData->getElementsByTagName('salt')->item(0)->nodeValue;
			$userLoginVal= $userData->getAttribute('login');
			if ($this->checkPassword($userPassVal, $userSaltVal, $receivedPass)) {
				return "Hello ".$userLoginVal;
			}

			return "incorrect";
			
		}

		return "incorrect";
		
	}
	
	//добавляет к данным пользователя поле cookieKey с уникальным идентификатором
	public function setCookieId($curLogin, $cookieKey) {
		$userLogin = $this->findLogin($curLogin);
		$cookieField = $this->dom->createElement('cookieKey', $cookieKey);
		$userLogin->appendChild($cookieField);
		$this->dom->save($this->fileName);
	}
	
	//обновляем значение поля cookieKey
	public function updateCookieId($curLogin, $cookieKey) {
		$userLogin = $this->findLogin($curLogin);
		$cookieField = $userLogin->getElementsByTagName('cookieKey')->item(0);
		$cookieField->nodeValue = $cookieKey;
		$this->dom->save($this->fileName);
	}
	
	//удаляет поле cookieKey
	public function deleteCookieId($curLogin) {
		$userLogin = $this->findLogin($curLogin);
		$cookieField = $userLogin->getElementsByTagName('cookieKey')->item(0);
		$userLogin->removeChild($cookieField);
		$this->dom->save($this->fileName);
	}
	
	//получает значение поля cookieKey
	public function getCookieId($curLogin) {
		$userLogin = $this->findLogin($curLogin);
		$cookieField = $userLogin->getElementsByTagName('cookieKey')->item(0);
		
		if ($cookieField) {
			return $cookieField->nodeValue;
		}
		
		return false;
	}
	
	//шифрует пароль+"соль"
	private function hashPassword($pass, $salt) {
		$hashPass = sha1($pass.$salt);
		return $hashPass;
	}
	
	//сверяет полученные пароль с паролем из бд
	private function checkPassword($userPass, $userSalt, $reseivedPass) {
		
		$hashReseivedPass = $this->hashPassword($reseivedPass, $userSalt);
		
		if ($userPass == $hashReseivedPass) {
			return true;
		}
		
		return false;
		
	}
	
	//поллучить всех пользователей из бд
	private function getAllUsers() {
		$this->dom->load($this->fileName);
		$userTags = $this->dom->getElementsByTagName('user');
		return $userTags;
	}
	
	//проверяет наличие определенного пользователя по атрибуту login.
	//возвращает элемент с данными пользователя в случае успеха либо false в случае неудачи
	private function findLogin($curLogin) {
		$userTags = $this->getAllUsers();
		
		foreach ($userTags as $user) {
			
			if ($user->getAttribute('login') == $curLogin) {
				return $user;
			}
			
		}
		
		return false;
		
	}
	
	//проверяет наличие определенного email в бд
	//возвращает значение поля email в случае успеха либо false в случае неудачи
	private function findEmail($curEmail) {
		$userTags = $this->getAllUsers();
		
		foreach ($userTags as $user) {
			$userEmail = $user->getElementsByTagName('userEmail')->item(0)->nodeValue;
			
			if($userEmail == $curEmail) {
				return $userEmail;
			}
			
		}
		
		return false;
	}
}  
	
?>