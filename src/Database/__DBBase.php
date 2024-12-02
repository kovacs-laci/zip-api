<?php

namespace App\Database;

//use App\Interfaces\DBInterface;
class DBBase extends DB // implements DBInterface
{

    protected string $tableName;

    /**
     * @param array $data
     * @return void
     */
    public function create(array $data): ?int
    {
        $sql = "INSERT INTO `%s` (%s) VALUES (%s)";
        $fields = '';
        $values = '';
        foreach ($data as $field => $value) {
            if ($fields > '') {
                $fields .= ',' . $field;
            } else
                $fields .= $field;

            if ($values > '') {
                $values .= ',' . "'$value'";
            } else
                $values .= "'$value'";
        }
        $sql = sprintf($sql, $this->tableName, $fields, $values);
        $this->mysqli->query($sql);

        $lastInserted = $this->mysqli->query("SELECT LAST_INSERT_ID() id;")->fetch_assoc();

        return $lastInserted['id'];
    }

    public function find(int $id): array
    {
        $query = $this->select() . "WHERE id = $id";

        $result = $this->mysqli->query($query)->fetch_assoc();
        if (!$result) {
            $result = [];
        }

        return $result;
    }

    public function getByName(string $name): array
    {
        $query = $this->select() . "WHERE name = '$name'";

        return $this->mysqli->query($query)->fetch_assoc();
    }

    public function getAll(): array
    {
        $query = $this->select() . "ORDER BY name";

        return $this->mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function update(int $id, array $data)
    {
        $query = "UPDATE `{$this->tableName}` SET %s WHERE id = $id;";

        $set = '';
        foreach ($data as $field => $value) {
            if ($set > '') {
                $set .= ", $field = '$value'";
            } else
                $set .= "$field = '$value'";
        }

        $query = sprintf($query, $set);
        $this->mysqli->query($query);

        return $this->find($id);
    }

    public function delete(int $id)
    {
        $query = "DELETE FROM `{$this->tableName}` WHERE id = $id";

        return $this->mysqli->query($query);
    }

    public function findByName($needle)
    {
        $query = $this->select() . "WHERE name LIKE '%$needle%' ORDER BY name";

        return $this->mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
    }

//    public function truncate()
//    {
//        $query = "TRUNCATE TABLE makers;";
//
//        return $this->mysqli->query($query);
//    }

    public function getCount()
    {
        $query = "SELECT COUNT(1) AS cnt FROM `{$this->tableName}`;";

        $result = $this->mysqli->query($query)->fetch_assoc();

        return $result['cnt'];
    }

    public function select()
    {
        return "SELECT * FROM `{$this->tableName}` ";
    }
}