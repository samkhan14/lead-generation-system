<?php

namespace Database\Seeders;

use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Domains\AI\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('email', 'superadmin@example.com')->value('id');
        $definitions = $this->loadDefinitions();
        $activeSlugs = collect($definitions)->pluck('slug')->all();

        foreach ($definitions as $definition) {
            KnowledgeBase::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'category' => KnowledgeBaseCategory::from($definition['category']),
                    'content' => $definition['content'],
                    'tags' => $definition['tags'] ?? [],
                    'status' => KnowledgeBaseStatus::Active,
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                ],
            );
        }

        KnowledgeBase::query()
            ->whereNotIn('slug', $activeSlugs)
            ->where('status', KnowledgeBaseStatus::Active)
            ->update(['status' => KnowledgeBaseStatus::Archived]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadDefinitions(): array
    {
        $path = __DIR__.'/data/knowledge_base/articles.php';

        return is_readable($path) ? require $path : [];
    }
}
