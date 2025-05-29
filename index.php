<?php
// Základní konfigurace
date_default_timezone_set('Europe/Prague');
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Výrobní systém - <?php echo date('d.m.Y'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style-calendar.css">
</head>
<body class="calendar-layout">
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-industry"></i>
                </div>
                <h1>Platforma pro přehled výroby</h1>
            </div>
            <div class="header-right">
                <span class="user-info">
                    <i class="fas fa-user"></i> 
                    Přihlášen: <?php echo 'notomeposercz'; ?> (Výroba)
                </span>
                <span class="date-info">
                    <i class="fas fa-calendar"></i> 
                    <?php echo date('d.m.Y H:i'); ?>
                </span>
                <button class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Odhlásit
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Left Sidebar - Pending Orders -->
        <aside class="left-panel">
            <div class="panel-header">
                <h2><i class="fas fa-shopping-cart"></i> Čekající objednávky</h2>
            </div>
            
            <!-- Filters -->
            <div class="panel-filters">
                <input type="text" id="orderSearchInput" placeholder="Filtrovat produkt/kód..." 
                       class="filter-input" onkeyup="filterOrders()">
                <div class="filter-row">
                    <input type="date" id="orderDateFilter" class="filter-input" 
                           title="Filtrovat podle data vytvoření" onchange="filterOrders()">
                    <select id="orderStatusFilter" class="filter-input" onchange="filterOrders()">
                        <option value="all">Všechny stavy</option>
                        <option value="Čekající">Čekající</option>
                        <option value="V_výrobě">V výrobě</option>
                        <option value="Hotovo">Hotovo</option>
                    </select>
                </div>
            </div>
            
            <!-- Orders List -->
            <div id="pendingOrdersList" class="orders-list">
                <!-- Orders will be loaded here -->
            </div>
            
            <!-- Add Order Button -->
            <div class="panel-footer">
                <button class="btn btn-primary btn-full" onclick="showAddOrderModal()">
                    <i class="fas fa-plus"></i> Nová objednávka
                </button>
            </div>
        </aside>

        <!-- Main Calendar Section -->
        <section class="center-panel">
            <div class="panel-header">
                <h2><i class="fas fa-calendar-alt"></i> Plán Výroby</h2>
                <div class="technology-filter">
                    <span class="filter-label">Filtr:</span>
                    <button class="filter-btn active" data-filter="all">Vše</button>
                    <button class="filter-btn" data-filter="Sítotisk">Sítotisk</button>
                    <button class="filter-btn" data-filter="Potisk">Potisk</button>
                    <button class="filter-btn" data-filter="Gravírování">Gravír</button>
                    <button class="filter-btn" data-filter="Výšivka">Výšivka</button>
                    <button class="filter-btn" data-filter="Laser">Laser</button>
                </div>
            </div>

            <!-- Week Navigation -->
            <div class="week-navigation">
                <button class="nav-btn" onclick="navigateWeek(-1)">
                    <i class="fas fa-chevron-left"></i> Předchozí
                </button>
                <span class="week-display" id="weekDisplay">Týden (28.4. - 2.5. 2025)</span>
                <button class="nav-btn" onclick="navigateWeek(1)">
                    Další <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div id="calendarGrid" class="calendar-grid">
                <!-- Calendar days will be generated here -->
            </div>
            
            <!-- Calendar Actions -->
            <div class="panel-footer">
                <button class="btn btn-primary" onclick="showBlockModal()">
                    <i class="fas fa-plus"></i> Vložit dovolenou/blokaci
                </button>
            </div>
        </section>

        <!-- Right Sidebar - Order Details -->
        <aside class="right-panel">
            <div class="panel-header">
                <h2><i class="fas fa-info-circle"></i> Detail Zakázky</h2>
            </div>
            <div id="orderDetails" class="order-details">
                <div class="no-selection">
                    <i class="fas fa-mouse-pointer"></i>
                    <p>Klikněte na objednávku pro zobrazení detailů</p>
                </div>
            </div>
        </aside>
    </main>

    <!-- Footer - Completed Orders -->
    <footer class="main-footer">
        <h2><i class="fas fa-check-circle"></i> Hotové Zakázky</h2>
        <div class="completed-orders-table">
            <table id="completedOrdersTable">
                <thead>
                    <tr>
                        <th>Kód obj.</th>
                        <th>Dokončeno</th>
                        <th>Obchodník</th>
                        <th>Technologie</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Completed orders will be loaded here -->
                </tbody>
            </table>
        </div>
    </footer>

    <!-- Původní modaly pro kompatibilitu -->
    <!-- Modal pro novou objednávku -->
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
                        <select id="previewStatus">
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
                <div class="form-group">
                    <label>Technologie:</label>
                    <select id="technology">
                        <option value="">Vyberte technologii</option>
                        <option value="Sítotisk">Sítotisk</option>
                        <option value="Potisk">Potisk</option>
                        <option value="Gravírování">Gravírování</option>
                        <option value="Výšivka">Výšivka</option>
                        <option value="Laser">Laser</option>
                    </select>
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

    <!-- Modal pro výrobní plán -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('scheduleModal')">&times;</span>
            <h2>Přidat do výrobního plánu</h2>
            <form id="scheduleForm" onsubmit="saveSchedule(event)">
                <div class="form-group">
                    <label>Objednávka:</label>
                    <select id="scheduleOrderId" required>
                        <option value="">Vyberte objednávku...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Technologie:</label>
                    <select id="scheduleTechId" required>
                        <option value="">Vyberte technologii...</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Datum začátku:</label>
                        <input type="date" id="scheduleStartDate" required>
                    </div>
                    <div class="form-group">
                        <label>Datum konce:</label>
                        <input type="date" id="scheduleEndDate" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="scheduleIsLocked"> Uzamknout v plánu
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('scheduleModal')">Zrušit</button>
                    <button type="submit" class="btn btn-primary">Uložit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Block/Holiday Modal -->
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

    <script src="script.js"></script>
    <script src="script-calendar.js"></script>
</body>
</html>