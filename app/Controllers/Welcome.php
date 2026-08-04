<?php

namespace App\Controllers;

/**
 * Ported from CI3 `application/controllers/Welcome.php`.
 */
class Welcome extends BaseController
{
    public function index()
    {
        return view('welcome_message');
    }
}
