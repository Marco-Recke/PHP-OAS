<?php
// start session if none existing
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}


// set user and possible admin privileges
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = $_SERVER['PHP_AUTH_USER'];

    // is filled later if admin imitates a repository
    $_SESSION['currentrep'] = 0;
    // set admin mode
    if (isAdmin()) {
    	$_SESSION['admin'] = true;
    }
}

// if admin is logged in we need additional informations to populate the repository dropdown list
if (isset($_SESSION['admin'])) {
  // check if repository informations are already saved in session otherwise make an API call
  if (!isset($_SESSION['reps'])) {
        if ($reps = getIdAndNameFromAllRepositories()) {
            $_SESSION['reps'] = $reps;
        }
  }

  // the repository to imitate is called by its id in the Request
  if (isset($_REQUEST['rep'])) {
        $_SESSION['currentrep'] = $_REQUEST['rep'];
    }
}

/**
 * make an API call to fetch the the id and name from all repositories
 * @return array with the id and name of the repositories
 */
function getIdAndNameFromAllRepositories() {
    $login = $_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW'];
    $URL = "https://".$login . '@' . BASE_URL_WITHOUTHTTP . API_PATH_RELATIVE . "index.php?do=status&format=json";
    $data = @file_get_contents($URL, null, stream_context_create(array(
        'http' => array(
            'ignore_errors'    => true,
            'protocol_version' => 1.1,
            'header'           => array(
                'Connection: close'
            ),
        ),
    )));

    if (!$data)
        return false;

    $data = json_decode($data,true);

    $reps = array();
    foreach ($data as $id => $dp) {
      $reps[$id] = array(
        'name' => $dp['repositoryname']);
    }
    return $reps;
}

/**
 * Checks if user is admin
 * @return true if user is admin, false if not
 */
function isAdmin()
{
	include(API_CONFIG_PATH);
	return ($_SESSION['user'] == $config['rest']['superHTTPUser']);
}

/**
 * Kills the session (as seen in http://php.net/manual/en/function.session-destroy.php)
 */
function killSession()
{
    // Initialize the session.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();
}

?>