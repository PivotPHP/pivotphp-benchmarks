<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Post;
use App\Models\User;
use Cycle\ORM\ORMInterface;
use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PostController
{
    private ?ORMInterface $orm = null;
    
    private function getORM(): ORMInterface
    {
        if ($this->orm === null) {
            $this->orm = app()->make(ORMInterface::class);
        }
        return $this->orm;
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $repository = $this->getORM()->getRepository(Post::class);
        $query = $repository->select();
        
        // Filter by published status
        $published = $request->getQueryParams()['published'] ?? null;
        if ($published !== null) {
            $query->where('published', filter_var($published, FILTER_VALIDATE_BOOLEAN));
        }
        
        // Filter by user
        $userId = $request->getQueryParams()['user_id'] ?? null;
        if ($userId) {
            $query->where('user_id', (int) $userId);
        }
        
        $posts = $query->fetchAll();
        $data = array_map(fn(Post $post) => $post->toArray(), $posts);
        
        return Response::json([
            'data' => $data,
            'total' => count($data),
        ]);
    }

    public function show(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $repository = $this->getORM()->getRepository(Post::class);
        $post = $repository->findByPK($id);
        
        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }
        
        $data = $post->toArray();
        $data['user'] = $post->getUser()->toArray();
        
        return Response::json(['data' => $data]);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        
        // Validation
        if (empty($data['title']) || empty($data['content']) || empty($data['user_id'])) {
            return Response::json([
                'error' => 'Validation failed',
                'messages' => [
                    'title' => empty($data['title']) ? 'Title is required' : null,
                    'content' => empty($data['content']) ? 'Content is required' : null,
                    'user_id' => empty($data['user_id']) ? 'User ID is required' : null,
                ],
            ], 422);
        }
        
        // Find user
        $userRepository = $this->getORM()->getRepository(User::class);
        $user = $userRepository->findByPK((int) $data['user_id']);
        
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        // Create post
        $post = new Post($data['title'], $data['content'], $user);
        
        if (!empty($data['published'])) {
            $post->publish();
        }
        
        $this->getORM()->persist($post);
        $this->getORM()->run();
        
        return Response::json([
            'message' => 'Post created successfully',
            'data' => $post->toArray(),
        ], 201);
    }

    public function update(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = json_decode((string) $request->getBody(), true);
        
        $repository = $this->getORM()->getRepository(Post::class);
        $post = $repository->findByPK($id);
        
        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }
        
        // Update fields
        if (!empty($data['title'])) {
            $post->setTitle($data['title']);
        }
        
        if (!empty($data['content'])) {
            $post->setContent($data['content']);
        }
        
        if (isset($data['published'])) {
            if ($data['published']) {
                $post->publish();
            } else {
                $post->unpublish();
            }
        }
        
        $this->getORM()->persist($post);
        $this->getORM()->run();
        
        return Response::json([
            'message' => 'Post updated successfully',
            'data' => $post->toArray(),
        ]);
    }

    public function destroy(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $repository = $this->getORM()->getRepository(Post::class);
        $post = $repository->findByPK($id);
        
        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }
        
        $this->getORM()->delete($post);
        $this->getORM()->run();
        
        return Response::json([
            'message' => 'Post deleted successfully',
        ]);
    }
}