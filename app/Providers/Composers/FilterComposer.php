<?php

namespace App\Providers\Composers;

use App\Repository\FilterRepository;
use App\Service\Site;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FilterComposer
{
    protected $filterRepository;
    protected $request;
    protected $lang;

    public function __construct(FilterRepository $filterRepository, Request $request)
    {
        $this->filterRepository = $filterRepository;
        $this->request = $request;

        $this->lang = Site::lang();;

        // Устанавливаем язык для репозитория
        $this->filterRepository->setLanguage($this->lang);
    }

    /**
     * Привязка данных к view
     */
    public function compose(View $view)
    {
        // Получаем значения фильтра
        $filterData = $this->getFilterData();

        // Получаем список городов
        $cities = $this->filterRepository->getActiveCities();

        // Если города не выбраны, устанавливаем по умолчанию
        if (!$filterData['departure']) {
            $filterData['departure'] = $this->filterRepository->getDefaultCity();
        }
        if (!$filterData['arrival']) {
            $filterData['arrival'] = $this->filterRepository->getDefaultCity();
        }

        // Формируем action URL для формы
        global $Router;
        $formAction = $Router ? $Router->writelink(76) : route('tickets.index');

        // Передаем данные во view
        $view->with([
            'filterDeparture' => $filterData['departure'],
            'filterArrival' => $filterData['arrival'],
            'filterDate' => $filterData['date'],
            'filterAdults' => $filterData['adults'],
            'filterKids' => $filterData['kids'],
            'cities' => $cities,
            'formAction' => $formAction,
            'lang' => $this->lang,
            'dictionary' => $this->getDictionary()
        ]);
    }

    /**
     * Получение данных фильтра из запроса и сессии
     */
    protected function getFilterData(): array
    {
        // Инициализация значений по умолчанию
        $filterData = [
            'departure' => 0,
            'arrival' => 0,
            'date' => 'today',
            'adults' => 1,
            'kids' => 0
        ];

        // Проверяем использование нативной сессии PHP (legacy)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Обработка POST запроса
        if ($this->request->isMethod('post')) {
            // Departure
            if ($this->request->has('departure') && (int)$this->request->input('departure') > 0) {
                $filterData['departure'] = (int)$this->request->input('departure');
                $_SESSION['filter']['departure'] = $filterData['departure'];
                Session::put('filter.departure', $filterData['departure']);
            }

            // Arrival
            if ($this->request->has('arrival') && (int)$this->request->input('arrival') > 0) {
                $filterData['arrival'] = (int)$this->request->input('arrival');
                $_SESSION['filter']['arrival'] = $filterData['arrival'];
                Session::put('filter.arrival', $filterData['arrival']);
            }

            // Date
            if ($this->request->has('date') && trim($this->request->input('date')) != '') {
                $dateInput = $this->request->input('date');
                $filterData['date'] = $this->sanitizeDate($dateInput);
                $_SESSION['filter']['date'] = $filterData['date'];
                Session::put('filter.date', $filterData['date']);
            }

            // Adults
            if ($this->request->has('adults')) {
                $filterData['adults'] = max(1, (int)$this->request->input('adults'));
                $_SESSION['filter']['adults'] = $filterData['adults'];
                Session::put('filter.adults', $filterData['adults']);
            }

            // Kids
            if ($this->request->has('kids')) {
                $filterData['kids'] = max(0, (int)$this->request->input('kids'));
                $_SESSION['filter']['kids'] = $filterData['kids'];
                Session::put('filter.kids', $filterData['kids']);
            }
        } else {
            // Получаем данные из сессии (сначала проверяем Laravel Session, затем PHP Session)

            // Departure
            if (Session::has('filter.departure')) {
                $filterData['departure'] = Session::get('filter.departure');
            } elseif (!empty($_SESSION['filter']['departure'])) {
                $filterData['departure'] = $_SESSION['filter']['departure'];
            } elseif ($this->request->has('departure')) {
                $filterData['departure'] = (int)$this->request->input('departure');
            }

            // Arrival
            if (Session::has('filter.arrival')) {
                $filterData['arrival'] = Session::get('filter.arrival');
            } elseif (!empty($_SESSION['filter']['arrival'])) {
                $filterData['arrival'] = $_SESSION['filter']['arrival'];
            } elseif ($this->request->has('arrival')) {
                $filterData['arrival'] = (int)$this->request->input('arrival');
            }

            // Date
            if (Session::has('filter.date')) {
                $filterData['date'] = Session::get('filter.date');
            } elseif (!empty($_SESSION['filter']['date'])) {
                $filterData['date'] = $_SESSION['filter']['date'];
            } elseif ($this->request->has('date')) {
                $filterData['date'] = $this->sanitizeDate($this->request->input('date'));
            }

            // Adults
            if (Session::has('filter.adults')) {
                $filterData['adults'] = Session::get('filter.adults');
            } elseif (!empty($_SESSION['filter']['adults'])) {
                $filterData['adults'] = $_SESSION['filter']['adults'];
            }

            // Kids
            if (Session::has('filter.kids')) {
                $filterData['kids'] = Session::get('filter.kids');
            } elseif (!empty($_SESSION['filter']['kids'])) {
                $filterData['kids'] = $_SESSION['filter']['kids'];
            }
        }

        // Преобразуем 'today' в текущую дату
        if ($filterData['date'] === 'today') {
            $filterData['date'] = date('Y-m-d');
        }

        return $filterData;
    }

    /**
     * Санитизация даты
     */
    protected function sanitizeDate(string $date): string
    {
        if ($date === 'today' || trim($date) === '') {
            return 'today';
        }

        $parts = explode('-', $date);
        if (count($parts) === 3) {
            return implode('-', array_map('intval', $parts));
        }

        return 'today';
    }

    /**
     * Получение словаря переводов
     */
    protected function getDictionary(): array
    {
        // Здесь можно вернуть переводы или использовать Laravel локализацию
        return [
            'MSG_ALL_ZVIDKI' => __('dictionary.MSG_ALL_ZVIDKI'),
            'MSG_ALL_KUDA' => __('dictionary.MSG_ALL_KUDA'),
            'MSG_ALL_KOLI' => __('dictionary.MSG_ALL_KOLI'),
            'MSG_ALL_PASAZHIRI' => __('dictionary.MSG_ALL_PASAZHIRI'),
            'MSG_ALL_DOROSLIH' => __('dictionary.MSG_ALL_DOROSLIH'),
            'MSG_ALL_DITEJ' => __('dictionary.MSG_ALL_DITEJ'),
            'MSG_ALL_DO_3_ROKIV_-_BEZKOSHTOVNO' => __('dictionary.MSG_ALL_DO_3_ROKIV_-_BEZKOSHTOVNO'),
            'MSG_ALL_ZNAJTI_KVITOK' => __('dictionary.MSG_ALL_ZNAJTI_KVITOK'),
        ];
    }
}
