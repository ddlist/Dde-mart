<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — CMS pages (original). Active pages only.
 */
class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('shop.page', compact('page'));
    }
}
