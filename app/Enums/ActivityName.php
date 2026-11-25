<?php

namespace App\Enums;

/**
 * The mentee actions written to activity_logs, used by the admin funnel to
 * see where mentees stop. Values are stored, so never rename a case value.
 */
enum ActivityName: string
{
    case Registered = 'registered';
    case ScholarshipsViewed = 'scholarships_viewed';
    case ScholarshipsFiltered = 'scholarships_filtered';
    case ScholarshipOpened = 'scholarship_opened';
    case MatchCtaClicked = 'match_cta_clicked';
    case ProfilingStarted = 'profiling_started';
    case ProfilingCompleted = 'profiling_completed';
    case ScholarshipsSelected = 'scholarships_selected';
    case ModuleOpened = 'module_opened';
    case ModuleCompleted = 'module_completed';
    case PracticeSubmitted = 'practice_submitted';
    case FeedbackSubmitted = 'feedback_submitted';
    case EventRegistered = 'event_registered';
    case EventAttended = 'event_attended';
    case PremiumViewed = 'premium_viewed';
    case CheckoutStarted = 'checkout_started';
    case TryoutStarted = 'tryout_started';
    case TryoutCompleted = 'tryout_completed';
    case TryoutReviewOpened = 'tryout_review_opened';
    case TryoutMentorAsked = 'tryout_mentor_asked';
    case TryoutTheoryOpened = 'tryout_theory_opened';
    case TryoutTheoryPdfViewed = 'tryout_theory_pdf_viewed';
    case TryoutMaterialsClicked = 'tryout_materials_clicked';

    /**
     * Indonesian label for the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Daftar',
            self::ScholarshipsViewed => 'Lihat beasiswa',
            self::ScholarshipsFiltered => 'Filter beasiswa',
            self::ScholarshipOpened => 'Buka detail beasiswa',
            self::MatchCtaClicked => 'Klik cari yang sesuai',
            self::ProfilingStarted => 'Mulai profiling',
            self::ProfilingCompleted => 'Profiling selesai',
            self::ScholarshipsSelected => 'Pilih beasiswa',
            self::ModuleOpened => 'Buka modul',
            self::ModuleCompleted => 'Modul selesai',
            self::PracticeSubmitted => 'Kirim praktik',
            self::FeedbackSubmitted => 'Kirim masukan modul',
            self::EventRegistered => 'Daftar event',
            self::EventAttended => 'Hadir di event',
            self::PremiumViewed => 'Lihat Premium',
            self::CheckoutStarted => 'Mulai checkout',
            self::TryoutStarted => 'Mulai Try Out',
            self::TryoutCompleted => 'Try Out selesai',
            self::TryoutReviewOpened => 'Buka pembahasan Try Out',
            self::TryoutMentorAsked => 'Tanya mentor Try Out',
            self::TryoutTheoryOpened => 'Buka teori Try Out',
            self::TryoutTheoryPdfViewed => 'Baca PDF teori Try Out',
            self::TryoutMaterialsClicked => 'Klik beli materi Try Out',
        };
    }

    /**
     * The steps of the mentee funnel shown in the admin, in journey order.
     *
     * @return array<int, self>
     */
    public static function funnel(): array
    {
        return [
            self::Registered,
            self::ScholarshipsViewed,
            self::MatchCtaClicked,
            self::ProfilingCompleted,
            self::ScholarshipsSelected,
            self::ModuleCompleted,
            self::FeedbackSubmitted,
            self::EventRegistered,
        ];
    }
}
