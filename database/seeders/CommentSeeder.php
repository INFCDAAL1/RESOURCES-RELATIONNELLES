<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\Resource;
use App\Models\User;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifiez que vous avez des ressources et des utilisateurs
        $resourceCount = Resource::count();
        $userCount = User::count();
        
        // Si aucune ressource ou utilisateur n'existe, ne rien faire
        if ($resourceCount == 0 || $userCount == 0) {
            return;
        }
        
        // Créer des commentaires manuellement pour avoir des exemples précis
        $resourceIds = Resource::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();
        
        $comments = [
            [
                'content' => 'Super ressource, merci pour le partage !',
                'status' => 'published',
                'resource_id' => $resourceIds[array_rand($resourceIds)],
                'user_id' => $userIds[array_rand($userIds)],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Cette documentation m\'a beaucoup aidé pour mon projet.',
                'status' => 'published',
                'resource_id' => $resourceIds[array_rand($resourceIds)],
                'user_id' => $userIds[array_rand($userIds)],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        // Insérer les commentaires manuels
        Comment::insert($comments);
        
        // Utiliser la factory pour créer plus de commentaires aléatoires
        Comment::factory(20)->create();
        
        // Créer des commentaires avec des statuts spécifiques
        Comment::factory(5)->published()->create();
        Comment::factory(3)->hidden()->create();
        Comment::factory(2)->flagged()->create();
    }
}