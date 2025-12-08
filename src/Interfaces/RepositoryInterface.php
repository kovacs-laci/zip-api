<?php
namespace App\Interfaces;

interface RepositoryInterface
{
    public function create(array $data): ?int;
    public function find(int $id): array;
    public function getAll(): array;
    public function update(int $id, array $data);
    public function delete(int $id);
}
