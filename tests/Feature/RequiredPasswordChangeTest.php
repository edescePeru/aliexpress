<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\{DB, Schema, Hash};

class RequiredPasswordChangeTest extends TestCase
{
    private $actor;
    private $temporaryPassword;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'password_qa', 'database.connections.password_qa'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>''
        ], 'session.driver'=>'array', 'cache.default'=>'array']);
        DB::setDefaultConnection('password_qa');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        Schema::create('users', function ($t) {
            $t->increments('id'); $t->string('name'); $t->string('email');
            $t->string('password'); $t->boolean('enable')->default(1);
            $t->boolean('must_change_password')->default(1);
            $t->string('remember_token')->nullable(); $t->timestamps();
        });
        $this->temporaryPassword = bin2hex(random_bytes(20));
        $this->actor = User::create(['name'=>'QA', 'email'=>'qa@example.invalid',
            'password'=>Hash::make($this->temporaryPassword), 'enable'=>1, 'must_change_password'=>1]);
        $this->actor->remember_token = bin2hex(random_bytes(20));
        $this->actor->save();
    }

    public function test_validation_errors_never_mutate_credentials()
    {
        $this->actingAs($this->actor);
        $originalHash = $this->actor->password;
        $valid = bin2hex(random_bytes(20));
        foreach ([[], ['password'=>'x','password_confirmation'=>'x'],
            ['password'=>$valid,'password_confirmation'=>$valid.'x']] as $input) {
            $this->postJson('/password/cambio-obligatorio', $input)
                ->assertStatus(422)->assertJsonValidationErrors('password');
        }
        $this->postJson('/password/cambio-obligatorio', ['password'=>$this->temporaryPassword,
            'password_confirmation'=>$this->temporaryPassword])->assertStatus(422);
        $this->assertSame($originalHash, $this->actor->fresh()->password);
        $this->assertTrue($this->actor->fresh()->must_change_password);
    }

    public function test_success_is_self_only_and_cannot_be_repeated()
    {
        $other = User::create(['name'=>'Other', 'email'=>'other@example.invalid',
            'password'=>Hash::make(bin2hex(random_bytes(20))), 'enable'=>1]);
        $otherHash = $other->password;
        $next = bin2hex(random_bytes(20));
        session()->start();
        $previousSessionId = session()->getId();
        $this->actingAs($this->actor)->postJson('/password/cambio-obligatorio', [
            'password'=>$next, 'password_confirmation'=>$next, 'user_id'=>$other->id, 'email'=>$other->email
        ])->assertOk()->assertJson(['redirect'=>route('dashboard.principal')]);
        $actor = $this->actor->fresh();
        $this->assertTrue(Hash::check($next, $actor->password));
        $this->assertFalse(Hash::check($this->temporaryPassword, $actor->password));
        $this->assertFalse($actor->must_change_password);
        $this->assertNull($actor->remember_token);
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertSame($otherHash, $other->fresh()->password);
        $this->get('/password/cambio-obligatorio')->assertRedirect(route('dashboard.principal'));
        $this->postJson('/password/cambio-obligatorio', ['password'=>$next,
            'password_confirmation'=>$next])->assertStatus(422);
    }

    public function test_guest_cannot_access_or_update()
    {
        $this->get('/password/cambio-obligatorio')->assertRedirect(route('login'));
        $this->postJson('/password/cambio-obligatorio', [])->assertStatus(401);
    }
}
