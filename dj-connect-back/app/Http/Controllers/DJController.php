<?php

namespace App\Http\Controllers;

use App\Models\DJ;
use Illuminate\Http\Request;
use App\Models\Track;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
     *              @OA\Property(property="payment_details", type="string", example="Bank details or any payment information"),
     *              @OA\Property(property="phone", type="string", example="+1234567890"),
     *              @OA\Property(property="email", type="string", example="dj@example.com"),
     *              @OA\Property(property="sex", type="string", example="Gender"),
     *              @OA\Property(property="price", type="number", format="float", example=150.00),
     *              @OA\Property(property="website", type="string", example="http://example.com")
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
            'stage_name' => 'required|string',
            'city' => 'required|string',
            'payment_details' => 'required|string',
            'phone' => 'nullable|string',
            'sex' => 'nullable|string',
            'email' => 'nullable|email',
            'price' => 'nullable|numeric',
            'website' => 'nullable|string',
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
     *              @OA\Property(property="payment_details", type="string", example="Bank details or any payment information"),
     *              @OA\Property(property="price", type="number", format="float", example=150.00),
     *              @OA\Property(property="sex", type="string", example="Gender"),
     *              @OA\Property(property="phone", type="string", example="+1234567890"),
     *              @OA\Property(property="email", type="string", format="email", example="dj@example.com"),
     *              @OA\Property(property="website", type="string", example="http://example.com")
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
            'stage_name' => 'nullable|string',
            'city' => 'nullable|string',
            'payment_details' => 'nullable|string',
            'price' => 'nullable|numeric',
            'sex' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|string',
        ]);

        $dj->update(array_filter($validated));

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
     *              @OA\Property(property="name", type="string", example="Track Name")
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
        ]);

        $dj = DJ::find($dj_id);

        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }
        $track = Track::create($validated);
        //Adding default price from dj profile
        $dj->tracks()->attach($track->id, ['price' => $dj->price]);

        return response()->json($track);
    }
    
    /**
     * @OA\Put(
     *      path="/dj/{dj_id}/track/{track_id}",
     *      operationId="updateTrack",
     *      tags={"DJ"},
     *      summary="Update a track",
     *      description="Allows a DJ to update an existing track",
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
     *              @OA\Property(property="name", type="string", example="Updated Track Name")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Track")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="DJ or Track not found")
     * )
     */
    public function updateTrack(Request $request, $dj_id, $track_id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dj = DJ::find($dj_id);
        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        $track = Track::find($track_id);
        if (!$track || !$dj->tracks->contains($track_id)) {
            return response()->json(['error' => 'Track not found'], 404);
        }

        $track->update($validated);

        return response()->json($track);
    }

    /**
     * @OA\Delete(
     *      path="/dj/{dj_id}/track/{track_id}",
     *      operationId="deleteTrack",
     *      tags={"DJ"},
     *      summary="Delete a track",
     *      description="Allows a DJ to delete an existing track",
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
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Track deleted successfully")
     *          )
     *      ),
     *      @OA\Response(response=404, description="DJ or Track not found"),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function deleteTrack($dj_id, $track_id)
    {
        $dj = DJ::find($dj_id);
        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        $track = Track::find($track_id);
        if (!$track || !$dj->tracks->contains($track_id)) {
            return response()->json(['error' => 'Track not found'], 404);
        }

        $dj->tracks()->detach($track_id);
        $track->delete();

        return response()->json(['message' => 'Track deleted successfully']);
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
     *             @OA\Property(property="income_current_month", type="number", format="float", example=50.25),
     *             @OA\Property(property="total_accepted_orders", type="integer", example=10),
     *             @OA\Property(property="total_rejected_orders", type="integer", example=2),
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

        // Fetch current month paid orders
        $currentMonthOrders = Order::where('dj_id', $dj_id)
            ->whereHas('transactions', function ($query) {
                $query->where('status', Transaction::STATUS_PAID);
            })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        // Fetch accepted and rejected orders
        $acceptedOrdersCount = Order::where('dj_id', $dj_id)
            ->whereHas('transactions', function ($query) {
                $query->where('status', Transaction::STATUS_PAID);
            })
            ->count();

        $rejectedOrdersCount = Order::where('dj_id', $dj_id)
            ->where('status', Order::STATUS_DECLINED)
            ->count();

        // Calculate statistics
        $statistics = [
            'total_orders' => $paidOrders->count(),
            'total_income' => round($paidOrders->sum('price'), 2),
            'income_current_month' => round($currentMonthOrders->sum('price'), 2),
            'total_accepted_orders' => $acceptedOrdersCount,
            'total_rejected_orders' => $rejectedOrdersCount,
            'average_price' => round($paidOrders->avg('price'), 2),
            'min_price' => round($paidOrders->min('price'), 2),
            'max_price' => round($paidOrders->max('price'), 2),
            'most_popular_tracks' => $paidOrders->groupBy('track_id')->map(function ($group) {
                return [
                    'track_id' => $group->first()->track_id,
                    'track_name' => Track::find($group->first()->track_id)->name,
                    'count' => $group->count(),
                ];
            })->sortByDesc('count')->values(),
        ];
        
        return response()->json($statistics); 
    }
    /**
     * @OA\Get(
     *      path="/dj/{dj_id}/qr-code",
     *      operationId="generateQRCode",
     *      tags={"DJ"},
     *      summary="Generate QR code for DJ profile [web-server request]",
     *      description="Generates a QR code for a DJ's profile. Make sure to make request to web server, not api server!",
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
     *          description="QR code generated successfully",
     *          @OA\MediaType(
     *              mediaType="image/png",
     *              @OA\Schema(
     *                  type="string",
     *                  format="binary"
     *              )
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="DJ not found")
     * ),
     * @OA\Server(
     *      url="http://localhost:8082",
     *      description="DJ Connect Web Server"
     * ),
     * * @OA\Server(
     *      url="https://dj-connect.xyz",
     *      description="DJ Connect Web Server"
     * )
     */
    public function generateQRCode($dj_id)
    {
        $dj = DJ::find($dj_id);

        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }
        $webAppDirectUrl = config('webapp.direct_url');
        $tgWebAppUrl = "{$webAppDirectUrl}?startapp={$dj_id}";

        $qrCode = QrCode::format('png')->size(300)->generate($tgWebAppUrl);

        return response($qrCode)->header('Content-Type', 'image/png');
    }
    /**
     * @OA\Delete(
     *     path="/dj/clear",
     *     summary="Clear all DJs from the database",
     *     description="Delete all DJ records from the database.",
     *     tags={"DJ"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="All DJs have been cleared.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to clear DJs.")
     *         )
     *     )
     * )
     */
    public function clearDJs()
    {
        try {
            DJ::truncate();
            return response()->json(['message' => 'All DJs have been cleared.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to clear DJs.'], 500);
        }
    }
}
