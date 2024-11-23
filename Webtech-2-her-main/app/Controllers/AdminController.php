<?php

namespace App\Controllers;

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

class AdminController implements RequestHandlerInterface
{

    public function handle(Request $request): Response
    {
        $databasePath = __DIR__ . '/../../database.db';
        $pdo = new PDO('sqlite:' . $databasePath);

        $userModel = new User();
        $cardModel = new Card();
        $deckModel = new Deck();

        $adminController = new AdminController();
        $errorhandler = new ErrorHandler();

        $orm = new ORM($pdo, [$userModel, $cardModel, $deckModel]);

        $navbarContent = file_get_contents(__DIR__ . '/../Views/navbar.html');

        $fields = [
            '{{first_item}}' => 'Cards management',
            '{{second_item}}' => 'User management',
            '{{first_href}}' => '/admin/admincard',
            '{{second_href}}' => '/admin/adminuser',
            '{{name}}' => 'WizzWave',
            '{{customComponent}}' => ''
        ];

        $path = $request->getUri()->getPath();

        //Admin aan navbar toevoegen
        if ($path === '/admin/admincard') {
            $fields['{{customComponent}}'] = '<form class="d-flex" action="/search" method="GET">
        <input class="form-control me-2" type="search" id="searchInput" placeholder="Search" aria-label="Search" name="query">
    </form>';
        }

        foreach ($fields as $placeholder => $value) {
            $navbarContent = str_replace($placeholder, $value, $navbarContent);
        }

        //Delete card pagina
        if (str_starts_with($path, '/admin/admindeletecard/')) {
            $cardId = substr($path, strlen('/admin/admindeletecard/'));
            $orm->delete('card', $cardId);
            $uri = new Uri('/admin/admincard');
            $newRequest = new Request(200, $uri, '', $request->getHeaders());
            $body = $adminController->handle($newRequest)->getBody();
            $alert = $errorhandler->handle('success', 'Card deleted successfully', 'code: 200');
            return new Response(200, $request->getHeaders(), 'OK', $alert . $body);


        }
        //Delete user pagina
        else if (str_starts_with($path, '/admin/admindeleteuser/')) {
            $userId = substr($path, strlen('/admin/admindeleteuser/'));
            $orm->delete('user', $userId);
            $uri = new Uri('/admin/adminuser');
            $newRequest = new Request(200, $uri, '', $request->getHeaders());
            $body = $adminController->handle($newRequest)->getBody();
            $alert = $errorhandler->handle('success', 'User deleted successfully', 'code: 200');
            return new Response(200, $request->getHeaders(), 'OK', $alert . $body);

        }
        //Edit user pagina
        else if (str_starts_with($path, '/admin/adminedituser/')) {
            $userId = substr($path, strlen('/admin/adminedituser/'));
            if ($request->getMethod() == 'POST'){
                $newRole = $request->getBody()['role'];
                $orm->updateRole($userId, strtolower($newRole));
                $uri = new Uri('/admin/adminuser');
                $newRequest = new Request(200, $uri, '', $request->getHeaders());
                $body = $adminController->handle($newRequest)->getBody();
                $alert = $errorhandler->handle('success', 'User edited successfully', 'code: 200');
                return new Response(200, $request->getHeaders(), 'OK', $alert . $body);

            }
            $content = file_get_contents(__DIR__ . '/../Views/editForm.html');

            $fields = [
                '{{type}}' => 'Edit user',
                '{{field1}}' => '<label for="role" class="form-label">Role</label>
        <select class="form-select" id="role" name="role" required>
            <option selected disabled>Select Role</option>
            <option>Admin</option>
            <option>Premium</option>
            <option>User</option>
            </select>',
                '{{field2}}' => '',
                '{{field3}}' => '',
                '{{field4}}' => '',
                '{{field5}}' => '',
                '{{submit}}' => 'Save',
                '{{endpoint}}' => '/admin/adminedituser/' . $userId
            ];

            foreach($fields as $field => $value){
                $content = str_replace($field, $value, $content);
            }


            return new Response(200, $request->getHeaders(), 'OK',$navbarContent . $content);

        }

        //Edit card pagina
        else if (str_starts_with($path, '/admin/admineditcard/')) {
            $cardId = substr($path, strlen('/admin/admineditcard/'));
            if ($request->getMethod() == 'POST'){
                $newRarity = $request->getBody()['rarity'];
                $newAttack = $request->getBody()['attack'];
                $newDefense = $request->getBody()['defense'];
                $newSeries = $request->getBody()['series'];
                $newMarketPrice = $request->getBody()['market_price'];

                $orm->updateCard($cardId, $newRarity, $newAttack, $newDefense, $newSeries, $newMarketPrice);

                $uri = new Uri('/admin/admincard');
                $newRequest = new Request(200, $uri, '', $request->getHeaders());
                $body = $adminController->handle($newRequest)->getBody();
                $alert = $errorhandler->handle('success', 'Card edited successfully', 'code: 200');
                return new Response(200, $request->getHeaders(), 'OK',$alert . $body);

            }
            $content = file_get_contents(__DIR__ . '/../Views/editForm.html');
            $card = $orm->select('card', $cardId);
            $cardRarity = $card[0]['rarity'];
            $cardAttack = $card[0]['attack'];
            $cardDefense = $card[0]['defense'];
            $cardSeries = $card[0]['series'];
            $cardMarketPrice = $card[0]['market_price'];

            $fields = [
                '{{type}}' => 'Edit card',
                '{{field1}}' => '<label for="rarity" class="form-label">Rarity</label>
        <input type="text" class="form-control" id="rarity" name="rarity" value="' . $cardRarity . '" required>',
                '{{field2}}' => '<label for="attack" class="form-label">Attack</label>
        <input type="text" class="form-control" id="attack" name="attack" value="' . $cardAttack . '" required>',
                '{{field3}}' => '<label for="defense" class="form-label">Defense</label>
        <input type="text" class="form-control" id="defense" name="defense" value="' . $cardDefense . '" required>',
                '{{field4}}' => '<label for="series" class="form-label">Series</label>
        <input type="text" class="form-control" id="series" name="series" value="' . $cardSeries . '" required>',
                '{{field5}}' => '<label for="market_price" class="form-label">Market price</label>
        <input type="text" class="form-control" id="market_price" name="market_price" value="' . $cardMarketPrice . '" required>',
                '{{submit}}' => 'Save',
                '{{endpoint}}' => '/admin/admineditcard/' . $cardId
            ];


            foreach($fields as $field => $value){
                $content = str_replace($field, $value, $content);
            }


            return new Response(200, $request->getHeaders(), 'OK',$navbarContent . $content);

        }

        switch ($path) {
            //Admin kaart management pagina
            case '/admin/admincard':
                $cards = $orm->getAll("Card");
                $cardsJson = json_encode($cards);
                $cardsData = json_decode($cardsJson, true);

                $adminrecords = '';
                $cardContent = file_get_contents(__DIR__ . '/../Views/cardInfo.html');
                foreach ($cardsData as $card) {
                    $cardContent2 = str_replace('{{name}}', $card['name'], $cardContent);
                    $cardContent2 = str_replace('{{attack}}', $card['attack'], $cardContent2);
                    $cardContent2 = str_replace('{{defense}}', $card['defense'], $cardContent2);
                    $cardContent2 = str_replace('{{rarity}}', $card['rarity'], $cardContent2);
                    $cardContent2 = str_replace('{{series}}', $card['series'], $cardContent2);
                    $cardContent2 = str_replace('{{market_price}}', $card['market_price'], $cardContent2);
                    $editHref = "/admin/admineditcard/" . $card['id'];
                    $cardContent2 = str_replace('{{edit}}', '<a href="' . $editHref . '" class="btn btn-warning" role="button">Edit</a>', $cardContent2);
                    $deleteHref = "/admin/admindeletecard/" . $card['id'];
                    $cardContent2 = str_replace('{{delete}}', '<a href="' . $deleteHref . '" class="btn btn-danger" role="button">Delete</a>', $cardContent2);
                    $cardContent2 = str_replace('{{image}}', 'http://localhost:8000/app/src/' . $card['image'], $cardContent2);
                    $adminrecords .= '<div class="record">' . $cardContent2 . '</div>';
                }
                $admincontent = file_get_contents(__DIR__ . '/../Views/adminCard.html');
                $admincontent = str_replace('{{card}}', $adminrecords, $admincontent);
                return new Response(200, [], "", $navbarContent . $admincontent);


            //Admin user management pagina
            case '/admin/adminuser':
                $users = $orm->getAll("User");
                $usersJson = json_encode($users);
                $usersData = json_decode($usersJson, true);

                $adminrecords = '';
                $userContent = file_get_contents(__DIR__ . '/../Views/userInfo.html');
                foreach ($usersData as $user) {
                    $userContent2 = str_replace('{{name}}', $user['name'], $userContent);
                    $userContent2 = str_replace('{{email}}', $user['email'], $userContent2);
                    $userContent2 = str_replace('{{role}}', $user['role'], $userContent2);
                    $editHref = "/admin/adminedituser/" . $user['id'];
                    $userContent2 = str_replace('{{edit}}', '<a href="' . $editHref . '" class="btn btn-warning" role="button">Edit</a>', $userContent2);
                    $deleteHref = "/admin/admindeleteuser/" . $user['id'];
                    $userContent2 = str_replace('{{delete}}', '<a href="' . $deleteHref . '" class="btn btn-danger" role="button">Delete</a>', $userContent2);
                    $adminrecords .= '<div class="record">' . $userContent2 . '</div>';
                }
                $admincontent = file_get_contents(__DIR__ . '/../Views/adminUser.html');
                $admincontent = str_replace('{{user}}', $adminrecords, $admincontent);
                return new Response(200, [], "", $navbarContent . $admincontent);

            //Admin kaart toevoegen pagina
            case '/admin/adminaddcard':
                $admincontent = file_get_contents(__DIR__ . '/../Views/adminForm.html');

                if ($request->getMethod() == 'POST') {
                    $cardName = $request->getBody()['name'];
                    $cardDefense = $request->getBody()['defense'];
                    $cardAttack = $request->getBody()['attack'];
                    $cardSeries = $request->getBody()['series'];
                    $cardMarketPrice = $request->getBody()['market_price'];
                    $cardRarity = $request->getBody()['rarity'];
                    $cardImage = $request->getBody()['image'];

                    $uploadDirectory = __DIR__ . '/../src/';
                    $fileName = str_replace(' ', '_', $cardName . '.png');
                    $destinationPath = $uploadDirectory . $fileName;

                    if (move_uploaded_file($cardImage['tmp_name'], $destinationPath)) {
                        $orm->addCard($cardName, $cardAttack, $cardDefense, $cardSeries, $cardRarity, $cardMarketPrice, $fileName);

                        $uri = new Uri('/admin/admincard');
                        $newRequest = new Request(200, $uri, '', $request->getHeaders());
                        $body = $adminController->handle($newRequest)->getBody();
                        $alert = $errorhandler->handle('success', 'Card added successfully', 'code: 200');
                        return new Response(200, $request->getHeaders(), 'OK', $navbarContent . $alert . $body);

                    } else {
                        $uri = new Uri('/admin/admincard');
                        $newRequest = new Request(200, $uri, '', $request->getHeaders());
                        $body = $adminController->handle($newRequest)->getBody();
                        $alert = $errorhandler->handle('danger', 'Failed to upload card: ' . $cardImage['error'], 'code: 400');
                        return new Response(400, $request->getHeaders(), 'OK', $navbarContent . $alert . $body);
                    }


                }
                return new Response(200, [], "", $admincontent);


            //Laadt admin home pagina
            default:
                $admincontent = file_get_contents(__DIR__ . '/../Views/adminHome.html');
                return new Response(200, [], "", $navbarContent . $admincontent);
        }



    }
}