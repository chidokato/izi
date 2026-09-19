<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_12_072056_add_permission_to_users_table.php'))->up();

        foreach (['employees', 'departments', 'attendance_requests'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->string('status')->default('pending');
            });
        }
    }

    private function account(int $permission = 1): User
    {
        return User::create([
            'name' => 'Test Administrator',
            'email' => 'admin@example.test',
            'password' => Hash::make('Test-password-937!'),
            'permission' => $permission,
        ]);
    }

    public function test_home_and_admin_login_show_the_admin_form_without_legacy_tables(): void
    {
        foreach (['/', '/admin/login'] as $url) {
            $this->get($url)->assertOk()->assertViewIs('backend.auth.login')
                ->assertSee('Đăng nhập quản trị')->assertSee('name="_token"', false)
                ->assertDontSee('123456')->assertDontSee('tuan.pn92@gmail.com');
        }
        $this->get('/login')->assertRedirect('/');
    }

    public function test_admin_pages_require_authentication(): void
    {
        foreach (['/admin', '/admin/users', '/admin/courses', '/admin/settings'] as $url) {
            $this->get($url)->assertRedirect('/');
        }
        $this->post('/admin/uploads/editor-image')->assertRedirect('/');
    }

    public function test_stored_admin_password_signs_in_and_dashboard_renders(): void
    {
        $user = $this->account();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'Test-password-937!'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertSee('Quản trị IZI');
        $this->get('/')->assertRedirect('/admin');
        $this->get('/admin/login')->assertRedirect('/admin');
    }

    public function test_bad_password_is_rejected(): void
    {
        $user = $this->account();
        $this->from('/')->post('/admin/login', ['email' => $user->email, 'password' => '123456'])
            ->assertRedirect('/')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_does_not_create_a_default_account(): void
    {
        $this->post('/admin/login', ['email' => 'tuan.pn92@gmail.com', 'password' => '123456'])
            ->assertSessionHasErrors('email');
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_non_admin_cannot_sign_in_or_access_admin_pages(): void
    {
        $user = $this->account(6);
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'Test-password-937!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->get('/')->assertOk();
    }

    public function test_logout_returns_to_home_login(): void
    {
        $this->actingAs($this->account())->post('/admin/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/');
    }

    public function test_frontend_routes_and_static_pages_are_removed(): void
    {
        foreach (['/courses/example', '/hoc-khoa-hoc/example', '/category/example', '/register', '/about-us-v1.html', '/courses-v1.html'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->post('/api/chatbot/ask')->assertNotFound();
        $this->assertSame([], glob(public_path('*.html')));
        $this->assertDirectoryDoesNotExist(resource_path('views/frontend'));
        foreach (Route::getRoutes() as $route) {
            $this->assertFalse(str_starts_with($route->getName() ?? '', 'frontend.'));
        }
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
                ->assertStatus(302);
        }
        $this->post('/admin/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
            ->assertStatus(429);
    }
}
