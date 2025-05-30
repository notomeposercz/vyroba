// === KALENDÁŘ - calendar.js ===
// Vytvořte nový soubor calendar.js a vložte tento kód

class ProductionCalendar {
    constructor() {
        this.currentWeekStart = this.getMonday(new Date());
        this.orders = [];
        this.blocks = [];
        this.technologies = [];
    }

    init() {
        this.setupWeekNavigation();
        this.loadData();
    }

    getMonday(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    setupWeekNavigation() {
        // Aktualizovat zobrazení týdne
        this.updateWeekDisplay();
        
        // Event listenery jsou již v HTML
    }

    updateWeekDisplay() {
        const weekEnd = new Date(this.currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 4); // Pátek
        
        const formatDate = (date) => {
            return `${date.getDate()}.${date.getMonth() + 1}.`;
        };
        
        const weekDisplay = document.getElementById('weekDisplay');
        if (weekDisplay) {
            weekDisplay.textContent = `Týden (${formatDate(this.currentWeekStart)} - ${formatDate(weekEnd)} ${weekEnd.getFullYear()})`;
        }
    }

    async loadData() {
        try {
            // Načíst objednávky
            const ordersResponse = await fetch('api.php/orders');
            this.orders = await ordersResponse.json();
            
            // Načíst technologie
            const techResponse = await fetch('api.php/technologies');
            this.technologies = await techResponse.json();
            
            // Načíst blokace pro aktuální týden
            await this.loadBlocks();
            
            // Vykreslit kalendář
            this.renderCalendar();
        } catch (error) {
            console.error('Chyba při načítání dat:', error);
        }
    }

    async loadBlocks() {
        const weekEnd = new Date(this.currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 4);
        
        const startDate = this.currentWeekStart.toISOString().split('T')[0];
        const endDate = weekEnd.toISOString().split('T')[0];
        
        try {
            const response = await fetch(`api.php/blocks?start=${startDate}&end=${endDate}`);
            this.blocks = await response.json();
        } catch (error) {
            console.error('Chyba při načítání blokací:', error);
        }
    }

    renderCalendar() {
        const container = document.getElementById('calendarGrid');
        if (!container) return;
        
        const weekDays = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek'];
        
        // Vyčistit kontejner
        container.innerHTML = '';
        
        // Vytvořit dny
        for (let i = 0; i < 5; i++) {
            const date = new Date(this.currentWeekStart);
            date.setDate(this.currentWeekStart.getDate() + i);
            const dateStr = date.toISOString().split('T')[0];
            
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            dayDiv.setAttribute('data-date', dateStr);
            
            // Header dne
            const headerDiv = document.createElement('div');
            headerDiv.className = 'day-header';
            headerDiv.textContent = `${weekDays[i]} (${date.getDate()}.${date.getMonth() + 1}.)`;
            
            // Content dne
            const contentDiv = document.createElement('div');
            contentDiv.className = 'day-content';
            contentDiv.setAttribute('data-date', dateStr);
            
            // Přidat objednávky pro tento den
            const dayOrders = this.getOrdersForDate(date);
            const dayBlocks = this.getBlocksForDate(date);
            
            // Nejdřív blokace
            dayBlocks.forEach(block => {
                const blockEl = this.createBlockElement(block);
                contentDiv.appendChild(blockEl);
            });
            
            // Pak objednávky
            if (dayOrders.length > 0) {
                dayOrders.forEach(order => {
                    const orderEl = this.createOrderElement(order);
                    contentDiv.appendChild(orderEl);
                });
            } else if (dayBlocks.length === 0) {
                const noOrdersDiv = document.createElement('div');
                noOrdersDiv.className = 'no-orders-day';
                noOrdersDiv.textContent = 'Žádné akce';
                contentDiv.appendChild(noOrdersDiv);
            }
            
            dayDiv.appendChild(headerDiv);
            dayDiv.appendChild(contentDiv);
            container.appendChild(dayDiv);
        }
    }

    getOrdersForDate(date) {
        const dateStr = date.toISOString().split('T')[0];
        
        return this.orders.filter(order => {
            // Pouze schválené objednávky
            if (order.preview_status !== 'Schváleno' || !order.preview_approved_date) {
                return false;
            }
            
            // Zkontrolovat, zda objednávka spadá do tohoto dne
            const startDate = order.preview_approved_date;
            const endDate = order.shipping_date || this.addDays(startDate, 14);
            
            return dateStr >= startDate && dateStr <= endDate;
        });
    }

    getBlocksForDate(date) {
        const dateStr = date.toISOString().split('T')[0];
        
        return this.blocks.filter(block => {
            return dateStr >= block.start_date && dateStr <= block.end_date;
        });
    }

    createOrderElement(order) {
        const div = document.createElement('div');
        div.className = `calendar-order ${this.getTechnologyClass(order.technology_name)}`;
        div.setAttribute('data-order-id', order.id);
        div.setAttribute('data-technology', order.technology_name || '');
        
        div.innerHTML = `
            <div class="order-code-cal">${order.order_code}</div>
            <div class="order-info-cal">
                ${order.quantity} ks${order.technology_name ? ' - ' + order.technology_name : ''}
            </div>
            <div class="order-actions-cal">
                <button class="mark-completed-btn" 
                        onclick="markOrderCompleted(${order.id})" 
                        title="Označit jako hotovo">
                    ✓
                </button>
            </div>
        `;
        
        div.addEventListener('click', (e) => {
            if (!e.target.classList.contains('mark-completed-btn')) {
                showOrderDetails(order.id);
            }
        });
        
        return div;
    }

    createBlockElement(block) {
        const div = document.createElement('div');
        div.className = `calendar-block block-${block.type}`;
        
        div.innerHTML = `
            <div class="block-content">
                <strong>${this.getBlockLabel(block.type)}</strong>
                ${block.note ? `<small>${block.note}</small>` : ''}
            </div>
        `;
        
        return div;
    }

    getTechnologyClass(tech) {
        if (!tech) return 'tech-default';
        
        switch (tech.toLowerCase()) {
            case 'sítotisk': return 'tech-sitotisk';
            case 'potisk': return 'tech-potisk';
            case 'gravírování': return 'tech-gravirovani';
            case 'výšivka': return 'tech-vysivka';
            case 'laser': return 'tech-laser';
            default: return 'tech-default';
        }
    }

    getBlockLabel(type) {
        const labels = {
            'dovolena': 'DOVOLENÁ',
            'udrzba': 'ÚDRŽBA',
            'svatek': 'SVÁTEK',
            'jine': 'BLOKACE'
        };
        return labels[type] || type.toUpperCase();
    }

    addDays(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    navigateWeek(direction) {
        this.currentWeekStart.setDate(this.currentWeekStart.getDate() + (direction * 7));
        this.updateWeekDisplay();
        this.loadBlocks().then(() => this.renderCalendar());
    }

    filterByTechnology(tech) {
        const orders = document.querySelectorAll('.calendar-order');
        
        orders.forEach(order => {
            const orderTech = order.getAttribute('data-technology');
            
            if (tech === 'all' || !orderTech || orderTech === tech) {
                order.style.display = '';
            } else {
                order.style.display = 'none';
            }
        });
    }
}

// Globální instance kalendáře
let productionCalendar = null;

// Inicializace při načtení stránky
document.addEventListener('DOMContentLoaded', function() {
    productionCalendar = new ProductionCalendar();
    productionCalendar.init();
});

// Globální funkce pro navigaci
function navigateWeek(direction) {
    if (productionCalendar) {
        productionCalendar.navigateWeek(direction);
    }
}

// Globální funkce pro označení jako hotovo
async function markOrderCompleted(orderId) {
    if (!confirm('Označit objednávku jako hotovou?')) return;
    
    try {
        const response = await fetch('api.php/orders', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: orderId,
                production_status: 'Hotovo',
                completion_date: new Date().toISOString()
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showNotification('Objednávka označena jako hotová', 'success');
            
            // Znovu načíst data
            productionCalendar.loadData();
            
            // Aktualizovat seznam dokončených objednávek
            if (typeof loadCompletedOrders === 'function') {
                loadCompletedOrders();
            }
        } else {
            throw new Error(result.error || 'Chyba při aktualizaci');
        }
    } catch (error) {
        console.error('Chyba:', error);
        showNotification('Chyba při označování jako hotovo', 'error');
    }
}