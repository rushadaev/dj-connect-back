<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
/** 
 *  @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     required={"telegram_id", "name", "phone_number", "email"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="User ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="telegram_id",
 *         type="string",
 *         description="Telegram ID of the user",
 *         example="782919745"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="User's name",
 *         example="Ruslan"
 *     ),
 *     @OA\Property(
 *         property="phone_number",
 *         type="string",
 *         description="User's phone number",
 *         example="beilec"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         description="User's email",
 *         example="beilec@telegram.com"
 *     ),
 *     @OA\Property(
 *         property="email_verified_at",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp when the user's email was verified",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2024-07-14T21:00:21.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2024-07-14T21:00:21.000000Z"
 *     ),
 *     @OA\Property(
 *         property="last_login",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp of the last login",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="is_dj",
 *         type="boolean",
 *         description="Indicates if the user is a DJ",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="dj",
 *         ref="#/components/schemas/DJ",
 *         description="DJ profile associated with the user"
 *     )
 * )
 */

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'stage_name' => 'required',
            'city' => 'required',
            'payment_details' => 'required',
        ]);
    
        $user = $request->user();
    
        // Check if the user already has a DJ profile
        if ($user->dj) {
            return response()->json([
                'message' => 'User already has a DJ profile.'
            ], 400);
        }
    
        $validated['user_id'] = $user->id;
    
        $dj = DJ::create($validated);
    
        return response()->json($dj);
    }

    public function login(Request $request)
    {
        $user = User::firstOrCreate(
            ['telegram_id' => $request['telegram_id']],
            [
                'telegram_id' => $request['telegram_id'],
                'name' => '',
                'phone_number' => $request['telegram_id'] ?? null,
                'email' =>  $request['telegram_id'].'@telegram.com',
            ]
        );

        Auth::login($user);

        return $user;
    }

    /**
     * @OA\Get(
     *      path="/profile/{telegram_id}",
     *      operationId="getUserProfileByTelegramId",
     *      tags={"User"},
     *      summary="Get a user's profile by Telegram ID",
     *      description="Returns the details of a specified user by their Telegram ID",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="telegram_id",
     *          description="Telegram ID of the user",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *              @OA\Property(property="telegram_id", type="string", example="123456789")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="User not found"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized"
     *      )
     * )
     */
    public function profile($telegram_id)
    {
        $user = User::where('telegram_id', $telegram_id)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }
    /**
     * @OA\Get(
     *      path="/profile/me",
     *      operationId="getCurrentUser",
     *      tags={"User"},
     *      summary="Get the current authenticated user's details",
     *      description="Returns the details of the authenticated user",
     *      security={{"telegramAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *              @OA\Property(property="telegram_id", type="string", example="123456789")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized"
     *      )
     * )
     */
    public function getMe(Request $request)
    {
        $user = Auth::user(); 
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($user);
    }

    /**
     * @OA\Post(
     *      path="/admin/set",
     *      operationId="setAdmin",
     *      tags={"User Management"},
     *      summary="Set a user as admin",
     *      description="Assign the 'admin' role to a user and update their email and password.",
     *      security={{"telegramAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"user_id", "email", "password"},
     *              @OA\Property(property="user_id", type="integer", example=1, description="ID of the user to be updated"),
     *              @OA\Property(property="email", type="string", format="email", example="djgod@t.me", description="New email for the user"),
     *              @OA\Property(property="password", type="string", format="password", example="12345678", description="New password for the user")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="User updated and set as admin",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", example="djgod@t.me"),
     *              @OA\Property(property="roles", type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      example="admin"
     *                  )
     *              ),
     *              @OA\Property(property="created_at", type="string", format="date-time", example="2024-08-12T00:00:00.000000Z"),
     *              @OA\Property(property="updated_at", type="string", format="date-time", example="2024-08-12T00:00:00.000000Z")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="User not found"
     *      )
     * )
     */
    public function setAdmin(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
    
        // Find the user by ID
        $user = User::find($validatedData['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        // Assign the 'admin' role to the user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole($adminRole);
    
        // Update the user's email and password
        $user->email = $validatedData['email'];
        $user->password = Hash::make($validatedData['password']);
        $user->save();
    
        // Return the updated user information
        return response()->json(['message' => 'User updated and set as admin', 'user' => $user]);
    }
}
