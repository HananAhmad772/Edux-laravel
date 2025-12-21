<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Auth;

class BadgeController extends Controller
{
    protected $badgeService;

    public function __construct()
    {
        $this->badgeService = new BadgeService();
    }

    /**
     * Get all badges with user's earned status
     */
    public function index()
    {
        $user = Auth::user();
        
        // Check and award badges based on user progress
        $this->badgeService->checkAndAwardBadges($user);
        
        // Get all badges
        $badges = Badge::all();
        
        // Get user's earned badges
        $userBadges = UserBadge::where('user_id', $user->id)
            ->pluck('badge_id', 'badge_id')
            ->toArray();
        
        // Add earned status to each badge
        $badgesWithStatus = $badges->map(function ($badge) use ($userBadges, $user) {
            $badge->earned = isset($userBadges[$badge->id]);
            
            // If earned, get the earned date
            if ($badge->earned) {
                $userBadge = UserBadge::where('user_id', $user->id)
                    ->where('badge_id', $badge->id)
                    ->first();
                $badge->earned_at = $userBadge->earned_at;
            }
            
            return $badge;
        });
        
        return response()->json([
            'status' => true,
            'message' => 'Badges retrieved successfully',
            'data' => $badgesWithStatus
        ]);
    }
    
    /**
     * Get user's earned badges
     */
    public function userBadges()
    {
        $user = Auth::user();
        
        $badges = UserBadge::where('user_id', $user->id)
            ->with('badge')
            ->get()
            ->map(function ($userBadge) {
                return [
                    'id' => $userBadge->badge->id,
                    'name' => $userBadge->badge->name,
                    'description' => $userBadge->badge->description,
                    'icon' => $userBadge->badge->icon,
                    'earned_at' => $userBadge->earned_at
                ];
            });
        
        return response()->json([
            'status' => true,
            'message' => 'User badges retrieved successfully',
            'data' => $badges
        ]);
    }
    
    /**
     * Award a badge to a user
     */
    public function awardBadge(Request $request)
    {
        $user = Auth::user();
        $badgeId = $request->input('badge_id');
        
        // Check if badge exists
        $badge = Badge::find($badgeId);
        if (!$badge) {
            return response()->json([
                'status' => false,
                'message' => 'Badge not found',
                'code' => 404
            ], 404);
        }
        
        // Check if user already has this badge
        $existingUserBadge = UserBadge::where('user_id', $user->id)
            ->where('badge_id', $badgeId)
            ->first();
            
        if ($existingUserBadge) {
            return response()->json([
                'status' => false,
                'message' => 'User already has this badge',
                'code' => 400
            ], 400);
        }
        
        // Award the badge
        $userBadge = UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $badgeId,
            'earned_at' => now()
        ]);
        
        return response()->json([
            'status' => true,
            'message' => 'Badge awarded successfully',
            'data' => $userBadge
        ]);
    }
}