<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-journal-text me-1"></i>
                        @if($guide)
                            {{ $guide->getName($lang) }}
                        @else
                            {{ $lang === 'pr' ? 'Guia' : 'Guide' }}
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        @if($guide)
                        <button type="button" class="btn btn-light text-muted" wire:click="backToList">
                            <i class="bi bi-arrow-left me-1"></i> {{ $lang === 'pr' ? 'Todos os guias' : 'All guides' }}
                        </button>
                        @endif

                        <div class="btn-group" role="group" aria-label="Language">
                            <button type="button" class="btn btn-sm {{ $lang === 'en' ? 'btn-primary' : 'btn-outline-primary' }}"
                                wire:click="setLang('en')">
                                English
                            </button>
                            <button type="button" class="btn btn-sm {{ $lang === 'pr' ? 'btn-primary' : 'btn-outline-primary' }}"
                                wire:click="setLang('pr')">
                                Português
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if(!$guide)
            {{-- Guide list grouped by category --}}
            @forelse($categories as $category)
            @if($category->guides->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-folder2-open me-1"></i> {{ $category->getName($lang) }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($category->guides as $g)
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded p-3 h-100 d-flex justify-content-between align-items-center"
                                role="button" wire:click="selectGuide({{ $g->id }})">
                                <div>
                                    <h6 class="mb-1">{{ $g->getName($lang) }}</h6>
                                    <span class="text-muted fs-12">
                                        {{ $g->sections_count }} {{ $lang === 'pr' ? ($g->sections_count === 1 ? 'seção' : 'seções') : Str::plural('section', $g->sections_count) }}
                                    </span>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @empty
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    {{ $lang === 'pr' ? 'Nenhum guia disponível ainda.' : 'No guides available yet.' }}
                </div>
            </div>
            @endforelse

            @else
            {{-- Guide content --}}
            <div class="row">
                <div class="col-lg-3 mb-3">
                    <div class="card position-sticky" style="top: 90px;">
                        <div class="card-header">
                            <h6 class="mb-0">{{ $lang === 'pr' ? 'Seções' : 'Sections' }}</h6>
                        </div>
                        <div class="card-body p-2">
                            @if($guide->sections->isEmpty())
                            <p class="text-muted fs-13 mb-0 px-2 py-1">
                                {{ $lang === 'pr' ? 'Nenhuma seção ainda.' : 'No sections yet.' }}
                            </p>
                            @else
                            <ul class="nav flex-column">
                                @foreach($guide->sections as $section)
                                <li class="nav-item">
                                    <a class="nav-link px-2 py-2 d-flex align-items-center gap-2"
                                        href="#guide-section-{{ $section->id }}">
                                        <span class="badge bg-light-primary rounded-pill">{{ $loop->iteration }}</span>
                                        {{ $section->getName($lang) }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    @forelse($guide->sections as $section)
                    <div class="card mb-3" id="guide-section-{{ $section->id }}">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-pill">{{ $loop->iteration }}</span>
                            <h5 class="mb-0">{{ $section->getName($lang) }}</h5>
                        </div>
                        <div class="card-body">
                            @forelse($section->blocks as $block)
                            <div class="{{ $loop->last ? '' : 'border-bottom pb-4 mb-4' }}">
                                @if($block->getTitle($lang))
                                <h5 class="mb-1">{{ $block->getTitle($lang) }}</h5>
                                @endif
                                @if($block->getSubtitle($lang))
                                <p class="text-muted mb-2">{{ $block->getSubtitle($lang) }}</p>
                                @endif
                                @if($block->getContent($lang))
                                <div class="ql-snow">
                                    <div class="ql-editor p-0" style="min-height:auto;">
                                        {!! $block->getContent($lang) !!}
                                    </div>
                                </div>
                                @endif
                            </div>
                            @empty
                            <p class="text-muted mb-0">
                                {{ $lang === 'pr' ? 'Nenhum conteúdo nesta seção ainda.' : 'No content in this section yet.' }}
                            </p>
                            @endforelse
                        </div>
                    </div>
                    @empty
                    <div class="card">
                        <div class="card-body text-center text-muted py-5">
                            <i class="bi bi-journal fs-1 d-block mb-2"></i>
                            {{ $lang === 'pr' ? 'Este guia ainda não tem conteúdo.' : 'This guide has no content yet.' }}
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
