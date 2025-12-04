<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Applications\Services\RejectionService;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class RejectionsController extends Controller
{
    public function __construct(
        private readonly RejectionService $rejections,
        private readonly HHVacancyInterface $hh,
    ) {}

    /**
     * Otkazlar ro'yxati (HH rejections) – index.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $data = $this->rejections->listForUser($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Bitta otkaz yozuvining detali – show.
     *
     * Frontend uchun:
     *  - id, status, created_at
     *  - vacancy: HH vacancy raw (agar mavjud bo'lsa),
     *    aks holda indexdagi qisqa vacancy ma'lumoti.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $payload = $this->rejections->findForUser($user, $id);
        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection not found',
            ], 404);
        }

        $vacancyRaw = null;
        $externalId = $payload['vacancy']['external_id'] ?? null;

        if ($externalId) {
            // Avval DB'dagi raw_data'dan foydalanamiz.
            $application = Application::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->with('vacancy')
                ->first();

            if ($application && $application->vacancy && $application->vacancy->raw_data) {
                $decoded = json_decode($application->vacancy->raw_data, true);
                if (is_array($decoded)) {
                    $vacancyRaw = $decoded;
                }
            }

            // Agar local raw_data bo'lmasa, HH API orqali olishga harakat qilamiz.
            if (! $vacancyRaw) {
                $vacancyRaw = $this->hh->getById($externalId);
            }
        }

        $payload['vacancy'] = $vacancyRaw ?: $payload['vacancy'];

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}

