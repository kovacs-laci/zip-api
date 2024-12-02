<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Repositories\CountyRepository;
use App\Html\Request;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock the $_SERVER superglobal for different HTTP request methods
        $_SERVER = [];
    }

    public function testGetRequest()
    {
        // Simulate a GET request to /counties
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/counties';

        // Mock the CountyRepository to return a list of counties
        $repositoryMock = $this->getMockBuilder(CountyRepository::class)
            ->onlyMethods(['getAll'])
            ->getMock();

        $repositoryMock->method('getAll')
            ->willReturn([
                ['id' => 1, 'name' => 'County A'],
                ['id' => 2, 'name' => 'County B']
            ]);

        // Expect a 200 OK response with the list of counties
        $this->expectOutputString(json_encode([
            'data' => [
                ['id' => 1, 'name' => 'County A'],
                ['id' => 2, 'name' => 'County B']
            ],
            'message' => 'OK',
            'status' => 200
        ]));

        // Call the handle method to simulate the request
        Request::handle();
    }

    public function testPostRequest()
    {
        // Simulate a POST request to /counties
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/counties';

        // Mock the CountyRepository to handle create operation
        $repositoryMock = $this->getMockBuilder(CountyRepository::class)
            ->onlyMethods(['create'])
            ->getMock();

        // Expect the create method to be called and return a new ID
        $repositoryMock->method('create')
            ->willReturn(57); // Mock new ID

        // Mock input data for the POST request
        $this->mockPostData(['name' => 'Bereg']);

        // Expect a 201 Created response with the new ID
        $this->expectOutputString(json_encode([
            'data' => [
                'id' => 57
            ],
            'message' => 'Created',
            'status' => 201
        ]));

        // Call the handle method to simulate the request
        Request::handle();
    }

    public function testDeleteRequest()
    {
        // Simulate a DELETE request to /counties/57
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/counties/57';

        // Mock the CountyRepository to handle delete operation
        $repositoryMock = $this->getMockBuilder(CountyRepository::class)
            ->onlyMethods(['delete'])
            ->getMock();

        // Expect the delete method to return true
        $repositoryMock->method('delete')
            ->willReturn(true); // Mock delete success

        // Expect a 204 No Content response
        $this->expectOutputString(json_encode([
            'data' => [],
            'message' => 'No content',
            'status' => 204
        ]));

        // Call the handle method to simulate the request
        Request::handle();
    }

    public function testPutRequest()
    {
        // Simulate a PUT request to /counties/57
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/counties/57';

        // Mock the CountyRepository to handle update operation
        $repositoryMock = $this->getMockBuilder(CountyRepository::class)
            ->onlyMethods(['find', 'update'])
            ->getMock();

        // Simulate the county being found
        $repositoryMock->method('find')
            ->willReturn(['id' => 57, 'name' => 'Old Name']);

        // Simulate a successful update
        $repositoryMock->method('update')
            ->willReturn(true); // Mock update success

        // Mock input data for the PUT request
        $this->mockPutData(['name' => 'New Name']);

        // Expect a 202 Accepted response
        $this->expectOutputString(json_encode([
            'data' => [],
            'message' => 'Accepted',
            'status' => 202
        ]));

        // Call the handle method to simulate the request
        Request::handle();
    }

    // Helper function to mock POST/PUT request data
    private function mockPostData(array $data)
    {
        file_put_contents('php://input', json_encode($data));
    }

    private function mockPutData(array $data)
    {
        file_put_contents('php://input', json_encode($data));
    }
}
