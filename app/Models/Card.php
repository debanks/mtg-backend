<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: dbanks
 * Date: 7/5/17
 * Time: 11:11 AM
 */
class Card extends Model {

    protected $fillable = [
        'set', 'set_name', 'name', 'rarity', 'colors', 'image', 'value',
        'arena_class', 'cost_text'
    ];
}