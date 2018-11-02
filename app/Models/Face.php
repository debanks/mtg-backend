<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: dbanks
 * Date: 7/5/17
 * Time: 11:11 AM
 */
class Face extends Model {

    protected $fillable = [
        'card_id', 'name', 'power', 'colors', 'toughness', 'total_cost',
        'cost_text', 'type', 'text'
    ];
}