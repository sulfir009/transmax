@php
    use App\Service\Site;
    use App\Helpers\LocaleHelper;
    
    $currentLocale = Site::lang();
    $supportedLocales = LocaleHelper::getSupportedLocales();
    
    $localeNames = [
        'ru' => 'РУС',
        'uk' => 'УКР', 
        'en' => 'ENG'
    ];
    
    $localeFull = [
        'ru' => 'Русский',
        'uk' => 'Українська',
        'en' => 'English'
    ];
@endphp

<div class="language-switcher" {{ $attributes }}>
    <div class="language-switcher__current">
        <span class="language-switcher__flag">
            @if($currentLocale === 'uk')
                🇺🇦
            @elseif($currentLocale === 'en')
                🇬🇧
            @else
                🇷🇺
            @endif
        </span>
        <span class="language-switcher__name">{{ $localeNames[$currentLocale] ?? $currentLocale }}</span>
        <svg class="language-switcher__arrow" width="12" height="8" viewBox="0 0 12 8" fill="none">
            <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2"/>
        </svg>
    </div>
    
    <ul class="language-switcher__dropdown">
        @foreach($supportedLocales as $locale)
            @if($locale !== $currentLocale)
                <li>
                    <a href="{{ Site::switchLanguageUrl($locale) }}" 
                       class="language-switcher__item"
                       data-locale="{{ $locale }}">
                        <span class="language-switcher__flag">
                            @if($locale === 'uk')
                                🇺🇦
                            @elseif($locale === 'en')
                                🇬🇧
                            @else
                                🇷🇺
                            @endif
                        </span>
                        <span class="language-switcher__name">{{ $localeFull[$locale] ?? $locale }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</div>

<style>
.language-switcher {
    position: relative;
    display: inline-block;
}

.language-switcher__current {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
}

.language-switcher__current:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

.language-switcher__flag {
    font-size: 20px;
    line-height: 1;
}

.language-switcher__name {
    font-weight: 500;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.language-switcher__arrow {
    transition: transform 0.3s ease;
}

.language-switcher:hover .language-switcher__arrow {
    transform: rotate(180deg);
}

.language-switcher__dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    min-width: 180px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    list-style: none;
    margin: 0;
    padding: 8px 0;
    z-index: 1000;
}

.language-switcher:hover .language-switcher__dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.language-switcher__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s ease;
}

.language-switcher__item:hover {
    background: #f5f5f5;
}

.language-switcher__dropdown .language-switcher__name {
    color: #333;
    text-transform: none;
    font-size: 14px;
    letter-spacing: normal;
}

/* Dark theme support */
.dark .language-switcher__current {
    border-color: rgba(255, 255, 255, 0.2);
}

.dark .language-switcher__current:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.3);
}

.dark .language-switcher__dropdown {
    background: #1a1a1a;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.dark .language-switcher__item {
    color: #fff;
}

.dark .language-switcher__item:hover {
    background: rgba(255, 255, 255, 0.1);
}

.dark .language-switcher__dropdown .language-switcher__name {
    color: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const switcher = document.querySelector('.language-switcher');
    if (!switcher) return;
    
    const current = switcher.querySelector('.language-switcher__current');
    const dropdown = switcher.querySelector('.language-switcher__dropdown');
    
    // Toggle dropdown on click (for mobile)
    current.addEventListener('click', function(e) {
        e.stopPropagation();
        switcher.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        switcher.classList.remove('active');
    });
    
    // Handle language switch
    const languageLinks = dropdown.querySelectorAll('.language-switcher__item');
    languageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Optionally add loading state
            this.style.opacity = '0.5';
            this.style.pointerEvents = 'none';
        });
    });
});

// Mobile styles
const style = document.createElement('style');
style.textContent = `
    @media (max-width: 768px) {
        .language-switcher.active .language-switcher__dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .language-switcher.active .language-switcher__arrow {
            transform: rotate(180deg);
        }
    }
`;
document.head.appendChild(style);
</script>
