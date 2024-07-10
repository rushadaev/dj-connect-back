<?php

namespace App\Http\Controllers;

use App\Models\DJ;
use Illuminate\Http\Request;
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="DJ Connect API",
 *      description="API documentation for DJ Connect",
 *      @OA\Contact(
 *          email="support@djconnect.com"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Server(
 *      url="http://localhost:8080/api/v1",
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
     *              @OA\Property(property="base_prices", type="string", example="100"),
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
            'base_prices' => 'required|json',
            'payment_details' => 'required',
        ]);

        $user = $request->user;

        $validated['user_id'] = $user->id;

        $dj = DJ::create($validated);

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
     *              @OA\Property(property="base_prices", type="string", example="100"),
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
            'base_prices' => 'nullable|json',
            'payment_details' => 'nullable',
        ]);

        $dj->update($validated);

        return response()->json($dj);
    }
}
