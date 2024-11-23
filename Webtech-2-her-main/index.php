<?php
namespace App;

use App\Middleware\Authenticate;
use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Nucleus\Container\Container;
use Nucleus\ORM\ORM;
use Nucleus\Router;
use PDO;
use Psr\Http\Message\Request;
use Psr\Http\Message\Uri;

require_once __DIR__ . '/vendor/autoload.php';

$databasePath = __DIR__ . '/database.db';
$container = new Container();
$container->set('pdo', new PDO('sqlite:' . $databasePath) );
$pdo = $container->get('pdo');


$container->set('user', new User());
$container->set('card',new Card());
$container->set('deck', new Deck());

$userModel = $container->get('user');
$cardModel = $container->get('card');
$deckModel = $container->get('deck');

$container->set('orm', new ORM($pdo, [$userModel, $cardModel, $deckModel]) );
$orm = $container->get('orm');
$orm->init();

//$orm->addUser("test", "test@admin.nl", password_hash("test", PASSWORD_DEFAULT), "admin");
//$orm->addUser("test", "test@user.nl", password_hash("test", PASSWORD_DEFAULT), "user");
//$orm->addUser("test", "test@premium.nl", password_hash("test", PASSWORD_DEFAULT), "premium");
//$orm->addCard("Raging Orc", 5, 8, "Orcs", "Common", 4.99, "raging_orc.png");
//$orm->addCard("Raging Orcc", 5, 8, "Orcs", "Common", 4.99, "raging_orc.png");
//$orm->addCard("Raging Orccc", 5, 8, "Orcs", "Common", 4.99, "raging_orc.png");
//$orm->addCard("Raging Orcccc", 5, 8, "Orcs", "Common", 4.99, "raging_orc.png");
//$orm->addCard("Shadow Assassin", 15, 1, "Assassins", "Rare", 8.99, "shadow_assassin.png");
//$orm->addDeck('Test', 1);
//$orm->addDeck('Deck 2', 1);
//$orm->addCardToDeck(1, 1);
//$orm->addCardToDeck(1, 2);
//$orm->addCardToDeck(2, 1);
//$orm->addCardToDeck(2, 2);
//$orm->clear('deck');
//$orm->clear('deck_card');
//$orm->updateRole(6, 'premium');

$method = $_SERVER['REQUEST_METHOD'];
$path = strtolower(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$container->set('uri', new Uri($path));
$uri = $container->get('uri');
$body = array_merge($_POST, $_FILES);
$cookies = $_COOKIE;
$headers = [];

foreach ($cookies as $name => $value) {
    $headers['cookie'][$name] = $value;
}

$container->set('router', new Router());
$container->set('request', new Request($method, $uri, $body, $headers));
$container->set('authenticate', new Authenticate());

$auth = $container->get('authenticate');
$request = $container->get('request');
$response = $auth->process($request);

echo $response->getBody();