<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dbPrefix = env('DB_PREFIX', 'mt');
        
        // Check if FAQ info exists
        $faqInfoTable = $dbPrefix . '_faq_txt';
        $faqInfoExists = DB::table($faqInfoTable)->exists();
        
        if (!$faqInfoExists) {
            DB::table($faqInfoTable)->insert([
                'title_ru' => 'Часто задаваемые вопросы',
                'title_uk' => 'Часті запитання',
                'title_en' => 'Frequently Asked Questions',
                'text_ru' => 'Здесь вы найдете ответы на самые популярные вопросы о наших услугах. Если вы не нашли ответ на свой вопрос, свяжитесь с нашей службой поддержки.',
                'text_uk' => 'Тут ви знайдете відповіді на найпопулярніші запитання про наші послуги. Якщо ви не знайшли відповідь на своє запитання, зв\'яжіться з нашою службою підтримки.',
                'text_en' => 'Here you will find answers to the most popular questions about our services. If you haven\'t found an answer to your question, please contact our support team.',
                'image' => 'faq-image.jpg'
            ]);
        }
        
        // Sample FAQ data
        $faqTable = $dbPrefix . '_faq';
        $faqs = [
            [
                'question_ru' => 'Как забронировать билет на автобус?',
                'question_uk' => 'Як забронювати квиток на автобус?',
                'question_en' => 'How to book a bus ticket?',
                'answer_ru' => 'Для бронирования билета выберите маршрут, дату поездки и количество пассажиров на главной странице. После этого выберите подходящий рейс и заполните данные пассажиров. Оплатить билет можно онлайн или наличными при посадке.',
                'answer_uk' => 'Для бронювання квитка оберіть маршрут, дату поїздки та кількість пасажирів на головній сторінці. Після цього оберіть відповідний рейс та заповніть дані пасажирів. Оплатити квиток можна онлайн або готівкою при посадці.',
                'answer_en' => 'To book a ticket, select the route, travel date and number of passengers on the main page. Then choose a suitable flight and fill in the passenger details. You can pay for the ticket online or in cash when boarding.',
                'active' => 1,
                'sort' => 100
            ],
            [
                'question_ru' => 'Можно ли вернуть или обменять билет?',
                'question_uk' => 'Чи можна повернути або обміняти квиток?',
                'question_en' => 'Can I return or exchange a ticket?',
                'answer_ru' => 'Да, вы можете вернуть или обменять билет не позднее чем за 24 часа до отправления автобуса. Для возврата билета свяжитесь с нашей службой поддержки. При возврате может взиматься комиссия согласно правилам перевозчика.',
                'answer_uk' => 'Так, ви можете повернути або обміняти квиток не пізніше ніж за 24 години до відправлення автобуса. Для повернення квитка зв\'яжіться з нашою службою підтримки. При поверненні може стягуватися комісія згідно з правилами перевізника.',
                'answer_en' => 'Yes, you can return or exchange a ticket no later than 24 hours before the bus departure. To return a ticket, contact our support service. A fee may be charged for the return according to the carrier\'s rules.',
                'active' => 1,
                'sort' => 90
            ],
            [
                'question_ru' => 'Какой багаж можно взять с собой?',
                'question_uk' => 'Який багаж можна взяти з собою?',
                'question_en' => 'What luggage can I take with me?',
                'answer_ru' => 'Каждый пассажир может бесплатно провезти:\n- Одно место багажа весом до 20 кг в багажном отделении\n- Ручную кладь весом до 5 кг в салоне автобуса\n\nДополнительный багаж оплачивается согласно тарифам перевозчика.',
                'answer_uk' => 'Кожен пасажир може безкоштовно провезти:\n- Одне місце багажу вагою до 20 кг в багажному відділенні\n- Ручну поклажу вагою до 5 кг в салоні автобуса\n\nДодатковий багаж оплачується згідно з тарифами перевізника.',
                'answer_en' => 'Each passenger can carry free of charge:\n- One piece of luggage weighing up to 20 kg in the luggage compartment\n- Hand luggage weighing up to 5 kg in the bus cabin\n\nAdditional luggage is paid according to the carrier\'s rates.',
                'active' => 1,
                'sort' => 80
            ],
            [
                'question_ru' => 'Есть ли WiFi в автобусах?',
                'question_uk' => 'Чи є WiFi в автобусах?',
                'question_en' => 'Is there WiFi on the buses?',
                'answer_ru' => 'Большинство наших автобусов оборудованы бесплатным WiFi. Однако доступность и качество соединения может варьироваться в зависимости от маршрута и местности. Точную информацию о наличии WiFi в конкретном автобусе вы можете уточнить при бронировании.',
                'answer_uk' => 'Більшість наших автобусів обладнані безкоштовним WiFi. Однак доступність та якість з\'єднання може варіюватися залежно від маршруту та місцевості. Точну інформацію про наявність WiFi в конкретному автобусі ви можете уточнити при бронюванні.',
                'answer_en' => 'Most of our buses are equipped with free WiFi. However, availability and connection quality may vary depending on the route and terrain. You can check the exact information about WiFi availability on a specific bus when booking.',
                'active' => 1,
                'sort' => 70
            ],
            [
                'question_ru' => 'Как узнать статус моего рейса?',
                'question_uk' => 'Як дізнатися статус мого рейсу?',
                'question_en' => 'How can I check my flight status?',
                'answer_ru' => 'Вы можете проверить статус вашего рейса в личном кабинете на нашем сайте или связавшись с нашей службой поддержки. За 24 часа до отправления вы получите SMS-уведомление с информацией о рейсе.',
                'answer_uk' => 'Ви можете перевірити статус вашого рейсу в особистому кабінеті на нашому сайті або зв\'язавшись з нашою службою підтримки. За 24 години до відправлення ви отримаєте SMS-повідомлення з інформацією про рейс.',
                'answer_en' => 'You can check your flight status in your personal account on our website or by contacting our support service. 24 hours before departure, you will receive an SMS notification with flight information.',
                'active' => 1,
                'sort' => 60
            ]
        ];
        
        foreach ($faqs as $faq) {
            DB::table($faqTable)->insert($faq);
        }
    }
}
