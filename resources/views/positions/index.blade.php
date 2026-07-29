@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="rec-page">

        <div class="rec-head">
            <div>
                <h1>Position Descriptions</h1>
                <div class="sub">
                    DBM-CSC Form No. 1 (Revised 2017). One standing description per plantilla item,
                    reused by every posting of that item.
                </div>
            </div>
            <div class="rec-actions">
                <a href="{{ route('psbMembers') }}" class="rec-btn">
                    <i class="fas fa-user-tie"></i> Selection Board
                </a>
                <a href="{{ route('positionDescriptionCreate') }}" class="rec-btn primary">
                    <i class="fas fa-plus"></i> New Position Description
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
        @endif

        <div class="rec-card">
            <header>
                <span class="n"><i class="fas fa-list"></i></span>
                <h2>All descriptions</h2>
                <span class="hint">{{ $descriptions->total() }} on file</span>
                <form method="GET" class="ml-auto" style="margin-left:auto;">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search title, item no. or office"
                           class="form-control form-control-sm" style="min-width:260px;">
                </form>
            </header>

            <div class="body" style="padding:0;">
                @if($descriptions->isEmpty())
                    <div class="rec-empty">
                        <div class="big"><i class="far fa-file-alt"></i></div>
                        @if($search !== '')
                            No position description matches &ldquo;{{ $search }}&rdquo;.
                        @else
                            No position descriptions yet. Create one to describe a plantilla item.
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:.87rem;">
                            <thead>
                                <tr>
                                    <th>Position title</th>
                                    <th style="width:120px;">Item no.</th>
                                    <th style="width:70px;">SG</th>
                                    <th>Office</th>
                                    <th class="text-center" style="width:80px;">Duties</th>
                                    <th class="text-center" style="width:90px;">Postings</th>
                                    <th class="text-center" style="width:90px;">Status</th>
                                    <th style="width:170px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($descriptions as $d)
                                    <tr>
                                        <td>
                                            <a href="{{ route('positionDescriptionEdit', $d->id) }}" style="font-weight:600;">
                                                {{ $d->full_title }}
                                            </a>
                                        </td>
                                        <td>{{ $d->item_number ?: '—' }}</td>
                                        <td>{{ $d->salary_grade ?: '—' }}</td>
                                        <td>{{ $d->bureau_office ?: '—' }}</td>
                                        <td class="text-center">{{ $d->duties_count }}</td>
                                        <td class="text-center">{{ $d->postings_count }}</td>
                                        <td class="text-center">
                                            <span class="rec-pill {{ $d->status }}">{{ $d->status }}</span>
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('positionDescriptionPrint', $d->id) }}" target="_blank"
                                               class="rec-btn" title="Print DBM-CSC Form No. 1">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ route('positionDescriptionEdit', $d->id) }}" class="rec-btn">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form method="POST" action="{{ route('positionDescriptionDelete', $d->id) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ $d->postings_count > 0
                                                        ? 'This description is used by '.$d->postings_count.' posting(s), so it will be archived rather than deleted. Continue?'
                                                        : 'Delete this position description?' }}');">
                                                @csrf
                                                <button type="submit" class="rec-btn ghost-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($descriptions->hasPages())
                        <div style="padding:.8rem 1rem;">{{ $descriptions->links() }}</div>
                    @endif
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
