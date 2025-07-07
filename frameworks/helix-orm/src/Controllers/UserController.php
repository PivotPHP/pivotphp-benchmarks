<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Cycle\ORM\ORMInterface;
use Helix\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    private UserRepository $userRepository;
    private ORMInterface $orm;
    
    public function __construct(UserRepository $userRepository, ORMInterface $orm)
    {
        $this->userRepository = $userRepository;
        $this->orm = $orm;
    }
    
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        
        $result = $this->userRepository->paginate($page, $limit);
        
        return Response::json([
            'data' => array_map(fn(User $user) => $user->toArray(), $result['items']),
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'pages' => $result['pages']
            ]
        ]);
    }
    
    public function show(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        return Response::json(['data' => $user->toArray()]);
    }
    
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        // Validate
        if (empty($data['name']) || empty($data['email'])) {
            return Response::json(['error' => 'Name and email are required'], 422);
        }
        
        // Check if email exists
        if ($this->userRepository->findByEmail($data['email'])) {
            return Response::json(['error' => 'Email already exists'], 422);
        }
        
        $user = new User();
        $user->setName($data['name'])
             ->setEmail($data['email']);
        
        $this->orm->persist($user);
        $this->orm->run();
        
        return Response::json(['data' => $user->toArray()], 201);
    }
    
    public function update(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        
        if (isset($data['email'])) {
            // Check if email is taken by another user
            $existing = $this->userRepository->findByEmail($data['email']);
            if ($existing && $existing->getId() !== $user->getId()) {
                return Response::json(['error' => 'Email already exists'], 422);
            }
            $user->setEmail($data['email']);
        }
        
        $user->setUpdatedAt(new \DateTime());
        
        $this->orm->persist($user);
        $this->orm->run();
        
        return Response::json(['data' => $user->toArray()]);
    }
    
    public function destroy(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        $this->orm->delete($user);
        $this->orm->run();
        
        return Response::json(['message' => 'User deleted successfully'], 204);
    }
    
    public function posts(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $userId = (int) $args['id'];
        
        $user = $this->userRepository->findByIdWithPosts($userId);
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        $posts = [];
        foreach ($user->getPosts() as $post) {
            $posts[] = $post->toArray();
        }
        
        return Response::json(['data' => $posts]);
    }
}