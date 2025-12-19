<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    // Soft-deleted kayıtları da listelemek istiyorsan WHERE koşulunu kaldır.
    public static function all(PDO $pdo): array
    {
        $sql = "
            SELECT
                id,
                email,
                uuid,
                first_name,
                middle_name,
                last_name,
                gender,
                birth_date,
                death_date,
                phone,
                secondary_phone,
                national_id,
                passport_no,
                marital_status,
                nationality_code,
                place_of_birth,
                timezone,
                language,
                photo_url,
                address_line1,
                address_line2,
                city,
                state_region,
                postal_code,
                country_code,
                notes,
                is_active,
                created_at,
                updated_at,
                deleted_at,
                last_login_at,
                created_by,
                updated_by,
                company_id
            FROM public.users
            WHERE deleted_at IS NULL
            ORDER BY id
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $sql = "
            SELECT
                id,
                email,
                uuid,
                first_name,
                middle_name,
                last_name,
                gender,
                birth_date,
                death_date,
                phone,
                secondary_phone,
                national_id,
                passport_no,
                marital_status,
                nationality_code,
                place_of_birth,
                timezone,
                language,
                photo_url,
                address_line1,
                address_line2,
                city,
                state_region,
                postal_code,
                country_code,
                notes,
                is_active,
                created_at,
                updated_at,
                deleted_at,
                last_login_at,
                created_by,
                updated_by,
                company_id
            FROM public.users
            WHERE id = :id AND deleted_at IS NULL
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Zorunlu alanlar: first_name, last_name, email (DB şemanıza göre ayarlayın).
    // Diğerleri opsiyonel ve null olabilir.
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO public.users (
                first_name,
                middle_name,
                last_name,
                email,
                gender,
                birth_date,
                death_date,
                phone,
                secondary_phone,
                national_id,
                passport_no,
                marital_status,
                nationality_code,
                place_of_birth,
                timezone,
                language,
                photo_url,
                address_line1,
                address_line2,
                city,
                state_region,
                postal_code,
                country_code,
                notes,
                is_active,
                created_by,
                updated_by,
                last_login_at,
                company_id
            ) VALUES (
                :first_name,
                :middle_name,
                :last_name,
                :email,
                :gender,
                :birth_date,
                :death_date,
                :phone,
                :secondary_phone,
                :national_id,
                :passport_no,
                :marital_status,
                :nationality_code,
                :place_of_birth,
                :timezone,
                :language,
                :photo_url,
                :address_line1,
                :address_line2,
                :city,
                :state_region,
                :postal_code,
                :country_code,
                :notes,
                :is_active,
                :created_by,
                :updated_by,
                :last_login_at,
                :company_id
            )
            RETURNING id
        ";

        $stmt = $pdo->prepare($sql);

        // Helper: olmayan key’leri null kabul et
        $v = static function (array $a, string $k, $default = null) {
            return array_key_exists($k, $a) ? $a[$k] : $default;
        };

        $stmt->execute([
            ':first_name'       => $v($data, 'first_name'),
            ':middle_name'      => $v($data, 'middle_name'),
            ':last_name'        => $v($data, 'last_name'),
            ':email'            => $v($data, 'email'),
            ':gender'           => $v($data, 'gender'),
            ':birth_date'       => $v($data, 'birth_date'),      // 'YYYY-MM-DD' veya null
            ':death_date'       => $v($data, 'death_date'),
            ':phone'            => $v($data, 'phone'),
            ':secondary_phone'  => $v($data, 'secondary_phone'),
            ':national_id'      => $v($data, 'national_id'),
            ':passport_no'      => $v($data, 'passport_no'),
            ':marital_status'   => $v($data, 'marital_status'),
            ':nationality_code' => $v($data, 'nationality_code'),
            ':place_of_birth'   => $v($data, 'place_of_birth'),
            ':timezone'         => $v($data, 'timezone'),
            ':language'         => $v($data, 'language'),
            ':photo_url'        => $v($data, 'photo_url'),
            ':address_line1'    => $v($data, 'address_line1'),
            ':address_line2'    => $v($data, 'address_line2'),
            ':city'             => $v($data, 'city'),
            ':state_region'     => $v($data, 'state_region'),
            ':postal_code'      => $v($data, 'postal_code'),
            ':country_code'     => $v($data, 'country_code'),
            ':notes'            => $v($data, 'notes'),
            ':is_active'        => $v($data, 'is_active', true),
            ':created_by'       => $v($data, 'created_by'),
            ':updated_by'       => $v($data, 'updated_by'),
            ':last_login_at'    => $v($data, 'last_login_at'),
            ':company_id'       => $v($data, 'company_id'),
        ]);

        // PostgreSQL RETURNING id kullanımı
        $id = (int) $stmt->fetchColumn();
        return $id;
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        // Yalın bir update: tüm alanları set ediyoruz; istemediğin alanları çıkarabilirsin.
        $sql = "
            UPDATE public.users SET
                first_name       = :first_name,
                middle_name      = :middle_name,
                last_name        = :last_name,
                email            = :email,
                gender           = :gender,
                birth_date       = :birth_date,
                death_date       = :death_date,
                phone            = :phone,
                secondary_phone  = :secondary_phone,
                national_id      = :national_id,
                passport_no      = :passport_no,
                marital_status   = :marital_status,
                nationality_code = :nationality_code,
                place_of_birth   = :place_of_birth,
                timezone         = :timezone,
                language         = :language,
                photo_url        = :photo_url,
                address_line1    = :address_line1,
                address_line2    = :address_line2,
                city             = :city,
                state_region     = :state_region,
                postal_code      = :postal_code,
                country_code     = :country_code,
                notes            = :notes,
                is_active        = :is_active,
                updated_at       = now(),
                updated_by       = :updated_by,
                last_login_at    = :last_login_at,
                company_id       = :company_id
            WHERE id = :id AND deleted_at IS NULL
        ";

        $stmt = $pdo->prepare($sql);

        $v = static function (array $a, string $k, $default = null) {
            return array_key_exists($k, $a) ? $a[$k] : $default;
        };

        $stmt->execute([
            ':first_name'       => $v($data, 'first_name'),
            ':middle_name'      => $v($data, 'middle_name'),
            ':last_name'        => $v($data, 'last_name'),
            ':email'            => $v($data, 'email'),
            ':gender'           => $v($data, 'gender'),
            ':birth_date'       => $v($data, 'birth_date'),
            ':death_date'       => $v($data, 'death_date'),
            ':phone'            => $v($data, 'phone'),
            ':secondary_phone'  => $v($data, 'secondary_phone'),
            ':national_id'      => $v($data, 'national_id'),
            ':passport_no'      => $v($data, 'passport_no'),
            ':marital_status'   => $v($data, 'marital_status'),
            ':nationality_code' => $v($data, 'nationality_code'),
            ':place_of_birth'   => $v($data, 'place_of_birth'),
            ':timezone'         => $v($data, 'timezone'),
            ':language'         => $v($data, 'language'),
            ':photo_url'        => $v($data, 'photo_url'),
            ':address_line1'    => $v($data, 'address_line1'),
            ':address_line2'    => $v($data, 'address_line2'),
            ':city'             => $v($data, 'city'),
            ':state_region'     => $v($data, 'state_region'),
            ':postal_code'      => $v($data, 'postal_code'),
            ':country_code'     => $v($data, 'country_code'),
            ':notes'            => $v($data, 'notes'),
            ':is_active'        => $v($data, 'is_active', true),
            ':updated_by'       => $v($data, 'updated_by'),
            ':last_login_at'    => $v($data, 'last_login_at'),
            ':company_id'       => $v($data, 'company_id'),
            ':id'               => $id,
        ]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        // Soft delete: geri döndürülebilir silme
        $stmt = $pdo->prepare("UPDATE public.users SET deleted_at = now() WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);

        // Gerçek silme istersen:
        // $stmt = $pdo->prepare("DELETE FROM public.users WHERE id = :id");
        // $stmt->execute([':id' => $id]);
    }

    // EK: Benzersiz alan kontrolü (email, national_id, passport_no, uuid)
    public static function existsByUnique(PDO $pdo, string $column, string $value, ?int $excludeId = null): bool
    {
        // Sadece bu kolonlara izin ver
        $allowed = ['email', 'national_id', 'passport_no', 'uuid'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid unique column: ' . $column);
        }

        $sql = "SELECT 1 FROM public.users WHERE \"$column\" = :val AND deleted_at IS NULL";
        $params = [':val' => $value];

        if ($excludeId !== null) {
            $sql .= " AND id <> :excludeId";
            $params[':excludeId'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }
}
