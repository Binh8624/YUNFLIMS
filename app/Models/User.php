<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_BRANCH_MANAGER = 'branch_manager';
    public const ROLE_TICKET_SELLER = 'ticket_seller';
    public const ROLE_TICKET_CHECKER = 'ticket_checker';
    public const ROLE_CONTENT_STAFF = 'content_staff';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'member_tier',
        'loyalty_points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'loyalty_points' => 'integer',
            'cinema_id' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    public static function resolveMemberTier(int $points): string
    {
        if ($points >= 2000) {
            return 'platinum';
        }

        if ($points >= 1000) {
            return 'gold';
        }

        if ($points >= 300) {
            return 'silver';
        }

        return 'new';
    }

    public static function memberTierOptions(): array
    {
        return [
            'new' => 'Thành viên mới',
            'silver' => 'Silver',
            'gold' => 'Gold',
            'platinum' => 'Platinum',
        ];
    }

    public function memberTierLabel(): string
    {
        return self::memberTierOptions()[$this->member_tier ?? 'new'] ?? 'Thành viên mới';
    }

    public static function normalizeRole(?string $role): ?string
    {
        return match ($role) {
            default => $role,
        };
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_BRANCH_MANAGER => 'Quản lý chi nhánh',
            self::ROLE_TICKET_SELLER => 'Nhân viên bán vé',
            self::ROLE_TICKET_CHECKER => 'Nhân viên soát vé',
            self::ROLE_CONTENT_STAFF => 'Nhân viên nội dung',
            self::ROLE_CUSTOMER => 'Khách hàng',
        ];
    }

    public static function allowedRoleValues(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_BRANCH_MANAGER,
            self::ROLE_TICKET_SELLER,
            self::ROLE_TICKET_CHECKER,
            self::ROLE_CONTENT_STAFF,
        ];
    }

    public static function roleLabel(?string $role): string
    {
        $normalized = self::normalizeRole($role);

        return self::roleOptions()[$normalized] ?? 'Khách hàng';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleLabel($this->role);
    }

    public function isAdminStaff(): bool
    {
        return in_array(self::normalizeRole($this->role), [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_BRANCH_MANAGER,
            self::ROLE_TICKET_SELLER,
            self::ROLE_TICKET_CHECKER,
            self::ROLE_CONTENT_STAFF,
        ], true);
    }

    public function cinema(): BelongsTo
    {
        return $this->belongsTo(Cinema::class);
    }

    public function canManageCinema(?int $cinemaId): bool
    {
        if ($this->role === self::ROLE_SUPER_ADMIN || $this->role === self::ROLE_ADMIN) {
            return true;
        }

        return $cinemaId !== null && (int) $this->cinema_id === $cinemaId;
    }

    public function hasPermission(string $permission): bool
    {
        $role = self::normalizeRole($this->role);

        $permissions = [
            self::ROLE_SUPER_ADMIN => [
                'access_admin',
                'manage_cinema',
                'manage_content',
                'manage_bookings',
                'manage_payments',
                'manage_reports',
                'manage_users',
                'check_tickets',
                'manage_services',
                'manage_promotions',
                'manage_members',
                'manage_gift_cards',
            ],
            self::ROLE_ADMIN => [
                'access_admin',
                'manage_cinema',
                'manage_content',
                'manage_bookings',
                'manage_payments',
                'manage_reports',
                'manage_users',
                'check_tickets',
                'manage_services',
                'manage_promotions',
                'manage_members',
                'manage_gift_cards',
            ],
            self::ROLE_BRANCH_MANAGER => [
                'access_admin',
                'manage_cinema',
                'manage_bookings',
                'manage_payments',
                'manage_reports',
                'check_tickets',
                'manage_services',
                'manage_gift_cards',
            ],

            self::ROLE_TICKET_SELLER => [
                'access_admin',
                'manage_bookings',
                'manage_payments',
                'check_tickets',
                'manage_gift_cards',
            ],

            self::ROLE_TICKET_CHECKER => [
                'access_admin',
                'check_tickets',
            ],

            self::ROLE_CONTENT_STAFF => [
                'access_admin',
                'manage_content',
                'manage_promotions',
            ],
        ];

        return in_array($permission, $permissions[$role] ?? [], true);
    }

    /**
     * Các đơn đặt vé của người dùng.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Các đơn đặt đồ ăn, thức uống.
     */
    public function concessionOrders(): HasMany
    {
        return $this->hasMany(ConcessionOrder::class);
    }

    /**
     * Lịch sử sử dụng mã khuyến mãi.
     */
    public function promotionUsages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    /**
     * Thẻ quà tặng đã mua.
     */
    public function purchasedGiftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'purchased_by');
    }

    /**
     * Thẻ quà tặng được nhận.
     */
    public function assignedGiftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'assigned_to');
    }

    /**
     * Lịch sử giao dịch điểm thành viên.
     */
    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function favoriteMovies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_favorites')
            ->withTimestamps();
    }

    public function isFavoriteMovie(Movie $movie): bool
    {
        return $this->favoriteMovies()
            ->whereKey($movie->getKey())
            ->exists();
    }
}
