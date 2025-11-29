<?php


/**
 * Step 1: Require the Slim Framework using Composer's autoloader
 *
 * If you are not using Composer, you need to load Slim Framework with your own
 * PSR-4 autoloader.
 */
require '../vendor/autoload.php';

use API\Middleware\TokenOverBasicAuth;
use API\Exception;
use API\Exception\ValidationException;

include('../lib/DanCoin.php');


/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new Slim\App();
// Enable CSRF protection for POST/PUT/DELETE requests
$app->add(new \Slim\Csrf\Guard);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer('/var/www/html/templates/');
};

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && substr($path, -1) == '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));
        return $response->withRedirect((string)$uri, 301);
    }

    return $next($request, $response);
});

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */
$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'welcome.html');
});

$app->get('/create_player/[{name}]', function ($request, $response, $args) {
	$dc = new DanCoin();
	if ($dc->createPlayer($args['name'])) {
		$output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
    } else
    	$response->write(json_encode(array("error" => "cannot create player")));
    
    header("Content-Type: application/json");
    return $response;
})->setArgument('name', 'World!');

$app->get('/balance', function ($request, $response, $args) {
   $dc = new DanCoin();
	if ($dc->getBalance()) {
		$output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"balance" => $dc->__get("balance"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
    } else
    	$response->write(json_encode(array("error" => "cannot get player balance")));
    
    header("Content-Type: application/json");
    return $response;
});

$app->get('/cashout/[{sendaddress}]', function ($request, $response, $args) {
   $dc = new DanCoin();
	if ($dc->cashout($args['sendaddress'])) {
		$output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"balance" => $dc->__get("balance"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
    } else
    	$response->write(json_encode(array("error" => "cannot cashout, try later after more confirmations")));
    
    header("Content-Type: application/json");
    return $response;
})->setArgument('name', 'World!');

$app->get('/deal', function ($request, $response, $args) {
    $dc = new DanCoin();
    $output=array();
    if ($dc->dealNewGame()) {
        $output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"balance" => $dc->__get("balance"),"dealer_hand" => $dc->__get("dealerHand"),"player_hand" => $dc->__get("userHand"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
   	} else 
   		$response->write(json_encode(array("error" => "cannot deal new game, check your balance or need to hit or stand")));
    return $response;
});

$app->get('/lastplay', function ($request, $response, $args) {
    $dc = new DanCoin();
    $output=array();
    if ($dc->getPlayStatus()) {
        $output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"balance" => $dc->__get("balance"),"dealer_hand" => $dc->__get("dealerHand"),"player_hand" => $dc->__get("userHand"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
   	} else 
   		$response->write(json_encode(array("error" => "cannot deal new game, check your balance or need to hit or stand")));
    return $response;
});

$app->get('/hit', function ($request, $response, $args) {
    $dc = new DanCoin();
    $output=array();
    if ($dc->hitme()) {
        $output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"balance" => $dc->__get("balance"),"dealer_hand" => $dc->__get("dealerHand"),"player_hand" => $dc->__get("userHand"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
   	} else 
   		$response->write(json_encode(array("error" => "cannot give you a hit, perhaps you need to deal a new hand")));
    return $response;
});

$app->get('/stand', function ($request, $response, $args) {
    $dc = new DanCoin();
    $output=array();
    if ($dc->stand()) {
        $output = array("wallet" => $dc->__get("wallet"),"player" => $dc->__get("player"),"balance" => $dc->__get("balance"),"dealer_hand" => $dc->__get("dealerHand"),"player_hand" => $dc->__get("userHand"),"access_token" => $dc->__get("access_token"));
    	$response->write(json_encode($output));
   	} else 
   		$response->write(json_encode(array("error" => "cannot take a stand, perhaps you need to deal a new hand")));
    return $response;
});



$app->get('/funds/{name}', function ($request, $response, $args) {
    // XSS fix: escape user input before output
    $response->write("Hello, " . htmlspecialchars($args['name'], ENT_QUOTES, 'UTF-8'));
    return $response;
})->setArgument('name', 'World!');

$app->get('/deposit/{name}/{amount}', function ($request, $response, $args) {
    // XSS fix: escape user input before output
    $response->write("Hello, " . htmlspecialchars($args['name'], ENT_QUOTES, 'UTF-8'));
    return $response;
})->setArgument('name', 'World!');

$app->get('/bet/{name}[/{amount}]', function ($request, $response, $args) {
    // XSS fix: escape user input before output
    $response->write("Hello, " . htmlspecialchars($args['name'], ENT_QUOTES, 'UTF-8'));
    return $response;
})->setArgument('name', 'World!');





$app->get('/stay/{name}', function ($request, $response, $args) {
    // XSS fix: escape user input before output
    $response->write("Hello, " . htmlspecialchars($args['name'], ENT_QUOTES, 'UTF-8'));
    return $response;
})->setArgument('name', 'World!');

$app->get('/hello/[{name}]', function ($request, $response, $args) {
    // XSS fix: escape user input before output
    $response->write("Hello, " . htmlspecialchars($args['name'], ENT_QUOTES, 'UTF-8'));
    return $response;
})->setArgument('name', 'World!');

/// Custom 404 error
$app->notFound(function () use ($app) {

    $mediaType = $app->request->getMediaType();

    $isAPI = (bool) preg_match('|^/api/v.*$|', $app->request->getPath());


    if ('application/json' === $mediaType || true === $isAPI) {

        $app->response->headers->set(
            'Content-Type',
            'application/json'
        );

        echo json_encode(
            array(
                'code' => 404,
                'message' => 'Not found'
            ),
            JSON_PRETTY_PRINT
        );

    } else {
        echo '<html>
        <head><title>404 Page Not Found</title></head>
        <body><h1>404 Page Not Found</h1><p>The page you are
        looking for could not be found.</p></body></html>';
    }
});



/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
