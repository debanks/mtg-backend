<?php namespace App\Http\Controllers;

use App\Console\Commands\CardCommand;
use App\Constants;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Face;
use App\Models\Favorite;
use App\Models\Bo4\Bo4Match;
use App\Models\Bo4\Bo4StatDiff;
use App\Models\Bo4\Bo4User;
use App\Models\Bo4\Bo4UserGun;
use App\Models\Bo4\Bo4UserMap;
use App\Models\Location;
use App\Models\Map;
use App\Models\Rank;
use App\Models\Squad;
use App\Services\Bo4Service;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use SVG\Nodes\Embedded\SVGImageElement;
use SVG\Nodes\Shapes\SVGLine;
use SVG\Nodes\Shapes\SVGPolyline;
use SVG\Nodes\Shapes\SVGRect;
use SVG\Nodes\SVGNodeContainer;
use SVG\Nodes\Texts\SVGText;
use SVG\SVGImage;
use SVG\SVGTest;

class CardController extends Controller {

    public function home() {

        return [
            'recent' => []
        ];
    }

    public function search(Request $request) {

        $search   = $request->input('q', '');
        $colors   = $request->input('colors', false);
        $sets      = $request->input('sets', false);
        $costs    = $request->input('costs', false);
        $orderBy  = $request->input('sort_field', 'faces.total_cost');
        $ordering = $request->input('sort_order', 'asc');
        $rarities = $request->input('rarities', false);
        $page = $request->input('page', 1);

        $query = Face::leftJoin('cards', 'cards.id', '=', 'faces.card_id')
            ->orderBy($orderBy, $ordering)
            ->select(
                'faces.name', 'faces.power', 'faces.toughness', 'cards.image', 'cards.set', 'cards.set_name',
                'cards.value', 'cards.arena_class', 'cards.rarity'
            );

        if ($sets !== false) {
            $splits = explode(',', $sets);

            $query->where(function($inner) use ($splits) {

                foreach ($splits as $set) {
                    $inner->orWhere('cards.set', '=', "$set");
                }
            });
        }

        if ($search != '') {
            $query->where(function($inner) use ($search) {
                $inner->orWhere('faces.name', 'like', "%$search%")
                    ->orWhere('faces.type', 'like', "%$search%")
                    ->orWhere('faces.text', 'like', "%$search%");
            });
        }

        if ($colors !== false) {
            $splits = explode(',', $colors);

            $query->where(function($inner) use ($splits) {

                foreach ($splits as $color) {
                    $inner->orWhere('faces.colors', 'like', "%$color%");
                }
            });
        }

        if ($costs !== false) {
            $splits = explode(',', $costs);

            $query->where(function($inner) use ($splits) {

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

            $query->where(function($inner) use ($splits) {

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
}