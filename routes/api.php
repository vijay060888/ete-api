<?php

use App\Helpers\LogActivity;
use App\Http\Controllers\Archive\ArchiveController;
use App\Http\Controllers\AssemblyController;
use App\Http\Controllers\Authentication\ForgotPasswordController;
use App\Http\Controllers\Authentication\PartyLoginController;
use App\Http\Controllers\Authentication\UserLoginController;
use App\Http\Controllers\Authentication\UserTypeController;
use App\Http\Controllers\CommentsController\CommentsManagementController;
use App\Http\Controllers\CompetitiveAnalysis\CompetitiveAnalysisManagementController;
use App\Http\Controllers\CompetitiveAnalysis\PartyCompetitiveAnalysisController;
use App\Http\Controllers\Consituency\ConsituencyController;
use App\Http\Controllers\DepartmentController\MasterDepartmentController;
use App\Http\Controllers\EmployeeManagement\EmployeeController;
use App\Http\Controllers\FollowController\FollowManagementController;
use App\Http\Controllers\HelpManagement\HelpManagementController;
use App\Http\Controllers\Leader\LeaderController;
use App\Http\Controllers\LeaderController\LeaderAffidavitController;
use App\Http\Controllers\LeaderController\LeaderManagementController;
use App\Http\Controllers\LeaderController\LeaderProfileController;
use App\Http\Controllers\LikesController\LikesManagementController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LoksabhaController;
use App\Http\Controllers\ManifestoTracker\ElectionHistoryDetailsController;
use App\Http\Controllers\ManifestoTracker\ManifestoTrackerManagementController;
use App\Http\Controllers\MinistryController\MasterMinsitryController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\PartyController\LeaderManagementonPartyPages;
use App\Http\Controllers\PartyController\MasterPartyController;
use App\Http\Controllers\PartyController\PartyLevelRoleController;
use App\Http\Controllers\PartyController\PartyManagementController;
use App\Http\Controllers\PartyController\PartyProfileController;
use App\Http\Controllers\PostController\FetchStoryController;
use App\Http\Controllers\PostController\HashTagController;
use App\Http\Controllers\PostController\MentionController;
use App\Http\Controllers\PostController\PollsManagementController;
use App\Http\Controllers\PostController\PostManagementByLeaderUserController;
use App\Http\Controllers\PostController\PostManagementByPartyController;
use App\Http\Controllers\PostController\PostSeenController;
use App\Http\Controllers\PostController\TrendingPostController;
use App\Http\Controllers\SimilarAssembly\SimilarAssemblyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController\AadharController;
use App\Http\Controllers\RegistrationController\RegisterController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleUpgradeController;
use App\Http\Controllers\SearchController\GlobalSearch;
use App\Http\Controllers\SearchController\SearchDepartMent;
use App\Http\Controllers\SearchController\SearchPartyController;
use App\Http\Controllers\Share\ShareManagementController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\Task\TaskManagementController;
use App\Http\Controllers\UserActivity\UserActivityController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\FactCheck\FactCheckController;
use App\Http\Controllers\CampaignController\BroadcastController;
use App\Http\Controllers\CampaignController\AdController;
use App\Http\Controllers\Subscription\PackageController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\SMSTemplateController;

use App\Http\Controllers\VolunteerManagement\VolunteerController;
use App\Http\Controllers\Gallery\GalleryController;
use App\Http\Controllers\Achievement\AchievementController;
use App\Http\Controllers\DirectMessage\DirectMessageController;
use App\Http\Controllers\Donation\DonationController;
use App\Models\Ministry;
use App\Models\PostController\FetchPostController;
use App\Http\Controllers\NewsAndMedia\NewsAndMediaController;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\WhatsAppController;
use App\Models\SMSTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('affidavitUpload', LeaderAffidavitController::class);


Route::post('login', [UserLoginController::class, 'login'], 'login');

Route::post('partyLogin', [PartyLoginController::class, 'login'], 'partyLogin');

Route::resource('register', RegisterController::class);
Route::resource('broadcastingsms', SMSTemplateController::class);

Route::post('aadhaarVerify', [AadharController::class, 'aadhaarVerify'], 'aadhaarVerify');
Route::post('submitotp', [AadharController::class, 'submitOTP'], 'submitotp');
Route::post('phoneSignup', [RegisterController::class, 'phoneSignup'], 'phoneSignup');
Route::post('phoneSubmitotp', [RegisterController::class, 'phoneSubmitotp'], 'phoneSubmitotp');
Route::post('verifyPhoneOtp', [RegisterController::class, 'verifyPhoneOtp'], 'verifyPhoneOtp');
Route::post('phoneSignupUsernamePassword', [RegisterController::class, 'phoneSignupUsernamePassword'], 'phoneSignupUsernamePassword');
Route::post('suggestUsername', [RegisterController::class, 'suggestUsername'], 'suggestUsername');
Route::post('checkUsernameAvailability', [RegisterController::class, 'checkUsernameAvailability'], 'checkUsernameAvailability');
Route::resource('forgotPassword',ForgotPasswordController::class);
Route::post('verifyOTP', [ForgotPasswordController::class, 'verifyOTP'], 'verifyOTP');
Route::post('resetPassword', [ForgotPasswordController::class, 'resetPassword'], 'resetPassword');



Route::group(['middleware' => ['jwt.verify']], function() {
    Route::post('saveLoginActivity', [UserLoginController::class, 'saveLoginActivity'], 'saveLoginActivity');
    Route::post('saveTimeSpend', [UserLoginController::class, 'saveTimeSpend'], 'saveTimeSpend');
    Route::post('saveFCMToken', [UserLoginController::class, 'saveToken'], 'saveToken');
    Route::post('saveTokenForParty', [PartyLoginController::class, 'saveTokenForParty'], 'saveTokenForParty');

    Route::resource('state', StateController::class);
    Route::get('preferences', [UserLoginController::class, 'preferences'], 'preferences');
    Route::post('submitPreferences', [UserLoginController::class, 'submitPreferences'], 'submitPreferences');
    Route::post('searchPreferences', [UserLoginController::class, 'searchPreferences'], 'searchPreferences');
    Route::post('aadharsubmitOTP', [UserLoginController::class, 'aadharsubmitOTP'], 'aadharsubmitOTP');
    

    Route::resource('checkuserType', UserTypeController::class);
    Route::resource('party', MasterPartyController::class);
    Route::resource('assembly', AssemblyController::class);
    Route::resource('loksabha', LoksabhaController::class);
    Route::post('logout', [UserLoginController::class, 'logout'], 'logout');
    Route::resource('profile',ProfileController::class);
    Route::post('addElectionid', [ProfileController::class, 'addElection'], 'addElectionid');
    Route::post('reset_password', [ProfileController::class, 'passwordUpdate'], 'reset_password');
    Route::get('tokenVerify', [UserLoginController::class, 'tokenVerify'], 'tokenVerify');
    Route::post('searchParty', [SearchPartyController::class, 'searchParty'], 'searchParty');
    Route::resource('upgrade',RoleUpgradeController::class);
    Route::get('viewPartyPage/{id}', [PartyManagementController::class, 'show'], 'viewPartyPage');
    Route::get('viewLeaderPage/{id}', [LeaderManagementController::class, 'show'], 'viewLeaderPage');
    Route::resource('consituency', ConsituencyController::class);


    Route::get('partyFollowUnfollow/{id}', [FollowManagementController::class, 'followParty'], 'partyFollowUnfollow');
    Route::get('leaderFollowUnfollow/{id}', [FollowManagementController::class, 'followLeader'], 'leaderFollowUnfollow');
    Route::get('followUnfollowConsituency/{id}', [FollowManagementController::class, 'followUnfollowConsituency'], 'followUnfollowConsituency');
    Route::post('searchfollowingLeader', [FollowManagementController::class, 'searchfollowingLeader'], 'searchfollowingLeader');
    Route::post('searchfollowingParty', [FollowManagementController::class, 'searchfollowingParty'], 'searchfollowingParty');
    Route::get('getYourFollowers', [FollowManagementController::class, 'getYourFollowers'], 'getYourFollowers');

    Route::post('searchMention', [MentionController::class, 'searchMention'], 'searchMention');
    Route::post('searchHashTag', [HashTagController::class, 'searchHashTag'], 'searchHashTag');
    Route::post('searchParty', [SearchPartyController::class, 'searchParty'], 'searchParty');
    Route::post('searchDepartment', [SearchDepartMent::class, 'searchDepartment'], 'searchDepartment');

    Route::resource('department', MasterDepartmentController::class);
    Route::post('createStoryByLeaderUser', [PostManagementByLeaderUserController::class, 'createStoryByLeaderUser'], 'createStoryByLeaderUser');
    Route::get('partyYouarenotFollowing', [FollowManagementController::class, 'partyYouarenotFollowing'], 'partyYouarenotFollowing');
    Route::get('leadersYouAreNotFollowing', [FollowManagementController::class, 'leadersYouAreNotFollowing'], 'leadersYouAreNotFollowing');
    Route::get('constituenciesYouAreNotFollowing', [FollowManagementController::class, 'constituenciesYouAreNotFollowing'], 'constituenciesYouAreNotFollowing');

    Route::get('leaderYouFollow', [FollowManagementController::class, 'leaderYouFollow'], 'leaderYouFollow');
    Route::get('partyYouFollow', [FollowManagementController::class, 'partyYouFollow'], 'partyYouFollow');
    Route::get('consituencyYouFollow', [FollowManagementController::class, 'consituencyYouFollow'], 'consituencyYouFollow');

    Route::resource('fetchallPost',\App\Http\Controllers\PostController\FetchPostController::class);
    Route::resource('fetchallStory',FetchStoryController::class);
    Route::get('fetchallPostTest', [\App\Http\Controllers\PostController\FetchPostController::class, 'fetchallPostTest'], 'fetchallPostTest');
    Route::get('getComplaintPost', [\App\Http\Controllers\PostController\FetchPostController::class, 'getComplaintPost'], 'getComplaintPost');
    Route::get('getIdeaPost', [\App\Http\Controllers\PostController\FetchPostController::class, 'getIdeaPost'], 'getIdeaPost');
    Route::get('getpollsPost', [\App\Http\Controllers\PostController\FetchPostController::class, 'getpollsPost'], 'getpollsPost');
    Route::get('geteventsPost', [\App\Http\Controllers\PostController\FetchPostController::class, 'geteventsPost'], 'geteventsPost');

    Route::resource('archive',ArchiveController::class);
    Route::post('getAllArchievePost', [ArchiveController::class, 'getAllArchievePost'], 'getAllArchievePost');
    Route::resource('help',HelpManagementController::class);
    Route::resource('share',ShareManagementController::class);
    Route::post('unarchive', [ArchiveController::class, 'unarchive'], 'unarchive');

    
    Route::resource('trendingpost',TrendingPostController::class);
    Route::resource('similarassembly',SimilarAssemblyController::class);
    Route::get('similarparties',[SimilarAssemblyController::class, 'similarparties'], 'similarparties');
    Route::get('similarleaders',[SimilarAssemblyController::class, 'similarleaders'], 'similarleaders');
    Route::resource('manifesto', ManifestoTrackerManagementController::class);
    Route::post('addReaction', [ManifestoTrackerManagementController::class, 'addReaction'], 'addReaction');
    Route::get('manifestoLikesdetails/{id}', [ManifestoTrackerManagementController::class, 'manifestoLikesdetails'], 'manifestoLikesdetails');
    Route::post('addComments', [ManifestoTrackerManagementController::class, 'addComments'], 'addComments');
    Route::get('showManifestoComments/{id}', [ManifestoTrackerManagementController::class, 'showManifestoComments'], 'showManifestoComments');
    Route::post('addReplyToManifestoComment', [ManifestoTrackerManagementController::class, 'addReplyToManifestoComment'], 'addReplyToManifestoComment');
    Route::get('getManifestoCommentsReplies/{id}', [ManifestoTrackerManagementController::class, 'getManifestoCommentsReplies'], 'getManifestoCommentsReplies');
    Route::get('viewPromises/{id}', [ManifestoTrackerManagementController::class, 'viewPromises'], 'viewPromises');

    Route::resource('electionhistoryDetails', ElectionHistoryDetailsController::class);
    Route::post('correctionRequest', [ElectionHistoryDetailsController::class, 'correctionRequest'], 'correctionRequest');
    Route::get('upcomingElection', [ElectionHistoryDetailsController::class, 'upcomingElection'], 'upcomingElection');

    Route::resource('like',LikesManagementController::class);
    Route::resource('comment',CommentsManagementController::class);
    Route::post('addReplyToComment', [CommentsManagementController::class, 'addReplyToComment'], 'addReplyToComment');
    Route::get('getCommentsReplies/{id}', [CommentsManagementController::class, 'getCommentsReplies'], 'getCommentsReplies');
    Route::put('updateRepliesComment/{id}', [CommentsManagementController::class, 'updateRepliesComment'], 'updateRepliesComment');
    Route::delete('deleteCommentReplies/{id}', [CommentsManagementController::class, 'deleteCommentReplies'], 'deleteCommentReplies');
    Route::resource('polls',PollsManagementController::class);
    Route::resource('postseen',PostSeenController::class);
    Route::post('reportPost', [\App\Http\Controllers\PostController\FetchPostController::class, 'reportPost'], 'reportPost');
    Route::resource('logactivity', UserActivityController::class);
    Route::get('activityOnPost', [UserActivityController::class, 'activityOnPost'], 'activityOnPost');
    Route::resource('notification',NotificationController::class);
    Route::get('notReadNotification',[NotificationController::class, 'notReadNotification'], 'notReadNotification');

    Route::resource('globalSearch', GlobalSearch::class);
    //expenses api
    Route::resource('expenses', ExpenseController::class);
    Route::get('expenseUserList ', [ExpenseController::class, 'expenseUserList'], 'expenseUserList');
    Route::post('generateExpenseReport',[ExpenseController::class, 'generateExpenseReport'], 'generateExpenseReport');
    Route::post('adsView', [AdController::class, 'adsView']);

    //expenses api
    Route::resource('factCheck', FactCheckController::class);

    Route::resource('donation', DonationController::class);
    Route::get('leaderList ', [DonationController::class, 'leaderList'], 'leaderList');
    Route::get('partyList ', [DonationController::class, 'partyList'], 'partyList');
    Route::get('search ', [DonationController::class, 'search'], 'search');
    Route::post('donate ', [DonationController::class, 'donate'], 'donate');
    // Route::post('getUrl ', [DonationController::class, 'getUrl'], 'getUrl');
    // Route::post('responseBack ', [DonationController::class, 'responseBack'], 'responseBack');
    Route::post('paymentDetails ', [DonationController::class, 'paymentDetails'], 'paymentDetails');

    Route::resource('postByLeaderUser', PostManagementByLeaderUserController::class);
    Route::get('/clearLogs',function(){
        $logs = LogActivity::logActivityClear();
        return true;
    });
    Route::get('logs',function(){
        $logs = LogActivity::logActivityLists()->toArray();
        return $logs;
    });

    /**Direct Message */
    
    Route::get('leaderPartyYouFollow', [DirectMessageController::class, 'leaderPartyYouFollow'], 'leaderPartyYouFollow');
    Route::post('sendMessage', [DirectMessageController::class, 'sendMessage'], 'sendMessage');
    /**Direct Message */
    /* Direct Message */
    Route::post('requestListDm', [DirectMessageController::class, 'requestListDm'], 'requestListDm');
    Route::post('requestListCitizenDm', [DirectMessageController::class, 'requestListCitizenDm'], 'requestListCitizenDm');
    Route::post('changeStatus', [DirectMessageController::class, 'changeStatus'], 'changeStatus');
    Route::post('displayMessage', [DirectMessageController::class, 'displayMessage'], 'displayMessage');
    Route::post('isReadDirectMessage', [DirectMessageController::class, 'isReadDirectMessage'], 'isReadDirectMessage');
    Route::post('isNotReadDirectMessage', [DirectMessageController::class, 'isNotReadDirectMessage'], 'isNotReadDirectMessage');
    Route::delete('deleteMessage', [DirectMessageController::class, 'deleteMessage'], 'deleteMessage');
});

Route::group(['middleware' => ['jwt.verify','role:Leader']], function() {
    Route::resource('leaderProfile',LeaderProfileController::class);
    Route::post('createPageRequest', [LeaderProfileController::class, 'createPageRequest'], 'createPageRequest');
    Route::post('createNewPartyRequest', [LeaderProfileController::class, 'createNewPartyRequest'], 'createNewPartyRequest');
    Route::post('createPageAccessRequest', [LeaderProfileController::class, 'createPageAccessRequest'], 'createPageAccessRequest');
    Route::post('searchPartywithAdminAccess', [SearchPartyController::class, 'searchPartywithAdminRole'], 'searchPartywithAdminAccess');
    Route::get('filterAdminRoleParty', [LeaderProfileController::class, 'filterAdminRoleParty'], 'filterAdminRoleParty');
    Route::get('filterPageRequest', [LeaderProfileController::class, 'filterPageRequest'], 'filterPageRequest');
    Route::get('partyPageMangement', [LeaderProfileController::class, 'partyPageMangement'], 'partyPageMangement');
    Route::post('changeCoreParty', [LeaderProfileController::class, 'changeCoreParty'], 'changeCoreParty');
    Route::post('acceptRequestFromParty', [LeaderProfileController::class, 'acceptRequestFromParty'], 'acceptRequestFromParty');
    Route::post('declineRequestFromParty', [LeaderProfileController::class, 'declineRequestFromParty'], 'declineRequestFromParty');
    Route::resource('ministry',MasterMinsitryController::class);
    Route::resource('getPartyLevelRole',PartyLevelRoleController::class);
    Route::resource('competetiveAnalysis',CompetitiveAnalysisManagementController::class);
    Route::get('getOtherLeaders',[CompetitiveAnalysisManagementController::class, 'getOtherLeaders'], 'getOtherLeaders');
    Route::post('compareLeaders',[CompetitiveAnalysisManagementController::class, 'compareLeaders'], 'compareLeaders');
    Route::post('generateAnalysisReport',[CompetitiveAnalysisManagementController::class, 'generateAnalysisReport'], 'generateAnalysisReport');
    Route::delete('cancelRequest/{id}', [LeaderProfileController::class, 'cancelRequest'], 'cancelRequest');

    Route::resource('partyCompetetiveAnalysis',PartyCompetitiveAnalysisController::class);
    Route::get('getOtherParties',[PartyCompetitiveAnalysisController::class, 'getOtherParties'], 'getOtherParties');
    Route::post('compareParties',[PartyCompetitiveAnalysisController::class, 'compareParties'], 'compareParties');
    Route::post('generatePartyAnalysisReport',[PartyCompetitiveAnalysisController::class, 'generatePartyAnalysisReport'], 'generatePartyAnalysisReport');

    Route::resource('task', TaskManagementController::class);
    Route::resource('employee', EmployeeController::class);
    Route::delete('deleteEmployee/{id}', [EmployeeController::class]);
    Route::get('employeeList ', [EmployeeController::class, 'employeeList'], 'employeeList');

    Route::get('reportingManager', [EmployeeController::class, 'reportingManager'], 'reportingManager');

    Route::get('getdepartMentList ', [EmployeeController::class, 'getdepartMentList'], 'getdepartMentList');
    Route::get('getTaskType', [TaskManagementController::class, 'getTaskType'], 'getTaskType');
    Route::get('taskAssignedTo', [TaskManagementController::class, 'taskAssignedTo'], 'taskAssignedTo');
    Route::resource('volunteer', VolunteerController::class);
    Route::get('getVolunteerdepartMentList ', [VolunteerController::class, 'getVolunteerdepartMentList'], 'getVolunteerdepartMentList');
    Route::get('volunteerList', [VolunteerController::class, 'volunteerList'], 'volunteerList');
    Route::get('VolunterreportingManager', [VolunteerController::class, 'VolunterreportingManager'], 'VolunterreportingManager');

    Route::post('searchVolunterWithAadhar', [VolunteerController::class, 'searchVolunterWithAadhar'], 'searchVolunterWithAadhar');
    Route::post('searchEmployeeWithAadhar', [EmployeeController::class, 'searchEmployeeWithAadhar'], 'searchEmployeeWithAadhar');


     /* campaign apis */
     Route::resource('broadcasts', BroadcastController::class);
     Route::post('broadcasts/publish', [BroadcastController::class, 'publish']);
     Route::post('broadcasts/publishlater', [BroadcastController::class, 'publishlater']);
     Route::post('broadcasts/reach', [BroadcastController::class, 'setEstimatedReach']);

     Route::resource('ads', AdController::class);
     Route::post('getreach', [AdController::class, 'setEstimatedReach']);
     Route::post('publishAds', [AdController::class, 'publish']);
     Route::get('getAdsPerformance/{id}', [AdController::class, 'getAdsPerformance']);
     Route::put('updateAdsStatus/{id}', [AdController::class, 'update']);
     Route::get('getArchieveAds', [AdController::class, 'getArchieveAds']);
     Route::get('getArchiveBroadcast', [BroadcastController::class, 'getArchiveBroadcast']);
     Route::get('getAdsPreview/{id}', [AdController::class, 'getAdsPreview']);
     Route::get('finalPublished/{id}', [AdController::class, 'finalPublished']);

    /* campaign apis */

     /* subscription apis */
     Route::resource('packages', PackageController::class);
     Route::post('subscription/generatePaymentUrl', [SubscriptionController::class, 'generatePaymentGatewayUrl']);
     Route::post('subscription/responseHandler', [SubscriptionController::class, 'responseHandler']);
     Route::post('subscription/PaymentStatus', [SubscriptionController::class, 'paymentStatus']);
     /* subscription apis */

     Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store');
     Route::put('/galleries/{id}', [GalleryController::class, 'edit'])->name('galleries.edit');
     Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
     Route::put('/galleries/{id}/status', [GalleryController::class, 'updateStatus'])->name('galleries.update_status');
     Route::get('/galleries/update/{id}', [GalleryController::class, 'update'])->name('galleries.update');
     /* Achievement */
     Route::post('/achievements', [AchievementController::class, 'store'])->name('achievements.store');
     Route::put('/achievements/{id}', [AchievementController::class, 'update'])->name('achievements.update');
     Route::put('/achievements/{id}/status', [AchievementController::class, 'updateStatus'])->name('achievements.update_status');
     Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
     Route::get('/achievements/update/{id}', [AchievementController::class, 'edit'])->name('achievements.edit');
     /* News and media */
     Route::post('/news-and-media', [NewsAndMediaController::class, 'store'])->name('news-and-media.store');
     Route::put('/news-and-media/{id}', [NewsAndMediaController::class, 'update'])->name('news-and-media.update');
     Route::put('/news-and-media/{id}/status', [NewsAndMediaController::class, 'updateStatus'])->name('news-and-media.update_status');
     Route::get('/news-and-media', [NewsAndMediaController::class, 'index'])->name('news-and-media.index');
     Route::get('/news-and-media/update/{id}', [NewsAndMediaController::class, 'edit'])->name('news-and-media.edit');
     /*Vendor */
     Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store'); 
     Route::put('/vendors/{id}', [VendorController::class, 'edit'])->name('vendors.edit');
     Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
     Route::get('/category', [VendorController::class, 'categoryList'])->name('category.categoryList');
     Route::delete('/vendors/{id}', [VendorController::class, 'delete'])->name('vendors.delete');
    
    
});


Route::middleware(['jwt.verify','check.user.party','assign.guard:party-api',])->group(function () {
    Route::resource('partyProfile', PartyProfileController::class);
    Route::get('checkPartyAccess', [PartyProfileController::class, 'checkPartyAccess'], 'checkPartyAccess');
    Route::post('createPartyFromRequest', [PartyProfileController::class, 'createPartyFromRequest'], 'createPartyFromRequest');
    Route::post('allowPartyPageAccess', [PartyProfileController::class, 'allowPageAccess'], 'allowPartyPageAccess');
    Route::post('deniedPartyPageAccess', [PartyProfileController::class, 'deniedPageAccess'], 'deniedPartyPageAccess');
    Route::resource('postByParty', PostManagementByPartyController::class);
    Route::post('createStoryByParty', [PostManagementByPartyController::class, 'createStoryByParty'], 'createStoryByParty');
    Route::post('assignLeaderToParty', [PartyProfileController::class, 'assignLeaderToParty'], 'assignLeaderToParty');
    Route::resource('leaderPartyManagement', LeaderManagementonPartyPages::class);
    Route::get('requestByLeader', [LeaderManagementonPartyPages::class, 'requestByLeader'], 'requestByLeader');
    Route::get('addNewLeader', [LeaderManagementonPartyPages::class, 'addNewLeader'], 'addNewLeader');
    Route::put('acceptRequest/{id}', [LeaderManagementonPartyPages::class, 'acceptRequest'], 'acceptRequest');
    Route::delete('removeLeader/{id}', [LeaderManagementonPartyPages::class, 'removeLeader'], 'removeLeader');
    Route::get('getLeaderDetailsById/{id}', [LeaderManagementonPartyPages::class, 'getLeaderDetailsById'], 'getLeaderDetailsById');
    Route::put('updateLeader/{id}', [LeaderManagementonPartyPages::class, 'updateLeader'], 'updateLeader');
    Route::get('getConsituencyPartyFollow', [FollowManagementController::class, 'getConsituencyPartyFollow'], 'getConsituencyPartyFollow');
    Route::get('getConsituencyPartyNotFollowed', [FollowManagementController::class, 'getConsituencyPartyNotFollowed'], 'getConsituencyPartyNotFollowed');
    Route::get('partyFollowUnfollowConsituency/{id}', [FollowManagementController::class, 'partyFollowUnfollowConsituency'], 'partyFollowUnfollowConsituency');

    Route::group(['prefix'=>'whatsapp'], function(){
        Route::post('send-template-request', [WhatsAppController::class, 'sendTemplateRequest']);
        Route::get('get-templates', [WhatsAppController::class, 'getTemplates']);
        Route::post('send-message',[WhatsAppController::class, 'sendWhatsappMessages']);
    });

});


// Route::middleware(['jwt.verify'])->group(function () {
//     Route::resource('checkuserType', UserTypeController::class);
// });

Route::group(['middleware' => ['jwt.verify', 'role:admin']], function () {
    Route::resource('roles',RoleController::class);
});


