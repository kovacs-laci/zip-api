<?php

namespace App\Html;

use App\Repositories\BaseRepository;
use App\Repositories\CountyRepository;
use App\Repositories\CityRepository;

class Request
{
    static array $acceptedRoutes = [
        'POST' => ['/counties', '/cities', '/counties/{county_id}/cities'],
        'GET' => [
            '/counties',
            '/cities',
            '/counties/{county_id}',
            '/counties/{county_id}/cities',
            '/cities/{city_id}',
        ],
        'DELETE' => ['/counties/{id}', '/cities/{id}'],
        'PUT' => ['/counties/{id}', '/cities/{id}'],
    ];

    static function handle()
    {
        // Get current request method and URI
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Check if the request is valid
        if (!self::isRouteAllowed($requestMethod, $requestUri, self::$acceptedRoutes)) {
            return Response::response([], 400);
        }

        $requestUri = $_SERVER['REQUEST_URI'];
        $requestData = self::getRequestData();
        $arrUri = self::requestUriToArray($requestUri);
        $resourceName = self::getResourceName($arrUri);
        $resourceId = self::getResourceId($arrUri);
        $childResourceName = self::getChildResourceName($arrUri);
//        $childResourceId = self::getChildResourceId($arrUri);
        switch ($requestMethod){
            case "POST":
                self::postRequest($resourceName, $requestData);
                break;
            case "PUT":
                self::putRequest($resourceName, $resourceId, $requestData);
                break;
            case "GET":
                self::getRequest($resourceName, $resourceId, $childResourceName);
                break;
            case "DELETE":
                self::deleteRequest($resourceName, $resourceId);
                break;
            default:
                echo 'Unknown request type';
                break;
        }
    }

    /**
     * @api {post} /counties Add a new county
     * @apiName create
     * @apiGroup Counties
     * @apiVersion 1.0.0
     *
     * @apiBody {String} name Mandatory name of county
     *
     * @apiParamExample {json} Request-Example:
     *       {
     *         "name": "Bereg"
     *       }
     *
     * @apiSuccess {Object[]} counties       List of counties.
     * @apiSuccess {Number}   counties.id    County id.
     *
     * @apiSuccessExample {json} Success-Response:
     *      HTTP/1.1 201 Created
     *      {
     *          "data":[
     *              {"id":57}
     *          ],
     *          "message":"Created",
     *          "status":201
     *      }
     */
    private static function postRequest($resourceName, $requestData)
    {
        $repository = self::getRepository($resourceName);
        if (!$repository) {
            return Response::response([], 400);
        }

        $newId = $repository->create($requestData);
        if ($newId) {
            $code = 201; // Created
        }

        Response::response(['id' => $newId], $code);
    }

    /**
     * @api {delete} /counties/:id Delete county with {id}
     * @apiName index
     * @apiGroup Counties
     * @apiVersion 1.0.0
     *
     * @apiParam {Number} id County unique ID.
     *
     * @apiName delete
     * @apiGroup Counties
     * @apiVersion 1.0.0
     *
     * @apiSuccessExample {json} Success-Response:
     *      HTTP/1.1 204 No content
     *      {
     *          "data":[],
     *          "message":"No content",
     *          "status":204
     *      }
     */
    private static function deleteRequest($resourceName, $resourceId)
    {
        $repository = self::getRepository($resourceName);
        $result = $repository->delete($resourceId);
        if ($result) {
            $code = 204;
        }
        Response::response([], $code);
    }
    /**
     * @api {get} /counties Get list of counties
     * @apiName index
     * @apiGroup Counties
     * @apiVersion 1.0.0
     *
     * @apiSuccess {Object[]} counties       List of counties.
     * @apiSuccess {Number}   counties.id    County id.
     * @apiSuccess {String}   counties.name  County Name.
     *
     * @apiSuccessExample {json} Success-Response:
     *      HTTP/1.1 200 OK
     *      {
     *          "data":[
     *              {"id":2,"name":"B\u00e1cs-Kiskun"},
     *              {"id":3,"name":"Baranya"},
     *              {...}
     *          ],
     *          "message":"OK",
     *          "status":200
     *      }
     * @apiErrorExample {json} Error-Response:
     *      HTTP/1.1 404 Not Found
     *      {
     *        "data":[],
     *           "message":"Not Found",
     *           "status":404
     *      }
     */
    private static function getRequest($resourceName, $resourceId = null, $childResourceName = null)
    {
        if ($childResourceName) {
            $repository = self::getRepository($childResourceName);
            if ($resourceId) {
                $entities = $repository->getCitiesByCounty($resourceId);
                Response::response($entities, 200);
                return;
            }
        }
        $repository = self::getRepository($resourceName);
        if ($resourceId) {
            $entity = $repository->find($resourceId);
            if (!$entity) {
                Response::response([], 404);
                return;
            }
            Response::response($entity, 200);
            return;
        }
        $entities = $repository->getAll();
        Response::response($entities, 200);
    }

    private static function putRequest($resourceName, $resourceId, $requestData)
    {
        $repository = self::getRepository($resourceName);
        $code = 404;
        $entity = $repository->find($resourceId);
        if ($entity) {
            foreach ($requestData as $key => $value) {
                $data[$key] = $value;
            }
            $result = $repository->update($resourceId, $data);
            if ($result) {
                $code = 202;
            }
        }
        Response::response([], $code);
    }

    private static function getRequestData(): ?array
    {
        return json_decode(file_get_contents("php://input"), true);
    }

    private static function requestUriToArray($uri): array
    {
        $arrUri = explode("/", $uri);
        $result = [
            'resourceName' => $arrUri[1] ?? null,
            'resourceId' => !empty($arrUri[2]) ? (int)$arrUri[2] :  null,
            'childResourceName' => $arrUri[3] ?? null,
            'childResourceId' => !empty($arrUri[4]) ? (int)$arrUri[4] : null,
        ];

        return $result;
    }
    private static function getResourceId(array $request): ?int
    {
//        return $request['childResourceId'] ?? $request['resourceId'];
        return $request['resourceId'];
    }

    private static function getResourceName(array $request): ?string
    {
        return $request['resourceName'];
    }

    private static function getChildResourceId(array $request): ?int
    {
//        return $request['childResourceId'] ?? $request['resourceId'];
        return $request['childResourceId'];
    }

    private static function getChildResourceName(array $request): ?string
    {
        return $request['childResourceName'];
    }

//    private static function getFilterData(): array
//    {
//        $result = [];
//        $arrUri = self::getArrUri($_SERVER['REQUEST_URI']);
//
//        return $result;
//    }

    // Function to check if route matches the request URI
    private static function isRouteMatch($route, $uri): bool
    {
        $routeParts = explode('/', trim($route, '/'));
        $uriParts = explode('/', $uri);
        if (count($routeParts) !== count($uriParts)) {
            return false; // Different number of segments
        }

        foreach ($routeParts as $index => $routePart) {
            if (strpos($routePart, '{') === 0 && strpos($routePart, '}') === (strlen($routePart) - 1)) {
                // Parameter placeholder, matches any value
                continue;
            }
            if ($routePart !== $uriParts[$index]) {
                return false; // Segment does not match
            }
        }

        return true; // All segments match
    }

    // Check if the request is in the routes
    private static function isRouteAllowed($method, $uri, $routes): bool
    {
        if (!isset($routes[$method])) {
            return false; // Method not allowed
        }

        foreach ($routes[$method] as $route) {
            if (self::isRouteMatch($route, $uri)) {
                return true;
            }
        }

        return false;
    }

    private static function getRepository($resourceName): ?BaseRepository
    {
        switch ($resourceName) {
            case 'counties':
                $repository = new CountyRepository();
            break;
            case 'cities':
                $repository = new CityRepository();
            break;
            default:
                $repository = null;
        }

        return $repository;
    }
}