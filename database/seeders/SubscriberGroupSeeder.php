<?php

namespace Database\Seeders;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Seeder;

class SubscriberGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name'        => 'Insight Subscribers',
                'slug'        => 'insight-subscribers',
                'description' => 'Dataphyte Insight newsletter subscribers',
                'sub_groups'  => [
                    ['name' => 'Topics',           'slug' => 'topics'],
                    ['name' => 'Marina & Maitama', 'slug' => 'marina-maitama'],
                    ['name' => 'SenorRita',         'slug' => 'senorrita'],
                ],
            ],
            [
                'name'        => 'Foundation',
                'slug'        => 'foundation',
                'description' => 'Dataphyte Foundation newsletter subscribers',
                'sub_groups'  => [
                    ['name' => 'Weekly',     'slug' => 'weekly'],
                    ['name' => 'Activities', 'slug' => 'activities'],
                ],
            ],
        ];

        foreach ($groups as $groupData) {
            $subGroups = $groupData['sub_groups'];
            unset($groupData['sub_groups']);

            $group = SubscriberGroup::firstOrCreate(
                ['slug' => $groupData['slug']],
                $groupData
            );

            foreach ($subGroups as $subGroup) {
                SubscriberSubGroup::firstOrCreate(
                    [
                        'subscriber_group_id' => $group->id,
                        'slug'                => $subGroup['slug'],
                    ],
                    ['name' => $subGroup['name']]
                );
            }
        }

        $this->command->info('Subscriber groups and sub-groups seeded.');
    }
}
