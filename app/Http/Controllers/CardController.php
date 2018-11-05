<?php namespace App\Http\Controllers;

use App\Console\Commands\CardCommand;
use App\Constants;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Models\DeckCard;
use App\Models\Face;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller {

    public function home() {

        return [
            'recent' => []
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

        $query = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
            ->orderBy($orderBy, $ordering)
            ->select(
                'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                'cards.value', 'cards.arena_class', 'cards.rarity'
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
                    ->where('cards.rarity', '=', 'mythic')
                    ->where('cards.set', '=', $set)
                    ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                    ->select(
                        'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                        'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors'
                    )
                    ->first();
            }

            if (!$card) {
                $card = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                    ->orderBy(\DB::raw("RAND()"))
                    ->where('cards.rarity', '=', 'rare')
                    ->where('cards.set', '=', $set)
                    ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                    ->select(
                        'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                        'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors'
                    )
                    ->first();
            }

            $uncommons = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                ->orderBy(\DB::raw("RAND()"))
                ->where('cards.rarity', '=', 'uncommon')
                ->where('cards.set', '=', $set)
                ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                ->limit(3)
                ->select(
                    'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                    'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors'
                )
                ->get();

            $commons = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
                ->orderBy(\DB::raw("RAND()"))
                ->where('cards.rarity', '=', 'common')
                ->where('cards.set', '=', $set)
                ->where('faces.type', 'NOT LIKE', '%Basic Land%')
                ->limit(10)
                ->select(
                    'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                    'cards.value', 'cards.arena_class', 'cards.rarity', 'cards.colors'
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

    public function simulate(Request $request, $deckId) {

        $times = $request->input('hands', 1);

        if (!$deckId) {
            return [
                'status' => false
            ];
        }

        $deck = Deck::find($deckId);

        if (!$deck) {
            return [
                'status' => false
            ];
        }

        $cards = Card::leftJoin('deck_cards', 'deck_cards.card_id', '=', 'cards.id')
            ->leftJoin(\DB::raw("
                (SELECT 
                    card_id,
                    SUM(IF(type like '%Land%', 1, 0)) as is_land,
                    MIN(IF(type like '%Land%', -1, total_cost)) as cost
                FROM faces
                GROUP BY 1) as faces
            "), 'faces.card_id', '=', 'cards.id')
            ->where('deck_cards.deck_id', '=', $deck->id)
            ->select(\DB::raw("
                cards.id, cards.image, cards.name, cards.rarity, cards.cost_text, cards.colors,
                cards.value, cards.arena_class, faces.is_land, faces.cost
            "))
            ->get();

        $cards = json_decode(json_encode($cards), true);

        if (count($cards) < 7) {
            return [
                'status' => false
            ];
        }

        $lands      = 0;
        $hands      = [];
        $totalLands = $this->countLands($cards);
        $curve      = round($totalLands / count($cards) * 7);

        for ($i = 0; $i < $times; $i++) {
            shuffle($cards);
            $first = array_slice($cards, 0, 7);
            shuffle($cards);
            $second = array_slice($cards, 0, 7);

            sort($first);
            sort($second);

            $firstLandCount  = $this->countLands($first);
            $secondLandCount = $this->countLands($second);

            $firstDiff  = $curve - $firstLandCount;
            $secondDiff = $curve - $secondLandCount;

            // If first hand is closer to the curve
            if (abs($firstDiff) < abs($secondDiff)) {
                $hands[] = [
                    'lands' => $firstLandCount,
                    'cards' => $first
                ];
                $lands   += $firstLandCount;
                continue;
            }

            // If the second hand is closer to the curve
            if (abs($secondDiff) < abs($firstDiff)) {
                $hands[] = [
                    'lands' => $secondLandCount,
                    'cards' => $second
                ];
                $lands   += $secondLandCount;
                continue;
            }

            // If the first hand is under the curve and second isnt
            if ($firstDiff > $secondDiff) {
                $hands[] = [
                    'lands' => $firstLandCount,
                    'cards' => $first
                ];
                $lands   += $firstLandCount;
                continue;
            }

            // If the second hand is under the curve and the first isnt
            if ($secondDiff > $firstDiff) {
                $hands[] = [
                    'lands' => $secondLandCount,
                    'cards' => $second
                ];
                $lands   += $secondLandCount;
                continue;
            }

            // Exactly the same, pick random hand
            $rand = rand(0, 1);
            if ($rand === 0) {
                $hands[] = [
                    'lands' => $secondLandCount,
                    'cards' => $second
                ];
                $lands   += $secondLandCount;
            } else {
                $hands[] = [
                    'lands' => $firstLandCount,
                    'cards' => $first
                ];
                $lands   += $firstLandCount;
            }
        }

        return [
            'lands' => $lands,
            'hands' => $hands
        ];
    }

    private function countLands($cards) {

        $total = 0;

        foreach ($cards as $card) {
            $total += ($card['is_land'] > 0) ? 1 : 0;
        }

        return $total;
    }

    public function insertDeck(Request $request) {

        $name        = $request->input('name', '');
        $image       = $request->input('image', '');
        $description = $request->input('description', '');
        $type        = $request->input('type', '');
        $cardIds     = $request->input('cards', false);

        $deck = new Deck([
            'name'        => $name,
            'image'       => $image,
            'description' => $description,
            'type'        => $type
        ]);
        $deck->save();

        $splits = explode(',', $cardIds);

        foreach ($splits as $id) {
            DeckCard::create([
                'deck_id' => $deck->id,
                'card_id' => $id
            ]);
        }

        return [
            'status' => true
        ];
    }

    public function updateDeck(Request $request, $id) {

        $deck = Deck::find($id);

        if (!$deck) {
            return [
                'status' => false
            ];
        }

        $name        = $request->input('name', '');
        $image       = $request->input('image', '');
        $description = $request->input('description', '');
        $type        = $request->input('type', '');
        $cardIds     = $request->input('cards', false);

        $deck->name        = $name;
        $deck->image       = $image;
        $deck->description = $description;
        $deck->type        = $type;
        $deck->save();

        DeckCard::where('deck_id', '=', $deck->id)->delete();

        $splits = explode(',', $cardIds);

        foreach ($splits as $id) {
            DeckCard::create([
                'deck_id' => $deck->id,
                'card_id' => $id
            ]);
        }

        return [
            'status' => true
        ];
    }
}