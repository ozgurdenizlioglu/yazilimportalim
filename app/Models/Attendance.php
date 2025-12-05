<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Attendance
{
    private PDO $db;

    public function __construct()
    {
        // Projenizde yaygın kullanım: Database::pdo()
        $this->db = Database::pdo();
    }

    public function search(array $filters, int $limit = 200, int $offset = 0): array
    {
        $where = [];
        $params = [];

        // Tarih filtrelerini normalize edelim (YYYY-MM-DD ise gün başı/sonu)
        if (!empty($filters['from'])) {
            $from = (string)$filters['from'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $from .= ' 00:00:00';
            }
            $where[] = 'a.scanned_at >= :from';
            $params[':from'] = $from;
        }
        if (!empty($filters['to'])) {
            $to = (string)$filters['to'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $to .= ' 23:59:59.999';
            }
            $where[] = 'a.scanned_at <= :to';
            $params[':to'] = $to;
        }

        if (!empty($filters['type'])) {
            $where[] = 'a.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['company_id'])) {
            $where[] = 'c.id = :company_id';
            $params[':company_id'] = (int)$filters['company_id'];
        }
        if (!empty($filters['name'])) {
            $where[] = '(u.first_name ILIKE :name OR u.last_name ILIKE :name)';
            $params[':name'] = '%'.$filters['name'].'%';
        }
        if (!empty($filters['device'])) {
            $where[] = 'a.source_device ILIKE :device';
            $params[':device'] = '%'.$filters['device'].'%';
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $sql = "
            SELECT
              a.id,
              a.scanned_at,
              a.type,
              a.source_device,
              a.notes,
              u.id AS user_id,
              u.first_name,
              u.last_name,
              c.id AS company_id,
              c.name AS company_name
            FROM public.attendance a
            JOIN public.users u ON u.id = a.user_id
            LEFT JOIN public.companies c ON c.id = u.company_id
            $whereSql
            ORDER BY a.scanned_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = "
            SELECT COUNT(*) AS total
            FROM public.attendance a
            JOIN public.users u ON u.id = a.user_id
            LEFT JOIN public.companies c ON c.id = u.company_id
            $whereSql
        ";
        $cstmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $cstmt->bindValue($k, $v);
        }
        $cstmt->execute();
        $total = (int)$cstmt->fetchColumn();

        return ['rows' => $rows, 'total' => $total];
    }

    public function listCompanies(): array
    {
        // Eğer companies tablosunda is_active yoksa alttaki satırı kullanın:
        $sql = "SELECT id, name FROM public.companies WHERE deleted_at IS NULL ORDER BY name ASC";
        // is_active kolonu varsa bu satırı tercih edebilirsiniz:
        // $sql = "SELECT id, name FROM public.companies WHERE deleted_at IS NULL AND is_active = true ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}