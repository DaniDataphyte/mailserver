<?php

namespace App\Console\Commands\Newsletter;

use Illuminate\Console\Command;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;

class ScaffoldCollections extends Command
{
    protected $signature   = 'newsletter:scaffold
                                {--fresh : Drop and recreate existing newsletter collections}';

    protected $description = 'Create newsletter collections, blueprints, taxonomy, GlobalSet and terms in the database';

    /* ------------------------------------------------------------------ */

    public function handle(): void
    {
        $this->info('Scaffolding newsletter collections...');

        if ($this->option('fresh')) {
            $this->dropExisting();
        }

        $this->scaffoldAssetContainer();
        $this->scaffoldTaxonomy();
        $this->scaffoldNewsletterSettings();
        $this->scaffoldCollection('insight_newsletters',    'Insight Newsletters',    '/newsletters/insight/{slug}');
        $this->scaffoldCollection('foundation_newsletters', 'Foundation Newsletters', '/newsletters/foundation/{slug}');

        $this->newLine();
        $this->info('✓ All newsletter structures saved to the database.');
        $this->table(
            ['Type', 'Handle / Path', 'Title'],
            array_merge(
                [
                    ['AssetContainer', 'assets',                    'Assets'],
                    ['GlobalSet',      'newsletter_settings',        'Newsletter Settings'],
                    ['Taxonomy',       'newsletter_audiences',       'Newsletter Audiences'],
                    ['Collection',     'insight_newsletters',        'Insight Newsletters'],
                ],
                array_map(fn ($b) => ['Blueprint', "insight_newsletters.{$b['handle']}", $b['title']],
                    $this->blueprintDefinitions('insight_newsletters')),
                [
                    ['Collection', 'foundation_newsletters', 'Foundation Newsletters'],
                ],
                array_map(fn ($b) => ['Blueprint', "foundation_newsletters.{$b['handle']}", $b['title']],
                    $this->blueprintDefinitions('foundation_newsletters')),
            )
        );
    }

    /* ------------------------------------------------------------------ */
    /* Drop existing                                                        */
    /* ------------------------------------------------------------------ */

    private function dropExisting(): void
    {
        foreach (['insight_newsletters', 'foundation_newsletters'] as $handle) {
            if ($col = Collection::findByHandle($handle)) {
                // Delete all blueprints in the namespace first
                $namespace = "collections.{$handle}";
                foreach ($this->blueprintDefinitions($handle) as $def) {
                    if ($bp = Blueprint::find("{$namespace}.{$def['handle']}")) {
                        $bp->delete();
                    }
                }
                $col->delete();
                $this->line("Dropped collection: {$handle}");
            }
        }

        if ($tax = Taxonomy::findByHandle('newsletter_audiences')) {
            $tax->delete();
            $this->line('Dropped taxonomy: newsletter_audiences');
        }

        if ($gs = GlobalSet::findByHandle('newsletter_settings')) {
            $gs->delete();
            $this->line('Dropped GlobalSet: newsletter_settings');
        }

        // Drop global blueprint
        if ($bp = Blueprint::find('globals.newsletter_settings')) {
            $bp->delete();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Asset container                                                      */
    /* ------------------------------------------------------------------ */

    private function scaffoldAssetContainer(): void
    {
        if (AssetContainer::find('assets')) {
            $this->line('Asset container already exists: assets — skipping');
            return;
        }

        AssetContainer::make('assets')
            ->title('Assets')
            ->disk('public')
            ->save();

        $this->line('Created asset container: assets (disk: public)');
    }

    /* ------------------------------------------------------------------ */
    /* Taxonomy + terms                                                     */
    /* ------------------------------------------------------------------ */

    private function scaffoldTaxonomy(): void
    {
        if (Taxonomy::findByHandle('newsletter_audiences')) {
            $this->line('Taxonomy already exists: newsletter_audiences — skipping');
        } else {
            Taxonomy::make('newsletter_audiences')->title('Newsletter Audiences')->save();
            $this->line('Created taxonomy: newsletter_audiences');
        }

        $terms = [
            ['slug' => 'topics',         'title' => 'Topics'],
            ['slug' => 'marina-maitama', 'title' => 'Marina & Maitama'],
            ['slug' => 'senorrita',      'title' => 'SenorRita'],
            ['slug' => 'weekly',         'title' => 'Weekly'],
            ['slug' => 'activities',     'title' => 'Activities'],
        ];

        foreach ($terms as $t) {
            if (Term::find("newsletter_audiences::{$t['slug']}")) {
                $this->line("Term exists: {$t['slug']} — skipping");
                continue;
            }
            Term::make()
                ->taxonomy('newsletter_audiences')
                ->slug($t['slug'])
                ->data(['title' => $t['title']])
                ->save();
            $this->line("Created term: {$t['slug']}");
        }
    }

    /* ------------------------------------------------------------------ */
    /* GlobalSet: newsletter_settings                                       */
    /* ------------------------------------------------------------------ */

    private function scaffoldNewsletterSettings(): void
    {
        // Blueprint first (CP needs it to render the fields)
        if (! Blueprint::find('globals.newsletter_settings')) {
            Blueprint::make('newsletter_settings')
                ->setNamespace('globals')
                ->setContents([
                    'title'    => 'Newsletter Settings',
                    'sections' => [
                        'site' => [
                            'display' => 'Site',
                            'fields'  => [
                                [
                                    'handle' => 'site_logo',
                                    'field'  => [
                                        'type'          => 'assets',
                                        'display'       => 'Site Logo',
                                        'instructions'  => 'Shown in the platform landing page.',
                                        'container'     => 'assets',
                                        'max_files'     => 1,
                                        'allow_uploads' => true,
                                        'restrict'      => false,
                                        'width'         => 100,
                                    ],
                                ],
                            ],
                        ],
                        'insight' => [
                            'display' => 'Insight Newsletter',
                            'fields'  => [
                                [
                                    'handle' => 'insight_logo',
                                    'field'  => [
                                        'type'          => 'assets',
                                        'display'       => 'Insight Collection Logo',
                                        'instructions'  => 'Appears in the header of every Insight newsletter email.',
                                        'container'     => 'assets',
                                        'max_files'     => 1,
                                        'allow_uploads' => true,
                                        'restrict'      => false,
                                        'width'         => 50,
                                    ],
                                ],
                                [
                                    'handle' => 'insight_brand_color',
                                    'field'  => [
                                        'type'         => 'color',
                                        'display'      => 'Insight Brand Color',
                                        'instructions' => 'Header background color for Insight emails.',
                                        'default'      => '#0d1b2a',
                                        'width'        => 50,
                                    ],
                                ],
                            ],
                        ],
                        'foundation' => [
                            'display' => 'Foundation Newsletter',
                            'fields'  => [
                                [
                                    'handle' => 'foundation_logo',
                                    'field'  => [
                                        'type'          => 'assets',
                                        'display'       => 'Foundation Collection Logo',
                                        'instructions'  => 'Appears in the header of every Foundation newsletter email.',
                                        'container'     => 'assets',
                                        'max_files'     => 1,
                                        'allow_uploads' => true,
                                        'restrict'      => false,
                                        'width'         => 50,
                                    ],
                                ],
                                [
                                    'handle' => 'foundation_brand_color',
                                    'field'  => [
                                        'type'         => 'color',
                                        'display'      => 'Foundation Brand Color',
                                        'instructions' => 'Header background color for Foundation emails.',
                                        'default'      => '#1b4332',
                                        'width'        => 50,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                ->save();

            $this->line('Created blueprint: globals.newsletter_settings');
        } else {
            $this->line('Blueprint already exists: globals.newsletter_settings — skipping');
        }

        // GlobalSet
        if (GlobalSet::findByHandle('newsletter_settings')) {
            $this->line('GlobalSet already exists: newsletter_settings — skipping');
            return;
        }

        $set = GlobalSet::make('newsletter_settings')->title('Newsletter Settings');
        $set->save();

        // Seed default brand colors so emails render correctly before logos are uploaded
        $site = Site::default()->handle();
        $variables = $set->makeLocalization($site);
        $variables->data([
            'insight_brand_color'    => '#0d1b2a',
            'foundation_brand_color' => '#1b4332',
        ]);
        $variables->save();

        $this->line('Created GlobalSet: newsletter_settings (with default brand colors)');
    }

    /* ------------------------------------------------------------------ */
    /* Collection                                                           */
    /* ------------------------------------------------------------------ */

    private function scaffoldCollection(string $handle, string $title, string $route): void
    {
        if (Collection::findByHandle($handle)) {
            $this->line("Collection already exists: {$handle} — skipping");
        } else {
            Collection::make($handle)
                ->title($title)
                ->routes($route)
                ->dated(true)
                ->sortDirection('desc')
                ->save();

            $this->line("Created collection: {$handle}");
        }

        $this->scaffoldBlueprints($handle);
    }

    /* ------------------------------------------------------------------ */
    /* Blueprints — one per template type                                  */
    /* ------------------------------------------------------------------ */

    private function scaffoldBlueprints(string $collectionHandle): void
    {
        $namespace = "collections.{$collectionHandle}";

        foreach ($this->blueprintDefinitions($collectionHandle) as $def) {
            $key = "{$namespace}.{$def['handle']}";

            // Remove stale copy so we always get a clean definition
            if ($existing = Blueprint::find($key)) {
                $existing->delete();
            }

            Blueprint::make($def['handle'])
                ->setNamespace($namespace)
                ->setContents($this->blueprintContents($def))
                ->save();

            $this->line("Created blueprint: {$key}");
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * All blueprints for a given collection.
     * Each entry carries: handle, title, template (Blade view key).
     */
    private function blueprintDefinitions(string $collectionHandle): array
    {
        return match ($collectionHandle) {
            'insight_newsletters' => [
                ['handle' => 'feature_lead',  'title' => 'Feature Lead',       'template' => 'emails.insight.feature-lead'],
                ['handle' => 'single_story',  'title' => 'Single Story',        'template' => 'emails.insight.single-story'],
                ['handle' => 'digest',        'title' => 'Digest',              'template' => 'emails.insight.digest'],
                ['handle' => 'roundup',       'title' => 'Newsroom Roundup',    'template' => 'emails.insight.roundup'],
                ['handle' => 'breaking_news', 'title' => 'Breaking News',       'template' => 'emails.insight.breaking'],
                ['handle' => 'data_story',    'title' => 'Data Story',          'template' => 'emails.insight.data-story'],
            ],
            'foundation_newsletters' => [
                ['handle' => 'weekly',          'title' => 'Weekly Update',   'template' => 'emails.foundation.weekly'],
                ['handle' => 'activities',      'title' => 'Activities',      'template' => 'emails.foundation.activities'],
                ['handle' => 'project_update',  'title' => 'Project Update',  'template' => 'emails.foundation.project-update'],
            ],
            default => [],
        };
    }

    /* ------------------------------------------------------------------ */

    private function blueprintContents(array $def): array
    {
        return [
            'title'    => $def['title'],
            'sections' => [

                'main' => [
                    'display' => 'Content',
                    'fields'  => [

                        [
                            'handle' => 'subject',
                            'field'  => [
                                'type'         => 'text',
                                'display'      => 'Subject Line',
                                'instructions' => 'The email subject line shown in the inbox.',
                                'validate'     => 'required',
                                'listable'     => true,
                                'width'        => 100,
                            ],
                        ],

                        [
                            'handle' => 'preheader',
                            'field'  => [
                                'type'         => 'text',
                                'display'      => 'Preheader',
                                'instructions' => 'Short preview text after the subject in most inboxes (50–90 chars).',
                                'width'        => 100,
                            ],
                        ],

                        [
                            'handle' => 'hero_image',
                            'field'  => [
                                'type'          => 'assets',
                                'display'       => 'Hero Image',
                                'container'     => 'assets',
                                'max_files'     => 1,
                                'restrict'      => false,
                                'allow_uploads' => true,
                                'width'         => 100,
                            ],
                        ],

                        [
                            'handle' => 'content',
                            'field'  => [
                                'type'     => 'bard',
                                'display'  => 'Content',
                                'validate' => 'required',
                                'buttons'  => [
                                    'h2', 'h3', 'bold', 'italic', 'underline',
                                    'unorderedlist', 'orderedlist', 'quote',
                                    'anchor', 'image',
                                ],
                                'save_html' => true,
                                'width'     => 100,
                            ],
                        ],

                        // Hidden — auto-filled on save via blueprint default
                        [
                            'handle' => 'template',
                            'field'  => [
                                'type'        => 'text',
                                'display'     => 'Email Template',
                                'visibility'  => 'hidden',
                                'default'     => $def['template'],
                                'listable'    => false,
                                'width'       => 100,
                            ],
                        ],

                    ],
                ],

                'sidebar' => [
                    'display' => 'Settings',
                    'fields'  => [

                        [
                            'handle' => 'audiences',
                            'field'  => [
                                'type'         => 'terms',
                                'display'      => 'Send To (Audiences)',
                                'instructions' => 'Select the sub-groups that will receive this newsletter.',
                                'taxonomies'   => ['newsletter_audiences'],
                                'mode'         => 'select',
                                'width'        => 100,
                            ],
                        ],

                        [
                            'handle' => 'send_to_all',
                            'field'  => [
                                'type'         => 'toggle',
                                'display'      => 'Send to All',
                                'instructions' => 'Override audience selection and send to every subscriber in this group.',
                                'default'      => false,
                                'width'        => 100,
                            ],
                        ],

                        [
                            'handle' => 'author',
                            'field'  => [
                                'type'    => 'text',
                                'display' => 'Author / Byline',
                                'width'   => 100,
                            ],
                        ],

                        [
                            'handle' => 'reply_to',
                            'field'  => [
                                'type'       => 'text',
                                'display'    => 'Reply-To (override)',
                                'instructions' => 'Leave blank to use the collection default.',
                                'input_type' => 'email',
                                'width'      => 100,
                            ],
                        ],

                    ],
                ],

            ],
        ];
    }
}
