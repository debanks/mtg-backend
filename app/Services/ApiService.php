<?php namespace App\Services;

use GuzzleHttp\Client;

/**
 * Created by PhpStorm.
 * User: delta
 * Date: 7/11/2017
 * Time: 11:17 AM
 */
class ApiService {

    public $client = false;

    public function __construct() {

        $this->client = new Client();
    }

    public function performGet($url, $headers = [], $query = [], $ip = null) {

        try {
            $req = [
                'headers' => $headers,
                'query'   => $query
            ];

            if ($ip) {
                $req['proxy'] = $ip;
            }

            $response = $this->client->get($url, $req);
            return $response->json();
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
        }
    }

    public function performPost($url, $headers = [], $query = [], $body = [], $ip = null) {

        try {

            $req = [
                'headers' => $headers,
                'query'   => $query,
                'body'    => $body
            ];

            if ($ip) {
                $req['proxy'] = $ip;
            }

            $response = $this->client->post($url, $req);
            return $response->json();
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
        }
    }
}