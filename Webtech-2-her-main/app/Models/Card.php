<?php

namespace App\Models;
use App\Models\Deck;
require_once __DIR__ . '/../../vendor/autoload.php';
class Card extends Models
{
    public function __construct()
    {
        parent::__construct('card', 'id', ['name' => 'TEXT', 'attack' => 'INTEGER', 'defense' => 'INTEGER', 'series' => 'TEXT', 'rarity' => 'TEXT', 'market_price' => 'REAL', 'image' => 'TEXT']);
    }

    public function relationships(): void
    {
        $this->belongsToMany(Deck::class, 'deck_card');
    }

}