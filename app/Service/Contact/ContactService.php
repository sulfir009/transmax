<?php

namespace App\Service\Contact;

use App\Mail\FeedbackFormMail;
use App\Repository\Contact\ContactRepository;
use App\Repository\Site\PhoneCodesRepository;
use App\Repository\Site\SiteSettingsRepository;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactService
{
    public function __construct(
        private ContactRepository $contactRepository,
        private PhoneCodesRepository $phoneCodesRepository,
        private SiteSettingsRepository $siteSettingsRepository
    ) {
    }

    /**
     * Получить все данные для страницы контактов
     * 
     * @param string $lang
     * @return array
     */
    public function getPageData(string $lang): array
    {
        $phoneCodes = $this->phoneCodesRepository->getAll();
        $firstPhoneCode = $phoneCodes->first();

        return [
            'contactInfo' => $this->contactRepository->getContactInfo($lang),
            'feedbackFormText' => $this->contactRepository->getFeedbackFormText($lang),
            'phoneCodes' => $phoneCodes,
            'firstPhoneExample' => $firstPhoneCode->phone_example ?? '',
            'firstPhoneMask' => $firstPhoneCode->phone_mask ?? '',
            'siteSettings' => $this->siteSettingsRepository->getSettings(),
        ];
    }

    /**
     * Обработать форму обратной связи
     * 
     * @param array $data
     * @return bool
     */
    public function processFeedback(array $data): bool
    {
        try {
            // Сохраняем в базу данных
            $feedbackData = [
                'name' => $data['name'],
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'message' => $data['message'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->contactRepository->saveFeedback($feedbackData);

            // Отправляем email администратору
            $this->sendFeedbackEmail($data);

            return true;
        } catch (\Exception $e) {
            Log::error('Error processing feedback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправить email с формой обратной связи
     * 
     * @param array $data
     * @return void
     */
    private function sendFeedbackEmail(array $data): void
    {
        try {
            $adminEmail = env('MAIL_ADMIN', env('CONTACT_EMAIL'));
            
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new FeedbackFormMail([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'message' => $data['message'],
                    'date' => now()->format('Y-m-d H:i:s'),
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Error sending feedback email: ' . $e->getMessage());
            // Не прерываем процесс, если email не отправился
        }
    }
}
