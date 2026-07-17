<div class="guide-portal" id="guidePortal">

    <button type="button" class="guide-mobile-toggle" aria-label="Toggle guide menu"
        onclick="document.getElementById('guidePortal').classList.toggle('sidebar-open')">
        <i class="bi bi-list"></i>
    </button>
    <div class="guide-backdrop"
        onclick="document.getElementById('guidePortal').classList.remove('sidebar-open')"></div>

    {{-- ── Left panel ─────────────────────────────────── --}}
    <aside class="guide-sidebar">
        <a href="{{ route('dashboard') }}" class="guide-back">
            <i class="bi bi-arrow-left"></i>
            {{ $lang === 'pr' ? 'Voltar ao painel' : 'Back to dashboard' }}
        </a>

        <div class="guide-search">
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="{{ $lang === 'pr' ? 'Pesquisar guias...' : 'Search guides...' }}">
            <i class="bi bi-search"></i>
        </div>

        <div class="guide-lang">
            <button type="button" class="{{ $lang === 'en' ? 'active' : '' }}" wire:click="setLang('en')">
                <svg width="17" height="17" viewBox="0 0 32 32" aria-hidden="true">
                    <clipPath id="gv-flag-uk"><circle cx="16" cy="16" r="16"/></clipPath>
                    <g clip-path="url(#gv-flag-uk)">
                        <rect width="32" height="32" fill="#012169"/>
                        <path d="M0 0 L32 32 M32 0 L0 32" stroke="#fff" stroke-width="6"/>
                        <path d="M0 0 L32 32 M32 0 L0 32" stroke="#C8102E" stroke-width="3"/>
                        <path d="M16 0 V32 M0 16 H32" stroke="#fff" stroke-width="10"/>
                        <path d="M16 0 V32 M0 16 H32" stroke="#C8102E" stroke-width="5"/>
                    </g>
                </svg>
                English
            </button>
            <button type="button" class="{{ $lang === 'pr' ? 'active' : '' }}" wire:click="setLang('pr')">
                <svg width="17" height="17" viewBox="0 0 32 32" aria-hidden="true">
                    <clipPath id="gv-flag-pt"><circle cx="16" cy="16" r="16"/></clipPath>
                    <g clip-path="url(#gv-flag-pt)">
                        <rect width="32" height="32" fill="#D80027"/>
                        <rect width="13" height="32" fill="#046A38"/>
                        <circle cx="13" cy="16" r="5.5" fill="#FFDA44"/>
                    </g>
                </svg>
                Português
            </button>
        </div>

        <nav class="guide-nav">
            @forelse($categories as $category)
            <div class="guide-cat-label">{{ $category->getName($lang) }}</div>

            @foreach($category->guides as $g)
            <button type="button" wire:click="selectGuide({{ $g->id }})" wire:key="guide-nav-{{ $g->id }}"
                class="guide-nav-item {{ $selectedGuideId === $g->id ? 'active' : '' }}">
                {{ $g->getName($lang) }}
            </button>
            @endforeach
            @empty
            <div class="guide-nav-empty">
                <i class="bi bi-search"></i>
                @if(trim($search) !== '')
                    {{ $lang === 'pr' ? 'Nenhum guia corresponde à sua pesquisa.' : 'No guides match your search.' }}
                @else
                    {{ $lang === 'pr' ? 'Nenhum guia disponível ainda.' : 'No guides available yet.' }}
                @endif
            </div>
            @endforelse
        </nav>
    </aside>

    {{-- ── Content ────────────────────────────────────── --}}
    <main class="guide-main">
        @if($guide)

        @if($guide->category)
        <div class="guide-cat-crumb">{{ $guide->category->getName($lang) }}</div>
        @endif

        <h1 class="guide-title">{{ $guide->getName($lang) }}</h1>

        @forelse($guide->sections as $section)
        <div class="guide-section-card" wire:key="guide-section-{{ $section->id }}">
            <h2 class="guide-section-title">{{ $section->getName($lang) }}</h2>

            @forelse($section->blocks as $block)
            <div class="guide-block" wire:key="guide-block-{{ $block->id }}">
                @if($block->getTitle($lang))
                <h3 class="guide-block-title">{{ $block->getTitle($lang) }}</h3>
                @endif

                @if($block->getSubtitle($lang))
                <p class="guide-block-subtitle">{{ $block->getSubtitle($lang) }}</p>
                @endif

                @if($block->getContent($lang))
                <div class="ql-snow">
                    <div class="ql-editor guide-rich">
                        {!! $block->getContent($lang) !!}
                    </div>
                </div>
                @endif
            </div>
            @empty
            <p class="guide-block-subtitle mb-0">
                {{ $lang === 'pr' ? 'Nenhum conteúdo nesta seção ainda.' : 'No content in this section yet.' }}
            </p>
            @endforelse
        </div>
        @empty
        <div class="guide-section-card">
            <p class="guide-block-subtitle mb-0">
                {{ $lang === 'pr' ? 'Este guia ainda não tem conteúdo.' : 'This guide has no content yet.' }}
            </p>
        </div>
        @endforelse

        @else
        <div class="guide-welcome">
            <div class="guide-welcome-icon">
                <i class="bi bi-journal-text"></i>
            </div>
            <h2>{{ $lang === 'pr' ? 'Bem-vindo ao Guia do Usuário' : 'Welcome to the User Guide' }}</h2>
            <p>
                {{ $lang === 'pr'
                    ? 'Selecione um guia no menu à esquerda para ver as instruções passo a passo.'
                    : 'Select a guide from the menu on the left to see step-by-step instructions.' }}
            </p>
        </div>
        @endif
    </main>

    @script
    <script>
        $wire.on('guide-selected', () => {
            document.querySelector('.guide-main')?.scrollTo({ top: 0 });
            document.getElementById('guidePortal')?.classList.remove('sidebar-open');
        });
    </script>
    @endscript
</div>
