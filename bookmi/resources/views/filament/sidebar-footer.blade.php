@auth
<div class="bookmi-sidebar-footer">
    {{-- Séparateur --}}
    <div style="height:1px; background:rgba(255,255,255,0.1); margin: 0 1rem 0.75rem;"></div>

    {{-- Info utilisateur --}}
    <div class="bookmi-user-row">
        <div class="bookmi-avatar">
            {{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? 'B', 0, 1)) }}
        </div>
        <div class="bookmi-user-info">
            <div class="bookmi-user-name">
                {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
            </div>
            <div class="bookmi-user-role">Administrateur</div>
        </div>
    </div>

    {{-- Bouton Déconnexion --}}
    <form method="POST" action="{{ filament()->getLogoutUrl() }}" style="padding: 0 0.75rem 1rem;">
        @csrf
        <button type="submit" class="bookmi-logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Déconnexion
        </button>
    </form>
</div>

<style>
.bookmi-sidebar-footer {
    margin-top: auto;
}

.bookmi-user-row {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0 0.75rem 0.625rem;
}

.bookmi-avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: rgba(33, 150, 243, 0.25);
    border: 1.5px solid rgba(33, 150, 243, 0.5);
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: 'Nunito', sans-serif;
}

.bookmi-user-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.bookmi-user-name {
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-family: 'Nunito', sans-serif;
}

.bookmi-user-role {
    color: rgba(255,255,255,0.45);
    font-size: 0.65rem;
    font-weight: 500;
    font-family: 'Nunito', sans-serif;
}

.bookmi-logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(239, 68, 68, 0.3);
    background: rgba(239, 68, 68, 0.08);
    color: rgba(252, 165, 165, 0.9);
    font-size: 0.8rem;
    font-weight: 600;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    transition: all 150ms ease;
    text-align: left;
}

.bookmi-logout-btn:hover {
    background: rgba(239, 68, 68, 0.18) !important;
    border-color: rgba(239, 68, 68, 0.5) !important;
    color: #fca5a5 !important;
}
</style>
@endauth
