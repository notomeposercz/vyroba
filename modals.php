<!-- Historie Modal -->
<?php if (hasPermission('view_history')): ?>
<div id="historyModal" class="modal">
    <div class="modal-content large">
        <span class="close" onclick="closeModal('historyModal')">&times;</span>
        <h2><i class="fas fa-history"></i> Historie změn</h2>
        
        <div class="history-filters">
            <div class="filter-row">
                <select id="historyTableFilter" class="filter-input">
                    <option value="">Všechny tabulky</option>
                    <option value="orders">Objednávky</option>
                    <option value="production_schedule">Výrobní plán</option>
                </select>
                <input type="date" id="historyDateFilter" class="filter-input">
                <button class="btn btn-secondary" onclick="loadHistory()">Filtrovat</button>
            </div>
        </div>
        
        <div id="historyList" class="history-list">
            <div class="loading">Načítání historie...</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <h2><i class="fas fa-sign-out-alt"></i> Odhlášení</h2>
        <p>Opravdu se chcete odhlásit?</p>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('logoutModal')">Zrušit</button>
            <button type="button" class="btn btn-primary" onclick="confirmLogout()">Odhlásit</button>
        </div>
    </div>
</div>

<!-- Modal pro novou objednávku -->
<?php if (hasPermission('edit_orders')): ?>
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('orderModal')">&times;</span>
        <h2 id="orderModalTitle">Nová objednávka</h2>
        <form id="orderForm" onsubmit="saveOrder(event)">
            <input type="hidden" id="orderId">
            <div class="form-row">
                <div class="form-group">
                    <label>Kód objednávky:</label>
                    <input type="text" id="orderCode" required>
                </div>
                <div class="form-group">
                    <label>Katalog:</label>
                    <input type="text" id="catalog">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Množství:</label>
                    <input type="number" id="quantity" required min="1">
                </div>
                <div class="form-group">
                    <label>Datum objednání:</label>
                    <input type="date" id="orderDate" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Objednáno zboží:</label>
                    <input type="date" id="goodsOrderedDate">
                </div>
                <div class="form-group">
                    <label>Naskladněno zboží:</label>
                    <input type="date" id="goodsStockedDate">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Stav náhledu:</label>
                    <select id="previewStatus" <?php echo (hasPermission('edit_preview_status') || hasPermission('edit_orders')) ? '' : 'disabled'; ?>>
                        <option value="Čeká">Čeká</option>
                        <option value="Schváleno">Schváleno</option>
                        <option value="Zamítnuto">Zamítnuto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stav výroby:</label>
                    <select id="productionStatus">
                        <option value="Čekající">Čekající</option>
                        <option value="V_výrobě">V výrobě</option>
                        <option value="Hotovo">Hotovo</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Obchodník:</label>
                    <input type="text" id="salesperson" placeholder="Jméno obchodníka">
                </div>
                <div class="form-group">
                    <label>Technologie:</label>
                    <select id="technology">
                        <option value="">Vyberte technologii</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Poznámky:</label>
                <textarea id="notes" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('orderModal')">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Block/Holiday Modal -->
<?php if (hasPermission('edit_schedule')): ?>
<div id="blockModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('blockModal')">&times;</span>
        <h2><i class="fas fa-ban"></i> Vložit blokaci/dovolenou</h2>
        <form id="blockForm" onsubmit="addBlock(event)">
            <div class="form-group">
                <label>Typ blokace:</label>
                <select id="blockType" name="blockType" required>
                    <option value="">Vyberte typ</option>
                    <option value="dovolena">Dovolená</option>
                    <option value="udrzba">Údržba</option>
                    <option value="svatek">Svátek</option>
                    <option value="jine">Jiné</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Od data:</label>
                    <input type="date" id="blockStartDate" name="blockStartDate" required>
                </div>
                <div class="form-group">
                    <label>Do data:</label>
                    <input type="date" id="blockEndDate" name="blockEndDate" required>
                </div>
            </div>
            <div class="form-group">
                <label>Poznámka:</label>
                <textarea id="blockNote" name="blockNote" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('blockModal')">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Základní Modal styly -->
<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease;
}

.modal-content.large {
    max-width: 800px;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
    cursor: pointer;
    margin-top: -10px;
    margin-right: -10px;
}

.modal .close:hover,
.modal .close:focus {
    color: #000;
    text-decoration: none;
}

.modal h2 {
    margin: 0 0 1.5rem 0;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Form styly */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group input:disabled,
.form-group select:disabled {
    background-color: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

/* Historie specifické styly */
.history-filters {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.history-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.history-item {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s;
}

.history-item:hover {
    background: #f8f9fa;
}

.history-item:last-child {
    border-bottom: none;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.history-action {
    font-weight: 600;
    color: #1f2937;
}

.history-table {
    color: #6b7280;
    margin-left: 0.5rem;
}

.history-record {
    color: #3b82f6;
    font-weight: 500;
    margin-left: 0.5rem;
}

.history-meta {
    text-align: right;
    font-size: 0.85rem;
}

.history-user {
    color: #3b82f6;
    font-weight: 500;
}

.history-date {
    color: #6b7280;
    display: block;
    margin-top: 0.25rem;
}

.history-details {
    font-size: 0.9rem;
    color: #6b7280;
    font-style: italic;
}

.history-changes {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f1f3f4;
    border-radius: 4px;
    font-size: 0.85rem;
    font-family: monospace;
}

/* Barevné označení podle akcí */
.history-item[data-action="INSERT"] {
    border-left: 4px solid #10b981;
}

.history-item[data-action="UPDATE"] {
    border-left: 4px solid #f59e0b;
}

.history-item[data-action="DELETE"] {
    border-left: 4px solid #ef4444;
}

/* Responsive úpravy */
@media (max-width: 768px) {
    .modal-content {
        margin: 2% auto;
        padding: 1rem;
        width: 95%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .form-actions button {
        width: 100%;
    }
    
    .history-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .history-meta {
        text-align: left;
    }
}

/* Loading stav */
.loading {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
    font-style: italic;
}
</style>