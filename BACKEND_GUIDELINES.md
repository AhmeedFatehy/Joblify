# Joblify Backend Guidelines

> API-only Laravel backend for the Joblify job board platform.
> **Tech Stack:** Laravel 13, Sanctum, Pest, Pint.

---

## 1. Architecture Principles

- **API-Only:** This backend serves exclusively JSON. There are no views, no sessions, no HTML responses.
- **Stateless:** Every request must be authenticated via Sanctum tokens (`Authorization: Bearer <token>`).
- **JSON First:** All success and error responses follow the standardized format defined below.

---

## 2. Response Format Standard (Option A)

Every API response must use the `ApiResponse` trait or match this shape exactly.

### Success Response

```json
{
  "success": true,
  "message": "Jobs retrieved successfully",
  "data": {
    "id": 1,
    "title": "Senior Laravel Developer",
    "company": { ... }
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

### Paginated Success

Use `$this->paginated($paginator, 'message')` from `ApiResponse` trait.

### Created Response (201)

```php
return $this->created($job, 'Job created successfully');
```

### No Content Response (204)

```php
return $this->noContent('Job deleted successfully');
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "title": ["The title must be at least 3 characters."]
  }
}
```

### Common HTTP Status Codes

| Status | Usage |
|--------|-------|
| 200 | OK (GET, PUT, PATCH) |
| 201 | Created (POST) |
| 204 | No Content (DELETE) |
| 400 | Bad Request (generic client error) |
| 401 | Unauthenticated |
| 403 | Forbidden (authorized but not allowed) |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 422 | Validation Error |
| 500 | Server Error |

---

## 3. Error Handling Rules

- **Never** throw raw exceptions in controllers.
- Use built-in Laravel exceptions (`ModelNotFoundException`, `ValidationException`, `AuthorizationException`) — the global handler converts them to JSON.
- For business-logic errors, throw `App\Exceptions\ApiException`:

```php
use App\Exceptions\ApiException;

throw new ApiException('You have already applied to this job.', 409);
```

- The global exception handler (`bootstrap/app.php`) catches everything and returns JSON. Zero HTML responses ever.

---

## 4. Naming Conventions

### Controllers
- **Plural**, suffixed with `Controller`.
- Located in `App\Http\Controllers\Api\`.
- **Must** extend `BaseApiController`.

```php
class JobController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $jobs = Job::paginate();
        return $this->paginated($jobs, 'Jobs retrieved successfully');
    }
}
```

### Form Requests
- **Action + Resource + Request**.
- Located in `App\Http\Requests\`.
- **Must** extend `BaseApiRequest`.

```php
class StoreJobRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'work_type' => ['required', 'string', 'in:remote,onsite,hybrid'],
        ];
    }
}
```

### Policies
- **Resource + Policy**.

```php
class JobPolicy
{
    public function update(User $user, Job $job): bool
    {
        return $user->role->isEmployer() && $job->company->user_id === $user->id;
    }
}
```

### Routes
- Use `apiResource()` for CRUD resources.
- Group routes by middleware and prefix.
- Name routes using dot notation.

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('jobs', JobController::class);
    Route::apiResource('companies', CompanyController::class)->except(['destroy']);
});
```

### Enums
- Located in `App\Enums\`.
- Used for all fixed-value fields (`role`, `status`, `work_type`, etc.).
- Cast in Eloquent models.

```php
enum JobStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
```

```php
// In Model
protected function casts(): array
{
    return [
        'status' => JobStatus::class,
    ];
}
```

---

## 5. Controller Pattern

All API controllers **must** follow this pattern:

1. Extend `BaseApiController`.
2. Use `FormRequest` classes for validation (extend `BaseApiRequest`).
3. Use `ApiResponse` methods for all returns.
4. Keep controllers thin — delegate complex logic to service classes or actions.

```php
namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreJobRequest;
use App\Models\Job;
use Illuminate\Http\JsonResponse;

class JobController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $jobs = Job::with(['company', 'categories', 'skills'])
            ->where('status', JobStatus::APPROVED)
            ->paginate(15);

        return $this->paginated($jobs, 'Jobs retrieved successfully');
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        $job = Job::create($request->validated());
        $job->categories()->sync($request->input('category_ids', []));
        $job->skills()->sync($request->input('skill_ids', []));

        return $this->created($job->load(['company', 'categories', 'skills']), 'Job created successfully');
    }

    public function show(Job $job): JsonResponse
    {
        return $this->success($job->load(['company', 'categories', 'skills']), 'Job retrieved successfully');
    }

    public function update(StoreJobRequest $request, Job $job): JsonResponse
    {
        $job->update($request->validated());
        $job->categories()->sync($request->input('category_ids', []));
        $job->skills()->sync($request->input('skill_ids', []));

        return $this->success($job->load(['company', 'categories', 'skills']), 'Job updated successfully');
    }

    public function destroy(Job $job): JsonResponse
    {
        $job->delete();
        return $this->noContent('Job deleted successfully');
    }
}
```

---

## 6. Testing Requirements

- **Framework:** Pest (already installed).
- **Location:** `tests/Feature/` for endpoint tests, `tests/Unit/` for isolated logic.
- **Naming:** `JobControllerTest.php`, `JobPolicyTest.php`.
- **Run tests:** `composer test` or `./vendor/bin/pest`.
- Every controller endpoint **must** have at least one feature test.
- Every policy **must** have unit tests.

### Example Feature Test

```php
<?php

use App\Models\User;
use App\Models\Job;
use App\Enums\UserRole;

it('allows candidates to view approved jobs', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->create(['status' => \App\Enums\JobStatus::APPROVED]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/jobs/' . $job->id);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $job->id);
});
```

---

## 7. Git Workflow (4 Developers)

### Branch Naming

```
feature/epic-1/auth-api
feature/epic-2/job-search
bugfix/application-duplicate-check
```

### Pull Request Rules

1. Create feature branch from `master`.
2. Run `composer lint:check` before pushing.
3. Open PR against `master`.
4. Require **1 review** before merge.
5. PR title format: `[Epic #X] Brief description`.

### Commit Message Format

```
[Epic 2] Add advanced filtering to job search
- Filter by experience_level, salary range, post date
- Combine filters with keyword search
- Add feature tests
```

---

## 8. Sanctum Authentication Setup

Sanctum is installed and configured. All protected routes use the `auth:sanctum` middleware.

### Token Creation (to be implemented in Epic 1)

```php
// Login flow returns a plain-text token
$token = $user->createToken('api-token')->plainTextToken;

return $this->success([
    'user' => $user,
    'token' => $token,
], 'Login successful');
```

### Logout

```php
$request->user()->currentAccessToken()->delete();
return $this->noContent('Logged out successfully');
```

---

## 9. Quick Reference: File Structure

```
app/
  Enums/           # All PHP enums
  Exceptions/
    ApiException.php
  Http/
    Controllers/
      Api/
        BaseApiController.php   # All controllers extend this
        JobController.php
        ...
    Requests/
      BaseApiRequest.php        # All requests extend this
      StoreJobRequest.php
      ...
    Middleware/                 # Keep minimal, API-only
  Models/                       # Eloquent models with casts & relations
  Traits/
    ApiResponse.php             # Standardized JSON responses
  Policies/                     # Authorization policies
  Services/                     # Complex business logic (when needed)

routes/
  api.php                       # All API routes
  web.php                       # Health check only
  console.php

database/
  migrations/
  factories/
  seeders/

tests/
  Feature/                      # Endpoint tests
  Unit/                         # Isolated logic tests
```

---

## 10. Running the Project

```bash
# Install dependencies
composer install

# Environment
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate

# Database
php artisan migrate --force

# Development server
composer dev          # or: php artisan serve

# Linting
composer lint         # Fix code style
composer lint:check   # Check code style (CI)

# Testing
composer test         # Run Pest tests + lint check
```

---

## 11. Important Notes

- **No HTML ever:** The app is configured to always return JSON. If you hit a 404, you get JSON. If validation fails, you get JSON. If the server crashes, you get JSON.
- **No sessions:** Do not use session-based auth. Use Sanctum tokens only.
- **No Fortify:** Fortify and Inertia have been removed. Authentication is fully custom Sanctum.
- **Enums over strings:** Always use Enums for fixed values. Never hardcode `'candidate'` or `'pending'` in controllers.
- **Fillable over Guarded:** All models use explicit `$fillable` arrays.

---

*Last updated: 2026-05-02*
