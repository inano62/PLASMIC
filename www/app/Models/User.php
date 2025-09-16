<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Stripe\StripeClient;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
// app/Models/User.php
    protected $fillable = [
        'name','email','password',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status', // ← これを画面で見たいなら必須
        'stripe_status',
        'stripe_default_pm',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function appointmentsAsClient(){ return $this->hasMany(Appointment::class, 'client_user_id'); }
    public function appointmentsAsLawyer(){ return $this->hasMany(Appointment::class, 'lawyer_user_id'); }

    // app/Models/User.php
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot('role')
            ->withTimestamps();
    }
    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isLawyer(): bool { return $this->role === 'lawyer'; }
    public function canBuildSite(): bool
    {
        return $this->role === 'admin'
            || ($this->role === 'lawyer' && $this->account_type === 'pro');
    }
    public function hasPro(): bool   { return $this->account_type === 'pro' || $this->isAdmin(); }
    public function signupAndCheckout(Request $req)
    {
        $data = $req->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // 1) ユーザーを作成
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // 必要ならそのままログイン
        Auth::login($user);

        // 2) 必要なら Stripe Customer を先に作って保存
        $stripe = new StripeClient(config('services.stripe.secret'));
        if (!$user->stripe_customer_id) {
            $customer = $stripe->customers->create([
                'email' => $user->email,
                'name'  => $user->name,
                'metadata' => ['user_id' => (string)$user->id],
            ]);
            $user->stripe_customer_id = $customer->id;
            $user->save();
        }

        // 3) Checkout セッションを「作った $user」の ID で作成
        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [[
                'price'    => config('services.stripe.price_id'),
                'quantity' => 1,
            ]],
            'success_url' => url('/billing/success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => url('/billing/cancel'),

            // ここが重要：auth() ではなく $user->id を使う
            'client_reference_id' => (string) $user->id,
            'metadata'            => ['user_id' => (string) $user->id],
            'subscription_data'   => [
                'metadata' => ['user_id' => (string) $user->id],
            ],

            'customer' => $user->stripe_customer_id, // 既存カスタマーを使う
        ]);

        return response()->json(['url' => $session->url]);
    }
}
