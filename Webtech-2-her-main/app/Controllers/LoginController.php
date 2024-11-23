<?php
namespace App\Controllers;
use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Nucleus\ErrorHandler;
use Nucleus\ORM\ORM;
use Nucleus\Router;
use PDO;
use Psr\Http\Message\Request;
use Psr\Http\Message\Response;
use Psr\Http\Message\Uri;
use Psr\Http\Server\RequestHandlerInterface;


require_once __DIR__ . '/../../vendor/autoload.php';
class LoginController implements RequestHandlerInterface {
    public function handle(Request $request): Response {
        $databasePath = __DIR__ . '/../../database.db';
        $pdo = new PDO('sqlite:' . $databasePath);

        $userModel = new User();
        $cardModel = new Card();
        $deckModel = new Deck();
        $errorhandler = new ErrorHandler();
        $router = new Router();

        $orm = new ORM($pdo, [$userModel, $cardModel, $deckModel]);

        $formComponent = file_get_contents(__DIR__ . "/../Views/formComponent.html");
        $fields = [
            '{{type}}'    => 'Login',
            '{{type1}}'   => 'Email',
            '{{type2}}'   => 'Password',
            '{{field1}}'  => 'Email',
            '{{field2}}'  => 'Password',
            '{{submit}}'  => 'Login',
            '{{endpoint}}' => 'login',
            '{{noAccount}}' => 'No account yet? Register <a href="/register">here</a>!'
        ];

        foreach ($fields as $placeholder => $value) {
            $formComponent = str_replace($placeholder, $value, $formComponent);
        }

        //Haal 3 veld van register weg bij login(templating)
        $formComponent = str_replace(
            '        <div class="mb-3">
            <label for="exampleInputName1" class="form-label">{{field3}}</label>
            <input type="{{type3}}" class="form-control" id="exampleInputName1" name="{{type3}}" required>
        </div>',
            '',
            $formComponent
        );

        if($request->getMethod() == 'POST'){
            $email = $request->getBody()['Email'];
            $user = $orm->getUser($email);
            $userdata = $orm->getUser($email);
            if(!empty($userdata) && password_verify($request->getBody()['Password'], $userdata[0]['password'])){
                setcookie('Auth', $user[0]['role'], time() + 86400, '/');
                setcookie('Id', $user[0]['id'], time() + 86400, '/');
                $uri = new Uri('/');
                $newRequest = new Request('GET', $uri, '', $request->getHeaders());
                return $router->start($newRequest);
            }
            else{
                $alert = $errorhandler->handle('danger', 'Invalid login credentials', 'code: 401');
                return new Response(200, $request->getHeaders(), 'OK', $formComponent . $alert);
            }
        }
        return new Response(200, [], "OK", $formComponent);
    }
}

