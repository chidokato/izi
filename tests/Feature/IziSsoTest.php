<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IziSsoTest extends TestCase
{
    private string $secret = 'test-izi-sso-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.izi_sso.shared_secret' => $this->secret,
        ]);
        DB::purge('sqlite');

        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_12_072056_add_permission_to_users_table.php'))->up();
        (require database_path('migrations/2026_09_21_105115_add_profile_fields_to_users_table.php'))->up();
    }

    public function test_signed_sso_payload_creates_and_authenticates_a_moderator(): void
    {
        $payload = [
            'id' => 'source-user-42',
            'email' => 'member@example.test',
            'name' => 'Nguoi dung IZI',
            'phone' => '0900000000',
            'timestamp' => now()->timestamp,
        ];

        $response = $this->get($this->signedUrl($payload));

        $response->assertRedirect('/admin');
        $user = User::where('email', $payload['email'])->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(3, $user->permission);
        $this->assertSame($payload['phone'], $user->phone);
        $this->assertTrue(Hash::check('123456', $user->password));
    }

    public function test_legacy_encrypted_token_creates_and_authenticates_a_moderator(): void
    {
        $sourceKey = random_bytes(32);
        config(['services.izi_sso.source_app_key' => 'base64:'.base64_encode($sourceKey)]);

        $payload = [
            'id' => 'source-user-99',
            'email' => 'legacy@example.test',
            'name' => 'Nguoi dung tu web nguon',
            'phone' => '0900999999',
            'timestamp' => now()->timestamp,
        ];
        $token = (new Encrypter($sourceKey, 'AES-256-CBC'))->encryptString(json_encode($payload));

        $this->get('/sso/izi?token='.urlencode($token))->assertRedirect('/admin');

        $user = User::where('email', $payload['email'])->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('123456', $user->password));
    }
    public function test_existing_account_keeps_its_password_when_sso_data_is_refreshed(): void
    {
        $user = User::create([
            'name' => 'Tên cũ',
            'email' => 'member@example.test',
            'phone' => '0900111111',
            'password' => Hash::make('a-different-password'),
            'permission' => 3,
        ]);

        $this->get($this->signedUrl([
            'email' => $user->email,
            'name' => 'Tên mới',
            'phone' => '0900222222',
            'timestamp' => now()->timestamp,
        ]))->assertRedirect('/admin');

        $user->refresh();
        $this->assertSame('Tên mới', $user->name);
        $this->assertSame('0900222222', $user->phone);
        $this->assertTrue(Hash::check('a-different-password', $user->password));
    }

    public function test_unsigned_sso_request_is_rejected(): void
    {
        $this->get('/sso/izi?payload=not-a-valid-payload&signature=invalid')
            ->assertForbidden();
        $this->assertSame(0, User::count());
    }

    private function signedUrl(array $payload): string
    {
        $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedPayload, $this->secret);

        return '/sso/izi?payload='.urlencode($encodedPayload).'&signature='.$signature;
    }
}