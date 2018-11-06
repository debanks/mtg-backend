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

class DeckController extends Controller {

    public function index(Request $request) {

        $page = $request->input('page', 1);

        return [
            'decks' => Deck::orderBy('priority', 'desc')
                ->limit(25)
                ->skip(($page - 1) * 25)
                ->get()
        ];
    }

    public function get(Request $request, $deckId) {

        $deck = Deck::find($deckId);

        if (!$deck) {
            return ['status' => false];
        }

        $cards = \DB::select(\DB::raw("
            SELECT
                dc.type as deck_type,
                cards.name, faces.power, faces.toughness, cards.image, cards.set, cards.set_name,
                cards.value, cards.arena_class, cards.rarity, cards.colors, cards.cost_text,
                MIN(faces.total_cost) as total_cost, faces.type
            FROM deck_cards dc
                LEFT JOIN cards on cards.id = dc.card_id
                LEFT JOIN faces on faces.card_id = cards.id
            WHERE dc.deck_id = $deckId
        "));

        return [
            'deck'  => $deck,
            'cards' => $cards
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

    public function insert(Request $request) {

        $name        = $request->input('name', '');
        $image       = $request->input('image', '');
        $description = $request->input('description', '');
        $type        = $request->input('type', '');
        $deckCards   = $request->input('deck', false);

        $deck = new Deck([
            'name'        => $name,
            'image'       => $image,
            'description' => $description,
            'type'        => $type
        ]);
        $deck->save();

        foreach ($deckCards['main'] as $name => $card) {
            for ($i = 0; $i < $card['number']; $i++) {
                DeckCard::create([
                    'deck_id' => $deck->id,
                    'section' => 'main',
                    'card_id' => $card['id']
                ]);
            }
        }

        foreach ($deckCards['sideboard'] as $name => $card) {
            for ($i = 0; $i < $card['number']; $i++) {
                DeckCard::create([
                    'deck_id' => $deck->id,
                    'section' => 'main',
                    'card_id' => $card['id']
                ]);
            }
        }

        return [
            'status' => true
        ];
    }

    public function update(Request $request, $id) {

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
        $deckCards   = $request->input('deck', false);

        $deck->name        = $name;
        $deck->image       = $image;
        $deck->description = $description;
        $deck->type        = $type;
        $deck->save();

        DeckCard::where('deck_id', '=', $deck->id)->delete();

        foreach ($deckCards['main'] as $card) {
            DeckCard::create([
                'deck_id' => $deck->id,
                'type'    => 'main',
                'card_id' => $card['id']
            ]);
        }

        foreach ($deckCards['sideboard'] as $card) {
            DeckCard::create([
                'deck_id' => $deck->id,
                'type'    => 'sideboard',
                'card_id' => $card['id']
            ]);
        }

        return [
            'status' => true
        ];
    }

    private function countLands($cards) {

        $total = 0;

        foreach ($cards as $card) {
            $total += ($card['is_land'] > 0) ? 1 : 0;
        }

        return $total;
    }
}