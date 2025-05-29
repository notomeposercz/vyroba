// Calendar-specific functionality

// Global variables for calendar
let vyrobaManager = null;
let currentWeekStart = new Date();
let selectedOrderId = null;
let orders = [];
let calendarOrders = [];

// Initialize calendar on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    loadOrdersForCalendar();
    setupCalendarEventListeners();
});

function initializeCalendar() {
    // Set current week start (Monday)
    const today = new Date();
    const dayOfWeek = today.getDay();
    const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
    currentWeekStart = new Date(today.setDate(diff));

    updateWeekDisplay();
    
    // Pokud existuje VyrobaManager, použít ho
    if (typeof VyrobaManager !== 'undefined') {
        vyrobaManager = new VyrobaManager();
        vyrobaManager.init();
    } else {
        // Fallback na starý způsob
        generateCalendarGrid();
        loadCompletedOrders();
    }
}

function setupCalendarEventListeners() {
    // Technology filter
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('filter-btn')) {
            // Remove active class from all filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            e.target.classList.add('active');
            
            // Filter calendar orders
            const filterValue = e.target.dataset.filter;
            filterCalendarByTechnology(filterValue);
        }
    });
    
    // Order selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.order-card')) {
            const orderCard = e.target.closest('.order-card');
            const orderId = orderCard.dataset.orderId;
            selectOrder(parseInt(orderId));
        }
    });
    
    // Status button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('status-btn')) {
            e.stopPropagation();
            const orderId = parseInt(e.target.dataset.orderId);
            const action = e.target.dataset.action;
            updateOrderStatus(orderId, action);
        }
    });
}

function loadOrdersForCalendar() {
    // This function would normally load from API
    // For now, using the existing loadOrders function from script.js
    if (typeof loadOrders === 'function') {
        loadOrders().then(() => {
            renderPendingOrdersCalendar();
        });
    } else {
        // Fallback with sample data
        orders = getSampleOrders();
        renderPendingOrdersCalendar();
    }
}

function getSampleOrders() {
    return [
        {
            id: 1,
            order_code: 'OBJ2025-0123',
            catalog: 'Hrnek bílý 300ml',
            quantity: 150,
            order_date: '2025-04-01',
            shipping_date: '2025-05-05',
            production_status: 'Čekající',
            preview_status: 'Čeká',
            goods_status: 'Na skladě',
            goods_stocked_date: '2025-04-15',
            technology: 'Sítotisk',
            salesperson: 'Jan Novák'
        },
        {
            id: 2,
            order_code: 'OBJ2025-0124',
            catalog: 'Hrnek černý 300ml',
            quantity: 50,
            order_date: '2025-04-02',
            shipping_date: '2025-05-15',
            production_status: 'Čekající',
            preview_status: 'Schváleno',
            preview_approved_date: '2025-04-29',
            goods_status: 'Objednáno',
            technology: 'Potisk',
            salesperson: 'Jan Novák'
        },
        {
            id: 3,
            order_code: 'OBJ2025-0125',
            catalog: 'Propiska kovová',
            quantity: 200,
            order_date: '2025-04-03',
            shipping_date: '2025-05-12',
            production_status: 'Čekající',
            preview_status: 'Čeká',
            goods_status: 'Není',
            technology: 'Gravírování',
            salesperson: 'Jana Svoboda'
        }
    ];
}

function renderPendingOrdersCalendar() {
    const container = document.getElementById('pendingOrdersList');
    if (!container) return;
    
    const pendingOrders = orders.filter(order => 
        order.production_status === 'Čekající' || order.production_status === 'V_výrobě'
    );
    
    container.innerHTML = pendingOrders.map(order => createOrderCardHTML(order)).join('');
}

function createOrderCardHTML(order) {
    const statusColor = getOrderStatusColor(order);
    const goodsStatusColor = getGoodsStatusColor(order.goods_status);
    const previewStatusColor = getPreviewStatusColor(order.preview_status);
    
    return `
        <div class="order-card ${statusColor}" data-order-id="${order.id}">
            <div class="order-header">
                <span class="order-code">${order.order_code}</span>
                <span class="order-date">Vytvořeno: ${formatDate(order.order_date)}</span>
            </div>
            <div class="order-info">${order.quantity} ks / ${order.technology || 'Neurčeno'}</div>
            <div class="order-info">
                Zboží: <span style="color: ${goodsStatusColor}; font-weight: 600;">${order.goods_status}</span>
                ${order.goods_stocked_date ? `<span style="font-size: 0.625rem; margin-left: 0.25rem;">(${formatDate(order.goods_stocked_date)})</span>` : ''}
            </div>
            <div class="order-status">
                <span>Náhled: <span style="color: ${previewStatusColor}; font-weight: 600;">${order.preview_status}</span></span>
                <div class="status-actions">
                    ${renderStatusButtons(order)}
                </div>
            </div>
            ${order.preview_approved_date ? `<div style="font-size: 0.625rem; color: #6b7280; margin-top: 0.25rem;">Schváleno: ${formatDate(order.preview_approved_date)}</div>` : ''}
            <div class="shipping-section">
                <label>Datum odeslání:</label>
                <input type="date" value="${order.shipping_date || ''}" 
                       onchange="updateShippingDate(${order.id}, this.value)"
                       onclick="event.stopPropagation()">
            </div>
        </div>
    `;
}

function renderStatusButtons(order) {
    if (order.preview_status === 'Čeká') {
        return `
            <button class="status-btn approve" data-order-id="${order.id}" data-action="approve" title="Schválit náhled">(S)</button>
            <button class="status-btn reject" data-order-id="${order.id}" data-action="reject" title="Zamítnout náhled">(Z)</button>
        `;
    } else if (order.preview_status === 'Schváleno') {
        return `
            <button class="status-btn revert" data-order-id="${order.id}" data-action="revert" title="Vrátit na čeká">(Č)</button>
            <button class="status-btn reject" data-order-id="${order.id}" data-action="reject" title="Zamítnout náhled">(Z)</button>
        `;
    }
    return '';
}

function getOrderStatusColor(order) {
    if (order.goods_status === 'Na skladě' && order.preview_status === 'Schváleno') {
        return 'status-green';
    } else if (order.goods_status === 'Objednáno' || order.preview_status === 'Čeká') {
        return 'status-orange';
    }
    return 'status-red';
}

function getGoodsStatusColor(status) {
    switch (status) {
        case 'Na skladě': return '#10b981';
        case 'Objednáno': return '#f59e0b';
        case 'Není': return '#ef4444';
        default: return '#6b7280';
    }
}

function getPreviewStatusColor(status) {
    switch (status) {
        case 'Schváleno': return '#10b981';
        case 'Čeká': return '#f59e0b';
        case 'Zamítnuto': return '#ef4444';
        default: return '#6b7280';
    }
}

function selectOrder(orderId) {
    selectedOrderId = orderId;
    const order = orders.find(o => o.id === orderId);
    
    if (order) {
        showOrderDetails(order);
        
        // Highlight selected order
        document.querySelectorAll('.order-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelector(`[data-order-id="${orderId}"]`)?.classList.add('selected');
    }
}

function showOrderDetails(order) {
    const container = document.getElementById('orderDetails');
    if (!container) return;
    
    const previewStatusColor = getPreviewStatusColor(order.preview_status);
    const goodsStatusColor = getGoodsStatusColor(order.goods_status);
    
    container.innerHTML = `
        <div class="detail-row">
            <span class="detail-label">Kód obj.:</span>
            <span class="detail-value">${order.order_code}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Vytvořeno:</span>
            <span class="detail-value">${formatDate(order.order_date)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Požad. expedice:</span>
            <span class="detail-value">${formatDate(order.shipping_date)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Obchodník:</span>
            <span class="detail-value">${order.salesperson || 'Neurčeno'}</span>
        </div>
        
        <div class="detail-section">
            <div style="font-weight: 600; margin-bottom: 0.5rem;">Položky:</div>
            <ul style="list-style: disc; list-style-position: inside; margin-left: 0.5rem; font-size: 0.75rem;">
                <li>${order.catalog}, ${order.quantity} ks, ${order.technology}</li>
            </ul>
        </div>
        
        <div class="detail-section">
            <div class="detail-row">
                <span class="detail-label">Stav náhledu:</span>
                <span style="color: ${previewStatusColor}; font-weight: 600;">${order.preview_status}</span>
                ${order.preview_approved_date ? ` (${formatDate(order.preview_approved_date)})` : ''}
            </div>
            <div class="detail-row">
                <span class="detail-label">Stav zboží:</span>
                <span style="color: ${goodsStatusColor}; font-weight: 600;">${order.goods_status}</span>
            </div>
        </div>
        
        <div class="detail-section">
            <div style="font-weight: 600; margin-bottom: 0.5rem;">Interní poznámky:</div>
            <div class="notes-section">
                ${order.preview_approved_date ? 
                    `<p><span style="color: #6b7280;">[${formatDate(order.preview_approved_date)} Novák]:</span> Schválen náhled.</p>` : 
                    '<p style="color: #6b7280; font-style: italic;">Žádné poznámky</p>'
                }
            </div>
            <button class="add-note-btn" onclick="addNote(${order.id})">
                <i class="fas fa-plus"></i> Přidat poznámku
            </button>
        </div>
        
        <div class="detail-actions">
            <button class="btn btn-success text-xs" onclick="markAsCompleted(${order.id})">
                <i class="fas fa-check"></i> Označit jako hotovo
            </button>
            <button class="btn btn-warning text-xs" onclick="lockOrder(${order.id})">
                <i class="fas fa-lock"></i> Uzamknout termín
            </button>
            <button class="btn btn-gray text-xs" onclick="returnToPlan(${order.id})">
                <i class="fas fa-undo"></i> Vrátit do plánu
            </button>
        </div>
    `;
}

function updateOrderStatus(orderId, action) {
    const order = orders.find(o => o.id === orderId);
    if (!order) return;
    
    switch (action) {
        case 'approve':
            order.preview_status = 'Schváleno';
            order.preview_approved_date = new Date().toISOString().split('T')[0];
            break;
        case 'reject':
            order.preview_status = 'Zamítnuto';
            delete order.preview_approved_date;
            break;
        case 'revert':
            order.preview_status = 'Čeká';
            delete order.preview_approved_date;
            break;
    }
    
    renderPendingOrdersCalendar();
    if (selectedOrderId === orderId) {
        showOrderDetails(order);
    }
    
    showNotification(`Náhled pro ${order.order_code} ${order.preview_status.toLowerCase()}`, 'success');
}

function updateShippingDate(orderId, date) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.shipping_date = date;
        if (selectedOrderId === orderId) {
            showOrderDetails(order);
        }
        showNotification(`Datum expedice pro ${order.order_code} aktualizováno`, 'success');
    }
}

// OPRAVIT generateCalendarGrid FUNKCI - najděte ji a přidejte na konec:
function generateCalendarGrid() {
    const container = document.getElementById('calendarGrid');
    if (!container) return;
    
    const weekDays = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek'];
    
    container.innerHTML = '';
    
    for (let i = 0; i < 5; i++) {
        const date = new Date(currentWeekStart);
        date.setDate(currentWeekStart.getDate() + i);
        
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';
        dayDiv.setAttribute('data-date', date.toISOString().split('T')[0]); // PŘIDAT TOTO
        
        const isHoliday = isDateHoliday(date);
        if (isHoliday) {
            dayDiv.classList.add('holiday-day');
        }
        
        dayDiv.innerHTML = `
            <div class="day-header">
                ${weekDays[i]} (${date.getDate()}.${date.getMonth() + 1}.)
            </div>
            <div class="day-content">
                ${isHoliday ? 
                    '<div class="holiday-content">SVÁTEK</div>' : 
                    generateDayOrders(date)
                }
            </div>
        `;
        
        container.appendChild(dayDiv);
    }
    
    // PŘIDAT NAČTENÍ BLOKACÍ
    loadBlocks();
}

function isDateHoliday(date) {
    const holidays = [
        '2025-05-01', '2025-05-08', '2025-07-05', '2025-07-06',
        '2025-09-28', '2025-10-28', '2025-11-17', '2025-12-24',
        '2025-12-25', '2025-12-26'
    ];
    
    const dateStr = date.toISOString().split('T')[0];
    return holidays.includes(dateStr);
}

function generateDayOrders(date) {
    // Sample calendar orders for demonstration
    const dateStr = date.toISOString().split('T')[0];
    const mockCalendarOrders = {
        '2025-05-29': [
            { id: 2, code: 'OBJ2025-0124', tech: 'Potisk', quantity: 50, completed: false },
            { id: 4, code: 'OBJ2025-0126', tech: 'Výšivka', quantity: 300, completed: true }
        ],
        '2025-05-30': [
            { id: 2, code: 'OBJ2025-0124', tech: 'Potisk', quantity: 50, completed: false },
            { id: 5, code: 'OBJ2025-0127', tech: 'Laser', quantity: 100, completed: true }
        ]
    };
    
    const dayOrders = mockCalendarOrders[dateStr] || [];
    let html = '';
    
    // Add orders
    dayOrders.forEach(order => {
        const techClass = getTechnologyClass(order.tech);
        html += `
            <div class="calendar-order ${techClass}" data-technology="${order.tech}" data-order-id="${order.id}">
                <div class="order-code-cal">${order.code}</div>
                <div class="order-info-cal">${order.quantity} ks - ${order.tech}</div>
                <button class="order-btn-cal ${order.completed ? 'completed' : 'pending'}" 
                        onclick="toggleOrderCompletion(${order.id}, '${order.code}')">
                    ${order.completed ? 'HOTOVO' : ''}
                </button>
            </div>
        `;
    });
    
    // Add maintenance blocks randomly
    if (Math.random() > 0.7) {
        html += '<div class="maintenance-block">ÚDRŽBA</div>';
    }
    
    return html || '<div style="color: #6b7280; font-size: 0.75rem; text-align: center; padding: 1rem;">Žádné akce</div>';
}

function getTechnologyClass(tech) {
    switch (tech) {
        case 'Sítotisk': return 'tech-sitotisk';
        case 'Potisk': return 'tech-potisk';
        case 'Gravírování': return 'tech-gravirovani';
        case 'Výšivka': return 'tech-vysivka';
        case 'Laser': return 'tech-laser';
        default: return 'tech-potisk';
    }
}

function navigateWeek(direction) {
    currentWeekStart.setDate(currentWeekStart.getDate() + (direction * 7));
    updateWeekDisplay();
    
    if (vyrobaManager) {
        vyrobaManager.renderCalendar();
        loadBlocks(); // Načíst blokace pro nový týden
    } else {
        generateCalendarGrid();
    }
}

function updateWeekDisplay() {
    const startDate = new Date(currentWeekStart);
    const endDate = new Date(currentWeekStart);
    endDate.setDate(endDate.getDate() + 4);
    
    const startStr = `${startDate.getDate()}.${startDate.getMonth() + 1}.`;
    const endStr = `${endDate.getDate()}.${endDate.getMonth() + 1}. ${endDate.getFullYear()}`;
    
    const displayElement = document.getElementById('weekDisplay');
    if (displayElement) {
        displayElement.textContent = `Týden (${startStr} - ${endStr})`;
    }
}



// PŘIDAT GLOBÁLNÍ FUNKCI pro označení jako hotovo
window.markOrderCompleted = async function(orderId) {
    if (!confirm('Označit objednávku jako hotovou?')) return;
    
    try {
        const response = await fetch('api.php/orders', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: orderId,
                production_status: 'Hotovo'
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showNotification('Objednávka označena jako hotová', 'success');
            
            // Obnovit data
            if (vyrobaManager) {
                await vyrobaManager.loadOrders();
                vyrobaManager.updateUI();
            } else {
                generateCalendarGrid();
            }
            
            loadCompletedOrders();
        } else {
            throw new Error(result.error || 'Chyba při aktualizaci');
        }
    } catch (error) {
        console.error('Chyba při označování jako hotovo:', error);
        showNotification('Chyba při označování jako hotovo', 'error');
    }
};




// Nahradit funkci filterCalendarByTechnology v script-calendar.js (řádky 455-465)
function filterCalendarByTechnology(tech) {
    // Filtrovat jednotlivé objednávky v kalendáři
    const calendarOrders = document.querySelectorAll('.calendar-order');
    calendarOrders.forEach(order => {
        const orderTech = order.dataset.technology;
        if (tech === 'all' || !orderTech || orderTech === tech) {
            order.style.display = ''; // Zobrazit
            order.classList.remove('hidden');
        } else {
            order.style.display = 'none'; // Skrýt
            order.classList.add('hidden');
        }
    });
    
    // Zajistit, že kalendářní grid zůstane zachovaný
    const calendarGrid = document.querySelector('.calendar-grid');
    if (calendarGrid) {
        // Ujistit se, že grid má správné vlastnosti
        calendarGrid.style.display = 'grid';
        calendarGrid.style.gridTemplateColumns = 'repeat(5, 1fr)';
    }
    
    // Zajistit, že kalendářní dny zůstanou viditelné
    const calendarDays = document.querySelectorAll('.calendar-day');
    calendarDays.forEach(day => {
        day.style.display = 'flex';
        day.style.flexDirection = 'column';
    });
}

function loadCompletedOrders() {
    const completedOrders = [
        {
            order_code: 'OBJ2025-0099',
            completed_date: '2025-04-25 15:30',
            salesperson: 'Novák',
            technology: 'Sítotisk',
            sent: false
        },
        {
            order_code: 'OBJ2025-0101',
            completed_date: '2025-04-26 10:00',
            salesperson: 'Svoboda',
            technology: 'Potisk',
            sent: true
        }
    ];
    
    const tbody = document.querySelector('#completedOrdersTable tbody');
    if (tbody) {
        tbody.innerHTML = completedOrders.map(order => `
            <tr>
                <td>${order.order_code}</td>
                <td>${formatDateTime(order.completed_date)}</td>
                <td>${order.salesperson}</td>
                <td>${order.technology}</td>
                <td>
                    ${order.sent ? 
                        '<span style="color: #6b7280;">(Odesláno)</span>' :
                        `<button class="send-btn" onclick="sendOrder('${order.order_code}')"><i class="fas fa-envelope"></i> Odeslat</button>`
                    }
                </td>
            </tr>
        `).join('');
    }
}

// Utility functions for calendar
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('cs-CZ');
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('cs-CZ') + ' ' + date.toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit' });
}

// Modal functions for calendar-specific modals
function showBlockModal() {
    const modal = document.getElementById('blockModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// NAHRADIT EXISTUJÍCÍ addBlock FUNKCI
async function addBlock(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    const blockData = {
        type: formData.get('blockType'),
        start_date: formData.get('blockStartDate'),
        end_date: formData.get('blockEndDate'),
        note: formData.get('blockNote')
    };
    
    try {
        const response = await fetch('api.php/blocks', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(blockData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            closeModal('blockModal');
            showNotification('Blokace byla úspěšně přidána', 'success');
            event.target.reset();
            generateCalendarGrid(); // Obnovit kalendář
            loadBlocks(); // Načíst blokace
        } else {
            showNotification('Chyba při ukládání: ' + (result.error || 'Neznámá chyba'), 'error');
        }
    } catch (error) {
        console.error('Chyba při ukládání blokace:', error);
        showNotification('Chyba při ukládání blokace', 'error');
    }
}

// PŘIDAT NOVOU FUNKCI pro načítání blokací
async function loadBlocks() {
    try {
        const startDate = new Date(currentWeekStart);
        const endDate = new Date(currentWeekStart);
        endDate.setDate(endDate.getDate() + 7);
        
        const response = await fetch(`api.php/blocks?start=${startDate.toISOString().split('T')[0]}&end=${endDate.toISOString().split('T')[0]}`);
        const blocks = await response.json();
        
        // Aplikovat blokace do kalendáře
        applyBlocksToCalendar(blocks);
    } catch (error) {
        console.error('Chyba při načítání blokací:', error);
    }
}

// PŘIDAT NOVOU FUNKCI pro aplikování blokací
function applyBlocksToCalendar(blocks) {
    blocks.forEach(block => {
        const startDate = new Date(block.start_date);
        const endDate = new Date(block.end_date);
        
        // Pro každý den v rozpětí blokace
        for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
            const dateStr = date.toISOString().split('T')[0];
            const dayElement = document.querySelector(`[data-date="${dateStr}"]`);
            
            if (dayElement) {
                const blockDiv = document.createElement('div');
                blockDiv.className = `calendar-block block-${block.type}`;
                blockDiv.innerHTML = `
                    <div class="block-content">
                        ${getBlockDisplayName(block.type)}
                        ${block.note ? `<small>${block.note}</small>` : ''}
                    </div>
                `;
                dayElement.appendChild(blockDiv);
            }
        }
    });
}

// PŘIDAT POMOCNOU FUNKCI
function getBlockDisplayName(type) {
    switch (type) {
        case 'dovolena': return 'DOVOLENÁ';
        case 'udrzba': return 'ÚDRŽBA';
        case 'svatek': return 'SVÁTEK';
        case 'jine': return 'BLOKACE';
        default: return 'BLOKACE';
    }
}

function markAsCompleted(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.production_status = 'Hotovo';
        showNotification(`Zakázka ${order.order_code} označena jako hotová`, 'success');
        renderPendingOrdersCalendar();
        loadCompletedOrders();
    }
}

function lockOrder(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        showNotification(`Termín pro zakázku ${order.order_code} uzamčen`, 'success');
    }
}

function returnToPlan(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        showNotification(`Zakázka ${order.order_code} vrácena do plánu`, 'success');
    }
}

function addNote(orderId) {
    const note = prompt('Zadejte poznámku:');
    if (note) {
        showNotification('Poznámka byla přidána', 'success');
        if (selectedOrderId === orderId) {
            const order = orders.find(o => o.id === orderId);
            if (order) {
                showOrderDetails(order);
            }
        }
    }
}

function sendOrder(orderCode) {
    showNotification(`Zakázka ${orderCode} byla odeslána`, 'success');
    loadCompletedOrders();
}

// Integration with existing modal functions
function showAddOrderModal() {
    const modal = document.getElementById('orderModal');
    if (modal) {
        modal.style.display = 'block';
        document.getElementById('orderModalTitle').textContent = 'Nová objednávka';
        document.getElementById('orderForm').reset();
        document.getElementById('orderId').value = '';
    }
}

// Filter functions
function filterOrders() {
    const searchValue = document.getElementById('orderSearchInput')?.value.toLowerCase() || '';
    const dateFilter = document.getElementById('orderDateFilter')?.value || '';
    const statusFilter = document.getElementById('orderStatusFilter')?.value || 'all';
    
    let filteredOrders = orders.filter(order => 
        order.production_status === 'Čekající' || order.production_status === 'V_výrobě'
    );
    
    if (searchValue) {
        filteredOrders = filteredOrders.filter(order => 
            order.order_code.toLowerCase().includes(searchValue) ||
            order.catalog.toLowerCase().includes(searchValue) ||
            (order.technology && order.technology.toLowerCase().includes(searchValue))
        );
    }
    
    if (dateFilter) {
        filteredOrders = filteredOrders.filter(order => 
            order.order_date === dateFilter
        );
    }
    
    if (statusFilter !== 'all') {
        filteredOrders = filteredOrders.filter(order => 
            order.production_status === statusFilter
        );
    }
    
    // Re-render filtered orders
    const container = document.getElementById('pendingOrdersList');
    if (container) {
        container.innerHTML = filteredOrders.map(order => createOrderCardHTML(order)).join('');
    }
}