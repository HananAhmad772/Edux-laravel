<?php
require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;

// Create a service container
$container = new Container();

// Create a dispatcher
$events = new Dispatcher($container);

// Create a capsule manager instance
$capsule = new Capsule($container);

// Add a connection
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'edux',
    'username'  => 'root',
    'password' => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Test badge awarding
echo "Testing badge awarding...\n";

// Get a user
$user = \App\Models\User::first();
if ($user) {
    echo "User found with ID: " . $user->id . "\n";
    
    // Check if user already has the First Login badge
    $firstLoginBadge = \App\Models\Badge::where('name', 'First Login')->first();
    if ($firstLoginBadge) {
        echo "First Login badge exists with ID: " . $firstLoginBadge->id . "\n";
        
        $existingUserBadge = \App\Models\UserBadge::where('user_id', $user->id)
            ->where('badge_id', $firstLoginBadge->id)
            ->first();
            
        if ($existingUserBadge) {
            echo "User already has the First Login badge\n";
        } else {
            echo "User does not have the First Login badge, awarding it now...\n";
            
            // Award the badge
            $userBadge = \App\Models\UserBadge::create([
                'user_id' => $user->id,
                'badge_id' => $firstLoginBadge->id,
                'earned_at' => now()
            ]);
            
            echo "Badge awarded with ID: " . $userBadge->id . "\n";
        }
    } else {
        echo "First Login badge does not exist\n";
    }
} else {
    echo "No user found\n";
}

// Count badges and user badges
echo "Total badges: " . \App\Models\Badge::count() . "\n";
echo "Total user badges: " . \App\Models\UserBadge::count() . "\n";

echo "Test completed.\n";
?>