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
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-industry"></i> Výrobní systém</h1>
            <div class="header-info">
                <span><i class="fas fa-user"></i> <?php echo 'notomeposercz'; ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i'); ?></span>
            </div>
            <nav>
                <button class="nav-btn active" data-tab="orders">
                    <i class="fas fa-shopping-cart"></i> Objednávky
                </button>
                <button class="nav-btn" data-tab="schedule">
                    <i class="fas fa-calendar-alt"></i> Výrobní plán
                </button>
                <button class="nav-btn" data-tab="analytics">
                    <i class="fas fa-chart-bar"></i> Statistiky
                </button>
            </nav>
        </header>

        <main>
            <!-- Objednávky tab -->
            <div id="orders" class="tab-content active">
                                <div class="toolbar">
                    <button class="btn btn-primary" onclick="showAddOrderModal()">
                        <i class="fas fa-plus"></i> Nová objednávka
                    </button>
                    <button class="btn btn-success" onclick="importCSV()" id="importBtn">
                        <i class="fas fa-file-csv"></i> Import CSV
                    </button>
                    <div class="filter-group">
                        <select id="statusFilter" onchange="loadOrders()">
                            <option value="all">Všechny stavy</option>
                            <option value="Čekající">Čekající</option>
                            <option value="V_výrobě">V výrobě</option>
                            <option value="Hotovo">Hotovo</option>
                        </select>
                        <input type="text" id="searchInput" placeholder="Hledat..." onkeyup="searchOrders()">
                    </div>
                </div>

                <div class="table-container">
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Kód objednávky</th>
                                <th>Katalog</th>
                                <th>Množství</th>
                                <th>Datum objednání</th>
                                <th>Stav náhledu</th>
                                <th>Stav výroby</th>
                                <th>Technologie</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="8" class="loading">Načítání...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Výrobní plán tab -->
            <div id="schedule" class="tab-content">
                <div class="toolbar">
                    <button class="btn btn-primary" onclick="showAddScheduleModal()">
                        <i class="fas fa-plus"></i> Přidat do plánu
                    </button>
                    <div class="date-range">
                        <input type="date" id="startDate" value="<?php echo date('Y-m-d'); ?>" onchange="loadSchedule()">
                        <span>až</span>
                        <input type="date" id="endDate" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" onchange="loadSchedule()">
                    </div>
                </div>

                <div id="scheduleCalendar" class="schedule-grid">
                    <div class="loading">Načítání výrobního plánu...</div>
                </div>
            </div>

            <!-- Statistiky tab -->
            <div id="analytics" class="tab-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-shopping-cart stat-icon"></i>
                        <h3>Celkem objednávek</h3>
                        <span class="stat-value" id="totalOrders">-</span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-cog stat-icon"></i>
                        <h3>V výrobě</h3>
                        <span class="stat-value" id="inProgress">-</span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check stat-icon"></i>
                        <h3>Dokončeno</h3>
                        <span class="stat-value" id="completed">-</span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock stat-icon"></i>
                        <h3>Čeká na schválení</h3>
                        <span class="stat-value" id="pendingApproval">-</span>
                    </div>
                </div>
                
                <div class="charts-container">
                    <div class="chart-card">
                        <h3>Nejpoužívanější technologie</h3>
                        <div id="techChart" class="chart"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

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

    <script src="script.js"></script>
</body>
</html>