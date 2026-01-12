<?php

namespace App\Repository\Site;

use Illuminate\Support\Facades\DB;

class TranslationRepository
{
    private const TABLE_DICTIONARY = 'mt_dictionary';

    public function getTranslationDictionary($lang)
    {
        return DB::table(self::TABLE_DICTIONARY)
            ->select([
                'code', 'title_'.$lang
            ]) ->pluck('title_' . $lang, 'code')
            ->toArray();
    }

    /**
     * Alias for getTranslationDictionary for backward compatibility
     */
    public function getDictionary($lang)
    {
        return $this->getTranslationDictionary($lang);
    }

    public function getCountWithEmptyTranslation()
    {
        $query = DB::table(self::TABLE_DICTIONARY, 'd')
            ->select([
                "*"
            ])
            ->where(function ($query) {
                $query->whereColumn('d.title_ru', 'd.code')
                    ->orWhereColumn('d.title_uk', 'd.code');
            })
            ->whereNotNull('d.code')

            ;


        return $query->count();
    }

    public function getById($id)
    {
        $query = DB::table(self::TABLE_DICTIONARY, 'd')
            ->select([
                "*"
            ])
            ->where('d.id', '=', $id);

        return $query->get();
    }

    public function getWithEmptyTranslation()
    {
        $query = DB::table(self::TABLE_DICTIONARY, 'd')
            ->select([
                "*"
            ])
            ->where(function ($query) {
                $query->whereColumn('d.title_ru', 'd.code')
                    ->orWhereColumn('d.title_uk', 'd.code');
            })
            ->whereNotNull('d.code')
            ;


        return $query->get();
    }

    public function addEmptyTranslation($key)
    {
        $data = [
            'section_id' => 1,
            'code' => $key,
            'title_ru' => $key,
            'title_en' => $key,
            'title_uk' => $key,
            'comments' => 'Перевод на странице: ' .  url()->full(),
            'edit_by_user' => 1,
        ];

        DB::table(self::TABLE_DICTIONARY)->insert($data);
    }

    public function updateTranslation($id, $data)
    {
        $data = [
            'title_ru' => $data['title_ru'] ?? '',
            'title_en' => $data['title_en'] ?? '',
            'title_uk' => $data['title_uk'] ?? '',
            'comments' => $data['comments'] ?? ''
        ];

        DB::table(self::TABLE_DICTIONARY)
            ->where('id', $id)
            ->update($data);
    }
}
