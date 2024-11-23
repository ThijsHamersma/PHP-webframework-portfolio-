<?php

namespace App\Controllers;
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Middleware\Authenticate;
use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Nucleus\ErrorHandler;
use Nucleus\ORM\ORM;
use PDO;
use Psr\Http\Message\Request;
use Psr\Http\Message\Response;
use Psr\Http\Message\Uri;
use Psr\Http\Server\RequestHandlerInterface;


class DeckController implements RequestHandlerInterface {
    public function handle(Request $request): Response {
        $databasePath = __DIR__ . '/../../database.db';
        $pdo = new PDO('sqlite:' . $databasePath);

        $userModel = new User();
        $cardModel = new Card();
        $deckModel = new Deck();
        $errorhandler = new ErrorHandler();
        $auth = new Authenticate();
        $deckController = new DeckController();

        $orm = new ORM($pdo, [$userModel, $cardModel, $deckModel]);

        $navbarContent = file_get_contents(__DIR__ . '/../Views/navbar.html');
        $deckPage = file_get_contents(__DIR__ . '/../Views/decks.html');
        $deckInfo = file_get_contents(__DIR__ . '/../Views/deckInfo.html');
        $deckContent = '';
        $addDeckPage = file_get_contents(__DIR__ . '/../Views/addDeck.html');
        $currentUser = $request->getHeader('cookie')['Id'];

        $fields = [
            '{{first_item}}' => 'Home',
            '{{first_href}}' => 'home',
            '{{second_item}}' => 'Cards',
            '{{second_href}}' => 'http://localhost:8000/cards',
            '{{name}}' => 'WizzWave',
            '{{customComponent}}' => ''
        ];

        foreach ($fields as $placeholder => $value) {
            $navbarContent = str_replace($placeholder, $value, $navbarContent);
        }

        //Voeg admin aan navbar als gebruiker een admin is
        if (!($request->getHeader('cookie')['Auth'] == 'admin')) {
            $navbarContent = str_replace('<li class="nav-item">
                    <a class="nav-link" href=/admin>Admin</a>
                </li>', '', $navbarContent);
        }

        $path = $request->getUri()->getPath();

        if (str_starts_with($path, '/decks/deletedeck/')){
            $deckId = substr($path, strlen('/decks/deletedeck/'));
            $orm->delete('deck', $deckId);

            $uri = new Uri('/decks');
            $newRequest = new Request(200, $uri, '', $request->getHeaders());
            $body = $deckController->handle($newRequest)->getBody();
            $alert = $errorhandler->handle('success', 'Deck deleted successfully', 'code: 200');
            return new Response(200, $request->getHeaders(), 'OK', $alert . $body);
        }

        //Deck toevoegen pagina
        if ($request->getUri()->getPath() == '/decks/adddeck') {
            if ($request->getMethod() == 'POST') {
                $name = $request->getBody()['deck_name'];
                $cardsarray = [];
                $cardIDs = [];
                for ($i = 1; $i <= 5; $i++) {
                    $cardsarray[] = $request->getBody()["card_$i"];
                }
                $orm->addDeck($name, $currentUser);
                foreach ($cardsarray as $card){
                    $cardIDs[] = $orm->getCardIdFromName($card);
                }
                $addedDeck = $orm->getDeck($name, $currentUser)[0]['id'];
                foreach ($cardIDs as $card) {
                    $orm->addCardToDeck($addedDeck, $card[0]['id']);
                }

                $uri = new Uri('/decks');
                $newRequest = new Request('GET', $uri, '', $request->getHeaders());
                $response = $auth->process($newRequest);
                $alert = $errorhandler->handle('success', 'Deck created successfully!', 'code: 200');
                return $response->withBody($alert . $response->getBody());

            }

            $cards = $orm->getAll('card');
            $cardOptions = '';
            foreach ($cards as $card) {
                $cardOptions .= "<option value='{$card['name']}'>{$card['name']}</option>";
            }

            $addDeckPage = str_replace('{{cardoptions}}', $cardOptions, $addDeckPage);
            return new Response(200, [], "Ok",$navbarContent . $addDeckPage);
        }

        $decks = $orm->getDecks($currentUser);
        $decksJson = json_encode($decks);
        $decksData = json_decode($decksJson, true);

        foreach ($decksData as $deck) {
            $deckCards = $orm->getDeckCards($deck['id']);
            $deckInfo2 = str_replace('{{name}}', $deck['name'], $deckInfo);
            $href = "/decks/deletedeck/" . $deck['id'];
            $deckInfo3 = str_replace('{{delete}}', '<a href="' . $href . '" class="btn btn-danger" role="button">Delete</a>', $deckInfo2);
            $deckCardsContent = '';
            $cardNumber = 1;

            foreach ($deckCards as $cards) {
                $cardName = $orm->getCard($cards['card_id'])[0]['name'];
                $deckCardsContent .= "<div class='card'>#$cardNumber: $cardName</div>";
                $cardNumber ++;
            }

            $deckInfo3 = str_replace('{{card}}', $deckCardsContent, $deckInfo3);
            $deckContent .= $deckInfo3;
        }
        $deckPage = str_replace('{{deck}}', $deckContent, $deckPage);
        return new Response(200, [], "Ok", $navbarContent . $deckPage);
    }
}