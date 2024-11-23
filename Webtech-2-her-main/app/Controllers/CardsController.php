<?php

namespace App\Controllers;
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Nucleus\ORM\ORM;
use PDO;
use Psr\Http\Message\Request;
use Psr\Http\Message\Response;
use Psr\Http\Server\RequestHandlerInterface;

class CardsController implements RequestHandlerInterface {
    public function handle(Request $request): Response {
        $databasePath = __DIR__ . '/../../database.db';
        $pdo = new PDO('sqlite:' . $databasePath);

        $userModel = new User();
        $cardModel = new Card();
        $deckModel = new Deck();

        $orm = new ORM($pdo, [$userModel, $cardModel, $deckModel]);

        $fields = [
            '{{first_item}}' => 'Home',
            '{{second_item}}' => 'Decks',
            '{{first_href}}' => 'Home',
            '{{second_href}}' => 'Decks',
            '{{name}}' => 'WizzWave',
            '{{customComponent}}' => '            <form class="d-flex" action="/search" method="GET">
                <input class="form-control me-2" type="search" id="searchInput" placeholder="Search" aria-label="Search" name="query">
            </form>'
        ];

        $navbarContent = file_get_contents(__DIR__ . '/../Views/navbar.html');

        foreach ($fields as $placeholder => $value) {
            $navbarContent = str_replace($placeholder, $value, $navbarContent);
        }

        $homeContent = file_get_contents(__DIR__ . '/../Views/cards.html');
        $cardContent = file_get_contents(__DIR__ . '/../Views/cardInfo.html');

        //Admin aan navbar toevoegen
        if (!($request->getHeader('cookie')['Auth'] == 'admin')) {
            $navbarContent = str_replace('<li class="nav-item">
                    <a class="nav-link" href=/admin>Admin</a>
                </li>', '', $navbarContent);
        }

        $cards = $orm->getAll("Card");
        $cardsJson = json_encode($cards);
        $cardsData = json_decode($cardsJson, true);

        foreach ($cardsData as $card) {
            $cardContent2 = str_replace('{{name}}', $card['name'], $cardContent);
            $cardContent2 = str_replace('{{attack}}', $card['attack'], $cardContent2);
            $cardContent2 = str_replace('{{defense}}', $card['defense'], $cardContent2);
            $cardContent2 = str_replace('{{rarity}}', $card['rarity'], $cardContent2);
            $cardContent2 = str_replace('{{series}}', $card['series'], $cardContent2);
            $cardContent2 = str_replace('{{market_price}}', $card['market_price'], $cardContent2);
            $cardContent2 = str_replace('{{image}}', 'http://localhost:8000/app/src/' . $card['image'], $cardContent2);

            $cardContent2 = str_replace('<div class="card-body">
        {{edit}}
        {{delete}}
    </div>', '', $cardContent2);
            $homeContent .= $cardContent2;
        }
        return new Response(200, [], "", $navbarContent . $homeContent);
    }
}