<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Episode;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * List comments for an episode.
     */
    public function index(Request $request, Episode $episode): JsonResponse
    {
        $comments = Comment::where('episode_id', $episode->id)
            ->approved()
            ->topLevel()
            ->with([
                'user:id,name,username,avatar',
                'replies' => fn($q) => $q->approved()->with('user:id,name,username,avatar')->latest(),
            ])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($comments);
    }

    /**
     * Post a comment on an episode.
     */
    public function store(StoreCommentRequest $request, Episode $episode): JsonResponse
    {
        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'episode_id' => $episode->id,
            'parent_id' => $request->parent_id,
            'body' => $request->body,
        ]);

        $comment->load('user:id,name,username,avatar');

        return $this->created($comment, 'Comment posted');
    }

    /**
     * Delete a comment (own comment only).
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return $this->forbidden('You can only delete your own comments.');
        }

        $comment->delete();

        return $this->noContent('Comment deleted');
    }

    /**
     * Like/unlike a comment.
     */
    public function toggleLike(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        $like = Like::where('user_id', $user->id)
            ->where('likeable_id', $comment->id)
            ->where('likeable_type', Comment::class)
            ->first();

        if ($like) {
            $like->delete();
            $comment->decrement('like_count');
            return $this->success(['liked' => false], 'Comment unliked');
        }

        Like::create([
            'user_id' => $user->id,
            'likeable_id' => $comment->id,
            'likeable_type' => Comment::class,
        ]);

        $comment->increment('like_count');

        return $this->success(['liked' => true], 'Comment liked');
    }
}
