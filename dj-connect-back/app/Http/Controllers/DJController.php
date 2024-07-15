<?php

namespace App\Http\Controllers;

use App\Models\DJ;
use Illuminate\Http\Request;
use App\Models\Track;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="DJ Connect API",
 *      description="API documentation for DJ Connect. All requests must include header Telegram-Init-Data: ${TWA.initData()}",
 *      @OA\Contact(
 *          name="Contact Developer",
 *          url="https://t.me/beilec"
 *      ),
 *      @OA\License(
 *          name="Contact Lead",
 *          url="https://t.me/alievdenis1"
 *      )
 * )
 * @OA\Server(
 *      url="http://localhost:8082/api/v1",
 *      description="DJ Connect API Server"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="telegramAuth",
 *      type="apiKey",
 *      in="header",
 *      name="Telegram-Init-Data",
 *      description = "Value of TWA.initData() from Telegram Web App. Example: query_id=AAFBaKouAAAAAEFoqi6boeTP&user=%7B%22id%22%3A782919745%2C%22first_name%22%3A%22Ruslan%22%2C%22last_name%22%3A%22Shadaev%22%2C%22username%22%3A%22beilec%22%2C%22language_code%22%3A%22en%22%2C%22is_premium%22%3Atrue%2C%22allows_write_to_pm%22%3Atrue%7D&auth_date=1720091705&hash=1ea39608f5d00f92bb5e7fd37e6cd11c998274ec927cb21899fdccfa626fc74d",
 * )
 */

class DJController extends Controller
{
    /**
     * @OA\Post(
     *      path="/dj/register",
     *      operationId="registerDJ",
     *      tags={"DJ"},
     *      summary="Register a new DJ",
     *      description="Registers a new DJ and returns the DJ object",
     *      security={{"telegramAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="stage_name", type="string", example="DJ Example"),
     *              @OA\Property(property="city", type="string", example="New York"),
     *              @OA\Property(property="payment_details", type="string", example="Bank details or any payment information")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful registration",
     *          @OA\JsonContent(ref="#/components/schemas/DJ")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'stage_name' => 'required',
            'city' => 'required',
            'payment_details' => 'required',
        ]);

        $user = Auth::user(); 

        try {
            $dj = $user->attachDJ($validated);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        return response()->json($dj);
    }

    /**
     * @OA\Get(
     *      path="/dj/profile/{id}",
     *      operationId="getDJProfile",
     *      tags={"DJ"},
     *      summary="Get DJ profile",
     *      description="Returns DJ profile by ID",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="DJ ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/DJ")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Not Found")
     * )
     */
    public function profile(DJ $dj)
    {
        return response()->json($dj);
    }

    /**
     * @OA\Put(
     *      path="/dj/profile/{id}",
     *      operationId="updateDJProfile",
     *      tags={"DJ"},
     *      summary="Update DJ profile",
     *      description="Updates DJ profile information",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="DJ ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="stage_name", type="string", example="DJ Example"),
     *              @OA\Property(property="city", type="string", example="New York"),
     *              @OA\Property(property="payment_details", type="string", example="Bank details or any payment information")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful update",
     *          @OA\JsonContent(ref="#/components/schemas/DJ")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Not Found")
     * )
     */
    public function updateProfile(Request $request, DJ $dj)
    {
        $validated = $request->validate([
            'stage_name' => 'nullable',
            'city' => 'nullable',
            'payment_details' => 'nullable',
        ]);

        $dj->update($validated);

        return response()->json($dj);
    }

    /**
     * @OA\Post(
     *      path="/dj/{dj_id}/track",
     *      operationId="addTrack",
     *      tags={"DJ"},
     *      summary="Add a new track",
     *      description="Allows a DJ to add a new track",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="dj_id",
     *          description="DJ ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Track Name"),
     *              @OA\Property(property="artist", type="string", example="Artist Name"),
     *              @OA\Property(property="duration", type="string", example="3:45")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Track")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="DJ not found")
     * )
     */
    public function addTrack(Request $request, $dj_id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'duration' => 'required|string|max:10',
        ]);

        $dj = DJ::find($dj_id);

        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        $track = Track::create($validated);
        $dj->tracks()->attach($track->id);

        return response()->json($track);
    }

        /**
     * @OA\Get(
     *      path="/dj/{dj_id}/tracks",
     *      operationId="getDJTracks",
     *      tags={"DJ"},
     *      summary="Get tracks by DJ ID",
     *      description="Returns the list of tracks associated with a specific DJ",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="dj_id",
     *          description="DJ ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Track")
     *          )
     *      ),
     *      @OA\Response(response=404, description="DJ not found"),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getTracks($dj_id)
    {
        $dj = DJ::find($dj_id);
        
        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        $tracks = $dj->tracks()->get(['tracks.*', 'dj_track.price']);

        return response()->json($tracks);
    }

    /**
     * @OA\Patch(
     *      path="/dj/{dj_id}/track/{track_id}/price",
     *      operationId="updateTrackPrice",
     *      tags={"DJ"},
     *      summary="Update track price",
     *      description="Allows a DJ to update the price of a track",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="dj_id",
     *          description="DJ ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="track_id",
     *          description="Track ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="price", type="number", format="float", example=19.99)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="price", type="number", format="float", example=19.99)
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="DJ or Track not found")
     * )
     */
    public function updateTrackPrice(Request $request, $dj_id, $track_id)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $dj = DJ::find($dj_id);
        $track = Track::find($track_id);

        if (!$dj || !$track) {
            return response()->json(['error' => 'DJ or Track not found'], 404);
        }

        $dj->tracks()->updateExistingPivot($track_id, ['price' => $validated['price']]);

        return response()->json(['success' => true, 'price' => $validated['price']]);
    }
    
    /**
     * @OA\Get(
     *     path="/dj/{dj_id}/statistics",
     *     summary="Get statistics for a DJ",
     *     description="Retrieve various statistics for a DJ including total orders, total income, average price, etc.",
     *     tags={"DJ"},
     *     @OA\Parameter(
     *         name="dj_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID of the DJ"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_orders", type="integer", example=5),
     *             @OA\Property(property="total_income", type="number", format="float", example=150.75),
     *             @OA\Property(property="average_price", type="number", format="float", example=30.15),
     *             @OA\Property(property="min_price", type="number", format="float", example=20.00),
     *             @OA\Property(property="max_price", type="number", format="float", example=50.00),
     *             @OA\Property(property="most_popular_tracks", type="object", 
     *                 @OA\Property(property="track_id", type="integer", example=1),
     *                 @OA\Property(property="count", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="DJ not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="DJ not found")
     *         )
     *     )
     * )
     */
    public function getStatistics($dj_id)
    {
        $dj = DJ::find($dj_id);
        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        // Fetch only paid orders
        $paidOrders = Order::where('dj_id', $dj_id)
            ->whereHas('transactions', function ($query) {
                $query->where('status', Transaction::STATUS_PAID);
            })
            ->get();

        // Calculate statistics
        $statistics = [
            'total_orders' => $paidOrders->count(),
            'total_income' => round($paidOrders->sum('price'), 2),
            'average_price' => round($paidOrders->avg('price'), 2),
            'min_price' => round($paidOrders->min('price'), 2),
            'max_price' => round($paidOrders->max('price'), 2),
            'most_popular_tracks' => $paidOrders->groupBy('track_id')->map(function ($group) {
                return [
                    'track_id' => $group->first()->track_id,
                    'count' => $group->count(),
                ];
            })->sortByDesc('count')->first(),
        ];
        
        return response()->json($statistics); 
    }
}
