<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BuildingType;
use App\Models\BoqItemCategory;
use App\Models\ConstructionStage;
use App\Models\Activity;
use App\Models\SubActivity;
use App\Models\BoqTemplateItem;
use App\Models\SubActivityMaterial;

class BoqTemplateSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Building Types
        $buildingTypes = [
            ['name' => 'Residential House', 'description' => 'Single family residential buildings'],
            ['name' => 'Commercial Building', 'description' => 'Office buildings, shops, and commercial spaces'],
            ['name' => 'Industrial Facility', 'description' => 'Factories, warehouses, and industrial buildings'],
            ['name' => 'Apartment Complex', 'description' => 'Multi-unit residential buildings'],
            ['name' => 'School Building', 'description' => 'Educational facilities and institutions'],
        ];

        foreach ($buildingTypes as $type) {
            BuildingType::firstOrCreate(['name' => $type['name']], $type);
        }

        // BOQ Item Categories (Hierarchical)
        $categories = [
            // Parent categories
            ['name' => 'Structural Materials', 'description' => 'Materials for structural construction', 'parent_id' => null, 'sort_order' => 1],
            ['name' => 'Finishing Materials', 'description' => 'Materials for finishing work', 'parent_id' => null, 'sort_order' => 2],
            ['name' => 'Electrical Materials', 'description' => 'Electrical components and materials', 'parent_id' => null, 'sort_order' => 3],
            ['name' => 'Plumbing Materials', 'description' => 'Plumbing and water systems', 'parent_id' => null, 'sort_order' => 4],
        ];

        $parentCategories = [];
        foreach ($categories as $category) {
            $cat = BoqItemCategory::firstOrCreate(['name' => $category['name']], $category);
            $parentCategories[$category['name']] = $cat->id;
        }

        // Sub-categories
        $subCategories = [
            // Structural subcategories
            ['name' => 'Concrete', 'parent_id' => $parentCategories['Structural Materials'], 'sort_order' => 1],
            ['name' => 'Steel & Rebar', 'parent_id' => $parentCategories['Structural Materials'], 'sort_order' => 2],
            ['name' => 'Masonry', 'parent_id' => $parentCategories['Structural Materials'], 'sort_order' => 3],
            
            // Finishing subcategories  
            ['name' => 'Paint & Coatings', 'parent_id' => $parentCategories['Finishing Materials'], 'sort_order' => 1],
            ['name' => 'Tiles & Flooring', 'parent_id' => $parentCategories['Finishing Materials'], 'sort_order' => 2],
            ['name' => 'Doors & Windows', 'parent_id' => $parentCategories['Finishing Materials'], 'sort_order' => 3],
        ];

        $subCategoryIds = [];
        foreach ($subCategories as $subCat) {
            $cat = BoqItemCategory::firstOrCreate(['name' => $subCat['name']], $subCat);
            $subCategoryIds[$subCat['name']] = $cat->id;
        }

        // Construction Stages
        $stages = [
            ['name' => 'Site Preparation', 'description' => 'Site clearing, surveying, and preparation', 'sort_order' => 1],
            ['name' => 'Foundation', 'description' => 'Foundation and basement construction', 'sort_order' => 2],
            ['name' => 'Structural Work', 'description' => 'Main structural elements construction', 'sort_order' => 3],
            ['name' => 'Roofing', 'description' => 'Roof structure and covering', 'sort_order' => 4],
            ['name' => 'MEP Systems', 'description' => 'Mechanical, Electrical, and Plumbing', 'sort_order' => 5],
            ['name' => 'Interior Finishing', 'description' => 'Interior walls, flooring, and finishes', 'sort_order' => 6],
            ['name' => 'Exterior Finishing', 'description' => 'Exterior walls, facades, and landscaping', 'sort_order' => 7],
        ];

        $stageIds = [];
        foreach ($stages as $stage) {
            $s = ConstructionStage::firstOrCreate(['name' => $stage['name']], $stage);
            $stageIds[$stage['name']] = $s->id;
        }

        // Activities
        $activities = [
            // Site Preparation Activities
            ['construction_stage_id' => $stageIds['Site Preparation'], 'name' => 'Site Clearing', 'description' => 'Clear vegetation and debris', 'sort_order' => 1],
            ['construction_stage_id' => $stageIds['Site Preparation'], 'name' => 'Excavation', 'description' => 'Excavate for foundations and utilities', 'sort_order' => 2],
            
            // Foundation Activities
            ['construction_stage_id' => $stageIds['Foundation'], 'name' => 'Footing Construction', 'description' => 'Concrete footings and foundation walls', 'sort_order' => 1],
            ['construction_stage_id' => $stageIds['Foundation'], 'name' => 'Foundation Waterproofing', 'description' => 'Waterproofing and damp proofing', 'sort_order' => 2],
            
            // Structural Work Activities
            ['construction_stage_id' => $stageIds['Structural Work'], 'name' => 'Concrete Works', 'description' => 'Reinforced concrete construction', 'sort_order' => 1],
            ['construction_stage_id' => $stageIds['Structural Work'], 'name' => 'Masonry Works', 'description' => 'Block and brick masonry', 'sort_order' => 2],
            
            // Interior Finishing Activities
            ['construction_stage_id' => $stageIds['Interior Finishing'], 'name' => 'Plastering', 'description' => 'Wall and ceiling plastering', 'sort_order' => 1],
            ['construction_stage_id' => $stageIds['Interior Finishing'], 'name' => 'Painting', 'description' => 'Interior painting and finishes', 'sort_order' => 2],
            ['construction_stage_id' => $stageIds['Interior Finishing'], 'name' => 'Flooring', 'description' => 'Floor finishes and installation', 'sort_order' => 3],
        ];

        $activityIds = [];
        foreach ($activities as $activity) {
            $a = Activity::firstOrCreate(['name' => $activity['name']], $activity);
            $activityIds[$activity['name']] = $a->id;
        }

        // Sub-Activities with Time Tracking
        $subActivities = [
            // Site Clearing Sub-Activities
            [
                'activity_id' => $activityIds['Site Clearing'],
                'name' => 'Tree Removal',
                'description' => 'Remove trees and large vegetation',
                'estimated_duration_hours' => 16,
                'duration_unit' => 'hours',
                'labor_requirement' => 3,
                'skill_level' => 'semi_skilled',
                'can_run_parallel' => false,
                'weather_dependent' => true,
                'sort_order' => 1
            ],
            [
                'activity_id' => $activityIds['Site Clearing'],
                'name' => 'Debris Removal',
                'description' => 'Clear debris and level site',
                'estimated_duration_hours' => 8,
                'duration_unit' => 'hours',
                'labor_requirement' => 4,
                'skill_level' => 'unskilled',
                'can_run_parallel' => true,
                'weather_dependent' => true,
                'sort_order' => 2
            ],
            
            // Excavation Sub-Activities
            [
                'activity_id' => $activityIds['Excavation'],
                'name' => 'Foundation Excavation',
                'description' => 'Excavate for building foundation',
                'estimated_duration_hours' => 24,
                'duration_unit' => 'hours',
                'labor_requirement' => 2,
                'skill_level' => 'skilled',
                'can_run_parallel' => false,
                'weather_dependent' => true,
                'sort_order' => 1
            ],
            
            // Concrete Works Sub-Activities
            [
                'activity_id' => $activityIds['Concrete Works'],
                'name' => 'Rebar Installation',
                'description' => 'Install reinforcement steel',
                'estimated_duration_hours' => 32,
                'duration_unit' => 'hours',
                'labor_requirement' => 4,
                'skill_level' => 'skilled',
                'can_run_parallel' => true,
                'weather_dependent' => false,
                'sort_order' => 1
            ],
            [
                'activity_id' => $activityIds['Concrete Works'],
                'name' => 'Concrete Pouring',
                'description' => 'Pour and finish concrete',
                'estimated_duration_hours' => 16,
                'duration_unit' => 'hours',
                'labor_requirement' => 6,
                'skill_level' => 'skilled',
                'can_run_parallel' => false,
                'weather_dependent' => true,
                'sort_order' => 2
            ],
            
            // Plastering Sub-Activities
            [
                'activity_id' => $activityIds['Plastering'],
                'name' => 'Wall Plastering',
                'description' => 'Apply plaster to interior walls',
                'estimated_duration_hours' => 40,
                'duration_unit' => 'hours',
                'labor_requirement' => 3,
                'skill_level' => 'skilled',
                'can_run_parallel' => true,
                'weather_dependent' => false,
                'sort_order' => 1
            ],
        ];

        $subActivityIds = [];
        foreach ($subActivities as $subActivity) {
            $sa = SubActivity::firstOrCreate(['name' => $subActivity['name']], $subActivity);
            $subActivityIds[$subActivity['name']] = $sa->id;
        }

        // BOQ Template Items
        $items = [
            // Concrete items
            ['name' => 'Ready Mix Concrete C25', 'unit' => 'm³', 'base_price' => 75000, 'category_id' => $subCategoryIds['Concrete'], 'description' => 'Grade C25 ready mix concrete'],
            ['name' => 'Portland Cement', 'unit' => 'bag', 'base_price' => 15000, 'category_id' => $subCategoryIds['Concrete'], 'description' => '50kg bag of Portland cement'],
            
            // Steel items  
            ['name' => 'Rebar Steel Y12', 'unit' => 'kg', 'base_price' => 2500, 'category_id' => $subCategoryIds['Steel & Rebar'], 'description' => '12mm diameter reinforcement steel'],
            ['name' => 'Rebar Steel Y16', 'unit' => 'kg', 'base_price' => 2600, 'category_id' => $subCategoryIds['Steel & Rebar'], 'description' => '16mm diameter reinforcement steel'],
            
            // Masonry items
            ['name' => 'Concrete Blocks 6"', 'unit' => 'piece', 'base_price' => 2000, 'category_id' => $subCategoryIds['Masonry'], 'description' => '6 inch concrete masonry blocks'],
            ['name' => 'Sand', 'unit' => 'm³', 'base_price' => 35000, 'category_id' => $subCategoryIds['Masonry'], 'description' => 'Fine sand for construction'],
            
            // Paint items
            ['name' => 'Interior Paint', 'unit' => 'liter', 'base_price' => 8000, 'category_id' => $subCategoryIds['Paint & Coatings'], 'description' => 'High quality interior wall paint'],
            ['name' => 'Primer', 'unit' => 'liter', 'base_price' => 6000, 'category_id' => $subCategoryIds['Paint & Coatings'], 'description' => 'Wall primer and sealer'],
        ];

        $itemIds = [];
        foreach ($items as $item) {
            $i = BoqTemplateItem::firstOrCreate(['name' => $item['name']], $item);
            $itemIds[$item['name']] = $i->id;
        }

        // Associate materials with sub-activities
        $materialAssociations = [
            // Rebar Installation materials
            ['sub_activity_id' => $subActivityIds['Rebar Installation'], 'boq_item_id' => $itemIds['Rebar Steel Y12'], 'quantity' => 100],
            ['sub_activity_id' => $subActivityIds['Rebar Installation'], 'boq_item_id' => $itemIds['Rebar Steel Y16'], 'quantity' => 50],
            
            // Concrete Pouring materials
            ['sub_activity_id' => $subActivityIds['Concrete Pouring'], 'boq_item_id' => $itemIds['Ready Mix Concrete C25'], 'quantity' => 10],
            ['sub_activity_id' => $subActivityIds['Concrete Pouring'], 'boq_item_id' => $itemIds['Portland Cement'], 'quantity' => 5],
            
            // Wall Plastering materials
            ['sub_activity_id' => $subActivityIds['Wall Plastering'], 'boq_item_id' => $itemIds['Portland Cement'], 'quantity' => 8],
            ['sub_activity_id' => $subActivityIds['Wall Plastering'], 'boq_item_id' => $itemIds['Sand'], 'quantity' => 2],
        ];

        foreach ($materialAssociations as $association) {
            SubActivityMaterial::firstOrCreate(
                [
                    'sub_activity_id' => $association['sub_activity_id'],
                    'boq_item_id' => $association['boq_item_id']
                ],
                $association
            );
        }

        echo "BOQ Template sample data seeded successfully!\n";
        echo "Created:\n";
        echo "- " . count($buildingTypes) . " Building Types\n";
        echo "- " . (count($categories) + count($subCategories)) . " BOQ Item Categories\n";
        echo "- " . count($stages) . " Construction Stages\n";
        echo "- " . count($activities) . " Activities\n";
        echo "- " . count($subActivities) . " Sub-Activities\n";
        echo "- " . count($items) . " BOQ Items\n";
        echo "- " . count($materialAssociations) . " Material Associations\n";
    }
}