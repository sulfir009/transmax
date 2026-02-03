@php
    $locale = app()->getLocale();
    $messages = [
        'ru' => [
            'title' => 'Страница не найдена',
            'button' => 'Вернуться на главную',
        ],
        'uk' => [
            'title' => 'Сторінку не знайдено',
            'button' => 'Повернутись на Головну',
        ],
        'en' => [
            'title' => 'Page not found',
            'button' => 'Back to Home',
        ],
    ];
    $text = $messages[$locale] ?? $messages['ru'];
@endphp

@extends('layout.app')

@section('content')
    <div class="content">
        <div class="page_content_wrapper">
            <div class="container">
                <div class="text-center" style="padding: 80px 0;">
                    <h1 class="h2_title">{{ $text['title'] }}</h1>
                    <p class="par" style="margin-top: 16px;">
                        {{ $text['title'] }}
                    </p>
                    <a href="{{ \App\Helpers\LocaleHelper::localizedRoute('main') }}"
                       class="blue_btn h4_title"
                       style="display:inline-block;margin-top:24px;">
                        {{ $text['button'] }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection