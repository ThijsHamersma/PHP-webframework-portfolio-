<?php

namespace App\Controllers;
require_once __DIR__ . '/../../vendor/autoload.php';

use Psr\Http\Message\Request;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\Response;

class HomeController implements RequestHandlerInterface {
    public function handle(Request $request): Response {
        $homeContent = file_get_contents(__DIR__ . '/../Views/home..html');
        $navbarContent = file_get_contents(__DIR__ . '/../Views/navbar.html');

        $fields = [
            '{{first_item}}' => 'Cards',
            '{{second_item}}' => 'Decks',
            '{{first_href}}' => 'Cards',
            '{{second_href}}' => 'Decks',
            '{{name}}' => 'WizzWave',
            '{{customComponent}}' => ''
        ];

        foreach ($fields as $placeholder => $value) {
            $navbarContent = str_replace($placeholder, $value, $navbarContent);
        }

        if (!isset($request->getHeader('cookie')['Auth']) || !($request->getHeader('cookie')['Auth'] == 'admin')) {
            $navbarContent = str_replace('<li class="nav-item">
                    <a class="nav-link" href=/admin>Admin</a>
                </li>', '', $navbarContent);
        }

        return new Response(200, [], "", $navbarContent . $homeContent);
    }
}