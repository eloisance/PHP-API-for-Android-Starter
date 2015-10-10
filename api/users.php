<?php

/**
 * Récupère un utilisateur à partir de son email et son mot de passe
 */
$app->get('/users/:email/:password', function($email, $password) use ($app, $bdd) {

	$stmt = $bdd->prepare("SELECT * FROM users WHERE email = :email AND password = :password AND provider = 'default' ");
	$stmt->bindParam('email', $email, PDO::PARAM_STR);
	$stmt->bindParam('password', $password, PDO::PARAM_STR);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if($user['id'] == null) {
		$app->render(404, array(
			'error' => true,
            'msg'   => 'user not found',
        ));
	} else {
		$app->render(200, $user);
	}
    
});

/**
 * Récupère un utilisateur à partir de son id Google
 */
$app->get('/users/:id_google', function($id) use ($app, $bdd) {

    $stmt = $bdd->prepare("SELECT * FROM users WHERE id_google = :id_google AND provider = 'google' ");
	$stmt->bindParam('id_google', $id, PDO::PARAM_STR);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if($user['id'] == null) {
		$app->render(404, array(
			'error' => true,
            'msg'   => 'user not found',
        ));
	} else {
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
				$app->render(404);
			} else {
				$logger->info('Create new user default successfully !');
				$app->render(201, $user);
			}

		} else {
			$error = $stmt->errorInfo();
			$logger->info('Create new user default fail : ' . $error);
			$app->render(400);
		}
	} 
	// Email déjà enregistré 
	else {
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
		$logger->info('Create new user with google fail : ' . $error);
		$app->render(400);
	}
    
    
});

?>