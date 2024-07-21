<?php
session_start();

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function authenticateUser($code) {
    // Exchange code for access token
    $token_url = 'https://github.com/login/oauth/access_token';
    $data = [
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $code
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($token_url, false, $context);
    
    parse_str($result, $token);
    $access_token = $token['access_token'];
    
    // Get user info
    $user_url = 'https://api.github.com/user';
    $options = [
        'http' => [
            'header'  => "Authorization: token $access_token\r\nUser-Agent: PHP"
        ]
    ];
    
    $context  = stream_context_create($options);
    $user_data = json_decode(file_get_contents($user_url, false, $context), true);
    
    $db = getDbConnection();
    
    checkTableExists('users');

    createUser($db, $user_data, $access_token);
    
    $_SESSION['user_id'] = $db->lastInsertRowID();
}

function getAuthURL($state){
    return  "https://github.com/login/oauth/authorize?" . http_build_query([
        'client_id' => GITHUB_CLIENT_ID,
        'redirect_uri' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
        'state' => $state,
        'scope' => 'user'
    ]);
}