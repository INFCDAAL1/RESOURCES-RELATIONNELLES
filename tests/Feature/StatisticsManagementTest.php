<?php

// namespace Tests\Feature;

// use App\Models\User;
// use App\Models\Resource;
// use App\Models\Type;
// use App\Models\Category;
// use App\Models\Visibility;
// use App\Models\Origin;
// use App\Models\ResourceInteraction;
// use App\Models\Comment;
// use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Str;
// use Tests\TestCase;

// class StatisticsManagementTest extends TestCase
// {
//     use RefreshDatabase;
    
//     protected $admin;
//     protected $citizen1;
//     protected $citizen2;
//     protected $resources = [];
//     protected $types = [];
//     protected $categories = [];
    
//     protected function setUp(): void
//     {
//         parent::setUp();
        
//         // Créer un administrateur et des utilisateurs standards
//         $this->admin = User::factory()->create([
//             'name' => 'Administrateur',
//             'email' => 'admin_'.Str::random(5).'@example.com',
//             'role' => 'admin',
//             'is_active' => true,
//         ]);
        
//         $this->citizen1 = User::factory()->create([
//             'name' => 'Citoyen 1',
//             'email' => 'citoyen1_'.Str::random(5).'@example.com',
//             'role' => 'user',
//             'is_active' => true,
//         ]);
        
//         $this->citizen2 = User::factory()->create([
//             'name' => 'Citoyen 2',
//             'email' => 'citoyen2_'.Str::random(5).'@example.com',
//             'role' => 'user',
//             'is_active' => true,
//         ]);
        
//         // Créer des types de ressources
//         $this->types = [
//             'document' => Type::firstOrCreate(['name' => 'Document']),
//             'activite' => Type::firstOrCreate(['name' => 'Activité']),
//             'jeu' => Type::firstOrCreate(['name' => 'Jeu']),
//         ];
        
//         // Créer des catégories
//         $this->categories = [
//             'education' => Category::firstOrCreate(['name' => 'Education']),
//             'culture' => Category::firstOrCreate(['name' => 'Culture']),
//             'sante' => Category::firstOrCreate(['name' => 'Santé']),
//         ];
        
//         // Créer des visibilités et origine
//         $visibility = Visibility::firstOrCreate(['name' => 'Public']);
//         $origin = Origin::firstOrCreate(['libelle' => 'Personnel']);
        
//         // Créer plusieurs ressources avec différentes caractéristiques
//         // 1. Ressource document d'éducation par citoyen 1
//         $this->resources[] = Resource::create([
//             'name' => 'Document éducatif',
//             'description' => 'Un document sur l\'éducation',
//             'published' => true,
//             'validated' => true,
//             'type_id' => $this->types['document']->id,
//             'category_id' => $this->categories['education']->id,
//             'visibility_id' => $visibility->id,
//             'origin_id' => $origin->id,
//             'user_id' => $this->citizen1->id,
//         ]);
        
//         // 2. Ressource activité de culture par citoyen 2
//         $this->resources[] = Resource::create([
//             'name' => 'Activité culturelle',
//             'description' => 'Une activité culturelle intéressante',
//             'published' => true,
//             'validated' => true,
//             'type_id' => $this->types['activite']->id,
//             'category_id' => $this->categories['culture']->id,
//             'visibility_id' => $visibility->id,
//             'origin_id' => $origin->id,
//             'user_id' => $this->citizen2->id,
//         ]);
        
//         // 3. Ressource jeu de santé par citoyen 1
//         $this->resources[] = Resource::create([
//             'name' => 'Jeu sur la santé',
//             'description' => 'Un jeu pour apprendre sur la santé',
//             'published' => true,
//             'validated' => true,
//             'type_id' => $this->types['jeu']->id,
//             'category_id' => $this->categories['sante']->id,
//             'visibility_id' => $visibility->id,
//             'origin_id' => $origin->id,
//             'user_id' => $this->citizen1->id,
//         ]);
        
//         // Créer des interactions avec les ressources pour générer des statistiques
//         $this->generateInteractions();
//     }
    
//     /**
//      * Génère des interactions variées avec les ressources
//      */
//     protected function generateInteractions()
//     {
//         // 1. Favoris
//         ResourceInteraction::create([
//             'user_id' => $this->citizen1->id,
//             'resource_id' => $this->resources[1]->id, // Citoyen 1 met en favori l'activité culturelle
//             'type' => 'favorite',
//         ]);
        
//         ResourceInteraction::create([
//             'user_id' => $this->citizen2->id,
//             'resource_id' => $this->resources[0]->id, // Citoyen 2 met en favori le document éducatif
//             'type' => 'favorite',
//         ]);
        
//         ResourceInteraction::create([
//             'user_id' => $this->citizen2->id,
//             'resource_id' => $this->resources[2]->id, // Citoyen 2 met en favori le jeu sur la santé
//             'type' => 'favorite',
//         ]);
        
//         // 2. Ressources exploitées
//         ResourceInteraction::create([
//             'user_id' => $this->citizen1->id,
//             'resource_id' => $this->resources[0]->id, // Citoyen 1 exploite son propre document
//             'type' => 'exploited',
//         ]);
        
//         ResourceInteraction::create([
//             'user_id' => $this->citizen2->id,
//             'resource_id' => $this->resources[2]->id, // Citoyen 2 exploite le jeu
//             'type' => 'exploited',
//         ]);
        
//         // 3. Ressources sauvegardées
//         ResourceInteraction::create([
//             'user_id' => $this->citizen1->id,
//             'resource_id' => $this->resources[2]->id, // Citoyen 1 sauvegarde le jeu
//             'type' => 'saved',
//         ]);
        
//         ResourceInteraction::create([
//             'user_id' => $this->citizen2->id,
//             'resource_id' => $this->resources[1]->id, // Citoyen 2 sauvegarde l'activité
//             'type' => 'saved',
//         ]);
        
//         // 4. Commentaires
//         Comment::create([
//             'content' => 'Excellent document !',
//             'status' => 'published',
//             'resource_id' => $this->resources[0]->id,
//             'user_id' => $this->citizen2->id,
//         ]);
        
//         Comment::create([
//             'content' => 'Très intéressant !',
//             'status' => 'published',
//             'resource_id' => $this->resources[1]->id,
//             'user_id' => $this->citizen1->id,
//         ]);
        
//         Comment::create([
//             'content' => 'Super jeu éducatif',
//             'status' => 'published',
//             'resource_id' => $this->resources[2]->id,
//             'user_id' => $this->citizen2->id,
//         ]);
//     }
    
//     /**
//      * Test que seul un administrateur peut accéder au tableau de bord statistiques
//      */
//     public function test_only_admin_can_access_statistics_dashboard()
//     {
//         // 1. Essai d'accès par un utilisateur standard
//         $this->actingAs($this->citizen1);
        
//         $this->assertFalse($this->citizen1->isAdmin());
        
//         // 2. Accès par un administrateur
//         $this->actingAs($this->admin);
//         $this->assertTrue($this->admin->isAdmin());
//     }
    
//     /**
//      * Test d'affichage du tableau de bord statistiques avec les métriques globales
//      */
//     public function test_admin_can_view_statistics_dashboard()
//     {
//         // Connecter l'administrateur
//         $this->actingAs($this->admin);
        
//         // Simuler la récupération des statistiques globales
        
//         // 1. Nombre total de ressources par type
//         $resourcesByType = [];
//         foreach ($this->types as $key => $type) {
//             $resourcesByType[$key] = Resource::where('type_id', $type->id)->count();
//         }
        
//         // 2. Nombre total de ressources par catégorie
//         $resourcesByCategory = [];
//         foreach ($this->categories as $key => $category) {
//             $resourcesByCategory[$key] = Resource::where('category_id', $category->id)->count();
//         }
        
//         // 3. Nombre total d'interactions par type
//         $interactionsByType = ResourceInteraction::selectRaw('type, count(*) as count')
//             ->groupBy('type')
//             ->pluck('count', 'type')
//             ->toArray();
        
//         // 4. Nombre total de commentaires
//         $commentCount = Comment::count();
        
//         // 5. Nombre de ressources par utilisateur créateur
//         $resourcesByUser = Resource::selectRaw('user_id, count(*) as count')
//             ->groupBy('user_id')
//             ->pluck('count', 'user_id')
//             ->toArray();
        
//         // Vérifier les statistiques
//         $this->assertEquals(1, $resourcesByType['document']);
//         $this->assertEquals(1, $resourcesByType['activite']);
//         $this->assertEquals(1, $resourcesByType['jeu']);
        
//         $this->assertEquals(1, $resourcesByCategory['education']);
//         $this->assertEquals(1, $resourcesByCategory['culture']);
//         $this->assertEquals(1, $resourcesByCategory['sante']);
        
//         $this->assertEquals(3, $interactionsByType['favorite'] ?? 0);
//         $this->assertEquals(2, $interactionsByType['exploited'] ?? 0);
//         $this->assertEquals(2, $interactionsByType['saved'] ?? 0);
        
//         $this->assertEquals(3, $commentCount);
        
//         $this->assertEquals(2, $resourcesByUser[$this->citizen1->id] ?? 0);
//         $this->assertEquals(1, $resourcesByUser[$this->citizen2->id] ?? 0);
//     }
    
//     /**
//      * Test de filtrage des statistiques
//      */
//     public function test_admin_can_filter_statistics()
//     {
//         // Connecter l'administrateur
//         $this->actingAs($this->admin);
        
//         // 1. Filtrer les ressources par type
//         $documentResources = Resource::where('type_id', $this->types['document']->id)->get();
//         $this->assertCount(1, $documentResources);
        
//         // 2. Filtrer les ressources par catégorie
//         $educationResources = Resource::where('category_id', $this->categories['education']->id)->get();
//         $this->assertCount(1, $educationResources);
        
//         // 3. Filtrer les interactions par type
//         $favoriteInteractions = ResourceInteraction::where('type', 'favorite')->get();
//         $this->assertCount(3, $favoriteInteractions);
        
//         // 4. Filtrer les ressources par période (créées dans les dernières 24 heures)
//         $recentResources = Resource::where('created_at', '>=', now()->subDay())->get();
//         $this->assertCount(3, $recentResources); 
        
//         // 5. Filtrer les ressources par créateur
//         $citizen1Resources = Resource::where('user_id', $this->citizen1->id)->get();
//         $this->assertCount(2, $citizen1Resources);
        
//         // 6. Filtrer les interactions par utilisateur
//         $citizen2Interactions = ResourceInteraction::where('user_id', $this->citizen2->id)->get();
//         $this->assertCount(4, $citizen2Interactions);
        
//         // 7. Filtrer les commentaires par ressource
//         $document1Comments = Comment::where('resource_id', $this->resources[0]->id)->get();
//         $this->assertCount(1, $document1Comments);
//     }
    
//     /**
//      * Test de filtrage avancé et combiné des statistiques
//      */
//     public function test_admin_can_use_combined_filters()
//     {
//         // Connecter l'administrateur
//         $this->actingAs($this->admin);
        
//         // 1. Ressources de type 'document' dans la catégorie 'education'
//         $educationalDocuments = Resource::where('type_id', $this->types['document']->id)
//             ->where('category_id', $this->categories['education']->id)
//             ->get();
//         $this->assertCount(1, $educationalDocuments);
        
//         // 2. Ressources exploitées dans la catégorie 'sante'
//         $exploitedHealthResources = Resource::whereHas('interactions', function($query) {
//                 $query->where('type', 'exploited');
//             })
//             ->where('category_id', $this->categories['sante']->id)
//             ->get();
//         $this->assertCount(1, $exploitedHealthResources);
        
//         // 3. Ressources créées par citizen1 et mises en favori par citizen2
//         $createdByOneAndFavoritedByTwo = Resource::where('user_id', $this->citizen1->id)
//             ->whereHas('interactions', function($query) {
//                 $query->where('user_id', $this->citizen2->id)
//                     ->where('type', 'favorite');
//             })
//             ->get();
//         $this->assertCount(2, $createdByOneAndFavoritedByTwo);
        
//         // 4. Commentaires sur les ressources de type 'jeu'
//         $commentsOnGames = Comment::whereHas('resource', function($query) {
//                 $query->where('type_id', $this->types['jeu']->id);
//             })
//             ->get();
//         $this->assertCount(1, $commentsOnGames);
//     }
    
//     /**
//      * Test d'exportation des statistiques
//      */
//     public function test_admin_can_export_statistics()
//     {
//         // Connecter l'administrateur
//         $this->actingAs($this->admin);
        
//         // 1. Statistiques des ressources par type
//         $resourceStatsByType = [];
//         foreach ($this->types as $key => $type) {
//             $resourceStatsByType[] = [
//                 'type' => $type->name,
//                 'count' => Resource::where('type_id', $type->id)->count(),
//             ];
//         }
        
//         // 2. Statistiques des interactions par type
//         $interactionStats = [];
//         $interactionTypes = ['favorite', 'exploited', 'saved'];
//         foreach ($interactionTypes as $type) {
//             $interactionStats[] = [
//                 'type' => $type,
//                 'count' => ResourceInteraction::where('type', $type)->count(),
//             ];
//         }
        
//         // 3. Statistiques des commentaires par ressource
//         $commentStats = [];
//         foreach ($this->resources as $resource) {
//             $commentStats[] = [
//                 'resource_name' => $resource->name,
//                 'comment_count' => Comment::where('resource_id', $resource->id)->count(),
//             ];
//         }
        
//         // Vérifier que les statistiques sont correctes
//         $this->assertCount(3, $resourceStatsByType); // 3 types de ressources
//         $this->assertCount(3, $interactionStats);    // 3 types d'interactions
//         $this->assertCount(3, $commentStats);        // 3 ressources
        
//         // Vérifier le contenu des statistiques
//         $documentStat = collect($resourceStatsByType)->firstWhere('type', 'Document');
//         $this->assertEquals(1, $documentStat['count']);
        
//         $favoriteStat = collect($interactionStats)->firstWhere('type', 'favorite');
//         $this->assertEquals(3, $favoriteStat['count']);
        
//         $document1Comments = collect($commentStats)->firstWhere('resource_name', 'Document éducatif');
//         $this->assertEquals(1, $document1Comments['comment_count']);
//     }
    
//     /**
//      * Test de génération de rapports statistiques pour différentes périodes
//      */
//     public function test_admin_can_generate_time_period_reports()
//     {
//         // Connecter l'administrateur
//         $this->actingAs($this->admin);
        
//         // 1. Statistiques pour aujourd'hui
//         $resourcesCreatedToday = Resource::whereDate('created_at', now()->toDateString())->count();
//         $interactionsCreatedToday = ResourceInteraction::whereDate('created_at', now()->toDateString())->count();
//         $commentsCreatedToday = Comment::whereDate('created_at', now()->toDateString())->count();
        
//         // 2. Statistiques pour cette semaine
//         $resourcesCreatedThisWeek = Resource::whereBetween('created_at', [
//             now()->startOfWeek(),
//             now()->endOfWeek(),
//         ])->count();
        
//         $interactionsCreatedThisWeek = ResourceInteraction::whereBetween('created_at', [
//             now()->startOfWeek(),
//             now()->endOfWeek(),
//         ])->count();
        
//         // 3. Statistiques pour ce mois
//         $resourcesCreatedThisMonth = Resource::whereMonth('created_at', now()->month)
//             ->whereYear('created_at', now()->year)
//             ->count();
        
//         // Vérifier les statistiques par période
//         $this->assertEquals(3, $resourcesCreatedToday);     // Toutes les ressources ont été créées aujourd'hui
//         $this->assertEquals(7, $interactionsCreatedToday);  // Toutes les interactions ont été créées aujourd'hui
//         $this->assertEquals(3, $commentsCreatedToday);      // Tous les commentaires ont été créés aujourd'hui
        
//         $this->assertEquals(3, $resourcesCreatedThisWeek);  // Toutes les ressources ont été créées cette semaine
//         $this->assertEquals(7, $interactionsCreatedThisWeek); // Toutes les interactions ont été créées cette semaine
        
//         $this->assertEquals(3, $resourcesCreatedThisMonth); // Toutes les ressources ont été créées ce mois
//     }
    
//     /**
//      * Test des statistiques de progression utilisateur
//      */
//     public function test_admin_can_view_user_progression_statistics()
//     {
//         // Connecter l'administrateur
//         $this->actingAs($this->admin);
        
//         // 1. Nombre d'utilisateurs actifs (ayant au moins une interaction)
//         $activeUserCount = User::whereHas('resourceInteractions')->count();
        
//         // 2. Nombre moyen d'interactions par utilisateur
//         $totalInteractions = ResourceInteraction::count();
//         $userCount = User::count();
//         $averageInteractionsPerUser = $userCount > 0 ? $totalInteractions / $userCount : 0;
        
//         // 3. Utilisateurs les plus actifs (par nombre d'interactions)
//         $mostActiveUsers = User::withCount('resourceInteractions')
//             ->orderBy('resource_interactions_count', 'desc')
//             ->limit(2)
//             ->get();
        
//         // Vérifier les statistiques
//         $this->assertEquals(2, $activeUserCount); 
//         $this->assertEquals(7/3, $averageInteractionsPerUser); 
        
//         $this->assertEquals(2, $mostActiveUsers->count());
        
//         // Identifier l'utilisateur le plus actif
//         $mostActiveUserId = $mostActiveUsers->sortByDesc('resource_interactions_count')->first()->id;
        
//         // Vérifier si c'est bien citizen2 qui a 4 interactions
//         $citizen2Interactions = ResourceInteraction::where('user_id', $this->citizen2->id)->count();
//         $this->assertEquals(4, $citizen2Interactions);
//         $this->assertEquals($this->citizen2->id, $mostActiveUserId);
//     }
// }