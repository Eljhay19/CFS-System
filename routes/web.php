<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerFeedbackController;
use App\Http\Controllers\FeedbackReviewController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SurveyStepController;
use App\Http\Controllers\AdminMessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});
Route::get('/home', [CustomerFeedbackController::class, 'home'])->name('home');
Route::get('/survey/{business:survey_slug}', [CustomerFeedbackController::class, 'publicSurvey'])->name('survey.public');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/terms', [CustomerFeedbackController::class, 'terms'])->name('terms');
Route::view('/privacy-policy', 'privacy')->name('privacy');
Route::view('/faqs', 'faqs')->name('faqs');

Route::redirect('/login', '/admin-login');

Route::middleware('guest')->group(function () {
    Route::get('/admin-login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/admin-login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/check-your-email', [PasswordResetController::class, 'sent'])->name('password.sent');
    Route::post('/check-your-email/resend', [PasswordResetController::class, 'resend'])->middleware('throttle:3,1')->name('password.resend');
    Route::post('/check-your-email/verify', [PasswordResetController::class, 'verify'])->middleware('throttle:6,1')->name('password.verify');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/notifications', [DashboardController::class, 'notifications'])->name('dashboard.notifications');
    Route::post('/dashboard/notifications/read-all', [DashboardController::class, 'markNotificationsRead'])->name('dashboard.notifications.read-all');
    Route::get('/dashboard/notifications/{notification}/open', [DashboardController::class, 'openNotification'])->whereNumber('notification')->name('dashboard.notifications.open');
    Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');
    Route::post('/dashboard/notification-email', [DashboardController::class, 'updateNotificationEmail'])->name('dashboard.notification-email');
    Route::get('/dashboard/{section}', [DashboardController::class, 'section'])
        ->where('section', 'total|average|today|unread|inbox')
        ->name('dashboard.section');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/admin-messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::get('/admin-messages/{contactMessage}', [AdminMessageController::class, 'show'])->name('messages.show');
    Route::post('/admin-messages/{contactMessage}/reply', [AdminMessageController::class, 'reply'])->middleware('throttle:10,1')->name('messages.reply');
    Route::post('/admin-messages/{contactMessage}/replies/{reply}/retry', [AdminMessageController::class, 'retryReply'])->middleware('throttle:3,1')->name('messages.reply.retry');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->middleware('throttle:3,1')->name('settings.test-email');
    Route::post('/settings/notification-email/verify', [SettingsController::class, 'verifyNotificationEmail'])->middleware('throttle:6,1')->name('settings.notification-email.verify');
    Route::post('/settings/account-email/verify', [SettingsController::class, 'verifyAccountEmail'])->middleware('throttle:6,1')->name('settings.account-email.verify');
    Route::get('/business-profile', [BusinessProfileController::class, 'edit'])->name('business.edit');
    Route::post('/business-profile', [BusinessProfileController::class, 'update'])->name('business.update');
    Route::resource('/questions', QuestionController::class)->except(['show']);
    Route::post('/questions/{question}/restore', [QuestionController::class, 'restore'])->name('questions.restore');
    Route::post('/survey-steps', [SurveyStepController::class, 'store'])->name('survey-steps.store');
    Route::put('/survey-steps/{surveyStep}', [SurveyStepController::class, 'update'])->name('survey-steps.update');
    Route::post('/survey-steps/{surveyStep}/publish', [SurveyStepController::class, 'publish'])->name('survey-steps.publish');
    Route::post('/survey-steps/{surveyStep}/archive', [SurveyStepController::class, 'archive'])->name('survey-steps.archive');
    Route::get('/admin-feedback', [FeedbackReviewController::class, 'index'])->name('feedback.index');
    Route::get('/admin-feedback/{feedback}', [FeedbackReviewController::class, 'show'])->name('feedback.detail');
    Route::post('/admin-feedback/{feedback}/action', [FeedbackReviewController::class, 'updateAction'])->name('feedback.action');
    Route::post('/admin-feedback/{feedback}/actions/{action}/retry-customer-email', [FeedbackReviewController::class, 'retryCustomerUpdate'])->middleware('throttle:3,1')->name('feedback.retry-customer-email');
    Route::post('/admin-feedback/{feedback}/retry-email', [SettingsController::class, 'retryEmail'])->middleware('throttle:3,1')->name('feedback.retry-email');
});

Route::get('/feedback', function () {
    return redirect()->route('feedback.show');
});
Route::get('/feedback/terms-agree', [CustomerFeedbackController::class, 'agreeFromTerms'])->name('feedback.terms-agree');
Route::post('/feedback/terms-agree', [CustomerFeedbackController::class, 'confirmTerms'])->name('feedback.terms-confirm');
Route::get('/feedback/date-time', [CustomerFeedbackController::class, 'show'])->name('feedback.show');
Route::post('/feedback/start', [CustomerFeedbackController::class, 'start'])->name('feedback.start');
Route::get('/feedback/continue', [CustomerFeedbackController::class, 'continueDraft'])->name('feedback.continue');
Route::post('/feedback/restart', [CustomerFeedbackController::class, 'restart'])->name('feedback.restart');
Route::post('/feedback/visit', [CustomerFeedbackController::class, 'saveVisit'])->name('feedback.visit');
Route::get('/feedback/order', [CustomerFeedbackController::class, 'order'])->name('feedback.order');
Route::post('/feedback/order', [CustomerFeedbackController::class, 'saveOrder'])->name('feedback.order.save');
Route::get('/feedback/store-rating', [CustomerFeedbackController::class, 'storeRating'])->name('feedback.store-rating');
Route::post('/feedback/store-rating', [CustomerFeedbackController::class, 'saveStoreRating'])->name('feedback.store-rating.save');
Route::get('/feedback/food-rating', [CustomerFeedbackController::class, 'foodRating'])->name('feedback.food-rating');
Route::post('/feedback/food-rating', [CustomerFeedbackController::class, 'saveFoodRating'])->name('feedback.food-rating.save');
Route::get('/feedback/final', [CustomerFeedbackController::class, 'finalStep'])->name('feedback.final');
Route::post('/feedback/final', [CustomerFeedbackController::class, 'saveFinal'])->name('feedback.final.save');
Route::get('/feedback/review', [CustomerFeedbackController::class, 'review'])->name('feedback.review');
Route::get('/feedback/steps/{stepKey}', [CustomerFeedbackController::class, 'additional'])->name('feedback.additional');
Route::post('/feedback/steps/{stepKey}', [CustomerFeedbackController::class, 'saveAdditional'])->name('feedback.additional.save');
Route::post('/feedback', [CustomerFeedbackController::class, 'store'])->middleware('throttle:10,1')->name('feedback.store');
Route::get('/thank-you', [CustomerFeedbackController::class, 'thankYou'])->name('feedback.thank-you');
