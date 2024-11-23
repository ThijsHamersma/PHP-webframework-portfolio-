<?php

namespace App\Middleware;

use Psr\Http\Message\Request;
use Psr\Http\Message\Response;
use Psr\Http\Message\Uri;
use Nucleus\Router;

class Authenticate
{
    function process(Request $request): Response
    {
        $router = new Router();

        //Login en register zijn altijd toegankelijk. Ook als je niet bent ingelogd.
        if($request->getMethod() == ($request->getUri()->getPath() == '/login' || $request->getUri()->getPath() == '/register') || $request->getUri()->getPath() == '/register' ){
            return $router->start($request);
        }
        //Auth voor standaard users
        if (isset($request->getHeader('cookie')['Auth']) &&
        ($request->getHeader('cookie')['Auth'] == 'user')
        && !strtolower(str_starts_with( $request->getUri()->getPath(), '/admin')) && strtolower($request->getUri()->getPath() != '/decks')) {
            $uri = new Uri($request->getUri()->getPath());
            $request2 = new Request('GET', $uri, $request->getBody(), $request->getHeaders());
        }
        //Auth voor admin users
        elseif (isset($request->getHeader('cookie')['Auth']) &&
            $request->getHeader('cookie')['Auth'] == 'admin') {
            $uri = new Uri($request->getUri()->getPath());
            $request2 = new Request($request->getMethod(), $uri, $request->getBody(), $request->getHeaders());
        }
        //Auth voor premium users
        elseif (isset($request->getHeader('cookie')['Auth']) &&
            $request->getHeader('cookie')['Auth'] == 'premium' && !strtolower(str_starts_with($request->getUri()->getPath(), '/admin'))){
            $uri = new Uri($request->getUri()->getPath());
            $request2 = new Request($request->getMethod(), $uri, $request->getBody(), $request->getHeaders());
        }
        else{
            //Indien niet ingelogd en naar een bepaalde route wilt, stuur terug naar login
            $uri = new Uri('/login');
            $request2 = new Request($request->getMethod(), $uri, $request->getBody(), $request->getHeaders());
        }
        return $router->start($request2);
    }
}