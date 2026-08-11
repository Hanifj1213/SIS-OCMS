<?php

namespace App\Support;

use App\Models\User;

/**
 * Matriks akses OCMS — satu sumber kebenaran untuk RBAC berbasis role.
 *
 * Tier:
 * - SuperAdmin: semua + manajemen user
 * - Full access (Dept/CRC/Section/Logistic Head, Planner): operasi + approval + gudang + analitik
 * - Group Leader & Supervisor: operasi mekanik + approval
 * - Mechanic: operasi overhaul, daftar komponen, ajukan approval, tanpa approve
 * - Logistik: review (baca) + daftarkan komponen baru saja
 * - Developer: kelola master data (template GSheet, template checksheet,
 *   edit/hapus komponen) tanpa akses operasi/approval overhaul
 */
class OcmsAccess
{
    public const ROLE_SUPER_ADMIN = 'SuperAdmin';

    public const ROLE_DEVELOPER = 'Developer';

    public const ROLE_DEPARTMENT_HEAD = 'Department Head';

    public const ROLE_CRC_HEAD = 'CRC Head';

    public const ROLE_SECTION_HEAD = 'Section Head';

    public const ROLE_LOGISTIC_HEAD = 'Logistic Head';

    public const ROLE_PLANNER = 'Planner';

    public const ROLE_LOGISTIK = 'Logistik';

    public const ROLE_MECHANIC = 'Mechanic';

    public const ROLE_GROUP_LEADER = 'Group Leader';

    public const ROLE_SUPERVISOR = 'Supervisor';

    /** @var list<string> */
    public const ALL_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_DEVELOPER,
        self::ROLE_DEPARTMENT_HEAD,
        self::ROLE_CRC_HEAD,
        self::ROLE_SECTION_HEAD,
        self::ROLE_LOGISTIC_HEAD,
        self::ROLE_PLANNER,
        self::ROLE_LOGISTIK,
        self::ROLE_MECHANIC,
        self::ROLE_GROUP_LEADER,
        self::ROLE_SUPERVISOR,
    ];

    /** @var list<string> */
    public const FULL_ACCESS = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_DEPARTMENT_HEAD,
        self::ROLE_CRC_HEAD,
        self::ROLE_SECTION_HEAD,
        self::ROLE_LOGISTIC_HEAD,
        self::ROLE_PLANNER,
    ];

    /** @var list<string> */
    public const OPERATE = [
        self::ROLE_MECHANIC,
        self::ROLE_GROUP_LEADER,
        self::ROLE_SUPERVISOR,
        ...self::FULL_ACCESS,
    ];

    /** @var list<string> */
    public const APPROVE = [
        self::ROLE_GROUP_LEADER,
        self::ROLE_SUPERVISOR,
        ...self::FULL_ACCESS,
    ];

    /** @var list<string> */
    public const REGISTER_COMPONENT = [
        self::ROLE_MECHANIC,
        self::ROLE_LOGISTIK,
        self::ROLE_DEVELOPER,
        ...self::FULL_ACCESS,
    ];

    /**
     * Role yang boleh mengelola master data: template GSheet per EGI,
     * template checksheet Receiving/Delivery, dan edit/hapus komponen.
     *
     * @var list<string>
     */
    public const DEVELOPER = [
        self::ROLE_DEVELOPER,
        self::ROLE_SUPER_ADMIN,
    ];

    /** @var list<string> */
    public const WAREHOUSE = [
        self::ROLE_LOGISTIC_HEAD,
        self::ROLE_PLANNER,
        ...self::FULL_ACCESS,
    ];

    /** @var list<string> */
    public const EXECUTIVE_DASHBOARD = [
        self::ROLE_GROUP_LEADER,
        self::ROLE_SUPERVISOR,
        ...self::FULL_ACCESS,
    ];

    public static function userHasAnyRole(?User $user, array $roles): bool
    {
        return $user !== null && $user->hasAnyRole($roles);
    }

    public static function canManageUsers(?User $user): bool
    {
        return self::userHasAnyRole($user, [self::ROLE_SUPER_ADMIN]);
    }

    public static function hasFullAccess(?User $user): bool
    {
        return self::userHasAnyRole($user, self::FULL_ACCESS);
    }

    public static function canRegisterComponents(?User $user): bool
    {
        return self::userHasAnyRole($user, self::REGISTER_COMPONENT);
    }

    /** Kelola template GSheet & template checksheet Receiving/Delivery. */
    public static function canManageTemplates(?User $user): bool
    {
        return self::userHasAnyRole($user, self::DEVELOPER);
    }

    /** Edit & hapus komponen yang sudah terdaftar. */
    public static function canManageComponents(?User $user): bool
    {
        return self::userHasAnyRole($user, self::DEVELOPER);
    }

    public static function canOperateOverhaul(?User $user): bool
    {
        return self::userHasAnyRole($user, self::OPERATE);
    }

    public static function canApproveStages(?User $user): bool
    {
        return self::userHasAnyRole($user, self::APPROVE);
    }

    public static function canManageWarehouse(?User $user): bool
    {
        return self::userHasAnyRole($user, self::WAREHOUSE);
    }

    public static function canViewExecutiveDashboard(?User $user): bool
    {
        return self::userHasAnyRole($user, self::EXECUTIVE_DASHBOARD);
    }

    /** Role Logistik: hanya review + daftar komponen (tanpa operasi/approval). */
    public static function isLogisticsReviewOnly(?User $user): bool
    {
        return $user !== null
            && $user->hasRole(self::ROLE_LOGISTIK)
            && ! self::hasFullAccess($user);
    }

    /** Deskripsi singkat tiap role — ditampilkan di form tambah user. */
    public static function roleDescriptions(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Akses penuh + manajemen akun pengguna',
            self::ROLE_DEVELOPER => 'Kelola template checksheet/GSheet + edit & hapus komponen (tanpa operasi overhaul)',
            self::ROLE_DEPARTMENT_HEAD => 'Akses penuh seluruh modul',
            self::ROLE_CRC_HEAD => 'Akses penuh seluruh modul',
            self::ROLE_SECTION_HEAD => 'Akses penuh seluruh modul',
            self::ROLE_LOGISTIC_HEAD => 'Akses penuh + kelola gudang/part request',
            self::ROLE_PLANNER => 'Akses penuh + perencanaan & gudang',
            self::ROLE_LOGISTIK => 'Review komponen + daftarkan komponen baru',
            self::ROLE_MECHANIC => 'Daftar komponen + proses overhaul, checksheet, FR/MOL — tanpa approve',
            self::ROLE_GROUP_LEADER => 'Semua akses mekanik + approve & review',
            self::ROLE_SUPERVISOR => 'Sama dengan Group Leader',
        ];
    }
}
