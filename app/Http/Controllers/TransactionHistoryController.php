<?php

namespace App\Http\Controllers;

use App\TransactionHistory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page');

        $query = TransactionHistory::query()->orderBy('created_at', 'desc');

        $transactionHistories = $perPage == -1
            ? $query->get()
            : $query->paginate($perPage);

        return JsonResource::collection($transactionHistories);
    }

    public function store(Request $request)
    {
        $httpClient = new Client([
            'base_uri' => 'http://data.fixer.io/api/latest?access_key=bbccb518625f7d0d31c811cf37409277&format=1',
        ]);
        $response = $httpClient->get('');
        $data = json_decode($response->getBody(), true);
        $rate = $data['rates']['BTC'] / $data['rates']['GEL'];

        $transactionHistory = TransactionHistory::query()->create([
            'rate' => $rate
        ]);

        return new JsonResource($transactionHistory);
    }
}
