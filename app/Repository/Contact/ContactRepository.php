<?php

namespace App\Repository\Contact;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ContactRepository
{
    private string $dbPrefix;

    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }

    /**
     * Получить информацию о контактах
     * 
     * @param string $lang
     * @return array
     */
    public function getContactInfo(string $lang): array
    {
        return Cache::remember("contact_info_{$lang}", 3600, function () use ($lang) {
            try {
                // Проверяем существование таблицы
                if (!$this->tableExists($this->dbPrefix . '_contacts_txt')) {
                    return $this->getDefaultContactInfo($lang);
                }
                
                $result = DB::table($this->dbPrefix . '_contacts_txt')
                    ->selectRaw("
                        image, 
                        title_{$lang} AS title, 
                        text_{$lang} AS text
                    ")
                    ->where('id', 1)
                    ->first();

                return $result ? (array) $result : $this->getDefaultContactInfo($lang);
            } catch (\Exception $e) {
                \Log::warning('Cannot get contact info from database: ' . $e->getMessage());
                return $this->getDefaultContactInfo($lang);
            }
        });
    }

    /**
     * Получить текст для формы обратной связи
     * 
     * @param string $lang
     * @return array
     */
    public function getFeedbackFormText(string $lang): array
    {
        return Cache::remember("feedback_form_text_{$lang}", 3600, function () use ($lang) {
            try {
                // Проверяем существование таблицы
                if (!$this->tableExists($this->dbPrefix . '_txt_blocks')) {
                    return $this->getDefaultFeedbackText($lang);
                }
                
                $result = DB::table($this->dbPrefix . '_txt_blocks')
                    ->selectRaw("text_{$lang} AS text")
                    ->where('id', 3)
                    ->first();

                return $result ? (array) $result : $this->getDefaultFeedbackText($lang);
            } catch (\Exception $e) {
                \Log::warning('Cannot get feedback form text from database: ' . $e->getMessage());
                return $this->getDefaultFeedbackText($lang);
            }
        });
    }
    
    /**
     * Получить информацию о контактах по умолчанию
     * 
     * @param string $lang
     * @return array
     */
    private function getDefaultContactInfo(string $lang): array
    {
        $title = $lang === 'uk' ? 'Контакти' : 'Контакты';
        $text = $lang === 'uk' ? 
            'Ми завжди раді відповісти на ваші запитання та допомогти з бронюванням квитків.' :
            'Мы всегда рады ответить на ваши вопросы и помочь с бронированием билетов.';
        
        return [
            'title' => $title,
            'text' => $text,
            'image' => 'default-contact.jpg',
        ];
    }
    
    /**
     * Получить текст формы по умолчанию
     * 
     * @param string $lang
     * @return array
     */
    private function getDefaultFeedbackText(string $lang): array
    {
        $text = $lang === 'uk' ? 
            'Заповніть форму і ми зв\'яжемося з вами найближчим часом.' :
            'Заполните форму и мы свяжемся с вами в ближайшее время.';
        
        return [
            'text' => $text,
        ];
    }

    /**
     * Сохранить обратную связь
     * 
     * @param array $data
     * @return bool
     */
    public function saveFeedback(array $data): bool
    {
        try {
            // Проверяем существование таблицы feedback, если нет - используем callback
            $tableName = $this->dbPrefix . '_feedback';
            
            if (!$this->tableExists($tableName)) {
                $tableName = $this->dbPrefix . '_callback';
                // Для таблицы callback используем другую структуру данных
                $callbackData = [
                    'date' => $data['created_at'] ?? now(),
                    'phone' => $data['phone'] ?? '',
                    'departure' => '',
                    'arrival' => '',
                    'message' => 'ФИО: ' . $data['name'] . ' | Email: ' . ($data['email'] ?? '') . ' | Сообщение: ' . $data['message'],
                ];
                return DB::table($tableName)->insert($callbackData);
            }
            
            // Добавляем дополнительную информацию для безопасности
            $data['ip_address'] = request()->ip();
            $data['user_agent'] = request()->userAgent();
            $data['status'] = 'new';

            return DB::table($tableName)->insert($data);
        } catch (\Exception $e) {
            \Log::error('Error saving feedback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверить существование таблицы
     * 
     * @param string $table
     * @return bool
     */
    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
