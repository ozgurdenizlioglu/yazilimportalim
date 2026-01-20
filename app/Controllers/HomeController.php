<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => trans('common.welcome'),
            'message' => 'Ozgur Denizlioglu'
        ]);
    }
}
