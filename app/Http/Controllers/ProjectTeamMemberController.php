<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectTeamMemberController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectTeamMember')) {
            return back();
        }

        $teamMembers = ProjectTeamMember::with(['project', 'user'])
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->when($request->role, function($query) use ($request) {
                return $query->where('role', $request->role);
            })
            ->get();

        $projects = Project::all();
        $users = User::all();

        $data = [
            'teamMembers' => $teamMembers,
            'projects' => $projects,
            'users' => $users
        ];
        return view('pages.projects.project_team_members')->with($data);
    }

    public function assign(Request $request) {
        // Check if user is already assigned to the project
        $exists = ProjectTeamMember::where('project_id', $request->project_id)
            ->where('user_id', $request->user_id)
            ->where('role', $request->role)
            ->exists();

        if($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User is already assigned to this project with this role'
            ], 400);
        }

        $teamMember = ProjectTeamMember::create([
            'project_id' => $request->project_id,
            'user_id' => $request->user_id,
            'role' => $request->role,
            'assigned_date' => $request->assigned_date ?? now(),
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'team_member' => $teamMember
        ]);
    }

    public function endAssignment(Request $request, $id) {
        $teamMember = ProjectTeamMember::findOrFail($id);

        $teamMember->update([
            'end_date' => $request->end_date ?? now(),
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'team_member' => $teamMember
        ]);
    }

    public function getProjectTeam($projectId) {
        $teamMembers = ProjectTeamMember::with('user')
            ->where('project_id', $projectId)
            ->where('status', 'active')
            ->get();

        return response()->json($teamMembers);
    }
}
