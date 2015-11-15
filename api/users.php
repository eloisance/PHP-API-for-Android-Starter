<?php

/**
 * Récupère un utilisateur à partir de son email et son mot de passe
 */
$app->get('/users/:email/:password', function($email, $password) use ($app, $bdd, $logger) {

	$stmt = $bdd->prepare("SELECT * FROM users WHERE email = :email AND password = :password AND provider = 'default' ");
	$stmt->bindParam('email', $email, PDO::PARAM_STR);
	$stmt->bindParam('password', $password, PDO::PARAM_STR);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if($user['id'] == null) {
		$logger->error('Get user from default fail, user not found');
		$app->render(404, array(
			'error' => true,
            'msg'   => 'user not found',
        ));
	} else {
		$logger->info('Get user '.$user['id'].' from default success');
		$app->render(200, $user);
	}
    
});


/**
 * Récupère un utilisateur à partir de son id Google
 */
$app->get('/users/:id_google', function($id) use ($app, $bdd, $logger) {

    $stmt = $bdd->prepare("SELECT * FROM users WHERE id_google = :id_google AND provider = 'google' ");
	$stmt->bindParam('id_google', $id, PDO::PARAM_STR);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if($user['id'] == null) {
		$logger->error('Get user from google fail, user not found');
		$app->render(404, array(
			'error' => true,
            'msg'   => 'user not found',
        ));
	} else {
		$logger->info('Get user '.$user['id'].' from google success');
		$app->render(200, $user);
	}
    
});


/**
 * Ajoute un utilisateur à partir des informations saisies dans l'application
 * Retourne l'utilisateur crée (avec son id)
 * 201 : User created
 * 
 */
$app->post('/users/create/default', function() use ($app, $bdd, $logger) {

	$firstname = $app->request()->params('firstname');
	$lastname = $app->request()->params('lastname');
	$email = $app->request()->params('email');
	$password = $app->request()->params('password');
	$provider = "default";
	$date = date('Y-m-d');

	// Vérification des variables
	$params = [$firstname, $lastname, $email, $password];
	if(!varAreOk($params)) {
		$logger->error('Create new user default fail, vars are not ok');
		$app->render(400);
	}

	// Vérifie que l'email n'existe pas déjà en base
	$stmt = $bdd->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
	$stmt->bindParam("email", $email, PDO::PARAM_STR);
	$stmt->execute();
	$count = $stmt->fetchColumn();

	// L'email n'existe pas encore, on ajoute
	if($count == 0) {
		$stmt = $bdd->prepare("INSERT INTO users (firstname, lastname, email, password, provider, registration_date) VALUES (:firstname, :lastname, :email, :password, :provider, :registration_date)");
		$stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
		$stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
		$stmt->bindParam(':email', $email, PDO::PARAM_STR);
		$stmt->bindParam(':password', $password, PDO::PARAM_STR);
		$stmt->bindParam(':provider', $provider, PDO::PARAM_STR);
		$stmt->bindParam(':registration_date', $date, PDO::PARAM_STR);

		// Si user bien ajouté on le recherche pour renvoyer le user complet 
		if($stmt->execute()) {
			$stmt = $bdd->prepare("SELECT * FROM users WHERE email = :email AND password = :password AND provider = '$provider' ");
			$stmt->bindParam('email', $email, PDO::PARAM_STR);
			$stmt->bindParam('password', $password, PDO::PARAM_STR);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if($user['id'] == null) {
				$logger->error('Create new user default fail, user not found');
				$app->render(404);
			} else {
				$logger->info('Create new user default successfully !');
				$app->render(201, $user);
			}

		} else {
			$error = $stmt->errorInfo();
			$logger->error('Create new user default fail : ' . $error);
			$app->render(400);
		}
	} 
	// Email déjà enregistré 
	else {
		$logger->error('Create new user default fail, email already exist');
		$app->render(409);
	}
  
});


/**
 * Ajoute un utilisateur à partir de son id Google
 * Retourne l'utilisateur crée (avec son id)
 */
$app->post('/users/create/google', function() use ($app, $bdd, $logger) {

	$idGoogle = $app->request()->params('idGoogle');
	$firstname = $app->request()->params('firstname');
	$lastname = $app->request()->params('lastname');
	$email = $app->request()->params('email');
	$provider = "google";
	$date = date('Y-m-d');

	$stmt = $bdd->prepare("INSERT INTO users (id_google, firstname, lastname, email, provider, registration_date) VALUES (:id_google, :firstname, :lastname, :email, :provider, :registration_date)");
	$stmt->bindParam(':id_google', $idGoogle);
	$stmt->bindParam(':firstname', $firstname);
	$stmt->bindParam(':lastname', $lastname);
	$stmt->bindParam(':email', $email);
	$stmt->bindParam(':provider', $provider);
	$stmt->bindParam(':registration_date', $date, PDO::PARAM_STR);

	// Si user bien ajouté on le recherche pour renvoyer le user complet 
	if($stmt->execute()) {
		$stmt = $bdd->prepare("SELECT * FROM users WHERE id_google = :id_google AND provider = '$provider' ");
		$stmt->bindParam('id_google', $idGoogle, PDO::PARAM_STR);
		$stmt->execute();
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if($user['id'] == null) {
			$logger->error('Create new user with google fail, user not found');
			$app->render(404, array(
				'error' => true,
	            'msg'   => 'user not found',
	        ));
		} else {
			$logger->info('Create new user with google successfully !');
			$app->render(201, $user);
		}

	} else {
		$error = $stmt->errorInfo();
		$logger->error('Create new user with google fail : ' . $error);
		$app->render(400);
	}

});


/**
 * Met à jour un utilisateur à partir de son id
 * Retourne les informations du nouvel utilisateur
 */
$app->put('/users/:id', function($id) use ($app, $bdd, $logger) {

	$firstname = $app->request()->params('firstname');
	$lastname = $app->request()->params('lastname');
	$email = $app->request()->params('email');
	$phone = $app->request()->params('phone'); 

	// Get user
	$stmt = $bdd->prepare("SELECT * FROM users WHERE id = :id");
	$stmt->bindParam('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if($user['id'] == null) {
		$logger->error('La tentative de mise à jour du user:' . $id . ' a échouée -> user not found');
		$app->render(404, array(
			'error' => true,
            'msg'   => 'user not found',
        ));
	} else {

		// if provider is google, dont update google account information like email, name.. 
		if($user['provider'] == 'google') {
			$stmt = $bdd->prepare("UPDATE users SET phone = :phone WHERE id = :id");
			$stmt->bindParam('id', $id, PDO::PARAM_INT);
			$stmt->bindParam('phone', $phone, PDO::PARAM_STR);
		} else if($user['provider'] == 'default') {
			$stmt = $bdd->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, email = :email, phone = :phone WHERE id = :id");
			$stmt->bindParam('id', $id, PDO::PARAM_INT);
			$stmt->bindParam('firstname', $firstname, PDO::PARAM_STR);
			$stmt->bindParam('lastname', $lastname, PDO::PARAM_STR);
			$stmt->bindParam('email', $email, PDO::PARAM_STR);
			$stmt->bindParam('phone', $phone, PDO::PARAM_STR);
		} else {
			$logger->error('Update user fail -> bad provider');
			$app->render(400);
		}

		// Update ok 
		if($stmt->execute()) {
			// Get new user
			$stmt = $bdd->prepare("SELECT * FROM users WHERE id = :id");
			$stmt->bindParam('id', $id, PDO::PARAM_INT);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if($user['id'] == null) {
				$logger->error('La tentative de mise à jour du user:' . $id . ' a échouée -> user updated not found');
				$app->render(404, array(
					'error' => true,
		            'msg'   => 'user not found',
		        ));
			} else {
				$logger->info('User:' . $id . ' a été mis correctement à jour');
		        $app->render(200, $user);
			}	
		} else {
			$logger->error('La tentative de mise à jour du user:' . $id . ' a échouée -> update fail');
		}
	}

});


/**
 * Supprime l'utilisateur à partir de son id
 */
$app->delete('/users/:id', function($id) use ($app, $bdd, $logger) {

	// Get user
	$stmt = $bdd->prepare("SELECT * FROM users WHERE id = :id");
	$stmt->bindParam('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if($user['id'] == null) {
		$logger->error('Impossible de supprimer le user:' . $id . ' -> user not found');
		$app->render(404, array(
			'error' => true,
            'msg'   => 'user not found',
        ));
	} else {
		$stmt = $bdd->prepare("DELETE FROM users WHERE id = :id");
		$stmt->bindParam('id', $id, PDO::PARAM_INT);
		if($stmt->execute()) {
			$logger->info('Delete user: ' . $id);
			$app->render(200);
		} else {
			$logger->error('Delete user fail, id: ' . $id);
			$app->render(400);
		}
	}

});

?>