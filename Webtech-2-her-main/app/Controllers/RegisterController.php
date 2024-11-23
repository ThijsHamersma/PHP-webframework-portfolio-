<?php
namespace App\Controllers;
use Nucleus\ErrorHandler;
use PDO;
use Psr\Http\Message\Request;
use Psr\Http\Message\Response;
use Psr\Http\Message\Uri;
use Psr\Http\Server\RequestHandlerInterface;


require_once __DIR__ . '/../../vendor/autoload.php';
class RegisterController implements RequestHandlerInterface {

    public function handle(Request $request): Response {
        $databasePath = __DIR__ . '/../../database.db';
        $pdo = new PDO('sqlite:' . $databasePath);

        $errorHandler = new ErrorHandler();
        $loginController = new LoginController();

        $formComponent = file_get_contents(__DIR__ . "/../Views/formComponent.html");

        $fields = [
            '{{type}}'   => 'Register',
            '{{endpoint}}' => 'register',
            '{{type1}}'  => 'Email',
            '{{field1}}' => 'Email',
            '{{type2}}'  => 'Password',
            '{{field2}}' => 'Password',
            '{{type3}}'  => 'Name',
            '{{field3}}' => 'Name',
            '{{submit}}' => 'Register',
            '{{noAccount}}' => ''
        ];

        foreach ($fields as $placeholder => $value) {
            $formComponent = str_replace($placeholder, $value, $formComponent);
        }

        if ($request->getMethod() == 'POST') {
            $hashedPassword = password_hash($request->getBody()['Password'], PASSWORD_DEFAULT);
            $password = $request->getBody()['Password'];
            $email = $request->getBody()['Email'];
            $name = $request->getBody()['Name'];

            $minLength = 8;
            $requiresNumber = true;
            $requiresSpecialChar = true;
            

            // Controleer de wachtwoordvereisten
            if (strlen($password) < $minLength) {
                $alert = $errorHandler->handle('danger', 'Password must be at least ' . $minLength . ' characters long', 'code: 400');
                return new Response('400', $request->getHeaders(), 'Password must be at least ' . $minLength . ' characters long', $formComponent . $alert);
            }

            if ($requiresNumber && !preg_match('/\d/', $password)) {
                $alert = $errorHandler->handle('danger', 'Password must contain at least one number', 'code: 400');
                return new Response('400', $request->getHeaders(), 'Password must contain at least one number', $formComponent . $alert);
            }

            if ($requiresSpecialChar && !preg_match('/[^a-zA-Z0-9]/', $password)) {
                $alert = $errorHandler->handle('danger', 'Password must contain at least one special character', 'code: 400');
                return new Response('400', $request->getHeaders(), 'Password must contain at least one special character', $formComponent . $alert);
            }
            $stmt = $pdo->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
            //controlleren of de statement correct is uitgevoerd:
            if ($stmt->execute([$name, $email, $hashedPassword, 'user'])) {
                $uri = new Uri('/login');
                $newRequest = new Request(200, $uri, '', $request->getHeaders());
                $response = $loginController->handle($newRequest);
                $alert = $errorHandler->handle('success', 'Registered successfully', 'code: 200');
                return new Response(200, $request->getHeaders(), 'OK', $response->getBody() . $alert);
            } else {
                $alert = $errorHandler->handle('danger', 'Internal Server Error', 'code: 500');
                return new Response(500, [], "Internal Server Error", $alert);
            }
        }
        return new Response(200, [], "OK", $formComponent);
    }
}

