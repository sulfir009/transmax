<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Service\Admin;
use App\Services\BonusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BonusController
{
    public function __construct()
    {
        if (!Admin::isAuth()) {
            redirect('/admin/auth.php')->send();
        }
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('query', ''));
        $clients = collect();

        if ($query !== '') {
            $clients = Client::query()
                ->where('email', 'like', '%' . $query . '%')
                ->orWhere('phone', 'like', '%' . $query . '%')
                ->orderBy('id')
                ->limit(50)
                ->get();
        }

        return view('admin.bonuses.index', [
            'query' => $query,
            'clients' => $clients,
        ]);
    }

    public function credit(Request $request, BonusService $bonusService): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => 'required|integer|min:1',
            'amount_uah' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:255',
        ]);

        $client = Client::find((int) $data['client_id']);
        if (!$client) {
            return redirect()->back()->with('error', 'Клієнта не знайдено.');
        }

        $amountCents = (int) round(((float) $data['amount_uah']) * 100);
        if ($amountCents <= 0) {
            return redirect()->back()->with('error', 'Сума повинна бути більше 0.');
        }

        $adminId = $_SESSION['admin']['id'] ?? null;

        $bonusService->credit(
            $client,
            $amountCents,
            'manual',
            [
                'admin_id' => $adminId,
                'comment' => $data['comment'] ?? null,
            ],
            null,
            $adminId ? (int) $adminId : null
        );

        return redirect()->back()->with('status', 'Бонуси успішно нараховані.');
    }
}