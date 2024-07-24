<?php
namespace App\Http\Swagger;
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
 *      url="http://localhost:8082/api/v1",
 *      description="DJ Connect API Server"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="telegramAuth",
 *      type="apiKey",
 *      in="header",
 *      name="Telegram-Init-Data"
 * )
 */
