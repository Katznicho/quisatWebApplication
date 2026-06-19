<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        return view('document-types.index');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only super administrators can manage document types.');
        }
    }
}
