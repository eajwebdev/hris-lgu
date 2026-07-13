@extends('layouts.master')

@section('body')
<style>
    .interview-leaderboard {
        --board-green: #16804b;
        --board-dark: #13251c;
        --board-muted: #68756e;
        --board-surface: #f4f7f5;
        padding-bottom: 24px;
    }

    .interview-board-header,
    .interview-live-summary,
    .interview-rank-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 10px 32px rgba(20, 43, 31, .08);
    }

    .interview-board-header {
        align-items: center;
        background: linear-gradient(135deg, #153f2b, #16804b);
        color: #fff;
        display: flex;
        gap: 18px;
        justify-content: space-between;
        margin-bottom: 16px;
        overflow: hidden;
        padding: 22px 24px;
        position: relative;
    }

    .interview-board-header::after {
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
        content: "";
        height: 180px;
        position: absolute;
        right: -50px;
        top: -90px;
        width: 180px;
    }

    .interview-board-header h1 {
        font-size: 1.35rem;
        font-weight: 800;
        margin: 2px 0 0;
    }

    .interview-board-header .btn {
        border-radius: 10px;
        position: relative;
        z-index: 1;
    }

    .interview-live-summary {
        align-items: center;
        background: #fff;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        margin-bottom: 18px;
        padding: 15px 18px;
    }

    .interview-live-indicator {
        align-items: center;
        background: #eaf8f0;
        border-radius: 999px;
        color: var(--board-green);
        display: inline-flex;
        font-size: .78rem;
        font-weight: 800;
        gap: 7px;
        padding: 7px 11px;
        text-transform: uppercase;
    }

    .interview-live-dot {
        animation: interviewPulse 1.5s infinite;
        background: #22a562;
        border-radius: 50%;
        height: 8px;
        width: 8px;
    }

    .interview-ranking-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .interview-rank-card {
        background: #fff;
        border: 1px solid #e6ece8;
        overflow: hidden;
        padding: 13px;
        position: relative;
        transition: box-shadow .25s ease, border-color .25s ease, transform .5s ease;
        will-change: transform;
    }

    .interview-rank-card.score-updated .interview-total-score { animation: interviewScoreFlash .7s ease; }
    .interview-rank-card.is-entering { animation: interviewCardEnter .45s ease both; }

    .interview-rank-card.is-active {
        border-color: #35a66d;
        box-shadow: 0 12px 34px rgba(22, 128, 75, .17);
    }

    .interview-rank-card.is-active::before {
        background: var(--board-green);
        content: "";
        height: 100%;
        left: 0;
        position: absolute;
        top: 0;
        width: 5px;
    }

    .interview-card-top {
        align-items: center;
        display: flex;
        gap: 10px;
    }

    .interview-avatar {
        align-items: center;
        background: linear-gradient(145deg, #edf3ef, #dce8e1);
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 3px 13px rgba(25, 65, 43, .12);
        color: #819188;
        display: flex;
        flex: 0 0 46px;
        font-size: 1.25rem;
        height: 46px;
        justify-content: center;
        width: 46px;
    }

    .interview-applicant-info {
        min-width: 0;
        padding-top: 0;
    }

    .interview-applicant-info h2 {
        color: var(--board-dark);
        font-size: .9rem;
        font-weight: 800;
        margin: 0 0 4px;
        overflow-wrap: anywhere;
    }

    .interview-applicant-number {
        color: var(--board-muted);
        font-size: .72rem;
    }

    .interview-rank-badge {
        align-items: center;
        background: #eef2f0;
        border-radius: 9px;
        color: #526159;
        display: flex;
        font-size: .72rem;
        font-weight: 800;
        justify-content: center;
        margin-left: auto;
        min-height: 34px;
        min-width: 38px;
        padding: 5px;
    }

    .interview-rank-card.rank-1 .interview-rank-badge {
        background: #fff2bf;
        color: #8a6500;
    }

    .interview-rank-card.rank-2 .interview-rank-badge {
        background: #e8edf1;
        color: #52616c;
    }

    .interview-rank-card.rank-3 .interview-rank-badge {
        background: #f5dfcf;
        color: #8c532d;
    }

    .interview-total-row {
        align-items: flex-end;
        border-bottom: 1px solid #edf1ee;
        display: flex;
        justify-content: space-between;
        margin: 11px 0 10px;
        padding-bottom: 10px;
    }

    .interview-total-score {
        color: var(--board-green);
        font-size: 1.55rem;
        font-weight: 900;
        line-height: 1;
    }

    .interview-score-grid {
        display: grid;
        gap: 5px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .interview-score-item {
        background: var(--board-surface);
        border-radius: 8px;
        padding: 6px 4px;
        text-align: center;
    }

    .interview-score-item small {
        color: var(--board-muted);
        display: block;
        font-size: .58rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .interview-score-item strong {
        color: var(--board-dark);
        display: block;
        font-size: .82rem;
    }

    .interview-score-item.completed {
        background: #e8f6ee;
    }

    .interview-score-item.pending {
        background: #fff7db;
    }

    .interview-progress-row {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-top: 9px;
    }

    .interview-progress-track {
        background: #e7ece9;
        border-radius: 999px;
        flex: 1;
        height: 5px;
        overflow: hidden;
    }

    .interview-progress-fill {
        background: linear-gradient(90deg, #16804b, #37ad72);
        border-radius: inherit;
        height: 100%;
        transition: width .4s ease;
    }

    .interview-cast-tag {
        background: #16804b;
        border-radius: 999px;
        color: #fff;
        display: none;
        font-size: .68rem;
        font-weight: 800;
        padding: 5px 9px;
        position: absolute;
        right: 13px;
        text-transform: uppercase;
        top: 58px;
    }

    .interview-rank-card.is-active .interview-cast-tag {
        display: inline-block;
    }

    .interview-empty-state {
        background: #fff;
        border: 1px dashed #cfd9d3;
        border-radius: 18px;
        color: var(--board-muted);
        grid-column: 1 / -1;
        padding: 36px 20px;
        text-align: center;
    }

    @keyframes interviewPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(34, 165, 98, .35); }
        50% { box-shadow: 0 0 0 6px rgba(34, 165, 98, 0); }
    }

    @keyframes interviewScoreFlash {
        0% { background:#fff1a8; border-radius:8px; transform:scale(1); }
        45% { background:#fff1a8; transform:scale(1.12); }
        100% { background:transparent; transform:scale(1); }
    }

    @keyframes interviewCardEnter {
        from { opacity:0; transform:translateY(14px) scale(.97); }
        to { opacity:1; transform:translateY(0) scale(1); }
    }

    @media (max-width: 1199.98px) {
        .interview-ranking-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 991.98px) {
        .interview-ranking-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575.98px) {
        .interview-leaderboard {
            padding-left: 8px;
            padding-right: 8px;
        }

        .interview-board-header,
        .interview-live-summary {
            align-items: stretch;
            flex-direction: column;
        }

        .interview-board-header .btn { width: 100%; }
        .interview-ranking-grid { grid-template-columns: 1fr; }
        .interview-rank-card { padding: 12px; }
        .interview-avatar {
            flex-basis: 44px;
            font-size: 1.2rem;
            height: 44px;
            width: 44px;
        }
        .interview-cast-tag {
            right: 12px;
            top: 55px;
        }
    }
</style>

<div class="container-fluid interview-leaderboard">
    <div class="interview-board-header">
        <div>
            <small class="text-white-50">Interview Candidate Results</small>
            <h1><i class="fas fa-ranking-star mr-2"></i>{{ $interview->job->title ?? 'Applicant Ranking' }}</h1>
            @if($interview->job && $interview->job->plantilla_item_no)
                <small class="text-white-50">{{ $interview->job->plantilla_item_no }}</small>
            @endif
        </div>
        <a href="{{ route('interviewEvaluationShow', $interview->id) }}" class="btn btn-light">
            <i class="fas fa-arrow-left mr-1"></i> Back to Interview
        </a>
    </div>

    <div class="interview-live-summary">
        <div>
            <small class="text-muted">Scoring access</small>
            <strong class="d-block">Candidates are ranked by weighted final rating.</strong>
        </div>
        <span class="interview-live-indicator">
            <span class="interview-live-dot"></span>
            Current results
        </span>
    </div>

    <div id="rankingBoard" class="interview-ranking-grid">
        <div class="interview-empty-state">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <div>Loading applicant rankings...</div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {
    const board = document.getElementById('rankingBoard');
    const previousScores = {};
    let rankingRequestRunning = false;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function rankLabel(rank) {
        if (rank === 1) return '<i class="fas fa-crown"></i>&nbsp; 1';
        if (rank === 2) return '<i class="fas fa-medal"></i>&nbsp; 2';
        if (rank === 3) return '<i class="fas fa-award"></i>&nbsp; 3';
        return '#' + rank;
    }

    function cardMarkup(item, rank) {
        const panelCount = Number(item.panel_count || 0);
        const startedCount = Number(item.started_count || item.submitted_count || 0);
        const submittedCount = Number(item.submitted_count || 0);
        const progress = panelCount ? Math.min(100, Math.round((startedCount / panelCount) * 100)) : 0;
        const completionClass = item.completed ? 'completed' : 'pending';

        return `
            <span class="interview-cast-tag">Currently cast</span>
            <div class="interview-card-top">
                <div class="interview-avatar" aria-label="Applicant profile placeholder">
                    <i class="fas fa-user"></i>
                </div>
                <div class="interview-applicant-info">
                    <h2>${escapeHtml(item.name)}</h2>
                    <div class="interview-applicant-number">${escapeHtml(item.app_number)}</div>
                </div>
                <div class="interview-rank-badge">${rankLabel(rank)}</div>
            </div>
            <div class="interview-total-row">
                <div>
                    <small class="text-muted d-block">Final rating</small>
                    <strong class="interview-total-score">${escapeHtml(item.total_score)}</strong>
                </div>
                <small class="text-muted">Rank ${rank}</small>
            </div>
            <div class="interview-score-grid">
                <div class="interview-score-item"><small>Qualifications</small><strong>${escapeHtml(item.qualification_score)}</strong></div>
                <div class="interview-score-item"><small>Interview</small><strong>${escapeHtml(item.interview_score)}</strong></div>
                <div class="interview-score-item"><small>Potential</small><strong>${escapeHtml(item.potential_score)}</strong></div>
            </div>
            <div class="interview-progress-row">
                <div class="interview-progress-track">
                    <div class="interview-progress-fill" style="width: ${progress}%"></div>
                </div>
                <small class="text-muted">${item.completed ? 'Complete' : submittedCount + ' submitted'}</small>
            </div>
        `;
    }

    function renderRanking(response) {
        if (!response.success || !response.data || response.data.length === 0) {
            board.innerHTML = `
                <div class="interview-empty-state">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <div>No interview ratings are available yet.</div>
                </div>`;
            return;
        }

        const oldPositions = {};
        board.querySelectorAll('.interview-rank-card').forEach(function (card) {
            oldPositions[card.dataset.applicationId] = card.getBoundingClientRect();
        });

        response.data.forEach(function (item, index) {
            const rank = index + 1;
            const id = String(item.application_id);
            let card = board.querySelector('[data-application-id="' + id + '"]');
            const isNewCard = !card;

            if (!card) {
                card = document.createElement('article');
                card.className = 'interview-rank-card';
                card.dataset.applicationId = id;
            }

            card.className = 'interview-rank-card rank-' + rank;
            if (item.is_active) card.classList.add('is-active');
            if (isNewCard) card.classList.add('is-entering');

            const newScore = Number(item.total_raw);
            if (Object.prototype.hasOwnProperty.call(previousScores, id)
                && previousScores[id] !== newScore) {
                card.classList.add('score-updated');
            }
            previousScores[id] = newScore;
            card.innerHTML = cardMarkup(item, rank);
            board.appendChild(card);
        });

        board.querySelectorAll('.interview-rank-card').forEach(function (card) {
            const stillExists = response.data.some(function (item) {
                return String(item.application_id) === card.dataset.applicationId;
            });
            if (!stillExists) card.remove();
        });

        board.querySelectorAll('.interview-empty-state').forEach(function (empty) {
            empty.remove();
        });

        board.querySelectorAll('.interview-rank-card').forEach(function (card) {
            const oldPosition = oldPositions[card.dataset.applicationId];
            if (!oldPosition) return;

            const newPosition = card.getBoundingClientRect();
            const deltaX = oldPosition.left - newPosition.left;
            const deltaY = oldPosition.top - newPosition.top;

            if (deltaX || deltaY) {
                card.style.transition = 'none';
                card.style.transform = 'translate(' + deltaX + 'px, ' + deltaY + 'px)';
                card.getBoundingClientRect();
                requestAnimationFrame(function () {
                    card.style.transition = 'transform .55s cubic-bezier(.22,.8,.28,1), box-shadow .25s ease, border-color .25s ease';
                    card.style.transform = '';
                });
            }
        });
    }

    function loadConsolidatedRanking() {
        if (rankingRequestRunning || document.hidden) return;
        rankingRequestRunning = true;
        $.ajax({
            url: "{{ route('interviewConsolidatedData', $interview->id) }}",
            method: 'GET',
            cache: false
        }).done(renderRanking).always(function () {
            rankingRequestRunning = false;
        });
    }

    loadConsolidatedRanking();
    window.setInterval(loadConsolidatedRanking, 500);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) loadConsolidatedRanking();
    });
});
</script>
@endsection
