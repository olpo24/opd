<div class="card">
    <div class="view-header">
        <div>
            <h2 class="view-title">Lesezeichen Übersicht</h2>
            <p class="view-subtitle">Verwalte deine gespeicherten Links und Werkzeuge.</p>
        </div>
        <button class="btn" onclick="openAddBookmarkModal()">+ Neues Lesezeichen</button>
    </div>

    <div class="filter-bar">
        <div class="filter-search-wrap">
            <input type="text" id="bm-detail-search" placeholder="🔍 Lesezeichen durchsuchen...">
        </div>
        <div id="bm-detail-category-pills" class="filter-pills"></div>
    </div>

    <div id="bm-detail-cards-grid" class="bm-cards-grid"></div>
</div>
