<?php namespace App\Http\Controllers;

use App\Console\Commands\CardCommand;
use App\Constants;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Face;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller {

    public function home() {

        return [
            'decks' => Deck::where('priority', '=', 10)->limit(4)->get()
        ];
    }

    public function search(Request $request) {

        $search   = $request->input('q', '');
        $colors   = $request->input('colors', false);
        $sets     = $request->input('sets', false);
        $costs    = $request->input('costs', false);
        $orderBy  = $request->input('sort_field', 'faces.total_cost');
        $ordering = $request->input('sort_order', 'asc');
        $rarities = $request->input('rarities', false);
        $page     = $request->input('page', 1);

        $query = Card::leftJoin('faces', 'cards.id', '=', 'faces.card_id')
            ->orderBy($orderBy, $ordering)
            ->groupBy('cards.id')
            ->select(
                'cards.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors', 'cards.cost_text',
                \DB::raw('MIN(faces.total_cost) as total_cost'), 'cards.id'
            );

        if ($sets !== false) {
            $splits = explode(',', $sets);

            $query->where(function ($inner) use ($splits) {

                foreach ($splits as $set) {
                    $inner->orWhere('cards.set', '=', "$set");
                }
            });
        }

        if ($search != '') {
            $query->where(function ($inner) use ($search) {

                $inner->orWhere('faces.name', 'like', "%$search%")
                    ->orWhere('faces.type', 'like', "%$search%")
                    ->orWhere('faces.text', 'like', "%$search%");
            });
        }

        if ($colors !== false) {
            $splits = explode(',', $colors);

            $query->where(function ($inner) use ($splits) {

                foreach ($splits as $color) {
                    $inner->orWhere('faces.colors', 'like', "%$color%");
                }
            });
        }

        if ($costs !== false) {
            $splits = explode(',', $costs);

            $query->where(function ($inner) use ($splits) {

                foreach ($splits as $cost) {
                    switch ($cost) {
                        case '7+':
                            $inner->orWhere('faces.total_cost', '>=', 7);
                            break;
                        default:
                            $inner->orWhere('faces.total_cost', '=', $cost);
                    }

                }
            });
        }

        if ($rarities !== false) {
            $splits = explode(',', $rarities);

            $query->where(function ($inner) use ($splits) {

                foreach ($splits as $rare) {
                    $inner->orWhere('cards.rarity', '=', $rare);
                }
            });
        }

        return [
            'count' => $query->count(),
            'cards' => $query
                ->limit(25)
                ->skip(($page - 1) * 25)
                ->get()
        ];
    }

    public function draft(Request $request) {

        $set   = $request->input('set', 'grn');
        $packs = [];

        for ($i = 0; $i < 24; $i++) {

            $pack = [];

            $rand     = rand(1, 8);
            $isMythic = $rand === 1;
            $card     = false;

            if ($isMythic) {
                $card = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                    ->orderBy(\DB::raw("RAND()"))
                    ->groupBy('cards.id')
                    ->where('cards.rarity', '=', 'mythic')
                    ->where('cards.set', '=', $set)
                    ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                    ->select(
                        'cards.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                        'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors', 'cards.cost_text',
                        \DB::raw('MIN(faces.total_cost) as total_cost'), 'cards.id'
                    )
                    ->first();
            }

            if (!$card) {
                $card = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                    ->orderBy(\DB::raw("RAND()"))
                    ->groupBy('cards.id')
                    ->where('cards.rarity', '=', 'rare')
                    ->where('cards.set', '=', $set)
                    ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                    ->select(
                        'cards.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                        'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors', 'cards.cost_text',
                        \DB::raw('MIN(faces.total_cost) as total_cost'), 'cards.id'
                    )
                    ->first();
            }

            $uncommons = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                ->orderBy(\DB::raw("RAND()"))
                ->groupBy('cards.id')
                ->where('cards.rarity', '=', 'uncommon')
                ->where('cards.set', '=', $set)
                ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                ->limit(3)
                ->select(
                    'cards.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                    'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors', 'cards.cost_text',
                    \DB::raw('MIN(faces.total_cost) as total_cost'), 'cards.id'
                )
                ->get();

            $commons = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                ->orderBy(\DB::raw("RAND()"))
                ->groupBy('cards.id')
                ->where('cards.rarity', '=', 'common')
                ->where('cards.set', '=', $set)
                ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                ->limit(10)
                ->select(
                    'cards.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                    'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors', 'cards.cost_text',
                    \DB::raw('MIN(faces.total_cost) as total_cost'), 'cards.id'
                )
                ->get();

            $uncommons = json_decode(json_encode($uncommons), true);
            $commons   = json_decode(json_encode($commons), true);

            $pack[] = $card;

            $pack    = array_merge($pack, $uncommons, $commons);
            $packs[] = $pack;
        }

        return [
            'packs' => $packs,
            'lands' => Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                ->where('cards.set', '=', $set)
                ->where('faces.type', 'LIKE', '%Basic Land%')
                ->select(
                    'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                    'cards.value', 'cards.arena_class', 'cards.rarity'
                )
                ->get()
        ];
    }

    public function grabSet(Request $request, $set) {

        return [
            'cards' => Card::leftJoin('faces', 'cards.id', '=', 'faces.card_id')
                ->orderBy('cards.value', 'desc')
                ->orderBy('cards.name', 'asc')
                ->where('cards.set', '=', $set)
                ->groupBy('cards.id')
                ->select(
                    'cards.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                    'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors', 'cards.cost_text',
                    \DB::raw('MIN(faces.total_cost) as total_cost'), 'cards.id'
                )->get()
        ];
    }
}