<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: dbanks
 * Date: 7/5/17
 * Time: 11:11 AM
 */
class DeckCard extends Model {

    protected $fillable = [
        'deck_id', 'card_id', 'type'
    ];
}