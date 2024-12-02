<?php
namespace App\Repositories;

use App\Html\Response;

class CityRepository extends BaseRepository
{
    function __construct(
        $host = self::HOST, 
        $user = self::USER,
        $password = self::PASSWORD,
        $database = self::DATABASE)
    {
        parent::__construct($host, $user, $password, $database);
        $this->tableName = 'cities';
    }

    function getAbc($idCounty)
    {
        $sql = "SELECT DISTINCT SUBSTRING(name, 1, 1) FROM {$this->tableName} WHERE id_county = ?";

        return $this->mysqli->query($sql, [$idCounty])->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll(): array
    {
        $query = $this->select() . "ORDER BY city";

        return $this->mysqli
            ->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    function getCitiesByCounty($countyId)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id_county = ? ORDER BY city";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param('i', $countyId);

        if (!$stmt->execute()) {
            Response::response([], 404);
        }

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}