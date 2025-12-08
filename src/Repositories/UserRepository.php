<?php
namespace App\Repositories;

class UserRepository extends BaseRepository
{
    public string $tableName = 'users';

    /**
     * Új felhasználó létrehozása
     * - Kötelező: email és password
     * - Jelszó hash-elése biztonságosan
     */
    public function create(array $data): ?int
    {
        if (!isset($data['email']) || !isset($data['password'])) {
            throw new \Exception("UserRepository error: email és password kötelező.");
        }

        // Jelszó biztonságos tárolása
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        return parent::create($data);
    }

    /**
     * Felhasználó keresése email alapján
     * - Login során használjuk
     */
    public function findByEmail(string $email): ?array
    {
        $query = $this->select() . "WHERE email = '$email'";
        return $this->mysqli->query($query)->fetch_assoc() ?? null;
    }

    /**
     * Token létrehozása és mentése
     * - Login után generáljuk
     */
    public function createToken(int $userId): string
    {
        // Biztonságos véletlen token generálása
        $rawToken = bin2hex(random_bytes(32));

        // Token mentése az adatbázisba
        $query = sprintf(
            "UPDATE `%s` SET token = '%s' WHERE id = %d",
            $this->tableName,
            $rawToken,
            $userId
        );
        $this->mysqli->query($query);

        // Bearer formátumban adjuk vissza
        return "Bearer " . $rawToken;
    }

    /**
     * Token érvénytelenítése
     * - Logout során használjuk
     */
    public function invalidateToken(string $token): bool
    {
        $query = sprintf("UPDATE `%s` SET token = NULL WHERE token = '%s'", $this->tableName, $token);
        return $this->mysqli->query($query);
    }

    /**
     * Token alapján felhasználó keresése
     * - Védett végpontoknál használjuk
     */
    public function findByToken(string $token): ?array
    {
        $query = $this->select() . "WHERE token = '$token'";
        return $this->mysqli->query($query)->fetch_assoc() ?? null;
    }
}
