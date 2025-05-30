// Globální proměnné
let currentOrders = [];
let currentSchedule = [];
let technologies = [];
let currentWeekStart = new Date();

// API URL
const API_URL = 'api.php';

// Inicializace aplikace
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Nastavení aktuálního týdne
    setCurrentWeek();
    
    // Načtení technologií
    loadTechnologies();
    
    // Načtení objednávek
    loadOrders();
    
    // Načtení kalendáře - ZAKOMENTOVÁNO, používá se calendar.js
    // loadCalendar();
    
    // Nastavení event listenerů
    setupEventListeners();
    
    // Načtení dokončených objednávek
    loadCompletedOrders();
}

function setCurrentWeek() {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // Pondělí jako první den
    currentWeekStart = new Date(today.setDate(diff));
    updateWeekDisplay();
}

function updateWeekDisplay() {
    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    const formatDate = (date) => {
        return `${date.getDate()}.${date.getMonth() + 1}.`;
    };
    
    const weekDisplay = document.getElementById('weekDisplay');
    if (weekDisplay) {
        weekDisplay.textContent = `Týden (${formatDate(currentWeekStart)} - ${formatDate(weekEnd)} ${currentWeekStart.getFullYear()})`;
    }
}

function setupEventListeners() {
    // Filtrování objednávek
    const searchInput = document.getElementById('orderSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', filterOrders);
    }
    
    const dateFilter = document.getElementById('orderDateFilter');
    if (dateFilter) {
        dateFilter.addEventListener('change', filterOrders);
    }
    
    const statusFilter = document.getElementById('orderStatusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterOrders);
    }
    
    // Technologie filtry - PŘESUNUTO DO calendar.js
    /*
    const techFilters = document.querySelectorAll('.filter-btn');
    techFilters.forEach(btn => {
        btn.addEventListener('click', function() {
            // Odstranit active ze všech
            techFilters.forEach(b => b.classList.remove('active'));
            // Přidat active k aktuálnímu
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            filterCalendarByTechnology(filter);
        });
    });
    */
}

// Načtení technologií
async function loadTechnologies() {
    try {
        const response = await fetch(`${API_URL}/technologies`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        technologies = await response.json();
        updateTechnologySelects();
    } catch (error) {
        console.error('Chyba při načítání technologií:', error);
        showNotification('Chyba při načítání technologií', 'error');
    }
}

function updateTechnologySelects() {
    const selects = document.querySelectorAll('#scheduleTechId, #technology');
    
    selects.forEach(select => {
        if (select) {
            select.innerHTML = '<option value="">Vyberte technologii</option>';
            technologies.forEach(tech => {
                const option = document.createElement('option');
                option.value = tech.id;
                option.textContent = tech.name;
                select.appendChild(option);
            });
        }
    });
}

// Načtení objednávek
async function loadOrders() {
    try {
        const response = await fetch(`${API_URL}/orders`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        currentOrders = await response.json();
        console.log('Načteno objednávek ze script.js:', currentOrders.length);
        
        displayOrders(currentOrders);
        updateOrderSelects();
        
        // Aktualizovat také kalendář pokud je na stránce
        // ZAKOMENTOVÁNO - calendar.js se stará o svůj vlastní refresh
        /*
        if (typeof generateCalendarGrid === 'function') {
            generateCalendarGrid();
        }
        */
        
        return currentOrders; // Vrátit pro použití v jiných funkcích
    } catch (error) {
        console.error('Chyba při načítání objednávek:', error);
        showNotification('Chyba při načítání objednávek: ' + error.message, 'error');
        
        // Zobrazit chybu v UI
        const ordersContainer = document.getElementById('pendingOrdersList');
        if (ordersContainer) {
            ordersContainer.innerHTML = '<div class="error-message">Chyba při načítání dat</div>';
        }
        
        return [];
    }
}

function displayOrders(orders) {
    const ordersList = document.getElementById('pendingOrdersList');
    if (!ordersList) return;
    
    if (orders.length === 0) {
        ordersList.innerHTML = '<div class="no-orders">Žádné objednávky</div>';
        return;
    }
    
    // Filtrovat jen čekající objednávky
    const pendingOrders = orders.filter(order => 
        order.production_status === 'Čekající' || order.production_status === 'V_výrobě'
    );
    
    ordersList.innerHTML = pendingOrders.map(order => `
        <div class="order-item" data-order-id="${order.id}" onclick="showOrderDetails(${order.id})">
            <div class="order-header">
                <strong class="order-code">${order.order_code}</strong>
                <span class="order-date">${formatDate(order.order_date)}</span>
            </div>
            <div class="order-info">
                <div class="catalog">${order.catalog || 'Bez katalogu'}</div>
                <div class="quantity">Množství: ${order.quantity}</div>
            </div>
            <div class="order-status">
                <span class="status-badge ${getStatusClass(order.preview_status)}">${order.preview_status}</span>
                <span class="status-badge ${getStatusClass(order.production_status)}">${order.production_status}</span>
            </div>
            ${order.technology_name ? `
                <div class="order-tech">
                    <span class="tech-tag" style="background-color: ${order.technology_color || '#4299e1'}">
                        ${order.technology_name}
                    </span>
                </div>
            ` : ''}
        </div>
    `).join('');
}

function filterOrders() {
    const searchTerm = document.getElementById('orderSearchInput')?.value.toLowerCase() || '';
    const dateFilter = document.getElementById('orderDateFilter')?.value || '';
    const statusFilter = document.getElementById('orderStatusFilter')?.value || 'all';
    
    let filteredOrders = currentOrders;
    
    // Textové vyhledávání
    if (searchTerm) {
        filteredOrders = filteredOrders.filter(order => 
            order.order_code.toLowerCase().includes(searchTerm) ||
            (order.catalog && order.catalog.toLowerCase().includes(searchTerm))
        );
    }
    
    // Filtr podle data
    if (dateFilter) {
        filteredOrders = filteredOrders.filter(order => 
            order.order_date === dateFilter
        );
    }
    
    // Filtr podle stavu
    if (statusFilter !== 'all') {
        filteredOrders = filteredOrders.filter(order => 
            order.production_status === statusFilter
        );
    }
    
    displayOrders(filteredOrders);
}

function showOrderDetails(orderId) {
    const order = currentOrders.find(o => o.id == orderId);
    if (!order) return;
    
    const detailsContainer = document.getElementById('orderDetails');
    if (!detailsContainer) return;
    
    // Zvýraznit vybranou objednávku
    document.querySelectorAll('.order-item').forEach(item => {
        item.classList.remove('selected');
    });
    document.querySelector(`[data-order-id="${orderId}"]`)?.classList.add('selected');
    
    detailsContainer.innerHTML = `
        <div class="order-detail-content">
            <div class="detail-header">
                <h3>${order.order_code}</h3>
                ${userPermissions.canEditOrders ? `
                    <button class="btn btn-sm btn-secondary" onclick="editOrder(${order.id})">
                        <i class="fas fa-edit"></i> Upravit
                    </button>
                ` : ''}
            </div>
            
            <div class="detail-section">
                <h4>Základní informace</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Katalog:</label>
                        <span>${order.catalog || 'Nespecifikováno'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Množství:</label>
                        <span>${order.quantity}</span>
                    </div>
                    <div class="detail-item">
                        <label>Datum objednání:</label>
                        <span>${formatDate(order.order_date)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Obchodník:</label>
                        <span>${order.salesperson || 'Nespecifikováno'}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Stavy</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Stav náhledu:</label>
                        <span class="status-badge ${getStatusClass(order.preview_status)}">${order.preview_status}</span>
                    </div>
                    <div class="detail-item">
                        <label>Stav výroby:</label>
                        <span class="status-badge ${getStatusClass(order.production_status)}">${order.production_status}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Termíny</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Objednáno zboží:</label>
                        <span>${formatDate(order.goods_ordered_date)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Naskladněno zboží:</label>
                        <span>${formatDate(order.goods_stocked_date)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Datum expedice:</label>
                        <span>${formatDate(order.shipping_date)}</span>
                    </div>
                </div>
            </div>
            
            ${order.notes ? `
                <div class="detail-section">
                    <h4>Poznámky</h4>
                    <div class="notes-content">${order.notes}</div>
                </div>
            ` : ''}
            
            ${userPermissions.canEditSchedule ? `
                <div class="detail-actions">
                    <button class="btn btn-primary" onclick="addToSchedule(${order.id})">
                        <i class="fas fa-calendar-plus"></i> Přidat do plánu
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}

// Kalendář - ZAKOMENTOVÁNO, používá se calendar.js
/*
function loadCalendar() {
    generateCalendarGrid();
    loadScheduleData();
    loadAndDisplayBlocks();
}

function generateCalendarGrid() {
    const calendarGrid = document.getElementById('calendarGrid');
    if (!calendarGrid) return;
    
    const days = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
    const technologies = ['Sítotisk', 'Potisk', 'Gravírování', 'Výšivka', 'Laser'];
    
    let gridHTML = '<div class="calendar-header">';
    gridHTML += '<div class="time-header">Technologie</div>';
    
    // Hlavička s dny
    for (let i = 0; i < 7; i++) {
        const date = new Date(currentWeekStart);
        date.setDate(date.getDate() + i);
        gridHTML += `
            <div class="day-header">
                <div class="day-name">${days[i]}</div>
                <div class="day-date">${date.getDate()}.${date.getMonth() + 1}.</div>
            </div>
        `;
    }
    gridHTML += '</div>';
    
    // Řádky pro technologie
    technologies.forEach(tech => {
        gridHTML += `<div class="calendar-row" data-technology="${tech}">`;
        gridHTML += `<div class="tech-label">${tech}</div>`;
        
        for (let i = 0; i < 7; i++) {
            const date = new Date(currentWeekStart);
            date.setDate(date.getDate() + i);
            const dateStr = date.toISOString().split('T')[0];
            
            gridHTML += `
                <div class="calendar-cell" 
                     data-date="${dateStr}" 
                     data-technology="${tech}"
                     ondrop="drop(event)" 
                     ondragover="allowDrop(event)">
                </div>
            `;
        }
        gridHTML += '</div>';
    });
    
    calendarGrid.innerHTML = gridHTML;
}
*/
// PŘIDAT TUTO FUNKCI (kolem řádku 323):
async function addToSchedule(orderId) {
    if (!userPermissions.canEditSchedule) {
        showNotification('Nemáte oprávnění upravovat plán', 'error');
        return;
    }
    
    try {
        const order = currentOrders.find(o => o.id === orderId);
        if (!order) {
            showNotification('Objednávka nebyla nalezena', 'error');
            return;
        }
        
        // Kontrola, zda je náhled schválen
        if (order.preview_status !== 'Schváleno') {
            showNotification('Nelze přidat do plánu - náhled není schválen', 'error');
            return;
        }
        
        // Najít nejbližší dostupný datum
        const today = new Date();
        const plannedDate = order.preview_approved_date ? 
            new Date(order.preview_approved_date) : today;
        
        if (plannedDate < today) {
            plannedDate.setTime(today.getTime());
        }
        
        const scheduleData = {
            order_id: orderId,
            planned_date: plannedDate.toISOString().split('T')[0],
            estimated_duration: calculateEstimatedDuration(order),
            notes: `Přidáno do plánu automaticky - ${new Date().toLocaleString('cs-CZ')}`
        };
        
        const response = await fetch(`${API_URL}/schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(scheduleData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Objednávka ${order.order_code} byla přidána do plánu`, 'success');
            // Obnovit zobrazení
            await loadOrders();
            await loadScheduleData();
        } else {
            throw new Error(result.message || 'Neznámá chyba');
        }
        
    } catch (error) {
        console.error('Chyba při přidávání do plánu:', error);
        showNotification('Chyba při přidávání objednávky do plánu', 'error');
    }
}

function calculateEstimatedDuration(order) {
    // Odhad času na základě technologie a množství
    const baseTimes = {
        'Sítotisk': 0.5,
        'Potisk': 0.3,
        'Gravírování': 0.8,
        'Výšivka': 1.0,
        'Laser': 0.2
    };
    
    const baseTime = baseTimes[order.technology] || 0.5;
    return Math.ceil(order.quantity * baseTime / 100); // dny
}

async function loadScheduleData() {
    try {
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        
        const startDate = currentWeekStart.toISOString().split('T')[0];
        const endDate = weekEnd.toISOString().split('T')[0];
        
        const response = await fetch(`${API_URL}/schedule?start=${startDate}&end=${endDate}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        currentSchedule = await response.json();
        // ZAKOMENTOVÁNO - calendar.js má vlastní zobrazování
        // displayScheduleOnCalendar(currentSchedule);
    } catch (error) {
        console.error('Chyba při načítání plánu:', error);
        showNotification('Chyba při načítání výrobního plánu', 'error');
    }
}

async function loadAndDisplayBlocks() {
    try {
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        
        const startDate = currentWeekStart.toISOString().split('T')[0];
        const endDate = weekEnd.toISOString().split('T')[0];
        
        const response = await fetch(`${API_URL}/blocks?start=${startDate}&end=${endDate}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const blocks = await response.json();
        
        // Zobrazit blokace v kalendáři
        blocks.forEach(block => {
            const blockStart = new Date(block.start_date);
            const blockEnd = new Date(block.end_date);
            
            // Pro každý den blokace
            for (let date = new Date(blockStart); date <= blockEnd; date.setDate(date.getDate() + 1)) {
                const dateStr = date.toISOString().split('T')[0];
                
                // Najít všechny buňky pro tento den
                const cells = document.querySelectorAll(`[data-date="${dateStr}"]`);
                
                cells.forEach(cell => {
                    const blockElement = document.createElement('div');
                    blockElement.className = `calendar-block block-${block.type}`;
                    blockElement.innerHTML = `
                        <div class="block-label">${getBlockLabel(block.type)}</div>
                        ${block.note ? `<div class="block-note">${block.note}</div>` : ''}
                    `;
                    cell.appendChild(blockElement);
                });
            }
        });
    } catch (error) {
        console.error('Chyba při načítání blokací:', error);
    }
}

function getBlockLabel(type) {
    const labels = {
        'dovolena': 'DOVOLENÁ',
        'udrzba': 'ÚDRŽBA',
        'svatek': 'SVÁTEK',
        'jine': 'JINÉ'
    };
    return labels[type] || type.toUpperCase();
}

// ZAKOMENTOVÁNO - calendar.js má vlastní zobrazování
/*
function displayScheduleOnCalendar(schedule) {
    // Vyčistit kalendář
    document.querySelectorAll('.calendar-cell').forEach(cell => {
        cell.innerHTML = '';
    });
    
    schedule.forEach(item => {
        const startDate = new Date(item.start_date);
        const endDate = new Date(item.end_date);
        const technology = item.technology_name;
        
        // Pro každý den v rozpětí přidat do příslušné buňky
        for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
            const dateStr = date.toISOString().split('T')[0];
            const cell = document.querySelector(`[data-date="${dateStr}"][data-technology="${technology}"]`);
            
            if (cell) {
                const orderElement = document.createElement('div');
                orderElement.className = 'schedule-order';
                orderElement.style.backgroundColor = item.color || '#4299e1';
                orderElement.innerHTML = `
                    <div class="order-code">${item.order_code}</div>
                    <div class="order-quantity">${item.quantity}ks</div>
                `;
                orderElement.draggable = userPermissions.canEditSchedule;
                orderElement.setAttribute('data-schedule-id', item.id);
                
                if (userPermissions.canEditSchedule) {
                    orderElement.addEventListener('dragstart', drag);
                }
                
                cell.appendChild(orderElement);
            }
        }
    });
}
*/

// Navigace týdne - UPRAVENO pro použití s calendar.js
function navigateWeek(direction) {
    // Tato funkce je nyní volána z calendar.js
    console.log('navigateWeek called from script.js - should be handled by calendar.js');
}

// Dokončené objednávky
async function loadCompletedOrders() {
    try {
        const response = await fetch(`${API_URL}/orders?status=Hotovo`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const completedOrders = await response.json();
        displayCompletedOrders(completedOrders);
    } catch (error) {
        console.error('Chyba při načítání dokončených objednávek:', error);
    }
}

function displayCompletedOrders(orders) {
    const tbody = document.querySelector('#completedOrdersTable tbody');
    if (!tbody) return;
    
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="no-data">Žádné dokončené objednávky</td></tr>';
        return;
    }
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td><strong>${order.order_code}</strong></td>
            <td>${formatDate(order.completion_date)}</td>
            <td>${order.salesperson || '-'}</td>
            <td>${order.technology_name || '-'}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="showOrderDetails(${order.id})" title="Detail">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Modaly a formuláře
function showAddOrderModal() {
    if (!userPermissions.canEditOrders) return;
    
    document.getElementById('orderModalTitle').textContent = 'Nová objednávka';
    document.getElementById('orderForm').reset();
    document.getElementById('orderId').value = '';
    document.getElementById('orderModal').style.display = 'block';
}

// OVĚŘIT/OPRAVIT editOrder FUNKCI
function editOrder(orderId) {
    if (!userPermissions.canEditOrders) {
        showNotification('Nemáte oprávnění k editaci objednávek', 'error');
        return;
    }
    
    const order = currentOrders.find(o => o.id == orderId);
    if (!order) {
        showNotification('Objednávka nebyla nalezena', 'error');
        return;
    }
    
    // Vyplnit formulář
    document.getElementById('orderModalTitle').textContent = 'Upravit objednávku';
    document.getElementById('orderId').value = order.id;
    document.getElementById('orderCode').value = order.order_code || '';
    document.getElementById('catalog').value = order.catalog || '';
    document.getElementById('quantity').value = order.quantity || '';
    document.getElementById('orderDate').value = order.order_date || '';
    document.getElementById('goodsOrderedDate').value = order.goods_ordered_date || '';
    document.getElementById('goodsStockedDate').value = order.goods_stocked_date || '';
    document.getElementById('previewStatus').value = order.preview_status || 'Čeká';
    document.getElementById('productionStatus').value = order.production_status || 'Čekající';
    document.getElementById('notes').value = order.notes || '';
    document.getElementById('salesperson').value = order.salesperson || '';
    
    // Zobrazit modal
    document.getElementById('orderModal').style.display = 'block';
}

// OPRAVIT SAVEEORDER FUNKCI - nahradit existující
async function saveOrder(event) {
    event.preventDefault();
    
    const formData = {
        order_code: document.getElementById('orderCode').value,
        catalog: document.getElementById('catalog').value,
        quantity: parseInt(document.getElementById('quantity').value),
        order_date: document.getElementById('orderDate').value,
        goods_ordered_date: document.getElementById('goodsOrderedDate').value || null,
        goods_stocked_date: document.getElementById('goodsStockedDate').value || null,
        preview_status: document.getElementById('previewStatus').value,
        production_status: document.getElementById('productionStatus').value,
        notes: document.getElementById('notes').value,
        salesperson: document.getElementById('salesperson').value
    };
    
    const orderId = document.getElementById('orderId').value;
    
    try {
        let response;
        if (orderId) {
            // Editace existující objednávky
            formData.id = parseInt(orderId);
            response = await fetch(`${API_URL}/orders`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        } else {
            // Nová objednávka
            response = await fetch(`${API_URL}/orders`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        }
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showNotification(orderId ? 'Objednávka upravena' : 'Objednávka vytvořena', 'success');
            closeModal('orderModal');
            loadOrders(); // Znovu načíst objednávky
            if (typeof loadCompletedOrders === 'function') {
                loadCompletedOrders();
            }
        } else {
            showNotification('Chyba při ukládání: ' + (result.error || 'Neznámá chyba'), 'error');
        }
    } catch (error) {
        console.error('Chyba při ukládání objednávky:', error);
        showNotification('Chyba při ukládání objednávky', 'error');
    }
}

function updateOrderSelects() {
    const selects = document.querySelectorAll('#scheduleOrderId');
    
    selects.forEach(select => {
        if (select) {
            select.innerHTML = '<option value="">Vyberte objednávku...</option>';
            currentOrders.forEach(order => {
                const option = document.createElement('option');
                option.value = order.id;
                option.textContent = `${order.order_code} - ${order.catalog || 'Bez katalogu'}`;
                select.appendChild(option);
            });
        }
    });
}

// Drag & Drop pro kalendář
function allowDrop(event) {
    event.preventDefault();
}

function drag(event) {
    event.dataTransfer.setData("text", event.target.getAttribute('data-schedule-id'));
}

function drop(event) {
    event.preventDefault();
    if (!userPermissions.canEditSchedule) return;
    
    const scheduleId = event.dataTransfer.getData("text");
    const targetCell = event.currentTarget;
    const newDate = targetCell.getAttribute('data-date');
    const newTechnology = targetCell.getAttribute('data-technology');
    
    // Zde by byla implementace pro přesun položky v kalendáři
    console.log(`Přesun položky ${scheduleId} na ${newDate} pro technologii ${newTechnology}`);
}

// Blokace/dovolená
function showBlockModal() {
    if (!userPermissions.canEditSchedule) return;
    
    document.getElementById('blockModal').style.display = 'block';
}

async function addBlock(event) {
    event.preventDefault();
    
    const blockType = document.getElementById('blockType').value;
    const blockStartDate = document.getElementById('blockStartDate').value;
    const blockEndDate = document.getElementById('blockEndDate').value;
    const blockNote = document.getElementById('blockNote').value;
    
    if (!blockType || !blockStartDate || !blockEndDate) {
        showNotification('Vyplňte všechny povinné údaje', 'error');
        return;
    }
    
    const blockData = {
        type: blockType,
        start_date: blockStartDate,
        end_date: blockEndDate,
        note: blockNote
    };
    
    try {
        const response = await fetch(`${API_URL}/blocks`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(blockData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            closeModal('blockModal');
            showNotification('Blokace byla úspěšně přidána', 'success');
            document.getElementById('blockForm').reset();
            
            // Obnovit kalendář
            if (productionCalendar) {
                productionCalendar.loadBlocks().then(() => productionCalendar.renderCalendar());
            }
        } else {
            showNotification('Chyba při ukládání: ' + (result.error || 'Neznámá chyba'), 'error');
        }
    } catch (error) {
        console.error('Chyba při ukládání blokace:', error);
        showNotification('Chyba při komunikaci se serverem', 'error');
    }
}

// Filtrace kalendáře podle technologie - PŘESUNUTO DO calendar.js
/*
function filterCalendarByTechnology(technology) {
    const rows = document.querySelectorAll('.calendar-row');
    
    rows.forEach(row => {
        if (technology === 'all') {
            row.style.display = 'flex';
        } else {
            const rowTech = row.getAttribute('data-technology');
            row.style.display = rowTech === technology ? 'flex' : 'none';
        }
    });
}
*/

// Historie
function loadHistory() {
    if (!userPermissions.canViewHistory) return;
    
    const tableFilter = document.getElementById('historyTableFilter')?.value || '';
    const dateFilter = document.getElementById('historyDateFilter')?.value || '';
    
    let url = `${API_URL}/history?`;
    const params = new URLSearchParams();
    
    if (tableFilter) params.append('table', tableFilter);
    if (dateFilter) {
        params.append('date_from', dateFilter);
        params.append('date_to', dateFilter);
    }
    
    url += params.toString();
    
    fetch(url)
        .then(response => response.json())
        .then(data => displayHistory(data))
        .catch(error => console.error('Chyba při načítání historie:', error));
}

function displayHistory(historyData) {
    const historyList = document.getElementById('historyList');
    if (!historyList) return;
    
    if (!historyData || historyData.length === 0) {
        historyList.innerHTML = '<p class="no-data">Žádné záznamy nenalezeny</p>';
        return;
    }
    
    historyList.innerHTML = historyData.map(item => `
        <div class="history-item" data-action="${item.action}">
            <div class="history-header">
                <div>
                    <span class="history-action">${getActionText(item.action)}</span>
                    <span class="history-table">${getTableText(item.table_name)}</span>
                    <span class="history-record">#${item.record_id}</span>
                </div>
                <div class="history-meta">
                    <span class="history-user">${item.user_name}</span>
                    <span class="history-date">${formatDateTime(item.created_at)}</span>
                </div>
            </div>
            ${item.description ? `<div class="history-details">${item.description}</div>` : ''}
        </div>
    `).join('');
}

// Pomocné funkce
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('cs-CZ');
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('cs-CZ');
}

function getStatusClass(status) {
    const statusMap = {
        'Čeká': 'status-waiting',
        'Schváleno': 'status-approved',
        'Zamítnuto': 'status-rejected',
        'Čekající': 'status-waiting',
        'V_výrobě': 'status-in-progress',
        'Hotovo': 'status-completed'
    };
    return statusMap[status] || '';
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

// NAHRADIT funkci showNotification:
function showNotification(message, type = 'info') {
    // Najít nebo vytvořit kontejner pro notifikace
    let container = document.getElementById('notifications');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            width: auto;
            min-width: 300px;
        `;
        document.body.appendChild(container);
    }
    
    // Vytvořit notifikaci
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease;
        word-wrap: break-word;
        max-width: 100%;
        box-sizing: border-box;
        position: relative;
    `;
    
    // Přidat křížek pro zavření
    const closeBtn = document.createElement('span');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = `
        position: absolute;
        top: 0.5rem;
        right: 0.75rem;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.7;
        line-height: 1;
    `;
    closeBtn.onclick = function() {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    };
    
    // Přidat obsah
    const content = document.createElement('div');
    content.style.paddingRight = '1.5rem'; // místo pro křížek
    content.textContent = message;
    
    notification.appendChild(closeBtn);
    notification.appendChild(content);
    container.appendChild(notification);
    
    // Automatické skrytí po 15 sekundách (změněno z 5)
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 15000); // ZMĚNĚNO na 15 sekund
}

// PŘIDAT CSS animace pro notifikace
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Export funkcí pro globální použití
window.showAddOrderModal = showAddOrderModal;
window.editOrder = editOrder;
window.saveOrder = saveOrder;
window.showOrderDetails = showOrderDetails;
window.navigateWeek = navigateWeek;
window.filterOrders = filterOrders;
window.showBlockModal = showBlockModal;
window.addBlock = addBlock;
window.loadHistory = loadHistory;
window.closeModal = closeModal;
window.allowDrop = allowDrop;
window.drag = drag;
window.drop = drop;