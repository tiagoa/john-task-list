<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(title="John Task List", version="0.1")
 * @OA\PathItem(path="/api")
 * @OA\SecurityScheme(
 *    securityScheme="Bearer",
 *    in="header",
 *    name="Bearer",
 *    type="http",
 *    scheme="Bearer",
 *    bearerFormat=""
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
