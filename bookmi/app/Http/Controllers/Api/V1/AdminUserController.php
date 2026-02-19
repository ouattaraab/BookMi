<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends BaseController
{
    public function __construct(private readonly AdminService $admin) {}

    /**
     * GET /api/v1/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->suspended, fn ($q) => $q->where('is_suspended', true))
            ->with('roles:name')
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse($users);
    }

    /**
     * GET /api/v1/admin/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['roles:name', 'warnings.issuedBy:id,first_name,last_name', 'talentProfile:id,user_id,stage_name,average_rating,talent_level,is_verified']);

        return $this->successResponse($user);
    }

    /**
     * POST /api/v1/admin/users/{user}/warnings
     */
    public function createWarning(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'reason'  => 'required|string|max:255',
            'details' => 'nullable|string|max:2000',
        ]);

        $warning = $this->admin->createWarning($request->user(), $user, $data['reason'], $data['details'] ?? null);

        return $this->successResponse($warning, 201);
    }

    /**
     * POST /api/v1/admin/users/{user}/suspend
     */
    public function suspend(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'until'  => 'nullable|date|after:now',
        ]);

        $this->admin->suspendUser($request->user(), $user, $data['reason'], $data['until'] ?? null);

        return $this->successResponse(['message' => 'Compte suspendu.']);
    }

    /**
     * POST /api/v1/admin/users/{user}/unsuspend
     */
    public function unsuspend(Request $request, User $user): JsonResponse
    {
        $this->admin->unsuspendUser($request->user(), $user);

        return $this->successResponse(['message' => 'Suspension levée.']);
    }

    // ─── Team delegation (8.6) ──────────────────────────────────────────────

    /**
     * GET /api/v1/admin/team
     */
    public function team(): JsonResponse
    {
        return $this->successResponse($this->admin->listAdminTeam());
    }

    /**
     * POST /api/v1/admin/team
     */
    public function createCollaborator(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'required|string',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:admin_comptable,admin_controleur,admin_moderateur',
        ]);

        $collaborator = $this->admin->createAdminCollaborator($request->user(), $data);

        return $this->successResponse($collaborator, 201);
    }

    /**
     * PUT /api/v1/admin/team/{user}
     */
    public function updateCollaboratorRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|in:admin_comptable,admin_controleur,admin_moderateur',
        ]);

        $this->admin->updateCollaboratorRole($request->user(), $user, $data['role']);

        return $this->successResponse(['message' => 'Rôle mis à jour.']);
    }

    /**
     * DELETE /api/v1/admin/team/{user}
     */
    public function revokeCollaborator(Request $request, User $user): JsonResponse
    {
        $this->admin->revokeCollaboratorAccess($request->user(), $user);

        return $this->successResponse(['message' => 'Accès révoqué.']);
    }
}
