<?php
namespace Nucleus;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AdminController;
use App\Controllers\CardsController;
use App\Controllers\DeckController;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\RegisterController;
use Psr\Http\Message\Request;
use Psr\Http\Message\Response;

class Router
{
    public array $urls = [
        "/" => HomeController::class . "@handle",
        "/home" => HomeController::class . "@handle",
        "/login" => LoginController::class . "@handle",
        "/register" => RegisterController::class . "@handle",
        "/cards" => CardsController::class . "@handle",
        "/decks" => DeckController::class . "@handle",
        "/decks/adddeck" => DeckController::class . "@handle",
        "/decks/deletedeck/{id}" => DeckController::class . "@handle",
        "/admin" => AdminController::class . "@handle",
        "/admin/admincard" => AdminController::class . "@handle",
        "/admin/adminuser" => AdminController::class . "@handle",
        "/admin/admindeleteuser/{id}" => AdminController::class . "@handle",
        "/admin/adminedituser/{id}" => AdminController::class . "@handle",
        "/admin/adminadduser" => AdminController::class . "@handle",
        "/admin/adminaddcard" => AdminController::class . "@handle",
        "/admin/admindeletecard/{id}" => AdminController::class . "@handle",
        "/admin/admineditcard/{id}" => AdminController::class . "@handle",
    ];

    public function start(Request $request): Response
    {
        $requestPath = strtolower($request->getUri()->getPath());
        foreach ($this->urls as $url => $handler) {
            if ($requestPath === $url || str_contains($url, '/{id}')) {
                //Als de URL bestaat:
                if (str_contains($url, '{id}')) {
                    //Als de URL een {id} mee krijgt:
                    $id = $this->extractIdFromUrl($url, $requestPath);
                    if ($id === null) {
                        continue;
                    }
                    $url = str_replace('{id}', $id, $url);
                }
                if ($requestPath === $url) {
                    //Passende handler aanroepen
                    [$className, $methodName] = explode('@', $handler);
                    if (class_exists($className) && method_exists($className, $methodName)) {
                        return (new $className())->$methodName($request, $id ?? null);
                    }
                }
            }
        }
        return $this->notFoundResponse();
    }

    //Haalt mogelijke id uit de URL
    private function extractIdFromUrl(string $routeUrl, string $requestPath): ?string
    {
        $startIndex = strrpos($routeUrl, '/') + 1;
        $id = substr($requestPath, $startIndex);
        //Checkt of id daadwerkelijk een getal is
        if (ctype_digit($id)) {
            return $id;
        }
        return null;
    }

    private function notFoundResponse(): Response
    {
        return new Response(404, ['Content-Type' => 'text/plain'], '404 Not Found', "Response not found");
    }
}

