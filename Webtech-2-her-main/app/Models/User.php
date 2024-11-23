<?php

namespace App\Models;
use App\Models\Deck;
require_once __DIR__ . '/../../vendor/autoload.php';


class User extends Models
{
    public function __construct()
    {
        parent::__construct('user', 'id', ['name' => 'TEXT', 'email' => 'TEXT', 'password' => 'TEXT', 'role' => 'TEXT']);
    }
    public function relationships(): void
    {
        $this->hasMany(Deck::class);
    }

}
