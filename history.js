// Rozšíření pro práci s historií

function showHistoryModal() {
    if (userPermissions.canViewHistory) {
        document.getElementById('historyModal').style.display = 'block';
        loadHistory();
    }
}

function loadHistory() {
    const tableFilter = document.getElementById('historyTableFilter').value;
    const dateFilter = document.getElementById('historyDateFilter').value;
    
    let url = 'api.php/history?';
    const params = new URLSearchParams();
    
    if (tableFilter) {
        params.append('table', tableFilter);
    }
    
    if (dateFilter) {
        params.append('date_from', dateFilter);
        params.append('date_to', dateFilter);
    }
    
    url += params.toString();
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            displayHistory(data);
        })
        .catch(error => {
            console.error('Chyba při načítání historie:', error);
        });
}

function displayHistory(historyData) {
    const historyList = document.getElementById('historyList');
    
    if (!historyData || historyData.length === 0) {
        historyList.innerHTML = '<p class="no-data">Žádné záznamy nenalezeny</p>';
        return;
    }
    
    const historyHtml = historyData.map(item => {
        const actionText = getActionText(item.action);
        const tableText = getTableText(item.table_name);
        const changes = formatChanges(item.old_values, item.new_values);
        
        return `
            <div class="history-item" data-action="${item.action}">
                <div class="history-header">
                    <div>
                        <span class="history-action">${actionText}</span>
                        <span class="history-table">${tableText}</span>
                        <span class="history-record">#${item.record_id}</span>
                    </div>
                    <div class="history-meta">
                        <span class="history-user">${item.user_name}</span>
                        <span class="history-date">${formatDate(item.created_at)}</span>
                    </div>
                </div>
                ${item.description ? `<div class="history-details">${item.description}</div>` : ''}
                ${changes ? `<div class="history-changes">${changes}</div>` : ''}
            </div>
        `;
    }).join('');
    
    historyList.innerHTML = historyHtml;
}

function getActionText(action) {
    const actions = {
        'INSERT': 'Vytvořeno',
        'UPDATE': 'Změněno',
        'DELETE': 'Smazáno'
    };
    return actions[action] || action;
}

function getTableText(tableName) {
    const tables = {
        'orders': 'objednávka',
        'production_schedule': 'výrobní plán',
        'users': 'uživatel'
    };
    return tables[tableName] || tableName;
}

function formatChanges(oldValues, newValues) {
    if (!oldValues && !newValues) return null;
    
    try {
        const old = oldValues ? JSON.parse(oldValues) : {};
        const new_ = newValues ? JSON.parse(newValues) : {};
        
        const changes = [];
        
        // Porovnej změny
        Object.keys(new_).forEach(key => {
            if (old[key] !== new_[key]) {
                const oldVal = old[key] || 'prázdné';
                const newVal = new_[key] || 'prázdné';
                changes.push(`${key}: "${oldVal}" → "${newVal}"`);
            }
        });
        
        return changes.length > 0 ? changes.join('<br>') : null;
    } catch (e) {
        return null;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('cs-CZ');
}

// Přidat do hlavního script.js
document.addEventListener('DOMContentLoaded', function() {
    // Nastav třídu role na body pro CSS styling
    if (typeof userRole !== 'undefined') {
        document.body.classList.add('role-' + userRole);
    }
});