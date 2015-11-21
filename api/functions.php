<?php 

/**
 * Check variables
 */
function varAreOk($params) {
	foreach ($params as $param) {
		if(!isset($param) || $param == '' || $param == null) {
			return false;
		}
	}
	return true;
}

/**
 * Return user based on id
 */
function getUserById($bdd, $id) {
	$stmt = $bdd->prepare("SELECT * FROM users WHERE id = :id");
	$stmt->bindParam('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
		return $user;
	}

	return null;
}

/**
 * Return user based on id and password
 */
function getUserByIdAndPassword($bdd, $id, $password) {
	$stmt = $bdd->prepare("SELECT * FROM users WHERE id = :id AND password = :password");
	$stmt->bindParam('id', $id, PDO::PARAM_INT);
	$stmt->bindParam('password', $password, PDO::PARAM_STR);
	$stmt->execute();
	if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
		return $user;
	}

	return null;
}

/**
 * Return user based on Google id
 */
function getGoogleUser($bdd, $idGoogle) {
	$stmt = $bdd->prepare("SELECT * FROM users WHERE id_google = :id_google AND provider = 'google' ");
	$stmt->bindParam('id_google', $idGoogle, PDO::PARAM_STR);
	$stmt->execute();
	if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
		return $user;
	}

	return null;
}

/**
 * Return user based on email and password
 */
function getDefaultUser($bdd, $email, $password) {
	$stmt = $bdd->prepare("SELECT * FROM users WHERE email = :email AND password = :password AND provider = 'default' ");
	$stmt->bindParam('email', $email, PDO::PARAM_STR);
	$stmt->bindParam('password', $password, PDO::PARAM_STR);
	$stmt->execute();
	if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
		return $user;
	}

	return null;
}

?>