<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JoinRequestController;
use App\Http\Controllers\Api\V1\Admin\JoinRequestAdminController;
use App\Http\Controllers\Api\V1\Org\OpportunityController as OrgOpportunityController;
use App\Http\Controllers\Api\V1\Admin\OpportunityApprovalController;
use App\Http\Controllers\Api\V1\PublicOpportunityController;
use App\Http\Controllers\Api\V1\Volunteer\ApplicationController as VolunteerApplicationController;
use App\Http\Controllers\Api\V1\Org\OrgApplicationController;
use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Org\VolunteerEvaluationController;
use App\Http\Controllers\Api\V1\Volunteer\OrganizationReviewController;
use App\Http\Controllers\Api\V1\Admin\EvaluationActionController;
use App\Http\Controllers\Api\V1\Social\PostController;
use App\Http\Controllers\Api\V1\Social\LikeController;
use App\Http\Controllers\Api\V1\Social\CommentController;
use App\Http\Controllers\Api\V1\Social\ShareController;
use App\Http\Controllers\Api\V1\Social\StoryController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    // Public list/details (optional auth)
    Route::get('/opportunities', [PublicOpportunityController::class, 'index']);
    Route::get('/opportunities/{id}', [PublicOpportunityController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        // Org (create/update/submit)
        Route::post('/org/opportunities', [OrgOpportunityController::class, 'store']);
        Route::put('/org/opportunities/{id}', [OrgOpportunityController::class, 'update']);
        Route::post('/org/opportunities/{id}/submit', [OrgOpportunityController::class, 'submit']);
        // Volunteer
    Route::post('/opportunities/{id}/apply', [VolunteerApplicationController::class, 'apply']);
    Route::get('/volunteer/applications', [VolunteerApplicationController::class, 'myApplications']);

    // Org (Coordinator)
    Route::get('/org/applications', [OrgApplicationController::class, 'index']);
    Route::get('/org/opportunities/{id}/applicants', [OrgApplicationController::class, 'applicants']);
    Route::post('/org/applications/{id}/accept', [OrgApplicationController::class, 'accept']);
    Route::post('/org/applications/{id}/reject', [OrgApplicationController::class, 'reject']);
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{id}/read', [ConversationController::class, 'markRead']);
   // notification
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

    // Org evaluates volunteer
    Route::post('/org/applications/{id}/evaluate', [VolunteerEvaluationController::class, 'store']);
    Route::get('/org/evaluations', [VolunteerEvaluationController::class, 'index']); // optional

    // Volunteer reviews org
    Route::post('/volunteer/applications/{id}/review-organization', [OrganizationReviewController::class, 'store']);
    Route::get('/volunteer/my-reviews', [OrganizationReviewController::class, 'myReviews']); // optional

    // Admin actions warn/suspend/block
    Route::post('/admin/volunteers/{id}/actions', [EvaluationActionController::class, 'store']);
    Route::get('/admin/volunteers/{id}/actions', [EvaluationActionController::class, 'index']); // optional

    // Posts
    Route::get('/posts', [PostController::class, 'index']);          // feed
    Route::post('/posts', [PostController::class, 'store']);         // create post + media + tags
    Route::get('/posts/{id}', [PostController::class, 'show']);      // single post

    // Likes
    Route::post('/posts/{id}/like', [LikeController::class, 'like']);
    Route::delete('/posts/{id}/like', [LikeController::class, 'unlike']);

    // Comments
    Route::get('/posts/{id}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{id}/comments', [CommentController::class, 'store']);

    // Share (creates a new post_share record)
    Route::post('/posts/{id}/share', [ShareController::class, 'store']);

    Route::get('/stories', [StoryController::class, 'index']);      // active stories feed
    Route::post('/stories', [StoryController::class, 'store']);     // create
    Route::delete('/stories/{id}', [StoryController::class, 'destroy']); // delete own story
});

        // Admin approval
        Route::get('/admin/opportunities/pending', [OpportunityApprovalController::class, 'pending']);
        Route::post('/admin/opportunities/{id}/approve', [OpportunityApprovalController::class, 'approve']);
        Route::post('/admin/opportunities/{id}/reject', [OpportunityApprovalController::class, 'reject']);
// User (volunteer/org)
    Route::post('/join-requests', [JoinRequestController::class, 'store']);
    Route::get('/join-requests/me', [JoinRequestController::class, 'myRequest']);

    // Admin (Approval Officer)
    Route::get('/admin/join-requests', [JoinRequestAdminController::class, 'index']);
    Route::get('/admin/join-requests/{id}', [JoinRequestAdminController::class, 'show']);
    Route::post('/admin/join-requests/{id}/approve', [JoinRequestAdminController::class, 'approve']);
    Route::post('/admin/join-requests/{id}/reject', [JoinRequestAdminController::class, 'reject']);
    });


