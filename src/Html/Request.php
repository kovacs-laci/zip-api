<?php

namespace App\Html;

use App\Repositories\BaseRepository;
use App\Repositories\CountyRepository;
use App\Repositories\CityRepository;
use App\Repositories\UserRepository;

class Request
{
    static array $acceptedRoutes = [
        'POST' => [
            '/users/login',
            '/users/logout',
            '/users',
            '/counties',
            '/cities',
            '/counties/{county}/cities'
        ],
        'GET' => [
            '/users',
            '/users/{id}',
            '/counties',
            '/counties/{id}',
            '/cities',
            '/cities/{id}',
            '/counties/{county}/cities',
            '/counties/{county}/abc',
            '/counties/{county}/abc/{initial}',
        ],
        'PUT' => [
            '/users/{id}',
            '/counties/{id}',
            '/cities/{id}',
            '/counties/{county}/cities/{id}'
        ],
        'DELETE' => [
            '/users/{id}',
            '/counties/{id}',
            '/cities/{id}',
            '/counties/{county}/cities/{id}'
        ],
    ];

    static function handle()
    {
        // CORS headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        // Preflight (OPTIONS) request kezelése
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Get current request method and URI
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Check if the request is valid
        if (!self::isRouteAllowed($requestMethod, $requestUri, self::$acceptedRoutes)) {
            Response::error('Bad request');
            exit;
        }

        $requestUri = $_SERVER['REQUEST_URI'];
        // Csak az útvonal rész (query string nélkül)
        $path = parse_url($requestUri, PHP_URL_PATH);
        $requestData = self::getRequestData();
        $arrUri = self::requestUriToArray($path);
        $resourceName = self::getResourceName($arrUri);
        $resourceId = self::getResourceId($arrUri);
        $childResourceName = self::getChildResourceName($arrUri);
        $childResourceId = self::getChildResourceId($arrUri);
        switch ($requestMethod){
            case "POST":
                $resourceName = $childResourceName ?: $resourceName;
                self::postRequest($resourceName, $requestData);
                break;
            case "PUT":
                self::putRequest($resourceName, $resourceId, $requestData);
                break;
            case "GET":
                self::getRequest($resourceName, $resourceId, $childResourceName, $childResourceId);
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
     * @api {post} /:resource Create new entity
     * @apiName CreateEntity
     * @apiGroup Generic
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} Request-Example:
     *     {
     *         "name": "Borsod-Abaúj-Zemplén"
     *     }
     *
     * @apiSuccess (201 Created) {Number} id Newly created entity ID.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 201 Created
     *     {
     *         "data": {
     *             "id": 42
     *         },
     *         "message": "Created",
     *         "status": 201
     *     }
     *
     * @apiError (400 Bad Request) BadRequest Couldn't create entity.
     *
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Bad Request
     *     {
     *         "data": [],
     *         "message": "Bad request",
     *         "status": 400
     *     }
     */

    /**
     * @api {post} /users/login User login
     * @apiName UserLogin
     * @apiGroup Users
     * @apiVersion 1.0.0
     *
     * @apiParam {String} email User email.
     * @apiParam {String} password User password.
     *
     * @apiParamExample {json} Request-Example:
     *     {
     *         "email": "test@example.com",
     *         "password": "secret"
     *     }
     *
     * @apiSuccess {String} token Authentication token.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "data": {
     *             "token": "abc123..."
     *         },
     *         "message": "OK",
     *         "status": 200
     *     }
     *
     * @apiError (401 Unauthorized) InvalidCredentials Wrong email or password.
     */

    /**
     * @api {post} /users/logout User logout
     * @apiName UserLogout
     * @apiGroup Users
     * @apiVersion 1.0.0
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "data": [],
     *         "message": "Logged out",
     *         "status": 200
     *     }
     */

    private static function postRequest($resourceName, $requestData)
    {
        // Speciális login kezelés
        if ($resourceName === 'users' && self::isLoginRequest()) {
            self::handleLogin($requestData);
            return;
        }
        // LOGOUT speciális kezelés
        if ($resourceName === 'users' && self::isLogoutRequest()) {
            self::handleLogout();
            return;
        }

        // Általános CRUD POST
        $repository = self::getRepository($resourceName);
        if (!$repository) {
            Response::error("Couldn't get repository", 400);
            return;
        }

        $newId = $repository->create($requestData);
        if ($newId) {
            $entity = $repository->find($newId);
            Response::created(['id' => (int)$newId, 'entity' => $entity]);
            return;
        }

        Response::error("Bad request", 400);
    }

    // Segédfüggvény: login felismerése
    private static function isLoginRequest(): bool
    {
        return isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/users/login') !== false;
    }

    // Segédfüggvény: login logika
    private static function handleLogin(array $requestData): void
    {
        /**
         * @var UserRepository $repository
         */
        $repository = self::getRepository('users');
        if (!$repository instanceof UserRepository) {
            Response::error("Couldn't get UserRepository", 400);
            return;
        }

        $user = $repository->findByEmail($requestData['email'] ?? '');
        if (!$user || !password_verify($requestData['password'] ?? '', $user['password'])) {
            Response::error("Invalid credentials", 401); // Unauthorized
            return;
        }

        $token = $repository->createToken($user['id']);
        Response::ok([
            'token' => $token,
            'user'  => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email']
            ]
        ], 200);
    }

    // Segédfüggvény: logout felismerése
    private static function isLogoutRequest(): bool
    {
        return isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/users/logout') !== false;
    }

    // Segédfüggvény: logout logika
    private static function handleLogout(): void
    {
        $repository = self::getRepository('users');
        if (!$repository) {
            Response::error("Couldn't get repository", 400);
            return;
        }

        // Token kinyerése az Authorization headerből
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (strpos($authHeader, 'Bearer ') !== 0) {
            Response::error("Missing or invalid Authorization header", 401);
            return;
        }

        $token = substr($authHeader, 7); // "Bearer " levágása

        // Token érvénytelenítése
        $result = $repository->invalidateToken($token);
        if ($result) {
            Response::ok(['message' => 'Logged out'], 204);
        } else {
            Response::error("Invalid token", 401);
        }
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
        $repository->delete($resourceId);
        Response::deleted();
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
    private static function getRequest($resourceName, $resourceId = null, $childResourceName = null, $childResourceId = null)
    {
        // Child resource (pl. /counties/5/cities)
        if ($childResourceName) {
            $repository = self::getRepository($childResourceName);

            if ($resourceId) {

                if ($childResourceName === 'cities') {
                    /** @var CityRepository $repository */

                    // 🔍 KERESÉS TÁMOGATÁSA
                    $needle = $_GET['needle'] ?? null;
                    if ($needle) {
                        $entities = $repository->searchByName($resourceId, $needle);
                        Response::ok(['entities' => $entities]);
                        exit;
                    }

                    // Ha nincs keresés → normál lista
                    $entities = $repository->getCitiesByCounty($resourceId);
                    Response::ok(['entities' => $entities]);
                    exit;
                }

                if ($childResourceName === 'abc') {
                    /** @var CityRepository $repository */
                    if ($childResourceId) {
                        $entities = $repository->getCitiesByInitial($resourceId, $childResourceId);
                        Response::ok(['entities' => $entities]);
                        exit;
                    }
                    $initials = $repository->getCitiesInitialsByCounty($resourceId);
                    Response::ok(['initials' => $initials]);
                    exit;
                }
            }
        }

        // Normál resource
        $repository = self::getRepository($resourceName);

        // ID szerinti lekérés
        if ($resourceId) {
            $entity = $repository->find($resourceId);
            if (!$entity) {
                Response::error('Not found', 404);
                exit;
            }
            Response::ok(['entity' => $entity]);
            exit;
        }

//        // 🔍 Keresés támogatása
//        $needle = $_GET['needle'] ?? null;
//
//        if ($needle) {
//            /** @var CityRepository $repository */
//            $entities = $repository->searchByName($resourceId, $needle);
//            Response::ok(['entities' => $entities]);
//            exit;
//        }

        // Teljes lista
        $entities = $repository->getAll();
        Response::ok(['entities' => $entities]);
    }


    private static function putRequest($resourceName, $resourceId, $requestData)
    {
        $repository = self::getRepository($resourceName);
        $entity = $repository->find($resourceId);
        if (!$entity) {
            Response::error('Not found', 404);
            exit;
        }

        $data = [];
        foreach ($requestData as $key => $value) {
            $data[$key] = $value;
        }
        $result = $repository->update($resourceId, $data);
        if ($result) {
            Response::updated(['id' => (int)$resourceId, 'entity' => $result]);
        }
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
            'childResourceId' => !empty($arrUri[4]) ? $arrUri[4] : null,
        ];

        return $result;
    }
    private static function getResourceId(array $request): ?int
    {
        return $request['resourceId'];
    }

    private static function getResourceName(array $request): ?string
    {
        return $request['resourceName'];
    }

    private static function getChildResourceId(array $request): int|string|null
    {
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
            case 'abc':
                $repository = new CityRepository();
                break;
            case 'users':
                $repository = new UserRepository();
                break;
            default:
                $repository = null;
        }

        return $repository;
    }
}