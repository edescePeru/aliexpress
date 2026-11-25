<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\{DB,Schema,Route,Hash};
use Illuminate\Http\UploadedFile;
class ProfileMutationClosureTest extends TestCase {
 private $dir;
 protected function setUp(): void {
  parent::setUp();
  config(['database.default'=>'profile_memory','database.connections.profile_memory'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'session.driver'=>'array']);
  DB::setDefaultConnection('profile_memory');
  $this->assertSame(':memory:',DB::connection()->getConfig('database'));
  Schema::create('users',function($t){$t->increments('id');$t->integer('tenant_id');$t->string('name');$t->string('email');$t->string('password');$t->string('image')->nullable();$t->string('remember_token')->nullable();$t->boolean('is_platform_admin')->default(0);$t->timestamps();});
  foreach([1,1,3] as $i=>$tenant) DB::table('users')->insert(['id'=>$i+1,'tenant_id'=>$tenant,'name'=>'Temporary QA','email'=>'qa'.$i.'@example.invalid','password'=>bcrypt('Temporary-old-123'),'remember_token'=>'unchanged']);
  $this->dir=sys_get_temp_dir().'/profile-closure-'.bin2hex(random_bytes(8));
  mkdir($this->dir.'/images/users',0777,true); $this->app->instance('path.public',$this->dir);
  foreach(['image'=>'changeImage','settings'=>'changeSettings','password'=>'changePassword'] as $part=>$action) Route::post('/qa-profile/'.$part.'/{user}','\\App\\Http\\Controllers\\UserController@'.$action)->middleware(\Illuminate\Routing\Middleware\SubstituteBindings::class);
  $this->actingAs(User::find(1));
 }
 protected function tearDown(): void {
  if($this->dir){foreach(glob($this->dir.'/images/users/*') as $f) unlink($f);rmdir($this->dir.'/images/users');rmdir($this->dir.'/images');rmdir($this->dir);}
  parent::tearDown();
 }
 private function upload($name='qa.png',$body=null){
  $path=tempnam(sys_get_temp_dir(),'pqa');
  if($body===null){$image=imagecreatetruecolor(2,2);imagepng($image,$path);imagedestroy($image);}else{file_put_contents($path,$body);}
  return new UploadedFile($path,$name,null,null,true);
 }
 public function test_foreign_and_missing_targets_fail_closed_before_validation(){
  $before=DB::table('users')->get()->toJson();
  foreach([2,3,999999] as $id) foreach(['settings','password','image'] as $part){
   $response=$this->postJson('/qa-profile/'.$part.'/'.$id,[]);
   $this->assertContains($response->status(),$id===999999?[403,404]:[403]);
  }
  foreach([2,3] as $id){$f=$this->upload('qa.txt','not an image');$this->post('/qa-profile/image/'.$id,['image'=>$f],['Accept'=>'application/json'])->assertStatus(403);$this->assertFileExists($f->getPathname());unlink($f->getPathname());}
  $this->assertSame([],glob($this->dir.'/images/users/*'));
  $this->assertSame($before,DB::table('users')->get()->toJson());
 }
 public function test_settings_and_forbidden_fields(){
  $old=User::find(1);
  $this->postJson('/qa-profile/settings/1',[])->assertStatus(422)->assertJsonValidationErrors(['name','email']);
  $this->postJson('/qa-profile/settings/1',['name'=>'Updated QA','email'=>'new@example.invalid','tenant_id'=>3,'is_platform_admin'=>1,'password'=>'tampered','user'=>2])->assertOk();
  $u=User::find(1);$this->assertSame('Updated QA',$u->name);$this->assertSame($old->email,$u->email);$this->assertEquals(1,$u->tenant_id);$this->assertFalse($u->is_platform_admin);$this->assertSame($old->password,$u->password);$this->assertSame(0,DB::transactionLevel());
 }
 public function test_password_validation_hash_and_transaction(){
  $before=User::find(1)->password;$foreign=User::find(2)->password;
  $this->postJson('/qa-profile/password/1',[])->assertStatus(422);
  $payload=['current_password'=>'Temporary-old-123','new_password'=>'Temporary-new-123','new_password_confirmation'=>'mismatch'];
  $this->postJson('/qa-profile/password/1',$payload)->assertStatus(422)->assertJsonValidationErrors('new_password');
  $payload['new_password_confirmation']=$payload['new_password'];$payload['current_password']='incorrect-123';
  $this->postJson('/qa-profile/password/1',$payload)->assertStatus(422);
  $this->assertSame(0,DB::transactionLevel());$this->assertSame($before,User::find(1)->password);$this->assertSame(3,User::count());
  $payload['current_password']='Temporary-old-123';$this->postJson('/qa-profile/password/1',$payload)->assertOk();
  $u=User::find(1);$this->assertTrue(Hash::check('Temporary-new-123',$u->password));$this->assertFalse(Hash::check('Temporary-old-123',$u->password));$this->assertSame($foreign,User::find(2)->password);$this->assertSame('unchanged',$u->remember_token);$this->assertSame(0,DB::transactionLevel());$this->assertEquals(1,auth()->id());
 }
 public function test_image_success_reencoding_and_validation_preserve_previous_uncertain_file(){
  $this->postJson('/qa-profile/image/1',[])->assertStatus(422);
  file_put_contents($this->dir.'/images/users/previous.jpg','previous');DB::table('users')->where('id',1)->update(['image'=>'previous.jpg']);
  $this->post('/qa-profile/image/1',['image'=>$this->upload()],['Accept'=>'application/json'])->assertOk();
  $name=User::find(1)->image;$this->assertMatchesRegularExpression('/^avatar-1-[a-f0-9]{32}\.png$/',$name);$this->assertFileExists($this->dir.'/images/users/'.$name);
  // R7.5: historical uncertain names are preserved; invalid bytes must not replace the new avatar.
  $this->assertFileExists($this->dir.'/images/users/previous.jpg');
  $this->post('/qa-profile/image/1',['image'=>$this->upload('qa.txt','harmless non-image')],['Accept'=>'application/json'])->assertStatus(422);
  $this->assertSame($name,User::find(1)->image);
 }
 public function test_save_failure_preserves_previous_avatar_and_cleans_new_file(){
  file_put_contents($this->dir.'/images/users/1.png','previous-avatar');DB::table('users')->where('id',1)->update(['image'=>'1.png']);
  User::saving(function(){throw new \RuntimeException('QA forced persistence failure');});
  try {$this->post('/qa-profile/image/1',['image'=>$this->upload()],['Accept'=>'application/json'])->assertStatus(500);
   $this->assertSame('previous-avatar',file_get_contents($this->dir.'/images/users/1.png'));
   $this->assertSame('1.png',DB::table('users')->where('id',1)->value('image'));$this->assertCount(1,glob($this->dir.'/images/users/*'));
  } finally {User::flushEventListeners();}
 }
}
