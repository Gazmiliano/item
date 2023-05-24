<?php

// Время жизни токена в минутах
const TOKEN_TIME = 30;

// Переменные для подключение к БД
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gaziz';
$db_options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
	PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8 COLLATE utf8_general_ci"
];

if(!empty($_POST)) {
	$request = $_POST;

    // Запрос на токен
	if ($request['get'] === 'token') {
		
		// Генерация токена
		$token = generateRandomString(12);		
				
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "INSERT `users`
					(`token`, `dt_token`)
				VALUES 
					(:token, NOW + INTERVAL :tokenLifeTime MINUTE)";
					
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'token' => $token, 
			'tokenLifeTime' => TOKEN_TIME
			));
			
		// Успешний ответ
		echo $token;
		exit;
	}

	
	// Если нету токена то отбрасываем
	if (!isset($request['token']) || $request['token'] === '') {
		echo 'need_token'; exit; 
	}
	
	// Ищем token в БД
    $sql = 'SELECT 
               token
            FROM users 
            WHERE                 
                token = :token
                AND dt_token >= NOW() ';
    $sth = $dbh->prepare($sql); $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
	
	// Если токен не верный то отбрасываем
	if (empty($result)) {
        echo 'wrong_token'; exit;
    }
	
	// Добавить item
	if (isset($request['set']) && $request['set'] === 'addItem') { 
	
		// Валидация
		if (!isset($request['name']) || $request['name'] === '') {
			echo 'need_name'; exit;
		}
		
		if (!isset($request['phone']) || $request['phone'] === '') {
			echo 'need_phone'; exit;
		}
		
		if (!filter_var($request['phone'], FILTER_VALIDATE_INT)) { 
			echo 'wrong_phone_format'; exit; 
		}
		
		if (!isset($request['key']) || $request['key'] === '') {
			echo 'need_key'; exit;
		}
		
		// Проверка на дублей
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "SELECT name
				FROM
				WHERE
					name = :name";
					
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'name' => $request['name']
			));
		$number_of_rows = $sth->fetchColumn(); 
		
		if ($number_of_rows > 0) {
			echo 'err_duplicated_name'; exit;
		}
		
		// Запрос на добавление
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "INSERT `item`
					(`name`, `phone`, `key`, `created_at`)
				VALUES 
					(:name, :phone, :key, NOW())";
					
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'name' => $request['name'], 
			'phone' => $request['phone'],
			'key' => $request['key']
			));
		exit;
	}	
	
	// Удалить item
	if (isset($request['set']) && $request['set'] === 'delItem') { 
	
		// Валидация
		if (!isset($request['setID']) || $request['setID'] === '') {
			echo 'need_setID'; exit;
		}
		
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "DELETE FROM `item`
				WHERE
					id = :id";
					
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'id' => $request['setID'] 
			));
		exit;
	}
	
	// Обновить item
	if (isset($request['set']) && $request['set'] === 'updItem') { 
	
		// Валидация
		if (!isset($request['setID']) || $request['setID'] === '') {
			echo 'need_setID'; exit;
		}

		if (!isset($request['name']) || $request['name'] === '') {
			echo 'need_name'; exit;
		}
		
		if (!isset($request['phone']) || $request['phone'] === '') {
			echo 'need_phone'; exit;
		}
		
		if (!filter_var($request['phone'], FILTER_VALIDATE_INT)) { 
			echo 'wrong_phone_format'; exit; 
		}
		
		if (!isset($request['key']) || $request['key'] === '') {
			echo 'need_key'; exit;
		}
		
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "UPDATE `item`
				SET	
					name = :name,
					phone = :phone,
					key = :key,
					updated_at = NOW()
				WHERE id = :id";
				
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'id' => $request['setID'],
			'name' => $request['name'], 
			'phone' => $request['phone'],
			'key' => $request['key']
			));
		exit;
	}

	// Получение информации о элементе 
	if (isset($request['get']) && $request['get'] === 'getItem') { 
		
		// Валидация
		if (!isset($request['getID']) || $request['getID'] === '') {
			echo 'need_getID'; exit;
		}
		
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "SELECT *
				FROM `item`
				WHERE id = :id";
				
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'id' => $request['getID']
			));
			
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($result);
		exit;
	}

	// Получение списка
	if (isset($request['get']) && $request['get'] === 'getListItem') { 
		
		// Валидация
		if (!isset($request['limBegin']) || $request['limBegin'] === '') {
			echo 'need_limBegin'; exit;
		}
		
		if (!isset($request['limEnd']) || $request['limEnd'] === '') {
			echo 'need_limEnd'; exit;
		}
		
		$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $db_options);    
		$sql = "SELECT
					name,
					phone
				FROM `item`
				LIMIT :limBegin,:limEnd";
				
		$sth = $dbh->prepare($sql); 
		$sth->execute(array(
			'limBegin' => $request['limBegin'],
			'limEnd' => $request['limEnd']
			));
			
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($result);
		exit;
	}
	
	echo 'wrong_request';
} else {
	{ echo 'need_request'; }
}

// *************************************************************************************
// Функция
// Для генераций строк, Пример для токена
// *************************************************************************************
function generateRandomString($length = 6)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    } return $randomString;
}