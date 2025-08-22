<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Hobby;
use App\Models\Interest;
use App\Models\UserPhoto;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create hobbies
        $hobbies = ['Reading', 'Traveling', 'Cooking', 'Photography', 'Music', 'Sports', 'Gaming', 'Art', 'Dancing', 'Hiking'];
        foreach ($hobbies as $hobbyName) {
            Hobby::firstOrCreate(['name' => $hobbyName]);
        }

        // Create interests
        $interests = ['Technology', 'Science', 'History', 'Philosophy', 'Psychology', 'Business', 'Finance', 'Health', 'Fitness', 'Fashion'];
        foreach ($interests as $interestName) {
            Interest::firstOrCreate(['name' => $interestName]);
        }

        // Create sample users
        $users = [
            [
                'name' => 'Sarah Johnson',
                'phone' => '+1234567890',
                'age' => 25,
                'bio' => 'Adventure seeker and coffee enthusiast.',
                'gender' => 'Female',
                'looking_for' => 'Male',
                'relationship_goal' => 'Long-term relationship',
                'education' => 'Bachelor\'s Degree',
                'occupation' => 'Marketing Manager',
                'city' => 'New York',
                'country' => 'USA',
                'height' => '5\'6"',
                'religion' => 'Spiritual',
                'smoking' => 'No',
                'drinking' => 'Socially',
                'has_children' => false,
                'zodiac_sign' => 'Libra',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'hobbies' => ['Traveling', 'Photography', 'Cooking'],
                'interests' => ['Technology', 'Business', 'Fashion'],
                'photos' => ['https://images.unsplash.com/photo-1494790108755-2616b612b786?w=400']
            ],
            [
                'name' => 'Michael Chen',
                'phone' => '+1234567891',
                'age' => 28,
                'bio' => 'Tech enthusiast and fitness lover.',
                'gender' => 'Male',
                'looking_for' => 'Female',
                'relationship_goal' => 'Serious relationship',
                'education' => 'Master\'s Degree',
                'occupation' => 'Software Engineer',
                'city' => 'San Francisco',
                'country' => 'USA',
                'height' => '6\'0"',
                'religion' => 'Agnostic',
                'smoking' => 'No',
                'drinking' => 'Occasionally',
                'has_children' => false,
                'zodiac_sign' => 'Capricorn',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'hobbies' => ['Gaming', 'Sports', 'Hiking'],
                'interests' => ['Technology', 'Science', 'Fitness'],
                'photos' => ['https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400']
            ]
        ];

        foreach ($users as $userData) {
            $hobbies = $userData['hobbies'];
            $interests = $userData['interests'];
            $photos = $userData['photos'];
            
            unset($userData['hobbies'], $userData['interests'], $userData['photos']);
            
            $user = User::create($userData);
            
            // Attach hobbies
            foreach ($hobbies as $hobbyName) {
                $hobby = Hobby::where('name', $hobbyName)->first();
                if ($hobby) {
                    $user->hobbies()->attach($hobby->id);
                }
            }
            
            // Attach interests
            foreach ($interests as $interestName) {
                $interest = Interest::where('name', $interestName)->first();
                if ($interest) {
                    $user->interests()->attach($interest->id);
                }
            }
            
            // Create photos
            foreach ($photos as $index => $photoUrl) {
                UserPhoto::create([
                    'user_id' => $user->id,
                    'photo_url' => $photoUrl,
                    'is_primary' => $index === 0
                ]);
            }
        }
    }
}
