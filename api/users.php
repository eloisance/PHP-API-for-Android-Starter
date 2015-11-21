<?php
/**
 * @author Emmanuel Loisance
 * 
 * GET /users/:email/:password
 * GET /users/:id_google
 * POST /users/create/default
 * POST /users/create/google
 * PUT /users/:id
 * PUT /users/password/:id
 * DELETE /users/:id
 */


/**
 * Get user based on email and password
 * 200 : user found
 * 404 : user not found
 */
$app->get('/users/:email/:password', function($email, $password) use ($app, $bdd, $logger) {

	if($user = getDefaultUser($bdd, $email, $password)) {
		$logger->info('Get user '.$user['id'].' from default success');
		$app->render(200, $user);
	} else {
		$logger->error('Get user from default fail -> user not found');
		$app->render(404);
	}

});


/**
 * Get user based on Google id
 * 200 : user found
 * 404 : user not found
 */
$app->get('/users/:id_google', function($idGoogle) use ($app, $bdd, $logger) {

	if($user = getGoogleUser($bdd, $idGoogle)) {
		$logger->info('Get user '.$user['id'].' from google success');
		$app->render(200, $user);
	} else {
		$logger->error('Get user from google fail -> user not found');
		$app->render(404);
	}

});


/**
 * Add user based on default form
 * Return this new user 
 * 201 : user created
 * 400 : error
 * 404 : new user not found 
 * 409 : email already used
 */
$app->post('/users/create/default', function() use ($app, $bdd, $logger) {

	$firstname = $app->request()->params('firstname');
	$lastname = $app->request()->params('lastname');
	$email = $app->request()->params('email');
	$password = $app->request()->params('password');
	$provider = "default";
	$date = date('Y-m-d');

	// check params
	$params = [$firstname, $lastname, $email, $password];
	if(!varAreOk($params)) {
		$logger->error('Create new user default fail -> vars are not ok');
		$app->render(400);
	}

	// Check if email already used
	$stmt = $bdd->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
	$stmt->bindParam("email", $email, PDO::PARAM_STR);
	$stmt->execute();
	$count = $stmt->fetchColumn();

	// Email doesn't exist yet
	if($count == 0) {
		$stmt = $bdd->prepare("INSERT INTO users (firstname, lastname, email, password, provider, registration_date) VALUES (:firstname, :lastname, :email, :password, :provider, :registration_date)");
		$stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
		$stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
		$stmt->bindParam(':email', $email, PDO::PARAM_STR);
		$stmt->bindParam(':password', $password, PDO::PARAM_STR);
		$stmt->bindParam(':provider', $provider, PDO::PARAM_STR);
		$stmt->bindParam(':registration_date', $date, PDO::PARAM_STR);

		// If user added successfully, get it and render it
		if($stmt->execute()) {
			if($user = getDefaultUser($bdd, $email, $password)) {
				$logger->info('Create new user: '.$user['id'].' default successfully');
				$app->render(201, $user);
			} else {
				$logger->error('Create new user default fail -> user not found');
				$app->render(404);
			}
		} else {
			$error = $stmt->errorInfo();
			$logger->error('Create new user default fail -> ' . $error);
			$app->render(400);
		}
	} 
	// Email already used
	else {
		$logger->error('Create new user default fail -> email already exist');
		$app->render(409);
	}
  
});


/**
 * Add user based on Google authentication
 * Return this new user 
 * 201 : user created
 * 400 : error
 * 404 : new user not found
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
	$stmt->bindParam(':registration_date', $date);

	// If user added successfully, get it and render it
	if($stmt->execute()) {
		if($user = getGoogleUser($bdd, $idGoogle)) {
			$logger->info('Create new user: '.$idGoogle.' with google successfully');
			$app->render(201, $user);
		} else {
			$logger->error('Create new user: '.$idGoogle.' with google fail -> user not found after insert');
			$app->render(404);
		}
	} else {
		$error = $stmt->errorInfo();
		$logger->error('Create new user with google fail -> '.$error);
		$app->render(400);
	}

});


/**
 * Update user based on id
 * Return updated user
 * 200 : user updated
 * 400 : error
 * 404 : user not found
 */
$app->put('/users/:id', function($id) use ($app, $bdd, $logger) {

	$firstname = $app->request()->params('firstname');
	$lastname = $app->request()->params('lastname');
	$email = $app->request()->params('email');
	$phone = $app->request()->params('phone'); 

	// Get user
	if($user = getUserById($bdd, $id)) {
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
			if($user = getUserById($bdd, $id)) {
				$logger->info('User: '.$id.' updated');
		        $app->render(200, $user);
			} else {
				$logger->error('User: '.$id.' update fail -> user updated not found');
				$app->render(404);
			}
		} else {
			$logger->error('User: '.$id.' update fail');
			$app->render(400);
		}
	} else {
		$logger->error('User: '.$id.' update fail -> user not found');
		$app->render(404);
	}

});


/**
 * Update user password (only default provider)
 * Return new user
 * 200 : user password updated
 * 400 : error
 * 404 : user not found
 */
$app->put('/users/password/:id', function($id) use ($app, $bdd, $logger) {

	$password = $app->request()->params('password');
	$newPassword = $app->request()->params('new_password');

	// Get user
	if($user = getUserByIdAndPassword($bdd, $id, $password)) {
		$stmt = $bdd->prepare("UPDATE users SET password = :new_password WHERE id = :id");
		$stmt->bindParam('id', $id, PDO::PARAM_INT);
		$stmt->bindParam('new_password', $newPassword, PDO::PARAM_STR);
		// Update ok
		if($stmt->execute()) {
			// Get new user
			if($user = getUserById($bdd, $id)) {
				$logger->info('User: '.$id.' password update');
		        $app->render(200, $user);
			} else {
				$logger->error('User: '.$id.' password update but user not found after update');
				$app->render(404);
			}
		} else {
			$logger->error('User: '.$id.' password fail update');
		    $app->render(400);
		}
	} else {
		$logger->error('User: '.$id.' password fail -> user not found');
		$app->render(404);
	}

});


/**
 * Delete user based on id
 * 200 : user deleted
 * 400 : error
 * 404 : user not found
 */
$app->delete('/users/:id', function($id) use ($app, $bdd, $logger) {

	if($user = getUserById($bdd, $id)) {
		$stmt = $bdd->prepare("DELETE FROM users WHERE id = :id");
		$stmt->bindParam('id', $id, PDO::PARAM_INT);
		if($stmt->execute()) {
			$logger->info('User: '.$id.' deleted');
			$app->render(200);
		} else {
			$logger->error('User: '.$id.' delete fail');
			$app->render(400);
		}
	} else {
		$logger->error('User: '.$id.' can\'t be deleted -> user not found');
		$app->render(404);
	}

});

?>