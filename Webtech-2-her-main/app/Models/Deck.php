<?php

namespace App\Models;
use App\Models\User;
use App\Models\Card;
require_once __DIR__ . '/../../vendor/autoload.php';

class Deck extends Models{

    public function __construct(){
        parent::__construct('deck', 'id', ['name' => 'text', 'user_id' => 'integer']);
    }
    public function relationships(): void
    {
        $this->belongsTo(User::class);
        $this->belongsToMany(Card::class, 'deck_card');
    }

}