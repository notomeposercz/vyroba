// Globální proměnné
let currentOrders = [];
let currentSchedule = [];
let technologies = [];

// API URL
const API_URL = 'api.php';

// Inicializace aplikace
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Načtení technologií
    loadTechnologies();
    
    // Načtení objednávek
    loadOrders();
    
    // Načtení statistik
    loadAnalytics();
    
    // Nastavení event listenerů pro taby
    setupTabs();
    
    // Nastavení dnešního data
    setupDateInputs();
}

// Správa tabů
function setupTabs() {
    const navBtns = document.querySelectorAll('.nav-btn');
    
    navBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            switchTab(targetTab);
        });
    });
}

function switchTab(tabName) {
    // Odstranit active třídu ze všech tlačítek a tabů
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    
    // Přidat active třídu k aktivnímu tlačítku a tabu
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(tabName).classList.add('active');
    
    // Načíst data podle aktivního tabu
    switch(tabName) {
        case 'orders':
            loadOrders();
            break;
        case 'schedule':
            loadSchedule();
            break;
        case 'analytics':
            loadAnalytics();
            break;
    }
}

// Načtení technologií
async function loadTechnologies() {
    try {
        const response = await fetch(`${API_URL}/technologies`);
        technologies = await response.json();
        
        // Naplnění select boxů
        updateTechnologySelects();
    } catch (error) {
        console.error('Chyba při načítání technologií:', error);
        showNotification('Chyba při načítání technologií', 'error');
    }
}

function updateTechnologySelects() {
    const selects = document.querySelectorAll('#scheduleTechId');
    
    selects.forEach(select => {
        select.innerHTML = '<option value="">Vyberte technologii...</option>';
        technologies.forEach(tech => {
            const option = document.createElement('option');
            option.value = tech.id;
            option.textContent = tech.name;
            select.appendChild(option);
        });
    });
}

// Načtení objednávek
async function loadOrders() {
    const tbody = document.querySelector('#ordersTable tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="loading">Načítání...</td></tr>';
    
    try {
        const status = document.getElementById('statusFilter').value;
        const url = status === 'all' ? `${API_URL}/orders` : `${API_URL}/orders?status=${status}`;
        
        const response = await fetch(url);
        currentOrders = await response.json();
        
        displayOrders(currentOrders);
        updateOrderSelects();
    } catch (error) {
        console.error('Chyba při načítání objednávek:', error);
        tbody.innerHTML = '<tr><td colspan="8" class="loading">Chyba při načítání dat</td></tr>';
        showNotification('Chyba při načítání objednávek', 'error');
    }
}

function displayOrders(orders) {
    const tbody = document.querySelector('#ordersTable tbody');
    
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="loading">Žádné objednávky</td></tr>';
        return;
    }
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td><strong>${order.order_code}</strong></td>
            <td>${order.catalog || '-'}</td>
            <td>${order.quantity}</td>
            <td>${formatDate(order.order_date)}</td>
            <td><span class="status-badge ${getStatusClass(order.preview_status)}">${order.preview_status}</span></td>
            <td><span class="status-badge ${getStatusClass(order.production_status)}">${order.production_status}</span></td>
            <td>${formatTechnologies(order.technologies, order.tech_colors)}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="editOrder(${order.id})" title="Upravit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteOrder(${order.id})" title="Smazat">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function updateOrderSelects() {
    const selects = document.querySelectorAll('#scheduleOrderId');
    
    selects.forEach(select => {
        select.innerHTML = '<option value="">Vyberte objednávku...</option>';
        currentOrders.forEach(order => {
            const option = document.createElement('option');
            option.value = order.id;
            option.textContent = `${order.order_code} - ${order.catalog || 'Bez katalogu'}`;
            select.appendChild(option);
        });
    });
}

// Vyhledávání v objednávkách
function searchOrders() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filteredOrders = currentOrders.filter(order => 
        order.order_code.toLowerCase().includes(searchTerm) ||
        (order.catalog && order.catalog.toLowerCase().includes(searchTerm))
    );
    displayOrders(filteredOrders);
}

// Modaly
function showAddOrderModal() {
    document.getElementById('orderModalTitle').textContent = 'Nová objednávka';
    document.getElementById('orderForm').reset();
    document.getElementById('orderId').value = '';
    document.getElementById('orderModal').style.display = 'block';
}

function editOrder(orderId) {
    const order = currentOrders.find(o => o.id == orderId);
    if (!order) return;
    
    document.getElementById('orderModalTitle').textContent = 'Upravit objednávku';
    document.getElementById('orderId').value = order.id;
    document.getElementById('orderCode').value = order.order_code;
    document.getElementById('catalog').value = order.catalog || '';
    document.getElementById('quantity').value = order.quantity;
    document.getElementById('orderDate').value = order.order_date;
    document.getElementById('goodsOrderedDate').value = order.goods_ordered_date || '';
    document.getElementById('goodsStockedDate').value = order.goods_stocked_date || '';
    document.getElementById('previewStatus').value = order.preview_status;
    document.getElementById('productionStatus').value = order.production_status;
    document.getElementById('notes').value = order.notes || '';
    
    document.getElementById('orderModal').style.display = 'block';
}

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
        notes: document.getElementById('notes').value
    };
    
    const orderId = document.getElementById('orderId').value;
    
    try {
        let response;
        if (orderId) {
            // Úprava existující objednávky
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
        
        if (result.success) {
            showNotification(orderId ? 'Objednávka upravena' : 'Objednávka vytvořena', 'success');
            closeModal('orderModal');
            loadOrders();
        } else {
            showNotification('Chyba při ukládání: ' + (result.error || 'Neznámá chyba'), 'error');
        }
    } catch (error) {
        console.error('Chyba při ukládání objednávky:', error);
        showNotification('Chyba při ukládání objednávky', 'error');
    }
}

async function deleteOrder(orderId) {
    if (!confirm('Opravdu chcete smazat tuto objednávku?')) return;
    
    try {
        const response = await fetch(`${API_URL}/orders`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: orderId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Objednávka smazána', 'success');
            loadOrders();
        } else {
            showNotification('Chyba při mazání objednávky', 'error');
        }
    } catch (error) {
        console.error('Chyba při mazání objednávky:', error);
        showNotification('Chyba při mazání objednávky', 'error');
    }
}

// Výrobní plán
function showAddScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'block';
}

async function loadSchedule() {
    const container = document.getElementById('scheduleCalendar');
    container.innerHTML = '<div class="loading">Načítání výrobního plánu...</div>';
    
    try {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        const response = await fetch(`${API_URL}/schedule?start=${startDate}&end=${endDate}`);
        currentSchedule = await response.json();
        
        displaySchedule(currentSchedule);
    } catch (error) {
        console.error('Chyba při načítání plánu:', error);
        container.innerHTML = '<div class="loading">Chyba při načítání plánu</div>';
        showNotification('Chyba při načítání výrobního plánu', 'error');
    }
}

function displaySchedule(schedule) {
    const container = document.getElementById('scheduleCalendar');
    
    if (schedule.length === 0) {
        container.innerHTML = '<div class="loading">Žádné položky v plánu</div>';
        return;
    }
    
    container.innerHTML = schedule.map(item => `
        <div class="schedule-item" style="border-left-color: ${item.color}">
            <h4>${item.order_code} - ${item.catalog || 'Bez katalogu'}</h4>
            <div class="date-range">
                <i class="fas fa-calendar"></i>
                ${formatDate(item.start_date)} - ${formatDate(item.end_date)}
            </div>
            <div class="tech-info">
                <span class="tech-tag" style="background-color: ${item.color}">
                    ${item.technology_name}
                </span>
                <span>Množství: ${item.quantity}</span>
                ${item.is_locked ? '<i class="fas fa-lock" title="Uzamčeno"></i>' : ''}
            </div>
        </div>
    `).join('');
}

async function saveSchedule(event) {
    event.preventDefault();
    
    const formData = {
        order_id: parseInt(document.getElementById('scheduleOrderId').value),
        technology_id: parseInt(document.getElementById('scheduleTechId').value),
        start_date: document.getElementById('scheduleStartDate').value,
        end_date: document.getElementById('scheduleEndDate').value,
        is_locked: document.getElementById('scheduleIsLocked').checked ? 1 : 0
    };
    
    try {
        const response = await fetch(`${API_URL}/schedule`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Položka přidána do plánu', 'success');
            closeModal('scheduleModal');
            loadSchedule();
        } else {
            showNotification('Chyba při přidávání do plánu: ' + (result.error || 'Neznámá chyba'), 'error');
        }
    } catch (error) {
        console.error('Chyba při ukládání plánu:', error);
        showNotification('Chyba při ukládání do plánu', 'error');
    }
}

// Statistiky
async function loadAnalytics() {
    try {
        const response = await fetch(`${API_URL}/orders`);
        const orders = await response.json();
        
        // Základní statistiky
        const totalOrders = orders.length;
        const inProgress = orders.filter(o => o.production_status === 'V_výrobě').length;
        const completed = orders.filter(o => o.production_status === 'Hotovo').length;
        const pendingApproval = orders.filter(o => o.preview_status === 'Čeká').length;
        
        // Aktualizace DOM
        document.getElementById('totalOrders').textContent = totalOrders;
        document.getElementById('inProgress').textContent = inProgress;
        document.getElementById('completed').textContent = completed;
        document.getElementById('pendingApproval').textContent = pendingApproval;
        
        // Jednoduchý graf technologií
        updateTechChart(orders);
        
    } catch (error) {
        console.error('Chyba při načítání statistik:', error);
        showNotification('Chyba při načítání statistik', 'error');
    }
}

function updateTechChart(orders) {
    const chartContainer = document.getElementById('techChart');
    
    // Spočítání použití technologií
    const techCount = {};
    orders.forEach(order => {
        if (order.technologies) {
            const techs = order.technologies.split(', ');
            techs.forEach(tech => {
                if (tech && tech !== '') {
                    techCount[tech] = (techCount[tech] || 0) + 1;
                }
            });
        }
    });
    
    if (Object.keys(techCount).length === 0) {
        chartContainer.innerHTML = 'Žádná data o technologiích';
        return;
    }
    
    // Jednoduchý sloupcový graf
    const maxCount = Math.max(...Object.values(techCount));
    const chartHtml = Object.entries(techCount)
        .sort((a, b) => b[1] - a[1])
        .map(([tech, count]) => {
            const percentage = (count / maxCount) * 100;
            return `
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <div style="width: 100px; font-size: 0.9rem;">${tech}</div>
                    <div style="flex: 1; background: #e2e8f0; height: 20px; border-radius: 10px; margin: 0 10px; overflow: hidden;">
                        <div style="width: ${percentage}%; height: 100%; background: #4299e1; border-radius: 10px;"></div>
                    </div>
                    <div style="width: 30px; text-align: right; font-weight: bold;">${count}</div>
                </div>
            `;
        }).join('');
    
    chartContainer.innerHTML = chartHtml;
}

// Pomocné funkce
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('cs-CZ');
}

function getStatusClass(status) {
    const statusMap = {
        'Čeká': 'status-pending',
        'Schváleno': 'status-approved',
        'Zamítnuto': 'status-rejected',
        'Čekající': 'status-waiting',
        'V_výrobě': 'status-in-progress',
        'Hotovo': 'status-completed'
    };
    return statusMap[status] || '';
}

function formatTechnologies(technologies, colors) {
    if (!technologies) return '-';
    
    const techArray = technologies.split(', ');
    const colorArray = colors ? colors.split(', ') : [];
    
    return techArray.map((tech, index) => {
        const color = colorArray[index] || '#4299e1';
        return `<span class="tech-tag" style="background-color: ${color}">${tech}</span>`;
    }).join('');
}

function setupDateInputs() {
    const today = new Date().toISOString().split('T')[0];
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 30);
    
    document.getElementById('startDate').value = today;
    document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
    document.getElementById('scheduleStartDate').value = today;
    document.getElementById('scheduleEndDate').value = today;
}

function showNotification(message, type = 'info') {
    // Jednoduchá notifikace - můžete nahradit toast knihovnou
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;
    
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#48bb78';
            break;
        case 'error':
            notification.style.backgroundColor = '#f56565';
            break;
        default:
            notification.style.backgroundColor = '#4299e1';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 4000);
}

// Event listener pro zavření modalu kliknutím mimo
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}