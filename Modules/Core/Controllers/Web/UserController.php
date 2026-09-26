<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Department;
use Modules\Core\Models\Role;
use Modules\Core\Models\UserBranch;
use Modules\Core\Models\UserCompany;
use Modules\Core\Models\UserDepartment;
use Modules\Core\Services\PermissionService;
use Modules\Core\Services\SegregationOfDutiesService;

/**
 * User administration is limited to the companies in which the administrator holds the relevant
 * user-management permission, and an administrator can only grant roles they fully hold themselves.
 */
class UserController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    public function index()
    {
        $companyIds = $this->administrableCompanyIds('core.users.view');

        $users = User::whereHas('userCompanies', fn ($query) => $query->whereIn('company_id', $companyIds))
            ->with([
                'userCompanies' => fn ($query) => $query->whereIn('company_id', $companyIds)->with('company'),
                'companyUserRoles' => fn ($query) => $query->whereIn('company_id', $companyIds)->with('role'),
            ])
            ->get();

        return view('core.users.index', compact('users'));
    }

    public function create()
    {
        return view('core.users.create', $this->formOptions('core.users.create'));
    }

    public function store(Request $request)
    {
        $companyIds = $this->administrableCompanyIds('core.users.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ...$this->accessRules($companyIds),
        ]);

        $this->ensureAccessIsGrantable($validated);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ];

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        DB::transaction(function () use ($data, $validated) {
            $user = User::create($data);

            $this->syncAccess($user, $validated, collect($validated['companies']));
        });

        return redirect()->route('core.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(int $id)
    {
        $options = $this->formOptions('core.users.update');
        $companyIds = $options['companies']->pluck('id');
        $user = $this->findAdministrableUser($id, $companyIds);

        return view('core.users.edit', $options + [
            'user' => $user,
            'userCompanies' => UserCompany::where('user_id', $id)->whereIn('company_id', $companyIds)->pluck('company_id')->toArray(),
            'userRoles' => CompanyUserRole::where('user_id', $id)->whereIn('company_id', $companyIds)->pluck('role_id')->toArray(),
            'userAllBranches' => UserCompany::where('user_id', $id)->whereIn('company_id', $companyIds)->where('all_branches', true)->pluck('company_id')->toArray(),
            'userBranches' => UserBranch::where('user_id', $id)->whereIn('company_id', $companyIds)->pluck('branch_id')->toArray(),
            'userDepartments' => UserDepartment::where('user_id', $id)->whereIn('company_id', $companyIds)->pluck('department_id')->toArray(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $companyIds = $this->administrableCompanyIds('core.users.update');
        $user = $this->findAdministrableUser($id, $companyIds);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status' => 'sometimes|in:active,inactive',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ...$this->accessRules($companyIds),
        ]);

        $this->ensureAccessIsGrantable($validated);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (isset($validated['status']) && $validated['status'] !== $user->status) {
            abort_if($user->is(auth()->user()), 422, 'You cannot change the status of your own account.');
            abort_if(
                UserCompany::where('user_id', $user->id)->whereNotIn('company_id', $companyIds)->exists(),
                403,
                'This user also belongs to companies you do not administer.'
            );
            $data['status'] = $validated['status'];
        }

        if (! empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $data['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        DB::transaction(function () use ($user, $data, $validated, $companyIds) {
            $user->update($data);

            $this->syncAccess($user, $validated, $companyIds);
        });

        return redirect()->route('core.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(int $id)
    {
        $companyIds = $this->administrableCompanyIds('core.users.delete');
        $user = $this->findAdministrableUser($id, $companyIds);

        abort_if(
            UserCompany::where('user_id', $id)->whereNotIn('company_id', $companyIds)->exists(),
            403,
            'This user also belongs to companies you do not administer.'
        );

        $user->delete();

        return redirect()->route('core.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * @return Collection<int, int>
     */
    protected function administrableCompanyIds(string $permission): Collection
    {
        return $this->permissionService->companyIdsWithPermission($permission);
    }

    /**
     * @param  Collection<int, int>  $companyIds
     */
    protected function findAdministrableUser(int $id, Collection $companyIds): User
    {
        return User::whereHas('userCompanies', fn ($query) => $query->whereIn('company_id', $companyIds))
            ->findOrFail($id);
    }

    /**
     * @return array{companies: Collection<int, Company>, roles: Collection<int, Role>, branches: Collection<int, Collection<int, Branch>>}
     */
    protected function formOptions(string $permission): array
    {
        $companyIds = $this->administrableCompanyIds($permission);

        return [
            'companies' => Company::whereIn('id', $companyIds)->get(),
            'roles' => Role::where('status', 'active')->get(),
            'branches' => Branch::with('company')->whereIn('company_id', $companyIds)
                ->orderBy('company_id')->orderBy('name')->get()->groupBy('company_id'),
        ];
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return array<string, mixed>
     */
    protected function accessRules(Collection $companyIds): array
    {
        return [
            'companies' => 'required|array|min:1',
            'companies.*' => ['integer', Rule::in($companyIds->all())],
            'roles' => 'required|array|min:1',
            'roles.*' => Rule::exists('roles', 'id')->where('status', 'active'),
            'all_branches' => 'array',
            'all_branches.*' => 'integer|in_array:companies.*',
            'branches' => 'array',
            'branches.*' => ['integer', Rule::exists('branches', 'id')->whereIn('company_id', $companyIds->all())],
            'departments' => 'array',
            'departments.*' => ['integer', Rule::exists('departments', 'id')->whereIn('company_id', $companyIds->all())],
        ];
    }

    /**
     * Reject roles carrying permissions the administrator lacks, and branches or departments outside the selected companies.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function ensureAccessIsGrantable(array $validated): void
    {
        $roles = Role::whereIn('id', $validated['roles'])->get();

        foreach ($validated['companies'] as $companyId) {
            foreach ($roles as $role) {
                if (! $this->permissionService->canGrantRole($role, (int) $companyId)) {
                    throw ValidationException::withMessages([
                        'roles' => "You cannot grant the {$role->name} role because it includes permissions you do not hold.",
                    ]);
                }
            }
        }

        $sod = app(SegregationOfDutiesService::class);

        if (($conflicts = $sod->conflictsForRoles($validated['roles']))->isNotEmpty()) {
            throw ValidationException::withMessages([
                'roles' => 'These roles together combine conflicting permissions: '.$sod->describe($conflicts).'.',
            ]);
        }

        $selectedCompanyIds = array_map('intval', $validated['companies']);

        if (Branch::whereIn('id', $validated['branches'] ?? [])->whereNotIn('company_id', $selectedCompanyIds)->exists()) {
            throw ValidationException::withMessages(['branches' => 'Branches must belong to the selected companies.']);
        }

        if (Department::whereIn('id', $validated['departments'] ?? [])->whereNotIn('company_id', $selectedCompanyIds)->exists()) {
            throw ValidationException::withMessages(['departments' => 'Departments must belong to the selected companies.']);
        }
    }

    /**
     * Replace the user's access within the given companies only, leaving access to other companies untouched.
     *
     * @param  array<string, mixed>  $validated
     * @param  Collection<int, int>  $companyIds
     */
    protected function syncAccess(User $user, array $validated, Collection $companyIds): void
    {
        $defaultCompanyIds = UserCompany::where('user_id', $user->id)->where('is_default', true)->pluck('company_id')->map(fn ($id) => (int) $id)->all();
        $hasDefaultElsewhere = UserCompany::where('user_id', $user->id)->where('is_default', true)->whereNotIn('company_id', $companyIds)->exists();

        foreach ([UserCompany::class, CompanyUserRole::class, UserBranch::class, UserDepartment::class] as $accessModel) {
            $accessModel::where('user_id', $user->id)->whereIn('company_id', $companyIds)->delete();
        }

        // Keep the user's default company; a new user's first company becomes their default.
        $firstCompanyId = (int) $validated['companies'][0];
        $keepsDefault = $hasDefaultElsewhere || array_intersect($defaultCompanyIds, array_map('intval', $validated['companies'])) !== [];

        foreach ($validated['companies'] as $companyId) {
            UserCompany::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_default' => in_array((int) $companyId, $defaultCompanyIds, true) || (! $keepsDefault && (int) $companyId === $firstCompanyId),
                'all_branches' => in_array((int) $companyId, array_map('intval', $validated['all_branches'] ?? []), true),
                'status' => 'active',
            ]);

            foreach ($validated['roles'] as $roleId) {
                CompanyUserRole::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'role_id' => $roleId,
                    'status' => 'active',
                ]);
            }
        }

        foreach (Branch::whereIn('id', $validated['branches'] ?? [])->get() as $branch) {
            UserBranch::create([
                'user_id' => $user->id,
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'status' => 'active',
            ]);
        }

        foreach (Department::whereIn('id', $validated['departments'] ?? [])->get() as $department) {
            UserDepartment::create([
                'user_id' => $user->id,
                'company_id' => $department->company_id,
                'branch_id' => $department->branch_id,
                'department_id' => $department->id,
                'status' => 'active',
            ]);
        }
    }
}
