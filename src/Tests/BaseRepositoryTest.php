<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Repositories\BaseRepository;
use mysqli;
use mysqli_result;

class BaseRepositoryTest extends TestCase
{
    private $mysqliMock;
    private $repository;

    protected function setUp(): void
    {
        // Create a mock of the mysqli class
        $this->mysqliMock = $this->createMock(\mysqli::class);

        // Extend the BaseRepository class and set the tableName and mocked mysqli connection
        $this->repository = new class($this->mysqliMock) extends BaseRepository {
            public string $tableName = 'test_table';  // Define a table name for testing
            public function __construct($mysqliMock) {
                $this->mysqli = $mysqliMock;  // Use the mocked mysqli object
            }
        };
    }

    public function testCreate()
    {
        // Simulate the first query for the INSERT operation
        $this->mysqliMock->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                true, // For the INSERT query
                $this->createMockMysqliResult(['id' => 1]) // For the LAST_INSERT_ID query
            );

        // Call the create method with sample data
        $data = ['name' => 'Sample Data'];
        $newId = $this->repository->create($data);

        // Assert that the returned ID matches the expected value
        $this->assertEquals(1, $newId);
    }

    // Helper function to create a mock mysqli_result with fetch_assoc method
    private function createMockMysqliResult(array $data)
    {
        // Mock the mysqli_result class
        $mockResult = $this->createMock(\mysqli_result::class);

        // Set the fetch_assoc method to return the provided data
        $mockResult->expects($this->once())
            ->method('fetch_assoc')
            ->willReturn($data);

        return $mockResult;
    }



    public function testFind()
    {
        // Simulate the query for finding a record by ID
        $this->mysqliMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT * FROM `test_table` WHERE id = 1'))
            ->willReturn($this->createMockMysqliResult(['id' => 1, 'name' => 'Sample Data']));

        // Call the find method and assert the returned array
        $result = $this->repository->find(1);
        $this->assertEquals(['id' => 1, 'name' => 'Sample Data'], $result);
    }

    public function testGetAll()
    {
        // Simulate the query for retrieving all records
        $this->mysqliMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT * FROM `test_table` ORDER BY name'))
            ->willReturn($this->createMockMysqliResultMultiple([
                ['id' => 1, 'name' => 'Sample Data 1'],
                ['id' => 2, 'name' => 'Sample Data 2']
            ]));

        // Call the getAll method and assert the returned array
        $result = $this->repository->getAll();
        $this->assertEquals([
            ['id' => 1, 'name' => 'Sample Data 1'],
            ['id' => 2, 'name' => 'Sample Data 2']
        ], $result);
    }

//    public function testUpdate()
//    {
//        // Simulate the query for updating a record
//        $this->mysqliMock->expects($this->once())
//            ->method('query')
//            ->with($this->stringContains("UPDATE `test_table` SET name = 'Updated Data' WHERE id = 1"))
//            ->willReturn(true);
//
//        // Simulate the query for finding the updated record
//        $this->mysqliMock->expects($this->once())
//            ->method('query')
//            ->with($this->stringContains('SELECT * FROM `test_table` WHERE id = 1'))
//            ->willReturn($this->createMockMysqliResult(['id' => 1, 'name' => 'Updated Data']));
//
//        // Call the update method and assert the returned updated record
//        $updatedRecord = $this->repository->update(1, ['name' => 'Updated Data']);
//        $this->assertEquals(['id' => 1, 'name' => 'Updated Data'], $updatedRecord);
//    }

    public function testUpdate()
    {
        // Simulate the query for updating a record (first call)
        $this->mysqliMock->expects($this->once())  // Only once for the update query
        ->method('query')
            ->with($this->stringContains("UPDATE `test_table` SET name = 'Updated Data' WHERE id = 1"))
            ->willReturn(true);  // Return true for successful UPDATE

        // Simulate the query for finding the updated record (second call)
//        $this->mysqliMock->expects($this->once())  // Only once for the select query
//        ->method('query')
//            ->with($this->stringContains("SELECT * FROM `test_table` WHERE id = 1"))
//            ->willReturn($this->createMockMysqliResult(['id' => 1, 'name' => 'Updated Data']));  // Return mock data

        // Call the update method and assert the returned updated record
        $updatedRecord = $this->repository->update(1, ['name' => 'Updated Data']);

        // Assert that the returned record matches the expected result
        $this->assertEquals(['id' => 1, 'name' => 'Updated Data'], $updatedRecord);
    }



    public function testDelete()
    {
        // Simulate the query for deleting a record
        $this->mysqliMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM `test_table` WHERE id = 1'))
            ->willReturn(true);

        // Call the delete method and assert the deletion returns true
        $result = $this->repository->delete(1);
        $this->assertTrue($result);
    }

    public function testFindByName()
    {
        // Simulate the query for finding records by name
        $this->mysqliMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT * FROM `test_table` WHERE name LIKE \'%sample%\' ORDER BY name'))
            ->willReturn($this->createMockMysqliResultMultiple([
                ['id' => 1, 'name' => 'Sample Data 1'],
                ['id' => 2, 'name' => 'Sample Data 2']
            ]));

        // Call the findByName method and assert the returned array
        $result = $this->repository->findByName('sample');
        $this->assertEquals([
            ['id' => 1, 'name' => 'Sample Data 1'],
            ['id' => 2, 'name' => 'Sample Data 2']
        ], $result);
    }

    public function testGetCount()
    {
        // Simulate the query for counting the records
        $this->mysqliMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT COUNT(1) AS cnt FROM `test_table`'))
            ->willReturn($this->createMockMysqliResult(['cnt' => 2]));

        // Call the getCount method and assert the count
        $result = $this->repository->getCount();
        $this->assertEquals(2, $result);
    }

    // Helper methods to mock mysqli results
//    private function createMockMysqliResult(array $row)
//    {
//        $resultMock = $this->getMockBuilder(mysqli_result::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        $resultMock->method('fetch_assoc')
//            ->willReturn($row);
//
//        return $resultMock;
//    }

    private function createMockMysqliResultMultiple(array $rows)
    {
        $resultMock = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultMock->method('fetch_all')
            ->willReturn($rows);

        return $resultMock;
    }
}
