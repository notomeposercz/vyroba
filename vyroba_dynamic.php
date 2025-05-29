<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platforma Výroby - Live</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/inter-ui/3.19.3/inter.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Zde by byl váš stávající CSS -->
    <style>
        /* Váš existující CSS ze stávajícího souboru */
    </style>
</head>
<body class="flex flex-col h-screen">
    <!-- Váš stávající HTML layout -->
    
    <script>
        class ProductionDashboard {
            constructor() {
                this.apiBase = 'api.php';
                this.orders = [];
                this.schedule = [];
                this.technologies = [];
                this.init();
            }
            
            async init() {
                await this.loadTechnologies();
                await this.loadOrders();
                await this.loadSchedule();
                this.setupEventListeners();
                this.updateUI();
            }
            
            async loadOrders() {
                try {
                    const response = await fetch(`${this.apiBase}/orders`);
                    this.orders = await response.json();
                } catch (error) {
                    console.error('Chyba při načítání objednávek:', error);
                }
            }
            
            async loadSchedule() {
                const startDate = this.getWeekStart();
                const endDate = this.getWeekEnd();
                
                try {
                    const response = await fetch(`${this.apiBase}/schedule?start=${startDate}&end=${endDate}`);
                    this.schedule = await response.json();
                } catch (error) {
                    console.error('Chyba při načítání rozvrhu:', error);
                }
            }
            
            async loadTechnologies() {
                try {
                    const response = await fetch(`${this.apiBase}/technologies`);
                    this.technologies = await response.json();
                } catch (error) {
                    console.error('Chyba při načítání technologií:', error);
                }
            }
            
            updateUI() {
                this.renderPendingOrders();
                this.renderCalendar();
                this.renderCompletedOrders();
            }
            
            renderPendingOrders() {
                const container = document.querySelector('.pending-orders-container');
                if (!container) return;
                
                const pendingOrders = this.orders.filter(order => order.production_status === 'Čekající');
                
                container.innerHTML = pendingOrders.map(order => this.createOrderCard(order)).join('');
            }
            
            createOrderCard(order) {
                const statusColor = this.getStatusColor(order);
                return `
                    <div class="p-3 border rounded-md shadow-sm order-card ${statusColor} hover:shadow-md" data-order-id="${order.id}">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-sm">${order.order_code}</span>
                            <span class="text-xs text-gray-500">Vytvořeno: ${this.formatDate(order.order_date)}</span>
                        </div>
                        <p class="text-xs text-gray-600 mb-2">${order.quantity} ks / ${order.technology_name || 'Neurčeno'}</p>
                        <div class="text-xs mb-2">
                            <span>Zboží: <span class="font-medium">${this.getGoodsStatus(order)}</span></span>
                        </div>
                        <div class="text-xs mb-2 border-t pt-2">
                            <div class="flex justify-between items-center">
                                <span>Náhled: <span class="font-medium">${order.preview_status}</span></span>
                                <div>
                                    <button class="approve-btn text-[10px] hover:underline" data-order-id="${order.id}">(S)</button>
                                    <button class="reject-btn ml-1 text-[10px] hover:underline" data-order-id="${order.id}">(Z)</button>
                                </div>
                            </div>
                        </div>
                        <div class="shipping-date-section text-xs border-t pt-2">
                            <label class="block text-gray-600 mb-0.5">Datum odeslání:</label>
                            <input type="date" class="shipping-date p-1 border rounded text-sm w-full" value="${order.shipping_date || ''}" data-order-id="${order.id}">
                        </div>
                    </div>
                `;
            }
            
            getStatusColor(order) {
                if (order.goods_stocked_date) return 'status-green';
                if (order.goods_ordered_date) return 'status-orange';
                return 'status-red';
            }
            
            getGoodsStatus(order) {
                if (order.goods_stocked_date) return 'Na skladě';
                if (order.goods_ordered_date) return 'Objednáno';
                return 'Není';
            }
            
            setupEventListeners() {
                // Event listeners pro schvalování náhledů
                document.addEventListener('click', async (e) => {
                    if (e.target.classList.contains('approve-btn')) {
                        const orderId = e.target.dataset.orderId;
                        await this.updateOrderStatus(orderId, 'preview_status', 'Schváleno');
                    }
                    
                    if (e.target.classList.contains('reject-btn')) {
                        const orderId = e.target.dataset.orderId;
                        await this.updateOrderStatus(orderId, 'preview_status', 'Zamítnuto');
                    }
                });
                
                // Event listeners pro změnu data odeslání
                document.addEventListener('change', async (e) => {
                    if (e.target.classList.contains('shipping-date')) {
                        const orderId = e.target.dataset.orderId;
                        await this.updateOrderField(orderId, 'shipping_date', e.target.value);
                    }
                });
            }
            
            async updateOrderStatus(orderId, field, value) {
                try {
                    const updateData = { id: orderId, [field]: value };
                    if (field === 'preview_status' && value === 'Schváleno') {
                        updateData.preview_approved_date = new Date().toISOString().split('T')[0];
                    }
                    
                    const response = await fetch(`${this.apiBase}/orders`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(updateData)
                    });
                    
                    if (response.ok) {
                        await this.loadOrders();
                        this.updateUI();
                    }
                } catch (error) {
                    console.error('Chyba při aktualizaci objednávky:', error);
                }
            }
            
            async updateOrderField(orderId, field, value) {
                try {
                    const response = await fetch(`${this.apiBase}/orders`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: orderId, [field]: value })
                    });
                    
                    if (response.ok) {
                        await this.loadOrders();
                    }
                } catch (error) {
                    console.error('Chyba při aktualizaci pole:', error);
                }
            }
            
            formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('cs-CZ');
            }
            
            getWeekStart() {
                const today = new Date();
                const day = today.getDay();
                const diff = today.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(today.setDate(diff)).toISOString().split('T')[0];
            }
            
            getWeekEnd() {
                const start = new Date(this.getWeekStart());
                return new Date(start.setDate(start.getDate() + 6)).toISOString().split('T')[0];
            }
        }
        
        // Inicializace aplikace
        document.addEventListener('DOMContentLoaded', () => {
            new ProductionDashboard();
        });
    </script>
</body>
</html>