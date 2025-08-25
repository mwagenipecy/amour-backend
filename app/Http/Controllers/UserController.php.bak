<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Hobby;
use App\Models\Interest;
use App\Models\UserPhoto;
use App\Models\UserMatch;
use App\Models\Like;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function profile($userId = null)
    {
        try {
            $user = $userId ? User::findOrFail($userId) : Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->load(['hobbies', 'interests', 'photos']);

            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPotentialMatches(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = User::where('id', '!=', $user->id)
                ->where('gender', $user->looking_for)
                ->where('looking_for', $user->gender)
                ->where('age', '>=', $user->age - 10)
                ->where('age', '<=', $user->age + 10);

            if ($user->latitude && $user->longitude) {
                $query->whereNotNull('latitude')
                      ->whereNotNull('longitude');
            }

            $users = $query->with(['hobbies', 'interests', 'photos'])
                          ->inRandomOrder()
                          ->limit(20)
                          ->get();

            foreach ($users as $potentialUser) {
                if ($user->latitude && $user->longitude && 
                    $potentialUser->latitude && $potentialUser->longitude) {
                    $distance = $this->calculateDistance(
                        $user->latitude, $user->longitude,
                        $potentialUser->latitude, $potentialUser->longitude
                    );
                    $potentialUser->distance = round($distance, 2);
                } else {
                    $potentialUser->distance = null;
                }
            }

            $users = $users->sortBy('distance');

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get potential matches: ' . $e->getMessage()
            ], 500);
        }
    }

    public function likeUser($likedUserId)
    {
        try {
            $user = Auth::user();
            
            if ($user->id == $likedUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot like yourself'
                ], 400);
            }

            $existingLike = Like::where('liker_id', $user->id)
                               ->where('liked_id', $likedUserId)
                               ->first();

            if ($existingLike) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already liked this user'
                ], 400);
            }

            Like::create([
                'liker_id' => $user->id,
                'liked_id' => $likedUserId
            ]);

            $mutualLike = Like::where('liker_id', $likedUserId)
                             ->where('liked_id', $user->id)
                             ->first();

            if ($mutualLike) {
                UserMatch::create([
                    'user1_id' => min($user->id, $likedUserId),
                    'user2_id' => max($user->id, $likedUserId),
                    'is_mutual' => true,
                    'matched_at' => now()
                ]);

                // Ensure conversation exists between the two users
                $u1 = min($user->id, $likedUserId);
                $u2 = max($user->id, $likedUserId);
                $existingConversation = Conversation::where(function($q) use ($u1, $u2) {
                    $q->where('user1_id', $u1)->where('user2_id', $u2);
                })->first();
                if (!$existingConversation) {
                    Conversation::create([
                        'user1_id' => $u1,
                        'user2_id' => $u2,
                        'last_message_at' => now(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'It\'s a match!',
                    'is_match' => true
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User liked successfully',
                'is_match' => false
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to like user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function passUser($passedUserId)
    {
        try {
            $user = Auth::user();
            
            if ($user->id == $passedUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid operation'
                ], 400);
            }

            Like::where('liker_id', $user->id)
                ->where('liked_id', $passedUserId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'User passed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pass user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getConversations()
    {
        try {
            $user = Auth::user();
            
            $conversations = Conversation::where('user1_id', $user->id)
                                       ->orWhere('user2_id', $user->id)
                                       ->with(['user1', 'user2', 'lastMessage'])
                                       ->get();

            $formattedConversations = [];
            foreach ($conversations as $conversation) {
                $otherUser = $conversation->user1_id == $user->id ? $conversation->user2 : $conversation->user1;
                
                $formattedConversations[] = [
                    'id' => $conversation->id,
                    'user_id' => $otherUser->id,
                    'user_name' => $otherUser->name,
                    'user_avatar' => $otherUser->photos->first()?->photo_url,
                    'last_message' => $conversation->lastMessage?->content,
                    'last_message_time' => $conversation->lastMessage?->created_at,
                    'unread_count' => Message::where('conversation_id', $conversation->id)
                                            ->where('sender_id', '!=', $user->id)
                                            ->where('is_read', false)
                                            ->count(),
                    'is_online' => $otherUser->is_online,
                    'last_activity' => $otherUser->last_active
                ];
            }

            return response()->json([
                'success' => true,
                'conversations' => $formattedConversations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createConversation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $otherUserId = $request->user_id;

            // Check if user is trying to create conversation with themselves
            if ($user->id == $otherUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create conversation with yourself'
                ], 400);
            }

            // Check if conversation already exists
            $existingConversation = Conversation::where(function($query) use ($user, $otherUserId) {
                $query->where('user1_id', $user->id)->where('user2_id', $otherUserId);
            })->orWhere(function($query) use ($user, $otherUserId) {
                $query->where('user1_id', $otherUserId)->where('user2_id', $user->id);
            })->first();

            if ($existingConversation) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conversation already exists',
                    'conversation' => [
                        'id' => $existingConversation->id,
                        'user1_id' => $existingConversation->user1_id,
                        'user2_id' => $existingConversation->user2_id,
                        'created_at' => $existingConversation->created_at
                    ]
                ]);
            }

            // Create new conversation
            $conversation = Conversation::create([
                'user1_id' => $user->id,
                'user2_id' => $otherUserId,
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'conversation' => [
                    'id' => $conversation->id,
                    'user1_id' => $conversation->user1_id,
                    'user2_id' => $conversation->user2_id,
                    'created_at' => $conversation->created_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMessages($conversationId)
    {
        try {
            $user = Auth::user();
            
            $conversation = Conversation::where('id', $conversationId)
                                      ->where(function($query) use ($user) {
                                          $query->where('user1_id', $user->id)
                                                ->orWhere('user2_id', $user->id);
                                      })
                                      ->firstOrFail();

            $messages = Message::where('conversation_id', $conversationId)
                              ->with('sender')
                              ->orderBy('created_at', 'asc')
                              ->get()
                              ->map(function($m) {
                                  return [
                                      'id' => $m->id,
                                      'conversation_id' => $m->conversation_id,
                                      'sender_id' => $m->sender_id,
                                      'content' => $m->content,
                                      'is_read' => (bool) $m->is_read,
                                      'timestamp' => $m->created_at,
                                  ];
                              });

            Message::where('conversation_id', $conversationId)
                   ->where('sender_id', '!=', $user->id)
                   ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request, $conversationId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            $conversation = Conversation::where('id', $conversationId)
                                      ->where(function($query) use ($user) {
                                          $query->where('user1_id', $user->id)
                                                ->orWhere('user2_id', $user->id);
                                      })
                                      ->firstOrFail();

            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $user->id,
                'content' => $request->content
            ]);

            $conversation->update(['last_message_at' => now()]);
            $message->load('sender');

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'sender_id' => $message->sender_id,
                    'content' => $message->content,
                    'is_read' => (bool) $message->is_read,
                    'timestamp' => $message->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateLocation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $user->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'last_active' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadPhoto(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $file = $request->file('photo');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('user_photos', $filename, 'public');

            $photo = UserPhoto::create([
                'user_id' => $user->id,
                'photo_url' => Storage::url($path),
            ]);

            // Reload user with photos
            $user->load(['photos']);

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'photo' => $photo,
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
