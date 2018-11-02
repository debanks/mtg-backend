<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\Face;
use App\Services\ApiService;
use Illuminate\Console\Command;
use Aws\S3\S3Client;

class CardCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:cards {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $fileName = $this->argument('file');

        $file = file_get_contents('./' . $fileName);
        $json = json_decode($file, true);

        if (!$json || !isset($json['data'])) {
            return;
        }

        $url    = 'https://s3.us-east-2.amazonaws.com/delta-magic-cards';
        $bucket = "delta-magic-cards";

        $client = new S3Client([
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY'),
                'secret' => env('AWS_SECRET_KEY'),
            ],
            'region'      => 'us-east-2',
            'version'     => 'latest'
        ]);

        foreach ($json['data'] as $row) {

            $existing = Card::where('set', '=', $row['set'])->where('name', '=', $row['name'])->first();

            if ($existing) {
                continue;
            }

            if (!isset($row['mana_cost'])) {
                if (count($row['card_faces']) > 0) {
                    foreach ($row['card_faces'] as $cardFace) {
                        if (isset($cardFace['image_uris']) && isset($cardFace['image_uris']['normal'])) {
                            $image_name = $cardFace['illustration_id'] . '.png';

                            $result = $client->putObject([
                                'Bucket' => $bucket,
                                'Key'    => $image_name,
                                'Body'   => file_get_contents($cardFace['image_uris']['normal']),
                                'ACL'    => 'public-read'
                            ]);

                            $client->waitUntil('ObjectExists', [
                                'Bucket' => $bucket,
                                'Key'    => $image_name
                            ]);
                        } else {
                            $image_name = '';
                        }

                        $manaSplit = explode('{', $cardFace['mana_cost']);
                        $totalCost = 0;
                        $value     = 0;

                        for ($i = 1; $i < count($manaSplit); $i++) {
                            $cost      = explode('}', $manaSplit[$i])[0];
                            $typeSplit = explode('/', $cost);

                            if (in_array($cost, ['R', 'G', 'B', 'U', 'W']) || count($typeSplit) > 1) {
                                $totalCost++;
                            } elseif (is_numeric($cost)) {
                                $totalCost += (int)$cost;
                            }
                        }

                        if (isset($cardFace['power'])) {
                            $powerToughness = (int)$cardFace['power'] + (int)$cardFace['toughness'];

                            $value = $powerToughness - $totalCost * 2;
                        }

                        $card = new Card([
                            'set'       => $row['set'],
                            'set_name'  => $row['set_name'],
                            'name'      => $cardFace['name'],
                            'rarity'    => $row['rarity'],
                            'cost_text' => $cardFace['mana_cost'],
                            'image'     => $url . '/' . $image_name,
                            'value'     => $value,
                            'colors'    => implode(',', $cardFace['colors'])
                        ]);
                        $card->save();

                        Face::create([
                            'card_id'    => $card->id,
                            'name'       => $cardFace['name'],
                            'text'       => $cardFace['oracle_text'],
                            'cost_text'  => $cardFace['mana_cost'],
                            'total_cost' => $totalCost,
                            'type'       => $cardFace['type_line'],
                            'colors'     => implode(',', $cardFace['colors']),
                            'power'      => isset($cardFace['power']) ? $cardFace['power'] : null,
                            'toughness'  => isset($cardFace['toughness']) ? $cardFace['toughness'] : null
                        ]);
                    }
                }
            } else {

                if (isset($row['image_uris']) && isset($row['image_uris']['normal'])) {
                    $image_name = $row['id'] . '.png';

                    $result = $client->putObject([
                        'Bucket' => $bucket,
                        'Key'    => $image_name,
                        'Body'   => file_get_contents($row['image_uris']['normal']),
                        'ACL'    => 'public-read'
                    ]);

                    $client->waitUntil('ObjectExists', [
                        'Bucket' => $bucket,
                        'Key'    => $image_name
                    ]);
                } else {
                    $image_name = '';
                }

                $costSplit = explode(' // ', $row['mana_cost']);

                $split     = false;
                $totalCost = 0;
                $value     = 0;

                if (count($costSplit) > 1) {
                    $split = true;
                } else {
                    $manaSplit = explode('{', $row['mana_cost']);

                    for ($i = 1; $i < count($manaSplit); $i++) {
                        $cost      = explode('}', $manaSplit[$i])[0];
                        $typeSplit = explode('/', $cost);

                        if (in_array($cost, ['R', 'G', 'B', 'U', 'W']) || count($typeSplit) > 1) {
                            $totalCost++;
                        } elseif (is_numeric($cost)) {
                            $totalCost += (int)$cost;
                        }
                    }

                    if (isset($row['power'])) {
                        $powerToughness = (int)$row['power'] + (int)$row['toughness'];

                        $value = $powerToughness - $totalCost * 2;
                    }
                }

                $card = new Card([
                    'set'       => $row['set'],
                    'set_name'  => $row['set_name'],
                    'name'      => $row['name'],
                    'rarity'    => $row['rarity'],
                    'cost_text' => $row['mana_cost'],
                    'image'     => $url . '/' . $image_name,
                    'value'     => $value,
                    'colors'    => implode(',', $row['colors'])
                ]);
                $card->save();

                if ($split && isset($row['card_faces'])) {
                    foreach ($row['card_faces'] as $cardFace) {

                        $totalCost = 0;
                        $manaSplit = explode('{', $cardFace['mana_cost']);
                        $colors    = [];
                        for ($i = 1; $i < count($manaSplit); $i++) {
                            $cost      = explode('}', $manaSplit[$i])[0];
                            $typeSplit = explode('/', $cost);

                            if (in_array($cost, ['R', 'G', 'B', 'U', 'W'])) {
                                if (!in_array($cost, $colors)) {
                                    $colors[] = $cost;
                                }
                                $totalCost++;
                            } elseif (count($typeSplit) > 1) {
                                $totalCost++;
                                if (!in_array($typeSplit[0], $colors)) {
                                    $colors[] = $typeSplit[0];
                                }
                                if (!in_array($typeSplit[1], $colors)) {
                                    $colors[] = $typeSplit[0];
                                }
                            } elseif (is_numeric($cost)) {
                                $totalCost += (int)$cost;
                            }
                        }

                        Face::create([
                            'card_id'    => $card->id,
                            'name'       => $cardFace['name'],
                            'text'       => $cardFace['oracle_text'],
                            'cost_text'  => $cardFace['mana_cost'],
                            'total_cost' => $totalCost,
                            'type'       => $cardFace['type_line'],
                            'colors'     => implode(',', $colors),
                            'power'      => null,
                            'toughness'  => null
                        ]);
                    }
                } else {
                    Face::create([
                        'card_id'    => $card->id,
                        'name'       => $row['name'],
                        'text'       => $row['oracle_text'],
                        'cost_text'  => $row['mana_cost'],
                        'total_cost' => $totalCost,
                        'type'       => $row['type_line'],
                        'colors'     => implode(',', $row['colors']),
                        'power'      => isset($row['power']) ? $row['power'] : null,
                        'toughness'  => isset($row['toughness']) ? $row['toughness'] : null
                    ]);
                }
            }
        }

    }
}
