<?php

namespace Database\Seeders;

use App\Models\ProjectActivityTemplate;
use Illuminate\Database\Seeder;

class ProjectActivityTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            ['activity_code' => 'A0', 'name' => 'Drone Survey and Data Analysis', 'phase' => 'Survey Stage', 'discipline' => 'Survey and Data Analysis', 'duration_days' => 8, 'predecessor_code' => null, 'sort_order' => 1],
            ['activity_code' => 'A1', 'name' => '2D First Draft Preparation', 'phase' => '2D Design Stage', 'discipline' => 'Architectural Drawing 1st Draft', 'duration_days' => 20, 'predecessor_code' => 'A0', 'sort_order' => 2],
            ['activity_code' => 'A2', 'name' => 'Client Review - 2D First Draft', 'phase' => '2D Design Stage', 'discipline' => 'Client', 'duration_days' => 1, 'predecessor_code' => 'A1', 'sort_order' => 3],
            ['activity_code' => 'A3', 'name' => '2D Second Draft Revision', 'phase' => '2D Design Stage', 'discipline' => 'Architectural Drawing 2nd Draft', 'duration_days' => 2, 'predecessor_code' => 'A2', 'sort_order' => 4],
            ['activity_code' => 'A4', 'name' => 'Client Review - 2D Second Draft', 'phase' => '2D Design Stage', 'discipline' => 'Client', 'duration_days' => 1, 'predecessor_code' => 'A3', 'sort_order' => 5],
            ['activity_code' => 'A5', 'name' => '2D Third Draft Revision', 'phase' => '2D Design Stage', 'discipline' => 'Architectural Drawing 3rd Draft', 'duration_days' => 2, 'predecessor_code' => 'A4', 'sort_order' => 6],
            ['activity_code' => 'A6', 'name' => 'Client Review - 2D Third Draft', 'phase' => '2D Design Stage', 'discipline' => 'Client', 'duration_days' => 1, 'predecessor_code' => 'A5', 'sort_order' => 7],
            ['activity_code' => 'A7', 'name' => '2D Final Draft Preparation', 'phase' => '2D Design Stage', 'discipline' => '2D Architectural Final Draft', 'duration_days' => 1, 'predecessor_code' => 'A6', 'sort_order' => 8],
            ['activity_code' => 'B1', 'name' => '3D First Draft Model', 'phase' => '3D Design Stage', 'discipline' => '3D Exterior Architectural Design 1st Draft', 'duration_days' => 16, 'predecessor_code' => 'A7', 'sort_order' => 9],
            ['activity_code' => 'B2', 'name' => 'Client Review - 3D First Draft', 'phase' => '3D Design Stage', 'discipline' => 'Client', 'duration_days' => 1, 'predecessor_code' => 'B1', 'sort_order' => 10],
            ['activity_code' => 'B3', 'name' => '3D Second Draft Revision', 'phase' => '3D Design Stage', 'discipline' => '3D Exterior Architectural Design 2nd Draft', 'duration_days' => 2, 'predecessor_code' => 'B2', 'sort_order' => 11],
            ['activity_code' => 'B4', 'name' => 'Client Review - 3D Second Draft', 'phase' => '3D Design Stage', 'discipline' => 'Client', 'duration_days' => 2, 'predecessor_code' => 'B3', 'sort_order' => 12],
            ['activity_code' => 'B5', 'name' => '3D Third Draft Revision', 'phase' => '3D Design Stage', 'discipline' => '3D Exterior Architectural Design 3rd Draft', 'duration_days' => 2, 'predecessor_code' => 'B4', 'sort_order' => 13],
            ['activity_code' => 'B6', 'name' => 'Client Review - 3D Third Draft', 'phase' => '3D Design Stage', 'discipline' => 'Client', 'duration_days' => 1, 'predecessor_code' => 'B5', 'sort_order' => 14],
            ['activity_code' => 'B7', 'name' => '3D Final Draft Preparation', 'phase' => '3D Design Stage', 'discipline' => '3D Exterior Architectural Design Final Draft', 'duration_days' => 1, 'predecessor_code' => 'B6', 'sort_order' => 15],
            ['activity_code' => 'C1', 'name' => 'Structure and MEP Preparation', 'phase' => 'Structural & MEP Design', 'discipline' => 'Structural & Service', 'duration_days' => 5, 'predecessor_code' => 'B7', 'sort_order' => 16],
            ['activity_code' => 'C2', 'name' => 'BOQ Document Preparation', 'phase' => 'Calculations & Quantification', 'discipline' => 'BOQ Preparation', 'duration_days' => 5, 'predecessor_code' => 'C1', 'sort_order' => 17],
            ['activity_code' => 'C4', 'name' => 'Issuing Stamped Hard Copies and BOQ Document', 'phase' => 'Final Submission', 'discipline' => 'Documents Handling', 'duration_days' => 2, 'predecessor_code' => 'C2', 'sort_order' => 18],
        ];

        foreach ($templates as $template) {
            ProjectActivityTemplate::updateOrCreate(
                ['activity_code' => $template['activity_code']],
                $template
            );
        }
    }
}
