<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LoginAdvert;
use Illuminate\Support\Facades\DB;

class LoginAdvertController extends Controller
{
    public function next()
    {
        $advert = DB::transaction(function () {
            $adverts = LoginAdvert::query()->scheduled()->orderBy('id')->lockForUpdate()->get();
            if ($adverts->isEmpty()) {
                return null;
            }

            $state = DB::table('login_advert_state')->where('id', 1)->lockForUpdate()->first();
            $lastId = (int) ($state->last_login_advert_id ?? 0);
            $next = $adverts->first(fn (LoginAdvert $advert) => $advert->id > $lastId) ?? $adverts->first();

            DB::table('login_advert_state')->updateOrInsert(
                ['id' => 1],
                [
                    'last_login_advert_id' => $next->id,
                    'updated_at' => now(),
                    'created_at' => $state->created_at ?? now(),
                ]
            );

            return $next;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'advert' => $advert?->toAppPayload(),
            ],
        ]);
    }
}
